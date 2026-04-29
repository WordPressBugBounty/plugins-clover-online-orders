<?php
/**
 * Renders Global-mode read-only summary cards, split by the admin tab
 * they belong to. Replaces the standalone admin-dashboard-mode.php view.
 */
class SooDashboardSummary {

    /**
     * Returns close info from the dashboard in Global mode, or null
     * when not in Global mode, fetch failed, or the store isn't closed
     * for any merchant-initiated reason.
     *
     * Delegates to DashboardCheckoutMapper::mapStoreStatus() so the
     * close-detection rule stays single-sourced. The mapper covers all
     * merchant-initiated close reasons (manually_closed, paused, blackout,
     * holiday) AND the explicit checkout_settings.isClosed toggle —
     * matching what the legacy getBlackoutStatus() conflated. Outside-hours
     * closures are not captured here (mapStoreStatus excludes them) — they
     * remain handled by mapOpeningStatus() / the existing hours-close gates.
     *
     * Shape when non-null (preserved for backward compatibility with
     * existing callers in CheckoutRoutes::openingStatus and
     * checkoutPage::advancedCheckout):
     *   [
     *     'message'  => string — closureMessage from dashboard or default,
     *     'hideMenu' => bool   — hideMenuWhenClosed from dashboard,
     *   ]
     */
    public static function manualCloseState() {
        if (!self::isGlobalActive()) {
            return null;
        }
        $dash = SooSettingsSource::instance()->dashboardClient()->fetch();
        if (!is_array($dash)) {
            return null;
        }
        try {
            $mapper = new DashboardCheckoutMapper($dash);
        } catch (SooDashboardUnavailableException $e) {
            return null;
        }

        $status = $mapper->mapStoreStatus();
        if ($status['status'] !== 'close') {
            return null;
        }

        $message = $status['custom_message'] !== ''
            ? $status['custom_message']
            : __('We are currently closed and will open again soon', 'moo_OnlineOrders');

        return array(
            'message'  => $message,
            'hideMenu' => $status['hide_menu'],
        );
    }

    /**
     * True when Global mode is active AND the dashboard fetch succeeded.
     * Consumers use this to decide whether to render summary cards vs the
     * existing local form.
     */
    public static function isGlobalActive() {
        if (SooSettingsSource::current() !== 'global') {
            return false;
        }
        return !SooSettingsSource::instance()->globalFetchFailed();
    }

    /**
     * Small top-of-tab banner indicating Global mode is active,
     * with Refresh and Open Dashboard buttons.
     */
    public static function renderBadge() {
        ?>
        <div class="soo-dash-banner" style="margin-bottom: 20px;">
            <div class="soo-dash-banner-title">
                <span class="dashicons dashicons-cloud"></span>
                <strong><?php _e('Central Dashboard Mode', 'moo_OnlineOrders'); ?></strong>
            </div>
            <div class="soo-dash-banner-sub">
                <?php _e('These settings come from the Smart Online Order dashboard.', 'moo_OnlineOrders'); ?>
            </div>
            <div class="soo-dash-banner-actions">
                <a href="<?php echo esc_url(add_query_arg('soo_refresh', time())); ?>" class="button"><?php _e('Refresh', 'moo_OnlineOrders'); ?></a>
                <a href="https://smartonlineorder.com/dashboard" target="_blank" class="button button-primary"><?php _e('Open Dashboard &rarr;', 'moo_OnlineOrders'); ?></a>
            </div>
        </div>
        <?php
    }

