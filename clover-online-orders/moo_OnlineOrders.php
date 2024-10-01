<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.zaytech.com
 * @since             1.0.0
 * @package           Wordpress_Integration
 *
 * @wordpress-plugin
 * Plugin Name:       Smart Online Order for Clover
 * Plugin URI:        https://www.zaytech.com
 * Description:       Start taking orders from your Wordpress website and have them sent to your Clover Station
 * Version:           1.5.7
 * Author:            Zaytech
 * Author URI:        https://www.zaytech.com
 * License:           Clover app
 * License URI:       https://www.zaytech.com
 * Text Domain:       moo_OnlineOrders
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

define(
    'SOO_PLUGIN_URL',
    untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)))
);
define('SOO_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('SOO_ENV', 'PROD');
define('SOO_DEFAULT_CDN', false);
define('SOO_VERSION', "1.5.7");
define('SOO_G_RECAPTCHA_URL', 'https://www.google.com/recaptcha/api/siteverify');
define('SOO_ACCEPT_GIFTCARDS', true);

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/moo-OnlineOrders.php';

/**
 * Helper functions
 */
require plugin_dir_path(__FILE__) . 'includes/moo-OnlineOrders-helpers.php';

require plugin_dir_path(__FILE__) . 'includes/moo-OnlineOrders-deactivator.php';
//Moo_OnlineOrders_Deactivator::onlyClean();

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/moo-OnlineOrders-activator.php
 */
function activate_moo_OnlineOrders($network_wide) {
    require_once plugin_dir_path(__FILE__) . 'includes/moo-OnlineOrders-activator.php';
    if (function_exists('is_multisite') && is_multisite() && $network_wide) {
        Moo_OnlineOrders_Activator::activateOnNetwork();
        return;
    }
    Moo_OnlineOrders_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/moo-OnlineOrders-deactivator.php
 */
function deactivate_moo_OnlineOrders() {

    require_once plugin_dir_path(__FILE__) . 'includes/moo-OnlineOrders-deactivator.php';
    Moo_OnlineOrders_Deactivator::deactivate();
}
function moo_OnlineOrders_shortcodes_allitems($atts, $content) {
    require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/storePage.php';
    $shortCode = new storePage();
    return $shortCode->render($atts, $content);
}
function moo_OnlineOrders_shortcodes_checkoutPage($atts, $content) {
    require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/checkoutPage.php';
    $checkoutPage = new CheckoutPage();
    return $checkoutPage->render($atts, $content);
}
function moo_OnlineOrders_shortcodes_receiptLinkInThanksPage($atts, $content) {
    require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/checkoutPage.php';
    $checkoutPage = new CheckoutPage();
    return $checkoutPage->renderReceiptLink($atts, $content);
}
function moo_OnlineOrders_shortcodes_buybutton($atts, $content) {
    require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/addToCartButton.php';
    $shortCode = new addToCartButton();
    return $shortCode->render($atts, $content);
}
function moo_OnlineOrders_shortcodes_thecart($atts, $content) {
    require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/cartPage.php';
    $cartPage = new CartPage();
    return $cartPage->render($atts, $content);
}
function moo_OnlineOrders_shortcodes_giftcards_balance($atts, $content) {
    require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/GiftCardBalance.php';
    $cartPage = new giftCardBalance();
    return $cartPage->render($atts, $content);
}
function moo_OnlineOrders_shortcodes_searchBar($atts, $content) {
    require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/searchBar.php';
    $shortCode = new searchBar();
    return $shortCode->render($atts, $content);
}
function moo_OnlineOrders_shortcodes_customerAccount($atts, $content) {
    require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/myOrdersPage.php';
    $shortCode = new myOrdersPage();
    return $shortCode->render($atts, $content);
}

function moo_OnlineOrders_shortcodes_categorymsg($atts, $content) {
    if (isset($atts["cat_id"]) && $atts["message"]) {
        if (isset($_GET["category"]) && $_GET["category"] == $atts["cat_id"]) {
            if (isset($atts["css-class"]) && $atts["css-class"]!="") {
                return "<div class='".$atts["css-class"]."'>".esc_attr($atts["message"])."</div>";
            } else {
                return sanitize_text_field($atts["message"]);
            }
        }
    } else {
        return "Please enter the category id (cat_id) and the message";
    }
}


/*
* Widgets Contents
*/
function moo_OnlineOrders_widget_opening_hours() {
    require_once plugin_dir_path(__FILE__) . 'includes/moo-OnlineOrders-widgets.php';
    register_widget('Moo_OnlineOrders_Widgets_Opening_hours');
}
function moo_OnlineOrders_widget_best_selling() {
    require_once plugin_dir_path(__FILE__) . 'includes/moo-OnlineOrders-widgets.php';
    register_widget('Moo_OnlineOrders_Widgets_best_selling');
}
function Moo_OnlineOrders_Widgets_categories() {
    require_once plugin_dir_path(__FILE__) . 'includes/moo-OnlineOrders-widgets.php';
    register_widget('Moo_OnlineOrders_Widgets_categories');
}

function moo_OnlineOrders_RestAPI() {
    require_once plugin_dir_path(__FILE__) . 'includes/moo-OnlineOrders-Restapi.php';
    $rest_api = new Moo_OnlineOrders_Restapi();
    $rest_api->register_routes();
}

/* Activate and deactivate hooks*/
register_activation_hook(__FILE__, 'activate_moo_OnlineOrders');
register_deactivation_hook(__FILE__, 'deactivate_moo_OnlineOrders');

/* adding shortcodes */
add_shortcode('moo_all_items', 'moo_OnlineOrders_shortcodes_allitems');
add_shortcode('moo_cart', 'moo_OnlineOrders_shortcodes_thecart');
add_shortcode('moo_checkout', 'moo_OnlineOrders_shortcodes_checkoutPage');
add_shortcode('moo_my_account', 'moo_OnlineOrders_shortcodes_customerAccount');

add_shortcode('moo_buy_button', 'moo_OnlineOrders_shortcodes_buybutton');
add_shortcode('moo_category_msg', 'moo_OnlineOrders_shortcodes_categorymsg');
add_shortcode('moo_search', 'moo_OnlineOrders_shortcodes_searchBar');
add_shortcode('moo_receipt_link', 'moo_OnlineOrders_shortcodes_receiptLinkInThanksPage');

//Gift Cards Shortcodes
add_shortcode('moo_giftcards_balance', 'moo_OnlineOrders_shortcodes_giftcards_balance');

/* adding widgets*/
add_action('widgets_init', 'moo_OnlineOrders_widget_opening_hours');
add_action('widgets_init', 'moo_OnlineOrders_widget_best_selling');
add_action('widgets_init', 'Moo_OnlineOrders_Widgets_categories');

/* Rest Api */
add_action('rest_api_init', 'moo_OnlineOrders_RestAPI');

// add links to plugin

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'moo_add_action_links');

function moo_add_action_links($links)
{
    $plugin_links_1 = array(
        '<a href="admin.php?page=moo_index">Settings</a>',
        '<a href="https://docs.zaytech.com/">Docs</a>',
        '<a href="https://zaytech.com/support/">Support</a>',
    );

    return array_merge($plugin_links_1, $links);
}
function moo_deactivateAndClean() {
    if (current_user_can( 'manage_options' ) && isset($_GET['page']) &&  $_GET['page'] === 'moo_deactivateAndClean') {
        require_once plugin_dir_path(__FILE__)."/includes/moo-OnlineOrders-deactivator.php";

        if (function_exists("is_plugin_active_for_network") && !is_plugin_active_for_network(plugin_basename(__FILE__))) {
            Moo_OnlineOrders_Deactivator::deactivateAndClean();
            deactivate_plugins(plugin_basename(__FILE__), true);
        } else {
            Moo_OnlineOrders_Deactivator::onlyClean();
        }

        $url = admin_url('plugins.php?deactivate=true');
        header("Location: $url");
        die();
    }
}
add_action('admin_init', 'moo_deactivateAndClean');
                 
if (get_option('moo_onlineOrders_version') != '157') {
    add_action('plugins_loaded', 'moo_onlineOrders_check_version');
}


/*
 * This function for updating the database structure when the version changed and updated it automatically
 *
 * @since v 1.1.2
 */
function moo_onlineOrders_check_version()
{
    global $wpdb;
    $wpdb->hide_errors();
    $version = get_option('moo_onlineOrders_version');
    $defaultOptions = get_option('moo_settings');
    if (empty($version)) {
        $version="120";
    }
    switch ($version) {
        case '120':
            //Adding new fields in category table
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_category` ADD `image_url` VARCHAR(255) NULL");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_category` ADD `alternate_name` VARCHAR(100) NULL");

        case '121':
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_modifier` ADD `sort_order` INT NULL");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_modifier` ADD `show_by_default` INT NOT NULL DEFAULT '1'");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_modifier_group` ADD `sort_order` INT NULL");
        case '122':
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `type` INT(1) NULL");
        case '123':
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_item` ADD `sort_order` INT NULL");
        case '124':
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `sort_order` INT NULL");
            @$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}moo_item_order` (
                          `_id` INT NOT NULL AUTO_INCREMENT,
                          `item_uuid` VARCHAR(100) NOT NULL,
                          `order_uuid` VARCHAR(100) NOT NULL,
                          `quantity` VARCHAR(100) NOT NULL,
                          `modifiers` TEXT NOT NULL,
                          `special_ins` VARCHAR(255) NOT NULL,
                          PRIMARY KEY (`_id`, `item_uuid`, `order_uuid`)
                            );");

            //Change where pages are saved
            $store_page     = get_option('moo_store_page');
            $checkout_page  = get_option('moo_checkout_page');
            $cart_page      = get_option('moo_cart_page');
            if (!isset($defaultOptions["store_page"]) || $defaultOptions["store_page"] == "") {
                $defaultOptions["store_page"] = $store_page;
            }
            if (!isset($defaultOptions["checkout_page"]) || $defaultOptions["checkout_page"] == "") {
                $defaultOptions["checkout_page"] = $checkout_page;
            }
            if (!isset($defaultOptions["cart_page"]) || $defaultOptions["cart_page"] == "") {
                $defaultOptions["cart_page"] = $cart_page;
            }
            if (!isset($defaultOptions["checkout_login"]) || $defaultOptions["checkout_login"] == "") {
                $defaultOptions["checkout_login"] = "enabled";
            }
        case '125':
            //add description to items
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_item` CHANGE `description` `description` TEXT ");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `minAmount` VARCHAR(100) NULL DEFAULT '0' ");
            //add default options for coupons feature
            if (!isset($defaultOptions["use_coupons"]) || $defaultOptions["use_coupons"] == "") {
                $defaultOptions["use_coupons"] = "disabled";
            }
        case '126':
            if (!isset($defaultOptions["use_special_instructions"]) || $defaultOptions["use_special_instructions"] == "") {
                $defaultOptions["use_special_instructions"] = "enabled";
            }
            if (!isset($defaultOptions["save_cards"]) || $defaultOptions["save_cards"] == "") {
                $defaultOptions["save_cards"] = "disabled";
            }
            if (!isset($defaultOptions["save_cards_fees"]) || $defaultOptions["save_cards_fees"] == "") {
                $defaultOptions["save_cards_fees"] = "disabled";
            }
            if (!isset($defaultOptions["service_fees_name"]) || $defaultOptions["service_fees_name"] == "") {
                $defaultOptions["service_fees_name"] = "Service Charge";
            }
            if (!isset($defaultOptions["service_fees_type"]) || $defaultOptions["service_fees_type"] == "") {
                $defaultOptions["service_fees_type"] = "amount";
            }
            if (!isset($defaultOptions["delivery_fees_name"]) || $defaultOptions["delivery_fees_name"] == "") {
                $defaultOptions["delivery_fees_name"] = "Delivery Charge";
            }
            if (!isset($defaultOptions["order_later_minutes_delivery"]) || $defaultOptions["order_later_minutes_delivery"] == "") {
                $defaultOptions["order_later_minutes_delivery"] = "60";
            }
            if (!isset($defaultOptions["order_later_days_delivery"]) || $defaultOptions["order_later_days_delivery"] == "") {
                $defaultOptions["order_later_days_delivery"] = "4";
            }
            if (!isset($defaultOptions["copyrights"]) || $defaultOptions["copyrights"] == "") {
                $defaultOptions["copyrights"] = 'Powered by <a href="https://wordpress.org/plugins/clover-online-orders/" target="_blank" title="Online Orders for Clover POS v 1.2.8">Smart Online Order</a>';
            }
        case '127':
            $default_options = array(
                array("name"=>"onePage_fontFamily","value"=>"Oswald,sans-serif"),
                array("name"=>"onePage_categoriesTopMargin","value"=>"0"),
                array("name"=>"onePage_width","value"=>"1024"),
                array("name"=>"onePage_categoriesFontColor","value"=>"#ffffff"),
                array("name"=>"onePage_categoriesBackgroundColor","value"=>"#282b2e"),
                array("name"=>"onePage_qtyWindow","value"=>"on"),
                array("name"=>"onePage_qtyWindowForModifiers","value"=>"on"),
                array("name"=>"onePage_backToTop","value"=>"off"),
                array("name"=>"order_later_asap_for_p","value"=>"off"),
                array("name"=>"order_later_asap_for_d","value"=>"off"),
                array("name"=>"mg_settings_displayInline","value"=>"disabled"),
                array("name"=>"mg_settings_qty_for_all","value"=>"enabled"),
                array("name"=>"mg_settings_qty_for_zeroPrice","value"=>"enabled"),
            );

            foreach ($default_options as $default_option) {
                if (!isset($defaultOptions[$default_option["name"]])) {
                    $defaultOptions[$default_option["name"]]=$default_option["value"];
                }
            }

        case '128':
        case '130':
        case '131':
            if (!isset($defaultOptions['onePage_show_more_button'])) {
                $defaultOptions['onePage_show_more_button']='on';
            }
            $defaultOptions['payment_creditcard'] = 'off';
            $defaultOptions['use_sms_verification'] = 'enabled';
        case '132':
            if (!isset($defaultOptions['my_account_page'])) {
                $defaultOptions['my_account_page']='';
            }
            if (!isset($defaultOptions['text_under_special_instructions'])) {
                $defaultOptions['text_under_special_instructions']='*additional charges may apply and not all changes are possible';
            }
            if (!isset($defaultOptions['use_couponsApp'])) {
                $defaultOptions['use_couponsApp']= "off";
            }
            if (!isset($defaultOptions['custom_sa_content'])) {
                $defaultOptions['custom_sa_content']= "";
            }
            if (!isset($defaultOptions['custom_sa_title'])) {
                $defaultOptions['custom_sa_title']= "";
            }
            if (!isset($defaultOptions['custom_sa_onCheckoutPage'])) {
                $defaultOptions['custom_sa_onCheckoutPage']= "off";
            }
            if (!isset($defaultOptions['closing_msg'])) {
                $defaultOptions['closing_msg']= "";
            }
        case '133':
            if (!isset($defaultOptions['accept_orders'])) {
                $defaultOptions['accept_orders']= "enabled";
            }
            if (!isset($defaultOptions['onePage_askforspecialinstruction'])) {
                $defaultOptions['onePage_askforspecialinstruction'] = 'off';
            }
            if (!isset($defaultOptions['onePage_messageforspecialinstruction'])) {
                $defaultOptions['onePage_messageforspecialinstruction'] = 'Type your instructions here, additional charges may apply and not all changes are possible';
            }
            if (!isset($defaultOptions['jTheme_askforspecialinstruction'])) {
                $defaultOptions['jTheme_askforspecialinstruction'] = 'off';
            }
            if (!isset($defaultOptions['jTheme_messageforspecialinstruction'])) {
                $defaultOptions['jTheme_messageforspecialinstruction'] = 'Type your instructions here, additional charges may apply and not all changes are possible';
            }
            if (!isset($defaultOptions['style2_askforspecialinstruction'])) {
                $defaultOptions['style2_askforspecialinstruction'] = 'off';
            }
            if (!isset($defaultOptions['style2_messageforspecialinstruction'])) {
                $defaultOptions['style2_messageforspecialinstruction'] = 'Type your instructions here, additional charges may apply and not all changes are possible';
            }

        case '136':
        case '137':
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_category` ADD `description` TEXT NULL");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_category` ADD `custom_hours` VARCHAR(100) NULL");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_category` ADD `time_availability` VARCHAR(10) NULL");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_item` CHANGE `description` `description` TEXT ");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `minAmount` VARCHAR(100) NULL DEFAULT '0' ");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `custom_hours` VARCHAR(100) NULL");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `time_availability` VARCHAR(10) DEFAULT 1");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `use_coupons` INT(1) NULL DEFAULT 1");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `custom_message` VARCHAR(255) NULL DEFAULT 'Not available yet'");
        case '138':
        case '139':
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `allow_sc_order` INT(1) NULL DEFAULT 1 ");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `maxAmount` VARCHAR(100) NULL DEFAULT '' ");
            $defaultOptions["mg_settings_minimized"] = "off";
            $defaultOptions["scp"] = "off";
            $defaultOptions["tips_selection"] = "10,15,20,25";
            $defaultOptions["tips_default"] = "";
            $defaultOptions["rollout_order_number"] = "on";
            $defaultOptions["rollout_order_number_max"] = "999";
            $defaultOptions["thanks_page_wp"] = "";
        case '140':
        case '141':
        case '142':
        case '143':
        case '144':
            set_transient('moo_blackout', false, 1);
            if (!isset($defaultOptions["delivery_errorMsg"]) || $defaultOptions["delivery_errorMsg"] == "") {
                $defaultOptions["delivery_errorMsg"] = "Sorry, zone not supported. We do not deliver to this address at this time";
            }
            $defaultOptions["special_instructions_required"] = "no";
        case '145':
        case '146':
        case '147':
        case '148':
        case '149':
            if (isset($defaultOptions["payment_creditcard"]) &&  $defaultOptions["payment_creditcard"] === "on") {
                $defaultOptions["payment_creditcard"] = "off";
                $defaultOptions["clover_payment_form"] = "on";
            }
        case '150':
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `allow_service_fee` INT(1) NULL DEFAULT 1");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_item` ADD `category_uuid` VARCHAR(45) NULL");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_item` ADD `custom_hours` VARCHAR(45) NULL");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_item` ADD `soo_name` VARCHAR(255) NULL after name");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_item` ADD `available` INT(1) NULL DEFAULT 1 after visible");
            update_option("moo_settings", $defaultOptions);
        case '151':
        case '152':
        case '153':
        case '154':
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_item` ADD `featured` INT(1) NULL DEFAULT 0 after available");
        case '155':
        case '156':
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_modifier` ADD `is_pre_selected` INT(1) NULL DEFAULT 0");
        case '157':
            // Get current charset
            $itemTableCharset = Moo_OnlineOrders_Helpers::getCharsetOfDbTable("moo_item");
            $itemUuidColumnCollate = Moo_OnlineOrders_Helpers::getCharsetOfDbColumn("moo_item","uuid");

            // Add new fields to tax rate
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_tax_rate` ADD `taxAmount` INT NULL");
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_tax_rate` ADD `taxType` VARCHAR(100) NULL");

            //Add a flag to the category table
            @$wpdb->query("ALTER TABLE `{$wpdb->prefix}moo_category` ADD `items_imported` INT(1) NOT NULL DEFAULT 0");

            //Create a new table for items_categories (many to many)
            @$wpdb->query("CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}moo_items_categories` (
                          `item_uuid` VARCHAR(45) NOT NULL,
                          `category_uuid` VARCHAR(100) NOT NULL,
                          `sort_order` INT NULL,
                          PRIMARY KEY (`item_uuid`, `category_uuid`),
                          INDEX `idx_item_has_categories` (`item_uuid` ASC) ,
                          INDEX `idx_category_has_items` (`category_uuid` ASC)  ,
                          CONSTRAINT `{$wpdb->prefix}fk_item_has_categories`
                            FOREIGN KEY (`item_uuid`)
                            REFERENCES `{$wpdb->prefix}moo_item` (`uuid`)
                            ON DELETE CASCADE
                            ON UPDATE CASCADE,
                          CONSTRAINT `{$wpdb->prefix}fk_category_has_items`
                            FOREIGN KEY (`category_uuid`)
                            REFERENCES `{$wpdb->prefix}moo_category` (`uuid`)
                            ON DELETE CASCADE
                            ON UPDATE CASCADE,
                            UNIQUE(`item_uuid`,`category_uuid`)) DEFAULT CHARACTER SET $itemTableCharset COLLATE $itemUuidColumnCollate");
            update_option('moo_onlineOrders_version', '157');
            break;
    }
}


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_moo_OnlineOrders()
{
    $plugin = new moo_OnlineOrders();
    $plugin->run();
}
run_moo_OnlineOrders();
