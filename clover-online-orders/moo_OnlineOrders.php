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
 * Version:           1.5.8
 * Author:            Zaytech
 * Author URI:        https://www.zaytech.com
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
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
define('SOO_VERSION', "1.5.8-beta");
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
    if (isset($atts["cat_id"]) && !empty($atts["message"])) {
        if (isset($_GET["category"]) && $_GET["category"] == $atts["cat_id"]) {
            if (!empty($atts["css-class"])) {
                return "<div class='".esc_attr($atts["css-class"])."'>".esc_attr($atts["message"])."</div>";
            } else {
                return esc_attr($atts["message"]);
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
                 
if (get_option('moo_onlineOrders_version') != '158') {
    add_action('plugins_loaded', 'moo_onlineOrders_check_version');
}


/*
 * This function for updating the database structure when the version changed and updated it automatically
 *
 * @since v 1.1.2
 */
function moo_onlineOrders_check_version() {
    $version = get_option('moo_onlineOrders_version');
    if (empty($version)) {
        $version = 120;
    } else {
        $version = intval($version);
    }

    if (! class_exists( 'Moo_OnlineOrders_Helpers' ) ){
        require_once SOO_PLUGIN_PATH ."/includes/moo-OnlineOrders-helpers.php";
    }
    //Upgrade Database
    if ($version <= 136 ) {
        Moo_OnlineOrders_Helpers::upgradeDatabaseToVersion136();
    }
    if ( $version <= 150 ) {
        Moo_OnlineOrders_Helpers::upgradeDatabaseToVersion150();
    }
    if ( $version <= 158 ) {
        Moo_OnlineOrders_Helpers::upgradeDatabaseToVersion158();
    }

    //Apply default options
    $defaultOptions = get_option('moo_settings');
    Moo_OnlineOrders_Helpers::applyDefaultOptions($defaultOptions);
    update_option("moo_settings", $defaultOptions);
    //Upgrade version
    update_option('moo_onlineOrders_version', '158');
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
