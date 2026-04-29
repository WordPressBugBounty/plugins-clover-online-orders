<?php
/**
 * Translates one /v2/merchants/checkout-settings-v2 response into the
 * array shapes the legacy checkout code expects — replacing direct calls
 * to getBlackoutStatus(), getOpeningStatus(), getCheckoutSettings(),
 * getPakmsKey(), getMerchantPubKey(), and getMerchantAddress() when in
 * Global mode.
 *
 * One instance per request. Construct with the unified-endpoint payload
 * already fetched (typically from SooSettingsSource::instance()
 * ->dashboardClient()->fetch()).
 *
 * Per the 2026-04-22 mode-handling-fixes spec: the mapper preserves the
 * legacy field names byte-for-byte. The mobile app sees no contract change.
 */
class DashboardCheckoutMapper {
    /** @var array */
    private $dash;

    /**
     * @param array $dash Unified endpoint response.
     * @throws SooDashboardUnavailableException when $dash is not a usable payload.
     */
    public function __construct($dash) {
        if (!is_array($dash)) {
            throw new SooDashboardUnavailableException('Dashboard response is not an array');
        }
        $hasShape = isset($dash['checkout_settings'])
            || isset($dash['store_status'])
            || isset($dash['pickup_slots'])
            || isset($dash['merchant']);
        if (!$hasShape) {
            throw new SooDashboardUnavailableException('Dashboard response missing expected top-level keys');
        }
        $this->dash = $dash;
    }

    /**
     * Returns a value-shaped like the legacy getBlackoutStatus() response.
     *
     * Treats manual close, blackouts, and holiday closures as "close"
     * since the legacy blackout endpoint conflated all merchant-initiated
     * closures into one signal. Outside-hours closures are reported as
     * "open" here — they're handled by mapOpeningStatus() instead.
     */
    public function mapStoreStatus() {
        $ss = isset($this->dash['store_status']) ? $this->dash['store_status'] : array();
        $cs = isset($this->dash['checkout_settings']) ? $this->dash['checkout_settings'] : array();

        $reason = isset($ss['close_reason']) ? $ss['close_reason'] : null;
        // All merchant-initiated close reasons that legacy getBlackoutStatus()
        // conflated into one "close" signal. Per the unified-endpoint spec
        // (close_reason enum: outside_hours|paused|blackout|null), 'paused'
        // is a temporary merchant pause — equivalent to manually_closed for
        // checkout-gating purposes. 'holiday' and 'manually_closed' are
        // codebase-used variants (SooStoreStatusChecker, SooDashboardSummary).
        // 'outside_hours' is deliberately excluded — mapOpeningStatus() owns it.
        $blackoutLikeReasons = array('manually_closed', 'paused', 'blackout', 'holiday');
        $reasonBlackoutLike = isset($ss['status'])
            && $ss['status'] === 'close'
            && in_array($reason, $blackoutLikeReasons, true);

        // Also honor the explicit merchant-toggle flag from checkout_settings.
        // This matches SooDashboardSummary::manualCloseState() — the dashboard
        // may set isClosed=true even when close_reason isn't 'manually_closed'
        // (e.g. if the backend hasn't re-derived the reason yet). Treating
        // either signal as "closed" prevents the REST endpoint from disagreeing
        // with the admin manual-close banner.
        $manuallyToggled = !empty($cs['isClosed']);
        $isBlackoutLike = $reasonBlackoutLike || $manuallyToggled;

        if (!$isBlackoutLike) {
            return array(
                'status' => 'open',
                'custom_message' => '',
                'hide_menu' => false,
            );
        }

        $message = '';
        if (!empty($ss['closureMessage'])) {
            $message = (string) $ss['closureMessage'];
        } elseif (!empty($cs['closureMessage'])) {
            $message = (string) $cs['closureMessage'];
        }

        return array(
            'status' => 'close',
            'custom_message' => $message,
            'hide_menu' => !empty($ss['hideMenuWhenClosed']) || !empty($cs['hideMenuWhenClosed']),
        );
    }

