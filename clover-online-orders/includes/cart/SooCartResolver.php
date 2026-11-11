<?php
/**
 * Resolves a parsed cart document against the local menu tables.
 *
 * Produces cart lines in exactly the shape MOO_SESSION stores, so SooCartPricer can
 * price a browser-held cart with the identical code that prices the session cart —
 * see the spec, §5.1. Every price, tax and modifier comes from the database here;
 * the document supplies only ids and quantities.
 *
 * Availability is reported per line rather than thrown, because the cart UI needs to
 * mark the offending row while still showing a priced total for the rest.
 */
class SooCartResolver {

    /** @var Moo_OnlineOrders_Model */
    private $model;

    /** @var Moo_OnlineOrders_SooApi */
    private $api;

    /** @var array|null Memoised stock, fetched at most once per resolve(). */
    private $stocks = null;

    public function __construct($model, $api) {
        $this->model = $model;
        $this->api   = $api;
    }

    /**
     * @param array $document Output of SooCartDocument::parse().
     * @return array array('items'=>array,'lines'=>array,'valid'=>bool)
     */
    public function resolve($document) {
        $items = array();
        $lines = array();
        $valid = true;

        $trackStock = $this->api->getTrackingStockStatus();
        if ($trackStock && $this->stocks === null) {
            $this->stocks = $this->api->getItemStocks();
            if (!is_array($this->stocks)) {
                $this->stocks = array();
            }
        }

        // Quantity per item across the whole document — stock is per item, not per line,
        // so two lines of the same item must be counted together.
        $perItem = array();
        foreach ($document['lines'] as $line) {
            $uuid = $line['item_uuid'];
            $perItem[$uuid] = (isset($perItem[$uuid]) ? $perItem[$uuid] : 0) + $line['quantity'];
        }

        foreach ($document['lines'] as $line) {
            $problems = array();
            $item = $this->model->getItem($line['item_uuid']);

            if (!$item) {
                $problems[] = array('code' => 'item_not_found',
                                    'message' => 'This item is no longer on the menu.');
                $lines[] = $this->report($line, $problems);
                $valid = false;
                continue;
            }

            if (!$this->isOrderable($item)) {
                $problems[] = array('code' => 'item_unavailable',
                                    'message' => 'This item is not available right now.');
            }

            if ($trackStock) {
                $stockProblem = $this->stockProblem($line, $perItem);
                if ($stockProblem !== null) {
                    $problems[] = $stockProblem;
                }
            }

            $modifiers = $this->resolveModifiers($line, $item, $problems);
            $this->checkGroups($item, $line, $modifiers, $problems);

            $cartLine = array(
                'item'        => $item,
                'quantity'    => $line['quantity'],
                'special_ins' => $line['note'],
                'tax_rate'    => $this->taxRates($line['item_uuid']),
                'modifiers'   => $modifiers,
            );

            // Kept even when it has problems: the cart still prices and displays the row,
            // and checkout is what refuses. Only a missing item has nothing to price.
            $items[$line['line_id']] = $cartLine;
            $lines[] = $this->report($line, $problems);
            if (!empty($problems)) {
                $valid = false;
            }
        }

        return array('items' => $items, 'lines' => $lines, 'valid' => $valid);
    }

    /**
     * The menu's own visibility rule, copied from the model's item queries.
     *
     * Also excludes item-group variants (item_group_uuid not null): every menu-listing
     * query in the model (getItemsByCategory(), getVisibleItemsByCategory(),
     * getItemsBySearch(), NbProducts(), getFeaturedProducts()) filters on
     * `item_group_uuid is null` too — a variant row is never something the storefront
     * lists or a customer orders directly, only its parent item is.
     *
     * @param object $item
     * @return bool
     */
    private function isOrderable($item) {
        return !empty($item->visible)
            && empty($item->hidden)
            && !empty($item->available)
            && empty($item->item_group_uuid)
            && (!isset($item->price_type) || $item->price_type !== 'VARIABLE');
    }

    /**
     * @param string $itemUuid
     * @return array Tax rate rows, or an empty array when the item has none.
     */
    private function taxRates($itemUuid) {
        $rates = $this->model->getItemTax_rate($itemUuid);
        return is_array($rates) ? $rates : array();
    }

