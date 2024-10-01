<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 * @package    zaytech_OnlineOrders
 * @subpackage zaytech_OnlineOrders/includes
 * @author     Mohammed EL BANYAOUI <elbanyaoui@hotmail.com>
 */
class moo_OnlineOrders {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      moo_OnlineOrders_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Api instance to call our API
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $api;
	/**
	 * Model  instance to mange the database
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	 protected $model;

	 /**
	 * Settings
	 *
	 * @since    1.3.2
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	 protected $settings;
    /**
     * The session instance that's responsible for maintaining session data
     *
     * @since    1.3.2
     * @access   protected
     * @var      MOO_SESSION    $session   Maintains the session.
     */
    protected $session;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'moo_OnlineOrders';

		if(defined('SOO_VERSION')){
            $this->version = SOO_VERSION;
        }
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - moo_OnlineOrders_Loader. Orchestrates the hooks of the plugin.
	 * - moo_OnlineOrders_i18n. Defines internationalization functionality.
	 * - moo_OnlineOrders_Admin. Defines all hooks for the admin area.
	 * - moo_OnlineOrders_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/moo-OnlineOrders-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/moo-OnlineOrders-i18n.php';

		/**
		 * The class responsible for defining session functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/moo-OnlineOrders-session.php';

        /**
         * The class responsible for defining all actions that need to call our servers
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/moo-OnlineOrders-sooapi.php';

        /**
         * The class responsible for defining all actions that occur in the databse
         * side of the site.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'models/moo-OnlineOrders-Model.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/moo-OnlineOrders-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/moo-OnlineOrders-public.php';

        $this->loader = new Moo_OnlineOrders_Loader();
        $this->api  = new Moo_OnlineOrders_SooApi();
        $this->model = new Moo_OnlineOrders_Model();
        $this->session = MOO_SESSION::instance();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the moo_OnlineOrders_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Moo_OnlineOrders_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}
	/**
	 * Define the settings
	 *
	 * Uses the moo_OnlineOrders_settings class in order to get the settings from teh database
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_plugin_settings() {


	}

	/**
	 * Register all the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new moo_OnlineOrders_Admin( $this->get_plugin_name(), $this->get_version(), $this->getApi(),$this->getModel() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_mysettings' );
		$this->loader->add_action( 'admin_bar_menu', $plugin_admin, 'toolbar_link_to_settings',999 );
		$this->loader->add_action( 'wpmu_new_blog', $plugin_admin, 'activate_plugin_in_network',10,6 );
		$this->loader->add_action( 'delete_blog', $plugin_admin, 'delete_plugin_in_network',10,1 );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'displayUpdateNotice' );
		//widgets
		//$this->loader->add_action( 'wp_dashboard_setup', $plugin_admin, 'dashboard_widgets' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Moo_OnlineOrders_Public( $this->get_plugin_name(), $this->get_version(),$this->getApi(),$this->getModel() );
        // Set session
        $this->loader->add_action( 'init', $this->session, 'myStartSession',1);

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        //allow redirection, even if my plugin starts to send output to the browser
        $this->loader->add_action( 'init', $plugin_public, 'do_output_buffer');

        //init cron jobs
        $this->loader->add_action( 'init', $plugin_public, 'moo_register_daily_jwtTokenUpdate');

        // Import inventory when hook fired
        $this->loader->add_action( 'smart_online_order_import_inventory', $plugin_public, 'moo_ImportInventory');

        // Update  jwt token
        $this->loader->add_action( 'smart_online_order_update_jwttoken', $plugin_public, 'moo_updateJwtToken');

        //Delete Item form Cart
        $this->loader->add_action( 'wp_ajax_moo_deleteItemFromcart', $plugin_public, 'moo_deleteItemFromcart');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_deleteItemFromcart', $plugin_public, 'moo_deleteItemFromcart');

        //Empty Cart
        $this->loader->add_action( 'wp_ajax_moo_emptycart', $plugin_public, 'moo_emptycart');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_emptycart', $plugin_public, 'moo_emptycart');

        //Get the total of the cart
        $this->loader->add_action( 'wp_ajax_moo_cart_getTotal', $plugin_public, 'mooGetCartTotal');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_cart_getTotal', $plugin_public, 'mooGetCartTotal');

        //Get the total of one line in the cart
        $this->loader->add_action( 'wp_ajax_moo_cart_getItemTotal', $plugin_public, 'moo_cart_getItemTotal');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_cart_getItemTotal', $plugin_public, 'moo_cart_getItemTotal');

        //MODIFIERS : get limit for an modifier
        $this->loader->add_action( 'wp_ajax_moo_modifiergroup_getlimits', $plugin_public, 'moo_modifiergroup_getlimits');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_modifiergroup_getlimits', $plugin_public, 'moo_modifiergroup_getlimits');

        $this->loader->add_action( 'wp_ajax_moo_check_item_modifiers', $plugin_public, 'moo_checkItemModifiers');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_check_item_modifiers', $plugin_public, 'moo_checkItemModifiers');

        //MODIFIERS : delete modifier from the Cart
        $this->loader->add_action( 'wp_ajax_moo_cart_DeleteItemModifier', $plugin_public, 'moo_cart_DeleteItemModifier');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_cart_DeleteItemModifier', $plugin_public, 'moo_cart_DeleteItemModifier');

        //Checkout
        $this->loader->add_action( 'wp_ajax_moo_checkout', $plugin_public, 'moo_checkout');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_checkout', $plugin_public, 'moo_checkout');

        //Checkout : Get orders Types
        $this->loader->add_action( 'wp_ajax_moo_getodertybes', $plugin_public, 'moo_GetOrderTypes');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_getodertybes', $plugin_public, 'moo_GetOrderTypes');

		//Checkout : Sending sms and verify the code
        $this->loader->add_action( 'wp_ajax_moo_send_sms', $plugin_public, 'moo_SendVerifSMS');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_send_sms', $plugin_public, 'moo_SendVerifSMS');
		$this->loader->add_action( 'wp_ajax_moo_check_verification_code', $plugin_public, 'moo_CheckVerificationCode');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_check_verification_code', $plugin_public, 'moo_CheckVerificationCode');


        /*
         * Importing and syncing the inventory
         */

