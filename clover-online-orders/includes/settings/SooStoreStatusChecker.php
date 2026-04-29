<?php
/**
 * Storefront-side store-status checker using the Varnish-cached
 * /v2/public/store-status/{pubKey} endpoint.
 *
 * INTENTIONAL DUAL-SOURCE NOTE — DO NOT CONSOLIDATE WITHOUT MEASURING
 * ───────────────────────────────────────────────────────────────────
 * The storefront uses this Varnish-cached endpoint for performance —
 * high-traffic storefront pageloads can't afford a live API call each
 * time. The CHECKOUT (PHP advancedCheckout + REST getCheckoutOptions)
 * uses the live /v2/merchants/checkout-settings-v2 endpoint via
 * SooDashboardClient because it needs real-time pickup-slot throttling
 * and must gate order creation accurately.
 *
 * These two endpoints can briefly disagree during cache TTL (typically
 * ~60s). This is by design — the checkout is the authoritative gate.
 * If the storefront says "open" and the customer clicks through to a
 * checkout that says "closed," ordering is correctly blocked at
 * checkout. Do not consolidate these without first measuring the
 * storefront perf impact of moving to the live endpoint.
 *
 * The server time comes from the HTTP Date response header — not from the WP
 * server's clock — so timezone misconfigurations on the hosting side cannot
 * produce wrong results.
 *
 * Endpoint: GET https://api.smartonlineorder.com/v2/public/store-status/{pubKey}
 * (no auth, Varnish-cached)
 */
class SooStoreStatusChecker {

    /** @var string Base URL for the public store-status endpoint. */
    private static $baseUrl = 'https://api.smartonlineorder.com/v2/public/store-status/';

    /** @var array|null Per-request memo keyed by pubKey. */
    private static $memo = array();

    /**
     * Compute the store's current open/close status from the Varnish-cached endpoint.
     *
     * @param string $pubKey Merchant's public key
     * @return array {
     *   status:       'open' | 'close',
     *   reason:       null | 'manually_closed' | 'holiday' | 'outside_hours',
     *   message:      string | null,
     *   hideMenu:     bool,
     *   acceptOrdersWhileClosed: bool,
     *   storeHoursToday: string (formatted from today's opening_hours elements),
     *   serverTime:   string (merchant-local time, Y-m-d H:i:s),
     * } or null on fetch failure.
     */
    public static function check($pubKey) {
        if (empty($pubKey)) {
            return null;
        }

        // Per-request memo
        if (isset(self::$memo[$pubKey])) {
            return self::$memo[$pubKey] === false ? null : self::$memo[$pubKey];
        }

        $fetched = self::fetchStoreStatus($pubKey);
        if ($fetched === null) {
            self::$memo[$pubKey] = false;
            return null;
        }

        $data       = $fetched['body'];
        $serverTime = $fetched['serverTime'];
        $timezone   = isset($data['time_zone']) && !empty($data['time_zone'])
            ? $data['time_zone']
            : 'UTC';

        // Convert server time (GMT from Date header) to merchant local time
        $now = self::parseServerTime($serverTime, $timezone);
        if ($now === null) {
            self::$memo[$pubKey] = false;
            return null;
        }

        $defaults = array(
            'status'                  => 'open',
            'reason'                  => null,
            'message'                 => null,
            'hideMenu'                => false,
            'acceptOrdersWhileClosed' => !empty($data['acceptOrdersWhileClosed']),
            'storeHoursToday'         => self::formatHoursToday($data, $now),
            'serverTime'              => $now->format('Y-m-d H:i:s'),
        );

        // 1. Manual close (isClosed)
        if (!empty($data['isClosed'])) {
            $msg = !empty($data['closureMessage'])
                ? $data['closureMessage']
                : __('We are currently closed and will open again soon', 'moo_OnlineOrders');
            $result = array_merge($defaults, array(
                'status'   => 'close',
                'reason'   => 'manually_closed',
                'message'  => $msg,
                'hideMenu' => !empty($data['hideMenuWhenClosed']),
            ));
            self::$memo[$pubKey] = $result;
            return $result;
        }

        // 2. Active blackout / holiday
        $blackouts = isset($data['blackouts']) && is_array($data['blackouts']) ? $data['blackouts'] : array();
        $activeBlackout = self::findActiveBlackout($blackouts, $now, $timezone);
        if ($activeBlackout !== null) {
            $msg = !empty($activeBlackout['custom_message'])
                ? $activeBlackout['custom_message']
                : __('We are currently closed and will open again soon', 'moo_OnlineOrders');
            $result = array_merge($defaults, array(
                'status'   => 'close',
                'reason'   => 'holiday',
                'message'  => $msg,
                'hideMenu' => !empty($activeBlackout['hide_menu']),
            ));
            self::$memo[$pubKey] = $result;
            return $result;
        }

        // 3. Opening hours check (only when useCloverHours is on)
        if (!empty($data['useCloverHours'])) {
            $hours = isset($data['opening_hours']) ? $data['opening_hours'] : array();
            if (!self::isWithinOpeningHours($hours, $now)) {
                $result = array_merge($defaults, array(
                    'status' => 'close',
                    'reason' => 'outside_hours',
                ));
                self::$memo[$pubKey] = $result;
                return $result;
            }
        }

        // Store is open
        self::$memo[$pubKey] = $defaults;
        return $defaults;
    }