    /**
     * Returns the Clover pakms (payment-form) key from the unified
     * endpoint, falling back to the locally-cached value. Replaces
     * getPakmsKey().
     *
     * IMPORTANT: this is NOT the merchant identity pubkey. The unified
     * endpoint exposes both `checkout_settings.pubkey` (merchant identity
     * UUID — used in moo_merchant_pubkey) and `checkout_settings.pakmsKey`
     * (Clover payment-form key — used in cloverPakmsPaymentKey). They are
     * distinct values; only the latter is what this method returns.
     * Merchant identity pubkey continues to be read via the legacy
     * getMerchantPubKey() local-cache path.
     */
    public function mapPubKey() {
        $cs = isset($this->dash['checkout_settings']) ? $this->dash['checkout_settings'] : array();
        if (!empty($cs['pakmsKey'])) {
            return (string) $cs['pakmsKey'];
        }
        $local = get_option('moo_pakms_key');
        return !empty($local) ? (string) $local : null;
    }

    /**
     * Returns the merchant address shape returned by the legacy
     * getMerchantAddress() — a flat array with at minimum address1,
     * city, state, zipCode.
     */
    public function mapMerchantAddress() {
        $cs = isset($this->dash['checkout_settings']) ? $this->dash['checkout_settings'] : array();
        $addr = (isset($cs['address']) && is_array($cs['address'])) ? $cs['address'] : array();
        return array(
            'address1' => isset($addr['address1']) ? (string) $addr['address1'] : '',
            'address2' => isset($addr['address2']) ? (string) $addr['address2'] : '',
            'city'     => isset($addr['city'])     ? (string) $addr['city']     : '',
            'state'    => isset($addr['state'])    ? (string) $addr['state']    : '',
            'zipCode'  => isset($addr['zipCode'])  ? (string) $addr['zipCode']  : '',
            'lat'      => isset($addr['lat'])      ? (string) $addr['lat']      : '',
            'lng'      => isset($addr['lng'])      ? (string) $addr['lng']      : '',
        );
    }

    /**
     * Returns a value shaped like the legacy getCheckoutSettings() response.
     *
     * Renames convenienceFee → convenience_fee to match the legacy field
     * the REST consumer expects. Other fields are passed through as-is.
     *
     * Kept intentionally minimal: most dashboard checkout_settings fields
     * are already mutated into pluginSettings via applyDashboardCheckoutOverrides
     * and surfaced as flat top-level response fields. Duplicating the entire
     * block here would bloat the response without adding new information.
     */
    public function mapCheckoutSettings() {
        $cs = isset($this->dash['checkout_settings']) ? $this->dash['checkout_settings'] : array();
        return array(
            'fraudTools'      => isset($cs['fraudTools']) ? $cs['fraudTools'] : null,
            'convenience_fee' => isset($cs['convenienceFee']) ? $cs['convenienceFee'] : 0,
        );
    }

    /**
     * Convert pickup_slots day-keyed map of rich slot objects into the
     * {day => [labels]} map the existing checkout JS iterates.
     *
     * When $excludeUnavailable is false (default): includes all slots so
     * the PHP advancedCheckout can grey them out in JS using the companion
     * mapThrottledTimes() map.
     *
     * When $excludeUnavailable is true: drops any slot whose 'available' is
     * false or whose 'throttled' is true. Used by the REST endpoint so
     * mobile clients only see bookable slots (the REST response does not
     * carry the throttled map).
     *
     * Returns an empty array (never null) when no slots are configured, so
     * callers can safely use bare foreach without a null guard.
     */
    private function slotsToPickupTimeMap($excludeUnavailable = false) {
        $slots = isset($this->dash['pickup_slots']) ? $this->dash['pickup_slots'] : array();
        if (!is_array($slots) || empty($slots)) {
            return array();
        }
        $out = array();
        foreach ($slots as $dayLabel => $daySlots) {
            if (!is_array($daySlots)) {
                continue;
            }
            $labels = array();
            foreach ($daySlots as $slot) {
                if ($excludeUnavailable) {
                    $isBlocked = (isset($slot['available']) && empty($slot['available']))
                        || !empty($slot['throttled']);
                    if ($isBlocked) {
                        continue;
                    }
                }
                if (isset($slot['local'])) {
                    $labels[] = $slot['local'];
                } elseif (isset($slot['label'])) {
                    $labels[] = $slot['label'];
                }
            }
            if (!empty($labels)) {
                $out[$dayLabel] = $labels;
            }
        }
        return $out;
    }