        // Import Categories
        $this->loader->add_action( 'wp_ajax_moo_import_categories', $plugin_public, 'moo_ImportCategories');
        // Import Labels
        $this->loader->add_action( 'wp_ajax_moo_import_labels', $plugin_public, 'moo_ImportLabels');
        // Import Taxes
        $this->loader->add_action( 'wp_ajax_moo_import_taxes', $plugin_public, 'moo_ImportTaxes');
        // Import Items
        $this->loader->add_action( 'wp_ajax_moo_import_items', $plugin_public, 'moo_ImportItems');
        $this->loader->add_action( 'wp_ajax_moo_import_items_v2', $plugin_public, 'moo_ImportItemsV2');
        // Import OrderTypes
        $this->loader->add_action( 'wp_ajax_moo_import_ordertypes', $plugin_public, 'moo_ImportOrderTypes');


		/* Sync manually */
		$this->loader->add_action( 'wp_ajax_moo_update_items', $plugin_public, 'moo_UpdateItems');
		$this->loader->add_action( 'wp_ajax_moo_update_categories', $plugin_public, 'moo_UpdateCategories');
		$this->loader->add_action( 'wp_ajax_moo_update_modifiers_groups', $plugin_public, 'moo_UpdateModifiersG');
		$this->loader->add_action( 'wp_ajax_moo_update_modifiers', $plugin_public, 'moo_UpdateModifiers');
		$this->loader->add_action( 'wp_ajax_moo_update_order_types', $plugin_public, 'moo_UpdateOrderTypes');
		$this->loader->add_action( 'wp_ajax_moo_update_taxes', $plugin_public, 'moo_UpdateTaxes');

        //Get Statistics
        $this->loader->add_action( 'wp_ajax_moo_get_stats', $plugin_public, 'moo_GetStats');

        //Get a list of saved OrderTypes
        $this->loader->add_action( 'wp_ajax_moo_getAllOrderTypes', $plugin_public, 'moo_getAllOrderTypes');

