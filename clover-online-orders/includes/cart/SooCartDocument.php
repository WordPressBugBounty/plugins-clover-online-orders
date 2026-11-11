<?php
/**
 * Parses an untrusted cart document into a predictable shape.
 *
 * This is the shape gate, not the truth gate: it decides whether the request is
 * well-formed, never whether the items exist or can be ordered. SooCartResolver
 * answers that, against the database.
 *
 * Nothing here reads the database and nothing here decides a price. The document
 * carries no money by design — see the spec, §4.
 */
class SooCartDocument {

    /** A single line cannot exceed this quantity. */
    const MAX_QTY = 999;

    /** A single modifier cannot exceed this quantity. */
    const MAX_MOD_QTY = 99;

    /** Line notes are truncated to this many characters. */
    const MAX_NOTE = 500;

    /**
     * A document cannot carry more than this many lines into the result — and, just
     * as importantly, parsing never examines more than this many entries of
     * raw['lines'] to get there. The cap is on input position, not on how many
     * entries turned out to be usable, so a payload of junk entries cannot force a
     * full scan.
     */
    const MAX_LINES = 100;

    /** item_uuid and a modifier's uuid are truncated to this many characters. */
    const MAX_UUID = 200;

    /** A client-supplied line_id is truncated to this many characters before the distinguishing suffix is appended. */
    const MAX_LINE_ID = 200;

    /** A coupon code is truncated to this many characters. */
    const MAX_COUPON = 100;

    /**
     * A single line's modifiers array is bounded by entries EXAMINED, the same way
     * MAX_LINES bounds raw['lines'] — not by how many turned out to be usable. Without
     * this, resolveModifiers() issues one database query per entry with no cap at all,
     * on a route (/v3/cart/price) that is unauthenticated. The real menus surveyed for
     * this fix top out at 178 distinct modifiers across every group attached to a
     * single item (i.e. selecting literally everything offered); 300 leaves comfortable
     * headroom above that ceiling while still keeping a malicious line's query count
     * bounded rather than proportional to an arbitrarily large payload.
     */
    const MAX_MODS_PER_LINE = 300;

    /**
     * @param mixed $raw Decoded JSON from the request, or anything at all.
     * @return array array('lines'=>array, 'coupon'=>string|null, 'errors'=>array)
     */
    public static function parse($raw) {
        $out = array('lines' => array(), 'coupon' => null, 'errors' => array());

        if (!is_array($raw)) {
            $out['errors'][] = 'malformed_document';
            return $out;
        }
        if (!isset($raw['lines']) || !is_array($raw['lines']) || empty($raw['lines'])) {
            $out['errors'][] = 'no_lines';
            return $out;
        }

        // Bounded by entries examined, not entries kept: a document of junk lines
        // cannot force a scan past MAX_LINES no matter how much of it is unusable.
        $examined = 0;
        foreach ($raw['lines'] as $i => $rawLine) {
            if ($examined >= self::MAX_LINES) {
                break;
            }
            $examined++;
            $line = self::parseLine($rawLine, $i);
            if ($line !== null) {
                $out['lines'][] = $line;
            }
        }

        if (empty($out['lines'])) {
            $out['errors'][] = 'no_usable_lines';
            return $out;
        }

        if (isset($raw['coupon']) && is_string($raw['coupon']) && trim($raw['coupon']) !== '') {
            $coupon = sanitize_text_field(trim($raw['coupon']));
            if (strlen($coupon) > self::MAX_COUPON) {
                $coupon = substr($coupon, 0, self::MAX_COUPON);
            }
            $out['coupon'] = $coupon;
        }

        return $out;
    }

    /**
     * @param mixed $rawLine
     * @param int   $index Position in the document, used to keep line ids distinct.
     * @return array|null Null when the line carries nothing usable.
     */
    private static function parseLine($rawLine, $index) {
        if (!is_array($rawLine)) {
            return null;
        }
        if (!isset($rawLine['item_uuid']) || !is_string($rawLine['item_uuid'])) {
            return null;
        }

        // Sanitise before judging emptiness: a whitespace-only value is not empty()
        // until it has been through sanitize_text_field(), and "0" must not be
        // dropped by an empty() check that treats it as falsy.
        $itemUuid = sanitize_text_field($rawLine['item_uuid']);
        if (strlen($itemUuid) > self::MAX_UUID) {
            $itemUuid = substr($itemUuid, 0, self::MAX_UUID);
        }
        if ($itemUuid === '') {
            return null;
        }

        $qty = isset($rawLine['quantity']) ? intval($rawLine['quantity']) : 0;
        if ($qty < 1) {
            return null;
        }
        if ($qty > self::MAX_QTY) {
            $qty = self::MAX_QTY;
        }

        $note = '';
        if (isset($rawLine['note']) && is_string($rawLine['note'])) {
            $note = sanitize_text_field($rawLine['note']);
            if (strlen($note) > self::MAX_NOTE) {
                $note = substr($note, 0, self::MAX_NOTE);
            }
        }

        // The client's line id is a convenience for matching a response back to a row.
        // It is never trusted to be unique, so the index guarantees distinctness.
        $lineId = '';
        if (isset($rawLine['line_id']) && is_string($rawLine['line_id'])) {
            $lineId = sanitize_text_field($rawLine['line_id']);
            if (strlen($lineId) > self::MAX_LINE_ID) {
                $lineId = substr($lineId, 0, self::MAX_LINE_ID);
            }
        }
        $lineId = ($lineId !== '') ? $lineId . '-' . $index : 'line-' . $index;

        return array(
            'line_id'   => $lineId,
            'item_uuid' => $itemUuid,
            'quantity'  => $qty,
            'note'      => $note,
            'modifiers' => self::parseModifiers($rawLine),
        );
    }

    /**
     * @param array $rawLine
     * @return array List of array('uuid'=>string,'qty'=>int).
     */
    private static function parseModifiers($rawLine) {
        $mods = array();
        if (!isset($rawLine['modifiers']) || !is_array($rawLine['modifiers'])) {
            return $mods;
        }

        // Bounded by entries examined, not entries kept — same rule as MAX_LINES above,
        // so a line front-loaded with junk modifier entries cannot force a scan past
        // this point into an arbitrarily large modifiers array.
        $examined = 0;
        foreach ($rawLine['modifiers'] as $rawMod) {
            if ($examined >= self::MAX_MODS_PER_LINE) {
                break;
            }
            $examined++;

            if (!is_array($rawMod) || !isset($rawMod['uuid']) || !is_string($rawMod['uuid'])) {
                continue;
            }

            // Same sanitise-then-check-emptiness rule as item_uuid above.
            $uuid = sanitize_text_field($rawMod['uuid']);
            if (strlen($uuid) > self::MAX_UUID) {
                $uuid = substr($uuid, 0, self::MAX_UUID);
            }
            if ($uuid === '') {
                continue;
            }

            $q = isset($rawMod['qty']) ? intval($rawMod['qty']) : 1;
            if ($q < 1) {
                continue;
            }
            if ($q > self::MAX_MOD_QTY) {
                $q = self::MAX_MOD_QTY;
            }
            $mods[] = array('uuid' => $uuid, 'qty' => $q);
        }
        return $mods;
    }
}