    /**
     * Look each modifier up and build the session's modifier map.
     *
     * getModifier() resolves a modifier uuid globally, with no notion of which item is
     * being ordered — the same topping name is commonly priced differently per group
     * (e.g. a size-specific "Bacon"), so a modifier must also belong to a group actually
     * attached to this item, or a client could name a cheaper/free modifier from an
     * unrelated group and be charged that price instead. See the spec, §4
     * (`modifier_not_found`).
     *
     * @param array  $line
     * @param object $item     The already-resolved item this line orders.
     * @param array  $problems Appended to when a modifier cannot be resolved.
     * @return array Keyed by modifier uuid, each carrying a 'qty'.
     */
    private function resolveModifiers($line, $item, &$problems) {
        $out = array();
        if (empty($line['modifiers'])) {
            return $out;
        }

        $attachedGroups = $this->attachedGroupIds($item->uuid);

        foreach ($line['modifiers'] as $wanted) {
            $modifier = $this->model->getModifier($wanted['uuid']);
            if (!$modifier || !isset($modifier->group_id) || !isset($attachedGroups[$modifier->group_id])) {
                $problems[] = array('code' => 'modifier_not_found',
                                    'message' => 'An option on this item is no longer available.');
                continue;
            }
            $modifier = (array) $modifier;
            $modifier['qty'] = $wanted['qty'];
            $out[$wanted['uuid']] = $modifier;
        }
        return $out;
    }

    /**
     * Modifier group uuids actually attached to an item, as a lookup set.
     *
     * @param string $itemUuid
     * @return array Map of group uuid => true.
     */
    private function attachedGroupIds($itemUuid) {
        $groups = $this->model->getModifiersGroupByItem($itemUuid);
        $ids = array();
        if (is_array($groups)) {
            foreach ($groups as $group) {
                if (isset($group->uuid)) {
                    $ids[$group->uuid] = true;
                }
            }
        }
        return $ids;
    }

    /**
     * Enforce each modifier group's min and max, counted in UNITS not distinct options —
     * a group capped at 6 is filled by one option at qty 6 exactly as by six at qty 1.
     * This mirrors mooClickOnMooOpBtnPlus() in public/js/sooModifiersSelector.js.
     *
     * @param object $item
     * @param array  $line
     * @param array  $modifiers Resolved modifiers, keyed by uuid.
     * @param array  $problems  Appended to.
     * @return void
     */
    private function checkGroups($item, $line, $modifiers, &$problems) {
        $unitsByGroup = array();
        $limits = array();

        foreach ($modifiers as $modifier) {
            if (!isset($modifier['group_id'])) {
                continue;
            }
            $groupId = $modifier['group_id'];
            $qty = isset($modifier['qty']) ? intval($modifier['qty']) : 1;
            $unitsByGroup[$groupId] = (isset($unitsByGroup[$groupId]) ? $unitsByGroup[$groupId] : 0)
                                    + ($qty > 1 ? $qty : 1);
            $limits[$groupId] = array(
                'min' => isset($modifier['group_min']) ? intval($modifier['group_min']) : 0,
                'max' => isset($modifier['group_max']) ? intval($modifier['group_max']) : 0,
            );
        }

        foreach ($limits as $groupId => $limit) {
            $units = $unitsByGroup[$groupId];
            if ($limit['max'] > 0 && $units > $limit['max']) {
                $problems[] = array('code' => 'group_above_max',
                                    'message' => 'Too many options chosen for this item.');
            }
            if ($limit['min'] > 0 && $units < $limit['min']) {
                $problems[] = array('code' => 'group_below_min',
                                    'message' => 'This item needs a required option chosen.');
            }
        }

        // A required group with nothing chosen at all never appears above, because the
        // group is only known through a modifier that was selected from it.
        $required = $this->model->getItemModifiersGroupsRequired($line['item_uuid']);
        if (is_array($required)) {
            foreach ($required as $group) {
                if (!isset($unitsByGroup[$group->uuid])) {
                    $problems[] = array('code' => 'group_below_min',
                                        'message' => 'This item needs a required option chosen.');
                }
            }
        }
    }

    /**
     * @param array $line
     * @param array $perItem Total quantity of each item across the document.
     * @return array|null
     */
    private function stockProblem($line, $perItem) {
        $stock = false;
        foreach ($this->stocks as $entry) {
            if (isset($entry['item']['id']) && $entry['item']['id'] == $line['item_uuid']) {
                $stock = $entry;
                break;
            }
        }
        if ($stock === false || !isset($stock['stockCount'])) {
            return null;
        }
        if (intval($stock['stockCount']) < 1) {
            return array('code' => 'out_of_stock',
                         'message' => 'This item is out of stock.');
        }
        if ($perItem[$line['item_uuid']] > intval($stock['stockCount'])) {
            return array('code' => 'quantity_above_stock',
                         'message' => 'Only ' . intval($stock['stockCount']) . ' left of this item.');
        }
        return null;
    }

    /**
     * @param array $line
     * @param array $problems
     * @return array
     */
    private function report($line, $problems) {
        return array(
            'line_id'   => $line['line_id'],
            'item_uuid' => $line['item_uuid'],
            'ok'        => empty($problems),
            'problems'  => $problems,
        );
    }
}