		//Add a new Order type
		$this->loader->add_action( 'wp_ajax_moo_add_ot', $plugin_public, 'moo_AddOrderType');

		//Delete a Order type
		$this->loader->add_action( 'wp_ajax_moo_delete_ot', $plugin_public, 'moo_DeleteOrderType');

		//Reorder Order Types
		$this->loader->add_action( 'wp_ajax_moo_reorder_ordertypes', $plugin_public, 'moo_ReorderOrderTypes');

		//Update Order Type
		$this->loader->add_action( 'wp_ajax_moo_update_ordertype', $plugin_public, 'moo_UpdateOrdertype');

        //Show or hide images of categories
		$this->loader->add_action( 'wp_ajax_moo_update_category_images_status', $plugin_public, 'moo_UpdateCategoryImagesStatus');

		/* Manage modifiers */
        //Change modifier Group name
		$this->loader->add_action( 'wp_ajax_moo_change_modifiergroup_name', $plugin_public, 'moo_ChangeModifierGroupName');

        //Change modifier name
        $this->loader->add_action( 'wp_ajax_moo_change_modifier_name', $plugin_public, 'moo_ChangeModifierName');

        //update modifier group status
		$this->loader->add_action( 'wp_ajax_moo_update_modifiergroup_status', $plugin_public, 'moo_UpdateModifierGroupStatus');

        //update modifier status
        $this->loader->add_action( 'wp_ajax_moo_update_modifier_status', $plugin_public, 'moo_UpdateModifierStatus');

        //update category status
		$this->loader->add_action( 'wp_ajax_moo_update_category_status', $plugin_public, 'moo_UpdateCategoryStatus');

        // Send the feedback
        $this->loader->add_action( 'wp_ajax_moo_send_feedback', $plugin_public, 'moo_SendFeedBack');


        // Update the quantity
        $this->loader->add_action( 'wp_ajax_moo_update_qte', $plugin_public, 'moo_UpdateQuantity');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_update_qte', $plugin_public, 'moo_UpdateQuantity');

		// Update the Special_ins for one Item
        $this->loader->add_action( 'wp_ajax_moo_update_special_ins', $plugin_public, 'moo_UpdateSpecial_ins');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_update_special_ins', $plugin_public, 'moo_UpdateSpecial_ins');

		//get the current quantity and the currant Special instruction of an item i the cart
		$this->loader->add_action( 'wp_ajax_moo_get_item_options', $plugin_public, 'moo_GetitemInCartOptions');
		$this->loader->add_action( 'wp_ajax_nopriv_moo_get_item_options', $plugin_public, 'moo_GetitemInCartOptions');

		//verify if the store is open, according to business hours configured in Clover
		$this->loader->add_action( 'wp_ajax_moo_store_isopen', $plugin_public, 'moo_StoreIsOpen');
		$this->loader->add_action( 'wp_ajax_nopriv_moo_store_isopen', $plugin_public, 'moo_StoreIsOpen');

        /*
         * category visibility
         */
        $this->loader->add_action( 'wp_ajax_moo_update_visiblite_category', $plugin_public, 'visibility_category');

        /*
         * category save image
         */
        $this->loader->add_action( 'wp_ajax_moo_save_category_image', $plugin_public, 'save_image_category');

        /*
         * category new order
         */
        $this->loader->add_action( 'wp_ajax_moo_new_order_categories', $plugin_public, 'new_order_categories');

        /*
         * delete image category
         */
        $this->loader->add_action( 'wp_ajax_moo_delete_img_category', $plugin_public, 'delete_img_category');

        /*
         * change name category
         */
        $this->loader->add_action( 'wp_ajax_moo_change_name_category', $plugin_public, 'change_name_category');

        // New order Modifiers Group
        $this->loader->add_action( 'wp_ajax_moo_new_order_group_modifier', $plugin_public, 'moo_NewOrderGroupModifier');

        // New order Modifiers Group
        $this->loader->add_action( 'wp_ajax_moo_new_order_modifier', $plugin_public, 'moo_NewOrderModifier');

        /*
         * Reorder items
         */
        $this->loader->add_action( 'wp_ajax_moo_reorder_items', $plugin_public, 'moo_reorder_items');

