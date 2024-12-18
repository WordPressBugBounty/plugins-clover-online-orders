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
            array("name"=>"copyrights","value"=>'Powered by <a href="https://wordpress.org/plugins/clover-online-orders/" target="_blank" title="Online Orders for Clover POS v 1.5.8">Smart Online Order</a>'),
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
}
