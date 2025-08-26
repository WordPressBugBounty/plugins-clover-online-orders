<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Moo_OnlineOrders_Helpers
{
    public static function getCharsetOfDbTable($table_name) {
        global $wpdb;
        $result = $wpdb->get_row("SHOW CREATE TABLE `{$wpdb->prefix}$table_name`",'ARRAY_A');
        if (isset($result["Create Table"])){
            preg_match('/CHARSET=([^\s]+)/', $result['Create Table'], $matches);
            if (isset($matches[0])){
                $matches = explode('=',$matches[0]);
                if (isset($matches[1])){
                    return $matches[1];
                }
            }
        }
        return $wpdb->charset;
    }
    public static function getEngineOfDbTable($table_name) {
        global $wpdb;
        $result = $wpdb->get_row("SHOW CREATE TABLE `{$wpdb->prefix}$table_name`",'ARRAY_A');
        if (isset($result["Create Table"])){
            preg_match('/ENGINE=([^\s]+)/', $result['Create Table'], $matches);
            if (isset($matches[0])){
                $matches = explode('=',$matches[0]);
                if (isset($matches[1])){
                    return $matches[1];
                }
            }
        }
        return $wpdb->engine;
    }
    public static function getCharsetOfDbColumn($table_name,$column_name) {
        global $wpdb;
        $results = $wpdb->get_results("SHOW FULL COLUMNS FROM `{$wpdb->prefix}$table_name`",'ARRAY_A');
        foreach ($results as $result) {
            if(isset($result['Field']) && $result['Field'] === $column_name ){
                if (isset($result['Collation'])){
                    return $result['Collation'];
                }
            }
        }
        return $wpdb->collate;
    }
    public static function upgradeDatabaseToVersion136($hideErrors = true)
    {
        global $wpdb;

        // Hide database errors if requested
        if ($hideErrors) {
            $wpdb->hide_errors();
            $wpdb->suppress_errors( true );
        }

        // Array of queries to execute
        $queries = [
            "ALTER TABLE `{$wpdb->prefix}moo_category` ADD `image_url` VARCHAR(255) NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_category` ADD `alternate_name` VARCHAR(100) NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_modifier` ADD `sort_order` INT NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_modifier` ADD `show_by_default` INT NOT NULL DEFAULT '1'",
            "ALTER TABLE `{$wpdb->prefix}moo_modifier_group` ADD `sort_order` INT NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `type` INT(1) NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_item` ADD `sort_order` INT NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `sort_order` INT NULL",
            "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}moo_item_order` (
            `_id` INT NOT NULL AUTO_INCREMENT,
            `item_uuid` VARCHAR(100) NOT NULL,
            `order_uuid` VARCHAR(100) NOT NULL,
            `quantity` VARCHAR(100) NOT NULL,
            `modifiers` TEXT NOT NULL,
            `special_ins` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`_id`, `item_uuid`, `order_uuid`)
        )",
            "ALTER TABLE `{$wpdb->prefix}moo_item` CHANGE `description` `description` TEXT",
            "ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `minAmount` VARCHAR(100) NULL DEFAULT '0'"
        ];

        // Loop through queries and execute them
        foreach ($queries as $query) {
            try {
                $wpdb->query($query); // Execute the query
            } catch (Exception $e) {
                // Log the error and continue to the next query
                //error_log("Failed to execute query: $query - Error: " . $e->getMessage());
            }
        }

    }
    public static function upgradeDatabaseToVersion150($hideErrors = true)
    {
        global $wpdb;

        // Hide database errors if requested
        if ($hideErrors) {
            $wpdb->hide_errors();
            $wpdb->suppress_errors( true );
        }

        // Array of queries to execute
        $queries = [
            "ALTER TABLE `{$wpdb->prefix}moo_category` ADD `description` TEXT NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_category` ADD `custom_hours` VARCHAR(100) NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_category` ADD `time_availability` VARCHAR(10) NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_item` CHANGE `description` `description` TEXT",
            "ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `minAmount` VARCHAR(100) NULL DEFAULT '0'",
            "ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `custom_hours` VARCHAR(100) NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `time_availability` VARCHAR(10) DEFAULT '1'",
            "ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `use_coupons` INT(1) NULL DEFAULT '1'",
            "ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `custom_message` VARCHAR(255) NULL DEFAULT 'Not available yet'",
            "ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `allow_service_fee` INT(1) NULL DEFAULT '1'",
            "ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `allow_sc_order` INT(1) NULL DEFAULT '1'",
            "ALTER TABLE `{$wpdb->prefix}moo_order_types` ADD `maxAmount` VARCHAR(100) NULL DEFAULT ''",
            "ALTER TABLE `{$wpdb->prefix}moo_item` ADD `category_uuid` VARCHAR(45) NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_item` ADD `custom_hours` VARCHAR(45) NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_item` ADD `soo_name` VARCHAR(255) NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_item` ADD `available` INT(1) NULL DEFAULT '1'"
        ];

        // Loop through queries and execute them
        foreach ($queries as $query) {
            try {
                $wpdb->query($query); // Execute the query
            } catch (Exception $e) {
                // Log the error and continue to the next query
                //error_log("Failed to execute query: $query - Error: " . $e->getMessage());
            }
        }


    }
    public static function upgradeDatabaseToVersion158($hideErrors = true)
    {
        global $wpdb;

        // Hide database errors if requested
        if ($hideErrors) {
            $wpdb->hide_errors();
            $wpdb->suppress_errors( true );
        }

        // Get current charset, engine, and column collation for table moo_item
        $itemTableCharset = self::getCharsetOfDbTable("moo_item");
        $itemTableEngine = self::getEngineOfDbTable("moo_item");
        $itemUuidColumnCollate = self::getCharsetOfDbColumn("moo_item", "uuid");

        // Array of queries to execute
        $queries = [
            "ALTER TABLE `{$wpdb->prefix}moo_item` ADD `featured` INT(1) NULL DEFAULT 0",
            "ALTER TABLE `{$wpdb->prefix}moo_modifier` ADD `is_pre_selected` INT(1) NULL DEFAULT 0",
            "ALTER TABLE `{$wpdb->prefix}moo_tax_rate` ADD `taxAmount` INT NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_tax_rate` ADD `taxType` VARCHAR(100) NULL",
            "ALTER TABLE `{$wpdb->prefix}moo_category` ADD `items_imported` INT(1) NOT NULL DEFAULT 0"
        ];

        // Execute all queries in the array
        foreach ($queries as $query) {
            try {
                $wpdb->query($query); // Execute the query
            } catch (Throwable $e) {
                // Log the error and continue to the next query
                error_log("Failed to execute query: $query - Error: " . $e->getMessage());
            }
        }

        // Create or update the `moo_items_categories` table
        $createTableQuery = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}moo_items_categories` (
        `item_uuid` VARCHAR(45) NOT NULL,
        `category_uuid` VARCHAR(100) NOT NULL,
        `sort_order` INT NULL,
        PRIMARY KEY (`item_uuid`, `category_uuid`),
        INDEX `idx_item_has_categories` (`item_uuid` ASC),
        INDEX `idx_category_has_items` (`category_uuid` ASC),
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
        UNIQUE(`item_uuid`, `category_uuid`)
    ) ENGINE=$itemTableEngine DEFAULT CHARACTER SET $itemTableCharset COLLATE $itemUuidColumnCollate";

        try {
            $res = $wpdb->query($createTableQuery); // Attempt to create the table

            // Fallback: Remove constraints and try again if creation fails
            if (!$res) {
                $fallbackQuery = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}moo_items_categories` (
                `item_uuid` VARCHAR(45) NOT NULL,
                `category_uuid` VARCHAR(100) NOT NULL,
                `sort_order` INT NULL,
                PRIMARY KEY (`item_uuid`, `category_uuid`),
                INDEX `idx_item_has_categories` (`item_uuid` ASC),
                INDEX `idx_category_has_items` (`category_uuid` ASC),
                UNIQUE(`item_uuid`, `category_uuid`)
            ) ENGINE=$itemTableEngine DEFAULT CHARACTER SET $itemTableCharset COLLATE $itemUuidColumnCollate";

                $wpdb->query($fallbackQuery); // Execute fallback query
            }
        } catch (Exception $e) {
            // Log the error if the table creation fails
            //error_log("Failed to create or update table `moo_items_categories` - Error: " . $e->getMessage());
        }


    }

    public static function getDefaultOptions() {
        return array(
            array("name"=>"api_key","value"=>""),
            array("name"=>"store_page","value"=>""),
            array("name"=>"checkout_page","value"=>""),
            array("name"=>"cart_page","value"=>""),
            array("name"=>"lat","value"=>""),
            array("name"=>"lng","value"=>""),
            array("name"=>"hours","value"=>"all"),
            array("name"=>"closing_msg","value"=>""),
            array("name"=>"merchant_email","value"=>""),
            array("name"=>"thanks_page","value"=>""),
            array("name"=>"my_account_page","value"=>""),
            array("name"=>"fb_appid","value"=>""),
            array("name"=>"use_coupons","value"=>"disabled"),
            array("name"=>"use_sms_verification","value"=>"enabled"),
            array("name"=>"custom_css","value"=>""),
            array("name"=>"custom_js","value"=>""),
            array("name"=>"custom_sa_content","value"=>""),
            array("name"=>"custom_sa_title","value"=>""),
            array("name"=>"custom_sa_onCheckoutPage","value"=>"off"),
            array("name"=>"copyrights","value"=>'Powered by <a href="https://wordpress.org/plugins/clover-online-orders/" target="_blank" title="Online Orders for Clover POS v 1.6.0">Smart Online Order</a>'),
            array("name"=>"default_style","value"=>"onePage"),
            array("name"=>"track_stock","value"=>""),
            array("name"=>"track_stock_hide_items","value"=>"off"),
            array("name"=>"checkout_login","value"=>"enabled"),
            array("name"=>"tips","value"=>"enabled"),
            array("name"=>"payment_creditcard","value"=>"off"),
            array("name"=>"clover_payment_form","value"=>"on"),
            array("name"=>"clover_googlepay","value"=>"on"),
            array("name"=>"clover_applepay","value"=>"on"),
            array("name"=>"clover_giftcards","value"=>"off"),
            array("name"=>"payment_cash","value"=>"off"),
            array("name"=>"payment_cash_delivery","value"=>"off"),
            array("name"=>"scp","value"=>"off"),
            array("name"=>"merchant_phone","value"=>""),
            array("name"=>"order_later","value"=>"on"),
            array("name"=>"order_later_mandatory","value"=>"off"),
            array("name"=>"order_later_days","value"=>"4"),
            array("name"=>"order_later_minutes","value"=>"20"),
            array("name"=>"order_later_days_delivery","value"=>"4"),
            array("name"=>"order_later_minutes_delivery","value"=>"60"),
            array("name"=>"order_later_asap_for_p","value"=>"off"),
            array("name"=>"order_later_asap_for_d","value"=>"off"),
            array("name"=>"free_delivery","value"=>""),
            array("name"=>"fixed_delivery","value"=>""),
            array("name"=>"other_zones_delivery","value"=>""),
            array("name"=>"delivery_fees_name","value"=>"Delivery Charge"),
            array("name"=>"delivery_errorMsg","value"=>"Sorry, zone not supported. We do not deliver to this address at this time"),
            array("name"=>"zones_json","value"=>""),
            array("name"=>"hide_menu","value"=>""),
            array("name"=>"hide_menu_w_closed","value"=>"off"),
            array("name"=>"accept_orders_w_closed","value"=>"on"),
            array("name"=>"show_categories_images","value"=>false),
            array("name"=>"save_cards","value"=>"disabled"),
            array("name"=>"save_cards_fees","value"=>"disabled"),
            array("name"=>"service_fees","value"=>"0.99"),
            array("name"=>"service_fees_name","value"=>"Service Charge"),
            array("name"=>"service_fees_type","value"=>"amount"),
            array("name"=>"use_special_instructions","value"=>"enabled"),
            array("name"=>"onePage_fontFamily","value"=>"Oswald,sans-serif"),
            array("name"=>"onePage_categoriesTopMargin","value"=>"0"),
            array("name"=>"onePage_width","value"=>"1024"),
            array("name"=>"onePage_categoriesFontColor","value"=>"#ffffff"),
            array("name"=>"onePage_categoriesBackgroundColor","value"=>"#282b2e"),
            array("name"=>"onePage_qtyWindow","value"=>"on"),
            array("name"=>"onePage_qtyWindowForModifiers","value"=>"on"),
            array("name"=>"onePage_backToTop","value"=>"off"),
            array("name"=>"onePage_show_more_button","value"=>"on"),
            array("name"=>"jTheme_width","value"=>"1024"),
            array("name"=>"jTheme_qtyWindow","value"=>"on"),
            array("name"=>"jTheme_qtyWindowForModifiers","value"=>"on"),
            array("name"=>"style1_width","value"=>"1024"),
            array("name"=>"style2_width","value"=>"1024"),
            array("name"=>"style3_width","value"=>"1024"),
            array("name"=>"mg_settings_displayInline","value"=>"disabled"),
            array("name"=>"mg_settings_qty_for_all","value"=>"enabled"),
            array("name"=>"mg_settings_qty_for_zeroPrice","value"=>"disabled"),
            array("name"=>"text_under_special_instructions","value"=>"*additional charges may apply and not all changes are possible"),
            array("name"=>"special_instructions_required","value"=>"no"),
            array("name"=>"use_couponsApp","value"=>"off"),
            array("name"=>"accept_orders","value"=>"enabled"),
            array("name"=>"onePage_askforspecialinstruction","value"=>"off"),
            array("name"=>"onePage_messageforspecialinstruction","value"=>"Type your instructions here, additional charges may apply and not all changes are possible"),
            array("name"=>"jTheme_askforspecialinstruction","value"=>"off"),
            array("name"=>"jTheme_messageforspecialinstruction","value"=>"Type your instructions here, additional charges may apply and not all changes are possible"),
            array("name"=>"style2_askforspecialinstruction","value"=>"off"),
            array("name"=>"style2_messageforspecialinstruction","value"=>"Type your instructions here, additional charges may apply and not all changes are possible"),
            array("name"=>"useAlternateNames","value"=>"enabled"),
            array("name"=>"hide_category_ifnotavailable","value"=>"off"),
            array("name"=>"show_order_number","value"=>"off"),
            array("name"=>"mg_settings_minimized","value"=>"off"),
            array("name"=>"tips_selection","value"=>"10,15,20,25"),
            array("name"=>"tips_default","value"=>""),
            array("name"=>"rollout_order_number","value"=>"on"),
            array("name"=>"rollout_order_number_max","value"=>"999"),
            array("name"=>"thanks_page_wp","value"=>""),
            array("name"=>"cdn_for_images","value"=>"off"),
            array("name"=>"cdn_url","value"=>""),
        );
    }

    public static function applyDefaultOptions($currentOptions) {
        $defaultOptions = self::getDefaultOptions();
        foreach ($defaultOptions as $default_option) {
            if(!isset($currentOptions[$default_option["name"]])) {
                $currentOptions[$default_option["name"]]=$default_option["value"];
            }
        }
        update_option('moo_settings', $currentOptions );
    }
    /**
     * @throws Exception
     */
    public static function uploadFileByUrl($image_url) {

        // If the function it's not available, require it.
        if ( ! function_exists( 'download_url' ) ) {
            // it allows us to use download_url() and wp_handle_sideload() functions
            require_once ABSPATH . '/wp-admin/includes/file.php';
        }

        // download to temp dir
        $temp_file = download_url( $image_url );

        if( is_wp_error( $temp_file ) ) {
            return false;
        }
        $filetype = wp_check_filetype( $temp_file );


        // move the temp file into the uploads directory
        $file = array(
            'name'     => basename( $image_url ),
            'type'     => $filetype['type'],
            'tmp_name' => $temp_file,
            'size'     => filesize( $temp_file ),
        );

        $sideload = wp_handle_sideload(
            $file,
            array(
                'test_form'   => false // no needs to check 'action' parameter
            )
        );

        if( ! empty( $sideload[ 'error' ] ) ) {
            // you may return an error message if you want
            throw new Exception($sideload[ 'error' ]);
        }

        // it is time to add our uploaded image into WordPress media library
        $attachment_id = wp_insert_attachment(
            array(
                'guid'           => $sideload[ 'url' ],
                'post_mime_type' => $sideload[ 'type' ],
                'post_title'     => basename( $sideload[ 'file' ] ),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ),
            $sideload[ 'file' ]
        );

        if( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            return false;
        }

        // update metadata, regenerate image sizes
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        wp_update_attachment_metadata(
            $attachment_id,
            wp_generate_attachment_metadata( $attachment_id, $sideload[ 'file' ] )
        );

        @unlink( $temp_file );

        return $sideload[ 'url' ];

    }

    public static function getDefaultI18N()
    {
        return array(
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
        );
    }
}