        /*
         * Item's images
         */

		$this->loader->add_action( 'wp_ajax_moo_get_items_with_images', $plugin_public, 'moo_getItemWithImages');
		$this->loader->add_action( 'wp_ajax_moo_save_items_with_images', $plugin_public, 'moo_saveItemWithImages');
		$this->loader->add_action( 'wp_ajax_moo_save_items_description', $plugin_public, 'moo_saveItemDescription');

        /*
         * Customer login & sign-up
         */

        $this->loader->add_action( 'wp_ajax_moo_customer_login', $plugin_public, 'moo_CustomerLogin');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_customer_login', $plugin_public, 'moo_CustomerLogin');

        $this->loader->add_action( 'wp_ajax_moo_customer_fblogin', $plugin_public, 'moo_CustomerFbLogin');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_customer_fblogin', $plugin_public, 'moo_CustomerFbLogin');

        $this->loader->add_action( 'wp_ajax_moo_customer_signup', $plugin_public, 'moo_CustomerSignup');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_customer_signup', $plugin_public, 'moo_CustomerSignup');

        $this->loader->add_action( 'wp_ajax_moo_customer_resetpassword', $plugin_public, 'moo_ResetPassword');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_customer_resetpassword', $plugin_public, 'moo_ResetPassword');

        $this->loader->add_action( 'wp_ajax_moo_customer_getAddresses', $plugin_public, 'moo_GetAddresses');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_customer_getAddresses', $plugin_public, 'moo_GetAddresses');

        $this->loader->add_action( 'wp_ajax_moo_customer_addAddress', $plugin_public, 'moo_AddAddress');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_customer_addAddress', $plugin_public, 'moo_AddAddress');

        $this->loader->add_action( 'wp_ajax_moo_customer_deleteAddresses', $plugin_public, 'moo_DeleteAddresses');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_customer_deleteAddresses', $plugin_public, 'moo_DeleteAddresses');

        $this->loader->add_action( 'wp_ajax_moo_customer_setDefaultAddresses', $plugin_public, 'moo_setDefaultAddresses');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_customer_setDefaultAddresses', $plugin_public, 'moo_setDefaultAddresses');

        $this->loader->add_action( 'wp_ajax_moo_customer_updateAddresses', $plugin_public, 'moo_updateAddresses');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_customer_updateAddresses', $plugin_public, 'moo_updateAddresses');

        $this->loader->add_action( 'wp_ajax_moo_customer_deleteCreditCard', $plugin_public, 'moo_DeleteCreditCard');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_customer_deleteCreditCard', $plugin_public, 'moo_DeleteCreditCard');
		/*
        * Coupons apply on checkout page
        */

        $this->loader->add_action( 'wp_ajax_moo_coupon_apply', $plugin_public, 'moo_CouponApply');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_coupon_apply', $plugin_public, 'moo_CouponApply');

        $this->loader->add_action( 'wp_ajax_moo_coupon_remove', $plugin_public, 'moo_CouponRemove');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_coupon_remove', $plugin_public, 'moo_CouponRemove');

        /*
         *  Get Opening hours
         */
        $this->loader->add_action( 'wp_ajax_moo_opening_hours', $plugin_public, 'moo_getOpeningHours');
        $this->loader->add_action( 'wp_ajax_nopriv_moo_opening_hours', $plugin_public, 'moo_getOpeningHours');

		/**
         * Plugin upgrade wp_upe_upgrade_completed
         */
        $this->loader->add_action( 'wp_upe_upgrade_completed', $plugin_public, 'moo_pluginUpdated',1,2);

        //Filters

