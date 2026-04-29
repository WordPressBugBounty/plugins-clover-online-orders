<?php
/**
 * Shared renderer for the closed-store banner used by the storefront and
 * checkout. Input is the normalized 6-field shape (status, reason, message,
 * hideMenu, acceptOrdersWhileClosed, storeHoursToday) produced by
 * SooStoreStatusChecker::check() and SooDashboardSummary::manualCloseState().
 *
 * Centralizes the banner copy so storefront and checkout can't drift apart.
 */
class SooClosureBanner {
    /**
     * Render the closed-store banner HTML. Returns the empty string when
     * $storeStatus is null or status is 'open'.
     *
     * @param array|null $storeStatus
     * @return string
     */
    public static function render($storeStatus) {
        if (!is_array($storeStatus) || !isset($storeStatus['status']) || $storeStatus['status'] !== 'close') {
            return '';
        }

        $html = '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">';

        if (!empty($storeStatus['message'])) {
            $html .= esc_html($storeStatus['message']);
        } else {
            // Only show today's hours for outside-hours closures. For
            // manual/holiday/blackout closes, hours are misleading — the
            // merchant chose to close even though the schedule says open.
            $isHoursClose = isset($storeStatus['reason']) && $storeStatus['reason'] === 'outside_hours';
            if ($isHoursClose && !empty($storeStatus['storeHoursToday'])) {
                $html .= '<strong>' . __("Today's Online Ordering Hours", 'moo_OnlineOrders') . '</strong><br/> '
                       . esc_html($storeStatus['storeHoursToday']) . '<br/> ';
            }
            $html .= __('Online Ordering Currently Closed', 'moo_OnlineOrders');
        }

        // "You may schedule your order in advance" hint applies only to
        // hours-based closures, not manual or holiday — those are merchant
        // pauses where scheduling shouldn't be offered.
        $isHoursClose = isset($storeStatus['reason']) && $storeStatus['reason'] === 'outside_hours';
        if ($isHoursClose && !empty($storeStatus['acceptOrdersWhileClosed'])) {
            $html .= '<br/><p style="color: #006b00">'
                   . __('You may schedule your order in advance', 'moo_OnlineOrders')
                   . '</p>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render an "ordering temporarily unavailable" banner used when the
     * dashboard endpoint can't be reached in Global mode. Shown in lieu of
     * the storefront menu or checkout form so customers don't place orders
     * under unknown state.
     */
    public static function renderUnavailable() {
        return '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'
             . esc_html__("We can't verify store availability right now. Please try again in a moment.", 'moo_OnlineOrders')
             . '</div>';
    }
}