    // ─── HTTP fetch ──────────────────────────────────────────────────

    /**
     * Fetch the Varnish-cached store-status endpoint.
     * Returns ['body' => array, 'serverTime' => string] or null.
     */
    private static function fetchStoreStatus($pubKey) {
        $url = self::$baseUrl . rawurlencode($pubKey);

        $response = wp_remote_get($url, array(
            'timeout' => 5,
            'headers' => array('Accept' => 'application/json'),
        ));

        if (is_wp_error($response)) {
            return null;
        }

        $httpCode = wp_remote_retrieve_response_code($response);
        if ($httpCode !== 200) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body) || !isset($body['time_zone'])) {
            return null;
        }

        // Date header is the reliable clock source
        $dateHeader = wp_remote_retrieve_header($response, 'date');
        if (empty($dateHeader)) {
            return null;
        }

        return array(
            'body'       => $body,
            'serverTime' => $dateHeader,
        );
    }

    // ─── Time helpers ────────────────────────────────────────────────

    /**
     * Parse the HTTP Date header and convert to the merchant's timezone.
     * Header format: "Mon, 20 Apr 2026 14:41:41 GMT"
     */
    private static function parseServerTime($dateHeader, $timezone) {
        $utc = DateTime::createFromFormat('D, d M Y H:i:s \G\M\T', $dateHeader, new DateTimeZone('GMT'));
        if (!$utc) {
            // Fallback: try PHP's generic parser
            try {
                $utc = new DateTime($dateHeader, new DateTimeZone('GMT'));
            } catch (Exception $e) {
                return null;
            }
        }
        try {
            $utc->setTimezone(new DateTimeZone($timezone));
        } catch (Exception $e) {
            return null;
        }
        return $utc;
    }

    /**
     * Convert an HHMM integer (e.g. 830 = 08:30, 2200 = 22:00) to total minutes.
     */
    private static function hhmmToMinutes($val) {
        $val = (int) $val;
        return intdiv($val, 100) * 60 + ($val % 100);
    }

    /**
     * Get the current time as total minutes since midnight.
     */
    private static function nowMinutes(DateTime $now) {
        return (int) $now->format('H') * 60 + (int) $now->format('i');
    }

    // ─── Opening hours ──────────────────────────────────────────────

    /**
     * Check if $now falls within any opening-hours element for today.
     */
    private static function isWithinOpeningHours(array $hours, DateTime $now) {
        $dayName = strtolower($now->format('l')); // e.g. "monday"
        if (!isset($hours[$dayName]) || !isset($hours[$dayName]['elements'])) {
            return false; // no entry for today → closed
        }
        $elements = $hours[$dayName]['elements'];
        if (empty($elements)) {
            return false; // empty elements → closed all day
        }

        $nowMins = self::nowMinutes($now);

        foreach ($elements as $el) {
            $start = self::hhmmToMinutes(isset($el['start']) ? $el['start'] : 0);
            $end   = self::hhmmToMinutes(isset($el['end']) ? $el['end'] : 0);
            if ($nowMins >= $start && $nowMins < $end) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build a human-readable string of today's hours for display.
     * E.g. "8:00am - 10:00pm" or "8:00am - 12:00pm, 2:00pm - 6:00pm"
     */
    private static function formatHoursToday(array $data, DateTime $now) {
        $dayName = strtolower($now->format('l'));
        if (!isset($data['opening_hours'][$dayName]['elements'])) {
            return '';
        }
        $elements = $data['opening_hours'][$dayName]['elements'];
        if (empty($elements)) {
            return '';
        }
        $parts = array();
        foreach ($elements as $el) {
            $start = isset($el['start']) ? (int) $el['start'] : 0;
            $end   = isset($el['end']) ? (int) $el['end'] : 0;
            $parts[] = self::formatHHMM($start) . ' - ' . self::formatHHMM($end);
        }
        return implode(', ', $parts);
    }

    /**
     * Format an HHMM integer as a human-readable time string.
     * 800 → "8:00am", 1400 → "2:00pm", 0 → "12:00am", 2359 → "11:59pm"
     */
    private static function formatHHMM($val) {
        $h = intdiv((int) $val, 100);
        $m = (int) $val % 100;
        $ampm = ($h >= 12) ? 'pm' : 'am';
        $h12 = $h % 12;
        if ($h12 === 0) {
            $h12 = 12;
        }
        return $h12 . ':' . str_pad($m, 2, '0', STR_PAD_LEFT) . $ampm;
    }

    // ─── Blackout / holiday checking ────────────────────────────────

    /**
     * Find the first active blackout covering $now, or null if none.
     */
    private static function findActiveBlackout(array $blackouts, DateTime $now, $timezone) {
        foreach ($blackouts as $bo) {
            if (!isset($bo['mode'])) {
                continue;
            }
            $active = false;
            switch ($bo['mode']) {
                case 'one_time':
                    $active = self::isOneTimeActive($bo, $now, $timezone);
                    break;
                case 'fixed_date':
                    $active = self::isFixedDateActive($bo, $now);
                    break;
                case 'weekday_rule':
                    $active = self::isWeekdayRuleActive($bo, $now, $timezone);
                    break;
            }
            if ($active) {
                return $bo;
            }
        }
        return null;
    }

    /**
     * one_time: check if $now falls between start_time and end_time.
     * Times are stored in merchant's local timezone.
     */
    private static function isOneTimeActive(array $bo, DateTime $now, $timezone) {
        if (empty($bo['start_time']) || empty($bo['end_time'])) {
            return false;
        }
        try {
            $tz = new DateTimeZone($timezone);
            $start = new DateTime($bo['start_time'], $tz);
            $end   = new DateTime($bo['end_time'], $tz);
            return ($now >= $start && $now <= $end);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * fixed_date: check if $now is on the specified month/day (full day, yearly).
     */
    private static function isFixedDateActive(array $bo, DateTime $now) {
        if (!isset($bo['month']) || !isset($bo['day_of_month'])) {
            return false;
        }
        return (int) $now->format('n') === (int) $bo['month']
            && (int) $now->format('j') === (int) $bo['day_of_month'];
    }

    /**
     * weekday_rule: check if $now is the nth weekday of the month.
     * E.g. 4th Thursday of November, or last Monday of May.
     */
    private static function isWeekdayRuleActive(array $bo, DateTime $now, $timezone) {
        if (!isset($bo['month']) || !isset($bo['week_of_month']) || !isset($bo['weekday'])) {
            return false;
        }
        // Only applies in the matching month
        if ((int) $now->format('n') !== (int) $bo['month']) {
            return false;
        }
        try {
            $tz   = new DateTimeZone($timezone);
            $year = (int) $now->format('Y');
            $month = (int) $bo['month'];
            $weekday = strtolower($bo['weekday']);
            $weekOfMonth = (int) $bo['week_of_month'];

            if ($weekOfMonth === -1) {
                // Last occurrence of the weekday in the month
                $target = new DateTime("last {$weekday} of {$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT), $tz);
            } else {
                // Nth occurrence (1-4)
                $firstOfMonth = new DateTime("{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01", $tz);
                // Find the first occurrence of the weekday
                $target = clone $firstOfMonth;
                if (strtolower($target->format('l')) !== $weekday) {
                    $target->modify("next {$weekday}");
                }
                // Advance to the nth occurrence
                if ($weekOfMonth > 1) {
                    $target->modify('+' . ($weekOfMonth - 1) . ' weeks');
                }
            }

            return $now->format('Y-m-d') === $target->format('Y-m-d');
        } catch (Exception $e) {
            return false;
        }
    }
}