    /**
     * 4 cards for the Store Settings tab:
     * Store Status, Hours, Stock & Items, Notifications.
     */
    public static function renderStoreCards(?array $dash = null) {
        $cs = self::cs($dash);
        ?>
        <div class="soo-dash-cards">

            <div class="soo-dash-card">
                <h3><?php _e('Store Status', 'moo_OnlineOrders'); ?></h3>
                <table class="soo-dash-kv">
                    <tr><th><?php _e('Accepting orders', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(empty($cs['isClosed'])); ?></td></tr>
                    <tr><th><?php _e('Closure message', 'moo_OnlineOrders'); ?></th><td><?php echo self::orPlaceholder(isset($cs['closureMessage']) ? $cs['closureMessage'] : null); ?></td></tr>
                    <tr><th><?php _e('Accept orders while closed', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(!empty($cs['acceptOrdersWhileClosed'])); ?></td></tr>
                    <tr><th><?php _e('Hide menu when closed', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(!empty($cs['hideMenuWhenClosed'])); ?></td></tr>
                </table>
            </div>

            <div class="soo-dash-card">
                <h3><?php _e('Hours', 'moo_OnlineOrders'); ?></h3>
                <table class="soo-dash-kv">
                    <tr><th><?php _e('Source', 'moo_OnlineOrders'); ?></th><td>
                    <?php
                    if (!empty($cs['useCustomHours'])) {
                        _e('Custom hours', 'moo_OnlineOrders');
                    } elseif (!empty($cs['useCloverHours'])) {
                        _e('Clover business hours', 'moo_OnlineOrders');
                    } else {
                        _e('Always open (no hour restrictions)', 'moo_OnlineOrders');
                    }
                    ?>
                    </td></tr>
                </table>
            </div>

            <div class="soo-dash-card">
                <h3><?php _e('Stock & Items', 'moo_OnlineOrders'); ?></h3>
                <table class="soo-dash-kv">
                    <tr><th><?php _e('Track stock', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(!empty($cs['enableStockTracking'])); ?></td></tr>
                    <tr><th><?php _e('Hide unavailable items', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(!empty($cs['hideUnavailableItems'])); ?></td></tr>
                </table>
            </div>

            <div class="soo-dash-card">
                <h3><?php _e('Notifications', 'moo_OnlineOrders'); ?></h3>
                <table class="soo-dash-kv">
                    <tr><th><?php _e('New order alerts', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(!empty($cs['notifyOnNewOrders'])); ?></td></tr>
                    <tr><th><?php _e('Emails', 'moo_OnlineOrders'); ?></th><td><?php echo self::orPlaceholder(isset($cs['notificationEmailList']) ? $cs['notificationEmailList'] : null); ?></td></tr>
                    <tr><th><?php _e('Phones', 'moo_OnlineOrders'); ?></th><td><?php echo self::orPlaceholder(isset($cs['notificationPhoneList']) ? $cs['notificationPhoneList'] : null); ?></td></tr>
                </table>
            </div>

        </div>
        <?php
    }

    /**
     * 4 cards for the Checkout Settings tab:
     * Scheduled Orders, Payment Methods, Checkout Experience, Receipt & Kitchen.
     */
    public static function renderCheckoutCards(?array $dash = null) {
        $cs = self::cs($dash);
        ?>
        <div class="soo-dash-cards">

            <div class="soo-dash-card">
                <h3><?php _e('Scheduled Orders', 'moo_OnlineOrders'); ?></h3>
                <table class="soo-dash-kv">
                    <tr><th><?php _e('Allow scheduled orders', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(!empty($cs['scheduleOrder'])); ?></td></tr>
                    <tr><th><?php _e('Require scheduled time', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(!empty($cs['isScheduledTimeRequired'])); ?></td></tr>
                    <tr><th><?php _e('Lead time', 'moo_OnlineOrders'); ?></th><td><?php echo self::orPlaceholder(isset($cs['leadTime']) ? $cs['leadTime'] . ' min' : null); ?></td></tr>
                    <tr><th><?php _e('Day range', 'moo_OnlineOrders'); ?></th><td><?php echo self::orPlaceholder(isset($cs['scheduleDayRange']) ? $cs['scheduleDayRange'] . ' days' : null); ?></td></tr>
                    <tr><th><?php _e('Slot interval', 'moo_OnlineOrders'); ?></th><td><?php echo self::orPlaceholder(isset($cs['scheduleInterval']) ? $cs['scheduleInterval'] . ' min' : null); ?></td></tr>
                    <tr><th><?php _e('Allow ASAP', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(!empty($cs['allowAsap'])); ?></td></tr>
                </table>
            </div>

            <div class="soo-dash-card">
                <h3><?php _e('Payment Methods', 'moo_OnlineOrders'); ?></h3>
                <?php $pm = isset($cs['paymentMethods']) ? $cs['paymentMethods'] : array(); ?>
                <?php if (is_array($pm) && !empty($pm)) { ?>
                    <ul class="soo-dash-list">
                        <?php foreach ($pm as $method) {
                            $label = is_array($method) && isset($method['label']) ? $method['label'] : (is_string($method) ? $method : '');
                            if ($label !== '') { echo '<li>' . esc_html($label) . '</li>'; }
                        } ?>
                    </ul>
                <?php } else { ?>
                    <p class="soo-dash-empty"><?php _e('[none enabled]', 'moo_OnlineOrders'); ?></p>
                <?php } ?>
            </div>

            <div class="soo-dash-card">
                <h3><?php _e('Tips & Fees', 'moo_OnlineOrders'); ?></h3>
                <table class="soo-dash-kv">
                    <tr><th><?php _e('Tips enabled', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(isset($cs['tipsEnabled']) ? $cs['tipsEnabled'] : true); ?></td></tr>
                    <tr><th><?php _e('Suggested tips', 'moo_OnlineOrders'); ?></th><td><?php echo self::orPlaceholder(isset($cs['tipsSuggested']) ? $cs['tipsSuggested'] : null); ?></td></tr>
                    <tr><th><?php _e('Default tip', 'moo_OnlineOrders'); ?></th><td><?php echo self::orPlaceholder(isset($cs['tipsDefault']) ? $cs['tipsDefault'] . '%' : null); ?></td></tr>
                    <tr><th><?php _e('Service fee', 'moo_OnlineOrders'); ?></th><td>
                    <?php
                    $feeAmt = isset($cs['serviceFeeAmount']) ? $cs['serviceFeeAmount'] : null;
                    $feeType = isset($cs['serviceFeeType']) ? $cs['serviceFeeType'] : null;
                    if ($feeAmt !== null && $feeAmt !== '') {
                        echo esc_html($feeAmt . ($feeType === 'percent' ? '%' : ''));
                    } else {
                        echo self::orPlaceholder(null);
                    }
                    ?>
                    </td></tr>
                    <tr><th><?php _e('Fee display name', 'moo_OnlineOrders'); ?></th><td><?php echo self::orPlaceholder(isset($cs['serviceFeeName']) ? $cs['serviceFeeName'] : null); ?></td></tr>
                </table>
            </div>

            <div class="soo-dash-card">
                <h3><?php _e('Checkout Experience', 'moo_OnlineOrders'); ?></h3>
                <table class="soo-dash-kv">
                    <tr><th><?php _e('Guest checkout', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(!empty($cs['guestModeEnabled'])); ?></td></tr>
                    <tr><th><?php _e('Announcement', 'moo_OnlineOrders'); ?></th><td>
                    <?php
                    if (isset($cs['announcement']) && is_array($cs['announcement']) && !empty($cs['announcement']['title'])) {
                        echo esc_html($cs['announcement']['title']);
                    } else {
                        echo '<span class="soo-dash-empty">' . esc_html__('[not set]', 'moo_OnlineOrders') . '</span>';
                    }
                    ?>
                    </td></tr>
                    <tr><th><?php _e('Confirmation message', 'moo_OnlineOrders'); ?></th><td><?php echo self::orPlaceholder(isset($cs['confirmation_message']) ? $cs['confirmation_message'] : null); ?></td></tr>
                    <tr><th><?php _e('On-demand delivery', 'moo_OnlineOrders'); ?></th><td>
                    <?php
                    if (!empty($cs['onDemandDeliveriesEnabled'])) {
                        echo esc_html(isset($cs['onDemandDeliveriesLabel']) ? $cs['onDemandDeliveriesLabel'] : __('Enabled', 'moo_OnlineOrders'));
                    } else {
                        echo self::yesno(false);
                    }
                    ?>
                    </td></tr>
                    <tr><th><?php _e('Special instructions', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(!empty($cs['specialInstructionsEnabled'])); ?></td></tr>
                    <tr><th><?php _e('Marketing checkbox', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(!empty($cs['marketingCheckboxEnabled'])); ?></td></tr>
                    <tr><th><?php _e('SMS verification', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(!empty($cs['smsVerificationEnabled'])); ?></td></tr>
                </table>
            </div>

            <div class="soo-dash-card">
                <h3><?php _e('Receipt & Kitchen', 'moo_OnlineOrders'); ?></h3>
                <table class="soo-dash-kv">
                    <tr><th><?php _e('Show order number on receipt', 'moo_OnlineOrders'); ?></th><td><?php echo self::yesno(!empty($cs['showOrderNumberOnReceipt'])); ?></td></tr>
                    <tr><th><?php _e('Order number roll-over', 'moo_OnlineOrders'); ?></th><td><?php echo self::orPlaceholder(isset($cs['orderNumberRollOverLimit']) ? $cs['orderNumberRollOverLimit'] : null); ?></td></tr>
                    <tr><th><?php _e('Print-ahead time', 'moo_OnlineOrders'); ?></th><td><?php
                        $pat = isset($cs['printAheadTimeMinutes']) ? (int) $cs['printAheadTimeMinutes'] : null;
                        if ($pat === null) {
                            echo self::orPlaceholder(null);
                        } elseif ($pat < 0) {
                            echo esc_html__('Real-time (disabled)', 'moo_OnlineOrders');
                        } elseif ($pat === 0) {
                            echo esc_html__('Immediate', 'moo_OnlineOrders');
                        } else {
                            echo esc_html($pat . ' min');
                        }
                    ?></td></tr>
                </table>
            </div>

        </div>
        <?php
    }

    /**
     * Returns the checkout_settings sub-array, or empty array if unavailable.
     */
    private static function cs($dash) {
        if (!is_array($dash) || !isset($dash['checkout_settings'])) {
            return array();
        }
        return $dash['checkout_settings'];
    }

    /**
     * Renders a yes/no span for a boolean-ish value.
     */
    private static function yesno($value) {
        if ($value === true || $value === 1 || $value === '1' || $value === 'on' || $value === 'true') {
            return '<span class="soo-dash-yes">' . esc_html__('Yes', 'moo_OnlineOrders') . '</span>';
        }
        return '<span class="soo-dash-no">' . esc_html__('No', 'moo_OnlineOrders') . '</span>';
    }

    /**
     * Renders a value or "[not set]" placeholder.
     */
    private static function orPlaceholder($value) {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return '<span class="soo-dash-empty">' . esc_html__('[not set]', 'moo_OnlineOrders') . '</span>';
        }
        if (is_array($value)) {
            return esc_html(implode(', ', $value));
        }
        return esc_html((string) $value);
    }

    /**
     * Local-only editable form for Tab 7 (Checkout Settings) in Global mode.
     * Renders tips, service fees, special instructions, and SMS verification.
     *
     * All OTHER moo_settings keys are preserved via hidden inputs so saving
     * this form doesn't wipe dashboard-managed or other local values.
     *
     * @param array $mooOptions the current moo_settings option array
     */
    public static function renderCheckoutLocalForm(array $mooOptions) {
        $editableKeys = array(
            'tips', 'tips_selection', 'tips_default',
            'service_fees', 'service_fees_type', 'service_fees_name',
            'use_special_instructions', 'special_instructions_required', 'text_under_special_instructions',
            'use_sms_verification',
        );

        $tips            = isset($mooOptions['tips']) ? $mooOptions['tips'] : 'disabled';
        $tips_selection  = isset($mooOptions['tips_selection']) ? $mooOptions['tips_selection'] : '';
        $tips_default    = isset($mooOptions['tips_default']) ? $mooOptions['tips_default'] : '';
        $service_fees    = isset($mooOptions['service_fees']) ? $mooOptions['service_fees'] : '';
        $service_fees_type = isset($mooOptions['service_fees_type']) ? $mooOptions['service_fees_type'] : 'amount';
        $service_fees_name = isset($mooOptions['service_fees_name']) ? $mooOptions['service_fees_name'] : '';
        $use_si          = isset($mooOptions['use_special_instructions']) ? $mooOptions['use_special_instructions'] : 'disabled';
        $si_required     = isset($mooOptions['special_instructions_required']) ? $mooOptions['special_instructions_required'] : '';
        $si_text         = isset($mooOptions['text_under_special_instructions']) ? $mooOptions['text_under_special_instructions'] : '';
        $use_sms         = isset($mooOptions['use_sms_verification']) ? $mooOptions['use_sms_verification'] : 'disabled';
        ?>
        <div class="soo-dash-local-only">
            <h2><?php _e('Local Only Settings', 'moo_OnlineOrders'); ?></h2>
            <p class="description"><?php _e('These checkout settings stay local on this site. They are not overridden by the dashboard.', 'moo_OnlineOrders'); ?></p>

            <form method="post" action="options.php" onsubmit="mooSaveChanges(event,this)">
                <?php settings_fields('moo_settings'); ?>

                <?php
                // Preserve every moo_settings entry not in $editableKeys via hidden inputs
                foreach ($mooOptions as $optKey => $optVal) {
                    if (in_array($optKey, $editableKeys, true)) {
                        continue;
                    }
                    if (!is_scalar($optVal)) {
                        continue;
                    }
                    echo '<input type="hidden" name="moo_settings[' . esc_attr($optKey) . ']" value="' . esc_attr((string) $optVal) . '">';
                }
                ?>

                <div class="soo-dash-local-section">
                    <h3><?php _e('Tips', 'moo_OnlineOrders'); ?></h3>
                    <table class="form-table soo-dash-form-table">
                        <tr>
                            <th scope="row"><label for="tips"><?php _e('Accept tips', 'moo_OnlineOrders'); ?></label></th>
                            <td>
                                <select id="tips" name="moo_settings[tips]">
                                    <option value="disabled" <?php selected($tips, 'disabled'); ?>><?php _e('Disabled', 'moo_OnlineOrders'); ?></option>
                                    <option value="enabled" <?php selected($tips, 'enabled'); ?>><?php _e('Enabled', 'moo_OnlineOrders'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="tips_selection"><?php _e('Suggested values', 'moo_OnlineOrders'); ?></label></th>
                            <td>
                                <input type="text" id="tips_selection" name="moo_settings[tips_selection]" class="regular-text" value="<?php echo esc_attr($tips_selection); ?>" placeholder="10,15,20">
                                <p class="description"><?php _e('Comma-separated values (percentages or amounts).', 'moo_OnlineOrders'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="tips_default"><?php _e('Default value', 'moo_OnlineOrders'); ?></label></th>
                            <td>
                                <input type="text" id="tips_default" name="moo_settings[tips_default]" class="regular-text" value="<?php echo esc_attr($tips_default); ?>">
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="soo-dash-local-section">
                    <h3><?php _e('Service Fees', 'moo_OnlineOrders'); ?></h3>
                    <table class="form-table soo-dash-form-table">
                        <tr>
                            <th scope="row"><label for="service_fees"><?php _e('Amount', 'moo_OnlineOrders'); ?></label></th>
                            <td>
                                <input type="text" id="service_fees" name="moo_settings[service_fees]" class="regular-text" value="<?php echo esc_attr($service_fees); ?>">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="service_fees_type"><?php _e('Type', 'moo_OnlineOrders'); ?></label></th>
                            <td>
                                <select id="service_fees_type" name="moo_settings[service_fees_type]">
                                    <option value="amount" <?php selected($service_fees_type, 'amount'); ?>><?php _e('Fixed amount', 'moo_OnlineOrders'); ?></option>
                                    <option value="percent" <?php selected($service_fees_type, 'percent'); ?>><?php _e('Percentage', 'moo_OnlineOrders'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="service_fees_name"><?php _e('Display name', 'moo_OnlineOrders'); ?></label></th>
                            <td>
                                <input type="text" id="service_fees_name" name="moo_settings[service_fees_name]" class="regular-text" value="<?php echo esc_attr($service_fees_name); ?>">
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="soo-dash-local-section">
                    <h3><?php _e('Special Instructions', 'moo_OnlineOrders'); ?></h3>
                    <table class="form-table soo-dash-form-table">
                        <tr>
                            <th scope="row"><label for="use_special_instructions"><?php _e('Accept special instructions', 'moo_OnlineOrders'); ?></label></th>
                            <td>
                                <select id="use_special_instructions" name="moo_settings[use_special_instructions]">
                                    <option value="disabled" <?php selected($use_si, 'disabled'); ?>><?php _e('Disabled', 'moo_OnlineOrders'); ?></option>
                                    <option value="enabled" <?php selected($use_si, 'enabled'); ?>><?php _e('Enabled', 'moo_OnlineOrders'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="special_instructions_required"><?php _e('Required', 'moo_OnlineOrders'); ?></label></th>
                            <td>
                                <select id="special_instructions_required" name="moo_settings[special_instructions_required]">
                                    <option value="" <?php selected($si_required, ''); ?>><?php _e('Optional', 'moo_OnlineOrders'); ?></option>
                                    <option value="yes" <?php selected($si_required, 'yes'); ?>><?php _e('Required', 'moo_OnlineOrders'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="text_under_special_instructions"><?php _e('Helper text', 'moo_OnlineOrders'); ?></label></th>
                            <td>
                                <input type="text" id="text_under_special_instructions" name="moo_settings[text_under_special_instructions]" class="regular-text" value="<?php echo esc_attr($si_text); ?>">
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="soo-dash-local-section">
                    <h3><?php _e('SMS Verification', 'moo_OnlineOrders'); ?></h3>
                    <table class="form-table soo-dash-form-table">
                        <tr>
                            <th scope="row"><label for="use_sms_verification"><?php _e('Verify phone via SMS', 'moo_OnlineOrders'); ?></label></th>
                            <td>
                                <select id="use_sms_verification" name="moo_settings[use_sms_verification]">
                                    <option value="disabled" <?php selected($use_sms, 'disabled'); ?>><?php _e('Disabled', 'moo_OnlineOrders'); ?></option>
                                    <option value="enabled" <?php selected($use_sms, 'enabled'); ?>><?php _e('Enabled', 'moo_OnlineOrders'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(__('Save Local Settings', 'moo_OnlineOrders')); ?>
            </form>
        </div>
        <?php
    }
}