        $this->loader->add_filter("moo_filter_order_creation_response",$plugin_public,"moo_localize_payment_errors",2,1);
        $this->loader->add_filter("moo_filter_order_creation_response",$plugin_public,"empty_pakms_when_invalid",1,1);
        $this->loader->add_filter("moo_filter_business_settings_response",$plugin_public,"empty_pubkey_when_invalid",1,1);
	}

	/**
	 * Run the loader to execute all the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
        define('SOO_I18N_DEFAULT', array(
            'loading' => esc_html__( 'Loading, please wait ...', 'moo_OnlineOrders' ),
            'loadingOptions' => esc_html__( 'Loading Options', 'moo_OnlineOrders' ),
            'loadingCart' => esc_html__( 'Loading Your cart', 'moo_OnlineOrders' ),
            'chooseACategory' => esc_html__( 'Choose a Category', 'moo_OnlineOrders' ),
            'addToCart' => esc_html__( 'Add to cart', 'moo_OnlineOrders' ),
            'chooseOptionsAndQty' => esc_html__( 'Choose Options & Qty', 'moo_OnlineOrders' ),
            'chooseOptions' => esc_html__( 'Choose Options', 'moo_OnlineOrders' ),
            'outOfStock' => esc_html__( 'Out Of Stock', 'moo_OnlineOrders' ),
            'notAvailableYet' => esc_html__( 'Not Available Yet', 'moo_OnlineOrders' ),
            'viewCart' => esc_html__( 'View Cart', 'moo_OnlineOrders' ),
            'cartEmpty' => esc_html__( 'Your cart is empty', 'moo_OnlineOrders' ),
            'close' => esc_html__( 'Close', 'moo_OnlineOrders' ),
            'ok' => esc_html__( 'Ok', 'moo_OnlineOrders' ),
            'checkout' => esc_html__( 'Checkout', 'moo_OnlineOrders' ),
            'item' => esc_html__( 'Item', 'moo_OnlineOrders' ),
            'qty' => esc_html__( 'Qty', 'moo_OnlineOrders' ),
            'subtotal' => esc_html__( 'Sub-Total', 'moo_OnlineOrders' ),
            'tax' => esc_html__( 'Tax', 'moo_OnlineOrders' ),
            'total' => esc_html__( 'Total', 'moo_OnlineOrders' ),
            'edit' => esc_html__( 'Edit', 'moo_OnlineOrders' ),
            'addedToCart' => esc_html__( 'Added to cart', 'moo_OnlineOrders' ),
            'notAddedToCart' => esc_html__( 'Item not added, try again', 'moo_OnlineOrders' ),
            'cancel' => esc_html__( 'Cancel', 'moo_OnlineOrders' ),
            'quantityCanBeUpdated' => esc_html__( 'Quantity can be updated during checkout', 'moo_OnlineOrders' ),
            'addingTheItems' => esc_html__( 'Adding the items to your cart', 'moo_OnlineOrders' ),
            'showMore' => esc_html__( 'Show More', 'moo_OnlineOrders' ),
            'items' => esc_html__( 'Items', 'moo_OnlineOrders' ),
            'noCategory' => esc_html__( 'There is no category available right now please try again later', 'moo_OnlineOrders' ),
            'noItemsInCategory' => esc_html__( 'There is no items available right now in this category please try again later', 'moo_OnlineOrders' ),
            'customQuantity' => esc_html__( 'Custom Quantity', 'moo_OnlineOrders' ),
            'selectTheQuantity' => esc_html__( 'Select the quantity', 'moo_OnlineOrders' ),
            'enterTheQuantity' => esc_html__( 'Enter the quantity', 'moo_OnlineOrders' ),
            'writeNumber' => esc_html__( 'You need to write a number', 'moo_OnlineOrders' ),
            'checkInternetConnection' => esc_html__( 'Check your internet connection or contact us', 'moo_OnlineOrders' ),
            'cannotLoadItemOptions' => esc_html__( 'We cannot Load the options for this item, please refresh the page or contact us', 'moo_OnlineOrders' ),
            'cannotLoadCart' => esc_html__( 'Error in loading your cart, please refresh the page', 'moo_OnlineOrders' ),
            'confirmItemDeletion' => esc_html__( 'Are you sure you want to delete this item', 'moo_OnlineOrders' ),
            'yesDelete' => esc_html__( 'Yes, delete it!', 'moo_OnlineOrders' ),
            'noThanks' => esc_html__( 'No Thanks', 'moo_OnlineOrders' ),
            'noCancel' => esc_html__( 'No Cancel', 'moo_OnlineOrders' ),
            'deleted' => esc_html__( 'Deleted!', 'moo_OnlineOrders' ),
            'canceled' => esc_html__( 'Canceled!', 'moo_OnlineOrders' ),
            'cannotDeleteItem' => esc_html__( 'Item not deleted, try again', 'moo_OnlineOrders' ),
            'tryAgain' => esc_html__( 'Try again', 'moo_OnlineOrders' ),
            'add' => esc_html__( 'Add', 'moo_OnlineOrders' ),
            'added' => esc_html__( 'Added', 'moo_OnlineOrders' ),
            'notAdded' => esc_html__( 'Not Added', 'moo_OnlineOrders' ),
            'update' => esc_html__( 'Update', 'moo_OnlineOrders' ),
            'updated' => esc_html__( 'Updated', 'moo_OnlineOrders' ),
            'notUpdated' => esc_html__( 'Not Updated', 'moo_OnlineOrders' ),
            'addSpecialInstructions' => esc_html__( 'Add Special Instructions', 'moo_OnlineOrders' ),
            'updateSpecialInstructions' => esc_html__( 'Update Your Special Instructions', 'moo_OnlineOrders' ),
            'specialInstructionsNotAdded' => esc_html__( 'Special instructions not submitted try again', 'moo_OnlineOrders' ),
            'textTooLongMax250' => esc_html__( 'Text too long, You cannot add more than 250 chars', 'moo_OnlineOrders' ),
            'enterYourName' => esc_html__( 'Please enter your name', 'moo_OnlineOrders' ),
            'enterYourPassword' => esc_html__( 'Please enter your password', 'moo_OnlineOrders' ),
            'enterYourEmail' => esc_html__( 'Please enter a valid email', 'moo_OnlineOrders' ),
            'enterYourEmailReason' => esc_html__( 'We need a valid email to contact you and send you the receipt', 'moo_OnlineOrders' ),
            'enterYourPhone' => esc_html__( 'Please enter your phone', 'moo_OnlineOrders' ),
            'enterYourPhoneReason' => esc_html__( 'We need your phone to contact you if we have any questions about your order', 'moo_OnlineOrders' ),
            'chooseOrderingMethod' => esc_html__( 'Please choose the ordering method', 'moo_OnlineOrders' ),
            'chooseOrderingMethodReason' => esc_html__( 'How you want your order to be served ?', 'moo_OnlineOrders' ),
            'YouDidNotMeetMinimum' => esc_html__( 'You did not meet the minimum purchase requirement', 'moo_OnlineOrders' ),
            'orderingMethodSubtotalGreaterThan' => esc_html__( 'this ordering method requires a subtotal greater than $', 'moo_OnlineOrders' ),
            'orderingMethodSubtotalLessThan' => esc_html__( 'this ordering method requires a subtotal less than $', 'moo_OnlineOrders' ),
            'continueShopping' => esc_html__( 'Continue shopping', 'moo_OnlineOrders' ),
            'continueToCheckout' => esc_html__( 'Continue to Checkout', 'moo_OnlineOrders' ),
            'updateCart' => esc_html__( 'Update Cart', 'moo_OnlineOrders' ),
            'reachedMaximumPurchaseAmount' => esc_html__( 'You reached the maximum purchase amount', 'moo_OnlineOrders' ),
            'verifyYourAddress' => esc_html__( 'Please verify your address', 'moo_OnlineOrders' ),
            'addressNotFound' => esc_html__( "We can't found this address on the map, please choose an other address", 'moo_OnlineOrders' ),
            'addDeliveryAddress' => esc_html__( "Please add the delivery address", 'moo_OnlineOrders' ),
            'addDeliveryAddressReason' => esc_html__( "You have chosen a delivery method, we need your address", 'moo_OnlineOrders' ),
            'chooseTime' => esc_html__( "Please choose a time", 'moo_OnlineOrders' ),
            'choosePaymentMethod' => esc_html__( "Please choose your payment method", 'moo_OnlineOrders' ),
            'verifyYourPhone' => esc_html__( "Please verify your phone", 'moo_OnlineOrders' ),
            'verifyYourPhoneReason' => esc_html__( "When you choose the cash payment you must verify your phone", 'moo_OnlineOrders' ),
            'verifyYourCreditCard' => esc_html__( "Please verify your card information", 'moo_OnlineOrders' ),
            'SpecialInstructionsRequired' => esc_html__( "Special instructions are required", 'moo_OnlineOrders' ),
            'minimumForDeliveryZone' => esc_html__( "The minimum order total for this selected zone is $", 'moo_OnlineOrders' ),
            'spend' => esc_html__( "Spend $", 'moo_OnlineOrders' ),
            'toGetFreeDelivery' => esc_html__( "to get free delivery", 'moo_OnlineOrders' ),
            'deliveryZoneNotSupported' => esc_html__( "Sorry, zone not supported. We do not deliver to this address at this time", 'moo_OnlineOrders' ),
            'deliveryAmount' => esc_html__( "Delivery amount", 'moo_OnlineOrders' ),
            'deliveryTo' => esc_html__( "Delivery to", 'moo_OnlineOrders' ),
            'editAddress' => esc_html__( "Edit address", 'moo_OnlineOrders' ),
            'addEditAddress' => esc_html__( "Add/Edit address", 'moo_OnlineOrders' ),
            'noAddressSelected' => esc_html__( "No address selected", 'moo_OnlineOrders' ),
            'CardNumberRequired' => esc_html__( "Card Number is required", 'moo_OnlineOrders' ),
            'CardDateRequired' => esc_html__( "Card Date is required", 'moo_OnlineOrders' ),
            'CardCVVRequired' => esc_html__( "Card CVV is required", 'moo_OnlineOrders' ),
            'CardStreetAddressRequired' => esc_html__( "Street Address is required", 'moo_OnlineOrders' ),
            'CardZipRequired' => esc_html__( "Zip Code is required", 'moo_OnlineOrders' ),
            'receivedDiscountUSD' => esc_html__( "Success! You have received a discount of $", 'moo_OnlineOrders' ),
            'receivedDiscountPercent' => esc_html__( "Success! You have received a discount of", 'moo_OnlineOrders' ),
            'thereIsACoupon' => esc_html__( "There is a coupon that can be applied to this order", 'moo_OnlineOrders' ),
            'verifyConnection' => esc_html__( "Verify your connection and try again", 'moo_OnlineOrders' ),
            'error' => esc_html__( "Error", 'moo_OnlineOrders' ),
            'payUponDelivery' => esc_html__( "Pay upon Delivery", 'moo_OnlineOrders' ),
            'payAtlocation' => esc_html__( "Pay at location", 'moo_OnlineOrders' ),
            'sendingVerificationCode' => esc_html__( "Sending the verification code please wait ..", 'moo_OnlineOrders' ),
            'anErrorOccurred' => esc_html__( "An error has occurred please try again or contact us", 'moo_OnlineOrders' ),
            'codeInvalid' => esc_html__( "Code invalid", 'moo_OnlineOrders' ),
            'codeInvalidDetails' => esc_html__( "this code is invalid please try again", 'moo_OnlineOrders' ),
            'phoneVerified' => esc_html__( "Phone verified", 'moo_OnlineOrders' ),
            'phoneVerifiedDetails' => esc_html__( "Please have your payment ready when picking up from the store and don't forget to finalize your order below", 'moo_OnlineOrders' ),
            'thanksForOrder' => esc_html__( "Thank you for your order", 'moo_OnlineOrders' ),
            'orderBeingPrepared' => esc_html__( "Your order is being prepared", 'moo_OnlineOrders' ),
            'seeReceipt' => esc_html__( "You can see your receipt", 'moo_OnlineOrders' ),
            'here' => esc_html__( "here", 'moo_OnlineOrders' ),
            'ourAddress' => esc_html__( "Our Address", 'moo_OnlineOrders' ),
            'cannotSendEntireOrder' => esc_html__( "We weren't able to send the entire order to the store, please try again or contact us", 'moo_OnlineOrders' ),
            'loadingAddresses' => esc_html__( "Loading your addresses", 'moo_OnlineOrders' ),
            'useAddress' => esc_html__( "USE THIS ADDRESS", 'moo_OnlineOrders' ),
            'sessionExpired' => esc_html__( "Your session is expired", 'moo_OnlineOrders' ),
            'login' => esc_html__( "Log In", 'moo_OnlineOrders' ),
            'register' => esc_html__( "Register", 'moo_OnlineOrders' ),
            'reset' => esc_html__( "Reset", 'moo_OnlineOrders' ),
            'invalidEmailOrPassword' => esc_html__( "Invalid Email or Password", 'moo_OnlineOrders' ),
            'invalidEmail' => esc_html__( "Invalid Email", 'moo_OnlineOrders' ),
            'useForgetPassword' => esc_html__( "Please click on forgot password or Please register as new user.", 'moo_OnlineOrders' ),
            'facebookEmailNotFound' => esc_html__( "You don't have an email on your Facebook account", 'moo_OnlineOrders' ),
            'cannotResetPassword' => esc_html__( "Could not reset your password", 'moo_OnlineOrders' ),
            'resetPasswordEmailSent' => esc_html__( "If the e-mail you specified exists in our system, then you will receive an e-mail shortly to reset your password.", 'moo_OnlineOrders' ),
            'enterYourAddress' => esc_html__( "Please enter your address", 'moo_OnlineOrders' ),
            'enterYourCity' => esc_html__( "Please enter your city", 'moo_OnlineOrders' ),
            'addressMissing' => esc_html__( "Address missing", 'moo_OnlineOrders' ),
            'cityMissing' => esc_html__( "City missing", 'moo_OnlineOrders' ),
            'cannotLocateAddress' => esc_html__( "We weren't able to locate this address,try again", 'moo_OnlineOrders' ),
            'confirmAddressOnMap' => esc_html__( "Please confirm your address on the map", 'moo_OnlineOrders' ),
            'confirmAddressOnMapDetails' => esc_html__( "By confirming  your address on the map you will help the driver to deliver your order faster, and you will help us to calculate your delivery fee better", 'moo_OnlineOrders' ),
            'confirm' => esc_html__( "Confirm", 'moo_OnlineOrders' ),
            'confirmAndAddAddress' => esc_html__( "Confirm and add address", 'moo_OnlineOrders' ),
            'addressNotAdded' => esc_html__( "Address not added to your account", 'moo_OnlineOrders' ),
            'AreYouSure' => esc_html__( "Are you sure?", 'moo_OnlineOrders' ),
            'cannotRecoverAddress' => esc_html__( "You will not be able to recover this address", 'moo_OnlineOrders' ),
            'enterCouponCode' => esc_html__( "Please enter your coupon code", 'moo_OnlineOrders' ),
            'checkingCouponCode' => esc_html__( "Checking your coupon...", 'moo_OnlineOrders' ),
            'couponApplied' => esc_html__( "Coupon applied", 'moo_OnlineOrders' ),
            'removingCoupon' => esc_html__( "Removing your coupon....", 'moo_OnlineOrders' ),
            'success' => esc_html__( "Success", 'moo_OnlineOrders' ),
            'optionRequired' => esc_html__( " (required) ", 'moo_OnlineOrders' ),
            'mustChoose' => esc_html__( "Must choose", 'moo_OnlineOrders' ),
            'options' => esc_html__( "options", 'moo_OnlineOrders' ),
            'mustChooseBetween' => esc_html__( "Must choose between", 'moo_OnlineOrders' ),
            'mustChooseAtLeastOneOption' => esc_html__( "Must choose at least 1 option", 'moo_OnlineOrders' ),
            'mustChooseAtLeast' => esc_html__( "Must choose at least", 'moo_OnlineOrders' ),
            'selectUpTo' => esc_html__( "Select up to", 'moo_OnlineOrders' ),
            'selectOneOption' => esc_html__( "Select one option", 'moo_OnlineOrders' ),
            'and' => esc_html__( " & ", 'moo_OnlineOrders' ),
            'chooseItemOptions' => esc_html__( "Choose Item Options", 'moo_OnlineOrders' ),
            'youDidNotSelectedRequiredOptions' => esc_html__( "You did not select all of the required options", 'moo_OnlineOrders' ),
            'checkAgain' => esc_html__( "Please check again", 'moo_OnlineOrders' ),
        ));
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Moo_OnlineOrders_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

    /**
     * @return string
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }


}
