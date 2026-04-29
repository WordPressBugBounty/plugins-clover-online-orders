<?php
/**
 * Single source of truth for settings that can be managed locally
 * (in the plugin admin) or globally (from the dashboard).
 *
 * Each entry declares:
 *   - group         logical grouping for admin UI (store, checkout, delivery, ...)
 *   - local_key     key in the moo_settings WP option array
 *   - global_path   dot-notation path in the unified endpoint response
 *   - type          rendering hint for SooSettingsRenderer
 *   - label         human label for the disabled-mode badge
 *   - admin_field_id   id used in admin forms (for inputs and labels)
 *
 * To add a setting: add one entry. The resolver, renderer, REST mapper,
 * and checkout flow pick it up automatically.
 */
class SooSettingsRegistry {
    public static function definitions() {
        return [
            'order_later' => [
                'group' => 'checkout',
                'local_key' => 'order_later',
                'global_path' => 'checkout_settings.scheduleOrder',
                'type' => 'bool_toggle',
                'label' => 'Allow Scheduled Orders',
                'admin_field_id' => 'order_later',
            ],
            'order_later_minutes' => [
                'group' => 'checkout',
                'local_key' => 'order_later_minutes',
                'global_path' => 'checkout_settings.leadTime',
                'type' => 'integer',
                'label' => 'Pickup Lead Time (minutes)',
                'admin_field_id' => 'order_later_minutes',
            ],
            'order_later_days' => [
                'group' => 'checkout',
                'local_key' => 'order_later_days',
                'global_path' => 'checkout_settings.scheduleDayRange',
                'type' => 'integer',
                'label' => 'Schedule Day Range',
                'admin_field_id' => 'order_later_days',
            ],
            'order_later_mandatory' => [
                'group' => 'checkout',
                'local_key' => 'order_later_mandatory',
                'global_path' => 'checkout_settings.isScheduledTimeRequired',
                'type' => 'bool_toggle',
                'label' => 'Require Scheduled Time',
                'admin_field_id' => 'myonoffswitch_order_later_mandatory',
            ],
            'order_later_asap_for_p' => [
                'group' => 'checkout',
                'local_key' => 'order_later_asap_for_p',
                'global_path' => 'checkout_settings.allowAsap',
                'type' => 'bool_toggle',
                'label' => 'Allow ASAP Pickup',
                'admin_field_id' => 'myonoffswitch_order_later_asap_for_p',
            ],
            'tips' => [
                'group' => 'checkout',
                'local_key' => 'tips',
                'global_path' => 'checkout_settings.tipsEnabled',
                'type' => 'bool_toggle',
                'label' => 'Accept Tips',
                'admin_field_id' => 'MooTips',
            ],
            'tips_selection' => [
                'group' => 'checkout',
                'local_key' => 'tips_selection',
                'global_path' => 'checkout_settings.tipsSuggested',
                'type' => 'text',
                'label' => 'Suggested Tip Values',
                'admin_field_id' => 'MooTipsSelections',
            ],
            'tips_default' => [
                'group' => 'checkout',
                'local_key' => 'tips_default',
                'global_path' => 'checkout_settings.tipsDefault',
                'type' => 'text',
                'label' => 'Default Tip Value',
                'admin_field_id' => 'MooTipsDefault',
            ],
            'service_fees' => [
                'group' => 'checkout',
                'local_key' => 'service_fees',
                'global_path' => 'checkout_settings.serviceFeeAmount',
                'type' => 'text',
                'label' => 'Service Fee Amount',
                'admin_field_id' => 'MooServiceFees',
            ],
            'service_fees_type' => [
                'group' => 'checkout',
                'local_key' => 'service_fees_type',
                'global_path' => 'checkout_settings.serviceFeeType',
                'type' => 'text',
                'label' => 'Service Fee Type',
                'admin_field_id' => 'MooServiceFeesType',
            ],
            'service_fees_name' => [
                'group' => 'checkout',
                'local_key' => 'service_fees_name',
                'global_path' => 'checkout_settings.serviceFeeName',
                'type' => 'text',
                'label' => 'Service Fee Name',
                'admin_field_id' => 'MooServiceFeesName',
            ],
            'use_sms_verification' => [
                'group' => 'checkout',
                'local_key' => 'use_sms_verification',
                'global_path' => 'checkout_settings.smsVerificationEnabled',
                'type' => 'bool_toggle',
                'label' => 'SMS Verification',
                'admin_field_id' => 'MooSMSVerification',
            ],
            'use_special_instructions' => [
                'group' => 'checkout',
                'local_key' => 'use_special_instructions',
                'global_path' => 'checkout_settings.specialInstructionsEnabled',
                'type' => 'bool_toggle',
                'label' => 'Special Instructions',
                'admin_field_id' => 'MooUse_special_instructions',
            ],
            'special_instructions_required' => [
                'group' => 'checkout',
                'local_key' => 'special_instructions_required',
                'global_path' => 'checkout_settings.specialInstructionsRequired',
                'type' => 'bool_toggle',
                'label' => 'Require Special Instructions',
                'admin_field_id' => 'special_instructions_required',
            ],
            'text_under_special_instructions' => [
                'group' => 'checkout',
                'local_key' => 'text_under_special_instructions',
                'global_path' => 'checkout_settings.specialInstructionsText',
                'type' => 'text',
                'label' => 'Special Instructions Helper Text',
                'admin_field_id' => 'MooTextUnderSI',
            ],
            'marketing_checkbox_enabled' => [
                'group' => 'checkout',
                'local_key' => 'marketing_checkbox_enabled',
                'global_path' => 'checkout_settings.marketingCheckboxEnabled',
                'type' => 'bool_toggle',
                'label' => 'Marketing Checkbox',
                'admin_field_id' => 'myonoffswitch_marketing_checkbox_enabled',
            ],
            'marketing_checkbox_text' => [
                'group' => 'checkout',
                'local_key' => 'marketing_checkbox_text',
                'global_path' => 'checkout_settings.marketingCheckboxText',
                'type' => 'text',
                'label' => 'Marketing Checkbox Text',
                'admin_field_id' => 'moo_marketing_checkbox_text',
            ],
            'use_coupons' => [
                'group' => 'checkout',
                'local_key' => 'use_coupons',
                'global_path' => 'checkout_settings.useCoupons',
                'type' => 'bool_toggle',
                'label' => 'Use Coupons',
                'admin_field_id' => 'Moouse_coupons',
            ],
            'confirmation_message' => [
                'group' => 'checkout',
                'local_key' => 'confirmation_message',
                'global_path' => 'checkout_settings.confirmation_message',
                'type' => 'text',
                'label' => 'Confirmation Message',
                'admin_field_id' => 'soo_confirmation_message',
            ],
            'show_order_number' => [
                'group' => 'checkout',
                'local_key' => 'show_order_number',
                'global_path' => 'checkout_settings.showOrderNumberOnReceipt',
                'type' => 'bool_toggle',
                'label' => 'Show Order Number On Receipt',
                'admin_field_id' => 'myonoffswitch_show_order_number',
            ],
            'rollout_order_number_max' => [
                'group' => 'checkout',
                'local_key' => 'rollout_order_number_max',
                'global_path' => 'checkout_settings.orderNumberRollOverLimit',
                'type' => 'integer',
                'label' => 'Order Number Roll-Over Limit',
                'admin_field_id' => 'MooRollout_order_number_max',
            ],
            'print_ahead_time_minutes' => [
                'group' => 'checkout',
                'local_key' => 'print_ahead_time_minutes',
                'global_path' => 'checkout_settings.printAheadTimeMinutes',
                'type' => 'integer',
                'label' => 'Print Ahead Time',
                'admin_field_id' => 'soo_print_ahead_time_minutes',
            ],
            'notify_on_new_orders' => [
                'group' => 'store',
                'local_key' => 'notify_on_new_orders',
                'global_path' => 'checkout_settings.notifyOnNewOrders',
                'type' => 'bool_toggle',
                'label' => 'New Order Alerts',
                'admin_field_id' => 'soo_notify_on_new_orders',
            ],
            'merchant_email' => [
                'group' => 'store',
                'local_key' => 'merchant_email',
                'global_path' => 'checkout_settings.notificationEmailList',
                'type' => 'text',
                'label' => 'Merchant Notification Emails',
                'admin_field_id' => 'MooDefaultMerchantEmail',
            ],
            'merchant_phone' => [
                'group' => 'store',
                'local_key' => 'merchant_phone',
                'global_path' => 'checkout_settings.notificationPhoneList',
                'type' => 'text',
                'label' => 'Merchant Notification Phones',
                'admin_field_id' => 'MooDefaultMerchantPhone',
            ],
            'custom_sa_title' => [
                'group' => 'store',
                'local_key' => 'custom_sa_title',
                'global_path' => 'checkout_settings.announcement.title',
                'type' => 'text',
                'label' => 'Announcement Title',
                'admin_field_id' => 'MooCustom_sa_title',
            ],
            'custom_sa_content' => [
                'group' => 'store',
                'local_key' => 'custom_sa_content',
                'global_path' => 'checkout_settings.announcement.content',
                'type' => 'text',
                'label' => 'Announcement Content',
                'admin_field_id' => 'custom_sa_content',
            ],
            'custom_sa_onCheckoutPage' => [
                'group' => 'store',
                'local_key' => 'custom_sa_onCheckoutPage',
                'global_path' => 'checkout_settings.announcement.showOnCheckout',
                'type' => 'bool_toggle',
                'label' => 'Show Announcement On Checkout Page',
                'admin_field_id' => 'myonoffswitch_custom_sa_onCheckoutPage',
            ],
            'track_stock' => [
                'group' => 'store',
                'local_key' => 'track_stock',
                'global_path' => 'checkout_settings.enableStockTracking',
                'type' => 'bool_toggle',
                'label' => 'Track Stock',
                'admin_field_id' => 'Mootrack_stock',
            ],
            'track_stock_hide_items' => [
                'group' => 'store',
                'local_key' => 'track_stock_hide_items',
                'global_path' => 'checkout_settings.hideUnavailableItems',
                'type' => 'bool_toggle',
                'label' => 'Hide Unavailable Items',
                'admin_field_id' => 'myonoffswitch_track_stock_hide_items',
            ],
            'closing_msg' => [
                'group' => 'store',
                'local_key' => 'closing_msg',
                'global_path' => 'store_status.closureMessage',
                'type' => 'text',
                'label' => 'Closing Message',
                'admin_field_id' => 'closing_msg',
            ],
            'accept_orders_w_closed' => [
                'group' => 'store',
                'local_key' => 'accept_orders_w_closed',
                'global_path' => 'store_status.acceptOrdersWhileClosed',
                'type' => 'bool_toggle',
                'label' => 'Accept Orders When Closed',
                'admin_field_id' => 'myonoffswitch_accept_orders',
            ],
            'hide_menu_w_closed' => [
                'group' => 'store',
                'local_key' => 'hide_menu_w_closed',
                'global_path' => 'store_status.hideMenuWhenClosed',
                'type' => 'bool_toggle',
                'label' => 'Hide Menu When Closed',
                'admin_field_id' => 'myonoffswitch_hide_menu_w_closed',
            ],
        ];
    }

    public static function get($key) {
        $defs = self::definitions();
        return isset($defs[$key]) ? $defs[$key] : null;
    }

    public static function byGroup($group) {
        $out = [];
        foreach (self::definitions() as $key => $def) {
            if ($def['group'] === $group) {
                $out[$key] = $def;
            }
        }
        return $out;
    }
}