    /**
     * Returns a value shaped like the legacy getOpeningStatus() response.
     *
     * $nb_days and $nb_minutes are accepted for legacy signature compatibility
     * but ignored — slot ranges are determined by the dashboard payload.
     * Pickup slots double as delivery slots in v1 (per the spec); both keys
     * point to the same data.
     *
     * Pass $excludeUnavailable=true from REST consumers to drop
     * throttled/unavailable slots (REST has no companion throttled map).
     */
    public function mapOpeningStatus($nb_days = 0, $nb_minutes = 0, $excludeUnavailable = false) {
        $ss = isset($this->dash['store_status']) ? $this->dash['store_status'] : array();
        $cs = isset($this->dash['checkout_settings']) ? $this->dash['checkout_settings'] : array();
        $merchant = isset($this->dash['merchant']) ? $this->dash['merchant'] : array();

        $pickupTime = $this->slotsToPickupTimeMap($excludeUnavailable);

        $status = isset($ss['status']) ? $ss['status'] : 'open';
        $storeTime = isset($ss['store_hours_today']) ? (string) $ss['store_hours_today'] : '';
        $message = '';
        if ($status === 'close' && !empty($ss['closureMessage'])) {
            $message = (string) $ss['closureMessage'];
        }

        return array(
            'status'                   => $status,
            'store_time'               => $storeTime,
            'time_zone'                => isset($merchant['time_zone']) ? (string) $merchant['time_zone'] : null,
            'current_time'             => isset($merchant['current_time']) ? (string) $merchant['current_time'] : null,
            'pickup_time'              => $pickupTime,
            'delivery_time'            => $pickupTime, // pickup slots double as delivery in v1
            'accept_orders_when_closed' => !empty($ss['acceptOrdersWhileClosed']),
            'schedule_orders'          => !empty($cs['scheduleOrder']),
            'hide_menu'                => !empty($ss['hideMenuWhenClosed']),
            'message'                  => $message,
            'fraudTools'               => isset($cs['fraudTools']) ? $cs['fraudTools'] : null,
        );
    }

    /**
     * Day-keyed map of slot labels that are unavailable (throttled or
     * otherwise blocked). The frontend uses this to add the disabled
     * attribute to the corresponding <option> elements.
     *
     * Same data source as slotsToPickupTimeMap() but only includes slots
     * whose 'available' flag is false or 'throttled' flag is true.
     */
    public function mapThrottledTimes() {
        $slots = isset($this->dash['pickup_slots']) ? $this->dash['pickup_slots'] : array();
        if (!is_array($slots) || empty($slots)) {
            return array();
        }
        $out = array();
        foreach ($slots as $dayLabel => $daySlots) {
            if (!is_array($daySlots)) {
                continue;
            }
            $blocked = array();
            foreach ($daySlots as $slot) {
                if (!isset($slot['local'])) {
                    continue;
                }
                $isBlocked = (isset($slot['available']) && empty($slot['available']))
                    || !empty($slot['throttled']);
                if ($isBlocked) {
                    $blocked[] = $slot['local'];
                }
            }
            if (!empty($blocked)) {
                $out[$dayLabel] = $blocked;
            }
        }
        return $out;
    }

    /**
     * Returns a value shaped like the legacy getBusinessSettings() response.
     * Used by advancedCheckout() to gate the on-demand-delivery order type
     * and to resolve the loyalty setting.
     */
    public function mapBusinessSettings() {
        $cs = isset($this->dash['checkout_settings']) ? $this->dash['checkout_settings'] : array();
        return array(
            'onDemandDeliveriesEnabled' => !empty($cs['onDemandDeliveriesEnabled']),
            'onDemandDeliveriesLabel'   => isset($cs['onDemandDeliveriesLabel']) ? (string) $cs['onDemandDeliveriesLabel'] : 'On-Demand',
            'loyaltySetting'            => isset($cs['loyaltySetting']) ? $cs['loyaltySetting'] : null,
        );
    }

    public function mapAcceptingAsap() {
        $ss = isset($this->dash['store_status']) ? $this->dash['store_status'] : array();
        return isset($ss['accepting_asap']) ? (bool) $ss['accepting_asap'] : true;
    }

    public function mapAsapUnavailableReason() {
        $ss = isset($this->dash['store_status']) ? $this->dash['store_status'] : array();
        return isset($ss['asap_unavailable_reason']) ? (string) $ss['asap_unavailable_reason'] : null;
    }
}
