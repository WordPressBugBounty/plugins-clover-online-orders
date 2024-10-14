<?php
/**
 * Created by Mohammed EL BANYAOUI.
 * Sync route to handle all requests to sync the inventory with Clover
 * User: Smart MerchantApps
 * Date: 3/5/2019
 * Time: 12:23 PM
 */
require_once "BaseRoute.php";

class DashboardRoutes extends BaseRoute {
    /**
     * The model of this plugin (For all interaction with the DATABASE ).
     * @access   private
     * @var      Moo_OnlineOrders_Model    Object of functions that call the Database pr the API.
     */
    private $model;

    /**
     * The model of this plugin (For all interaction with the DATABASE ).
     * @access   private
     * @var Moo_OnlineOrders_SooApi
     */
    private $api;





    /**
     * SyncRoutes constructor.
     *
     */
    public function __construct($model, $api){

        parent::__construct();

        $this->model          = $model;
        $this->api            = $api;

    }


    // Register our routes.
    public function register_routes(){
        // Update category name and description
        register_rest_route($this->namespace, '/dash/category/(?P<cat_id>[a-zA-Z0-9-]+)', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashUpdateCategory'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // Update time for category
        register_rest_route($this->namespace, '/dash/category/(?P<cat_id>[a-zA-Z0-9-]+)/time', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashUpdateCategoryTime'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        //get category
        register_rest_route($this->namespace, '/dash/category/(?P<cat_id>[a-zA-Z0-9-]+)', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'dashGetCategory'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));

        // get all categories
        register_rest_route($this->namespace, '/dash/categories', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'dashGetCategories'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));

        // get categories hours
        register_rest_route($this->namespace, '/dash/categories_hours', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'dashGetCategoriesHours'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // get all ordertypes hours
        register_rest_route($this->namespace, '/dash/ordertypes_hours', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'dashGetOrderTypesHours'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // update api key
        register_rest_route($this->namespace, '/dash/update_api_key', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashUpdateApiKey'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // Enable the new Checkout
        register_rest_route($this->namespace, '/dash/enable-old-checkout', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'POST',
                'callback' => array($this, 'enableOldCheckout'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));

        // update the custom name for an item
        register_rest_route($this->namespace, '/dash/update_item_name', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashUpdateItemName'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));

        // update api key
        register_rest_route($this->namespace, '/dash/save_settings', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashSaveSettings'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));

        // export settings
        register_rest_route($this->namespace, '/dash/export/settings', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'dashExportSettings'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // export items descriptions
        register_rest_route($this->namespace, '/dash/export/descriptions', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'dashExportDescriptions'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // export items descriptions
        register_rest_route($this->namespace, '/dash/export/images', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'dashExportImages'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // export inventory by options
        register_rest_route($this->namespace, '/dash/export', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashExportInventory'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        //import images
        register_rest_route($this->namespace, '/dash/import/images', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashImportImages'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));

        // import descriptions
        register_rest_route($this->namespace, '/dash/import/descriptions', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashImportItemsDescriptions'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // import Orders Types
        register_rest_route($this->namespace, '/dash/import/ordersTypes', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashImportOrdersTypes'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // import ModifiersAndGroups
        register_rest_route($this->namespace, '/dash/import/modifiers', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashImportModifiersAndGroups'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // import settings
        register_rest_route($this->namespace, '/dash/import/settings', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashImportSettings'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));

        // import Custom Hours
        register_rest_route($this->namespace, '/dash/import/custom-hours', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashImportCustomHours'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // Check the api key and send the website for sync
        register_rest_route($this->namespace, '/dash/check_apikey', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'dashCheckApiKey'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // Save the api key
        register_rest_route($this->namespace, '/dash/save_apikey', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashSaveApiKey'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // Get the opening hours (business Hours)
        register_rest_route($this->namespace, '/dash/opening_hours', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'dashGetOpeningHours'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // Get the autosync status
        register_rest_route($this->namespace, '/dash/autosync', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'dashGetAutoSyncStatus'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // Change the auto sync status
        register_rest_route($this->namespace, '/dash/autosync', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashUpdateAutoSyncStatus'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // Get the detail of the auto sync status
        register_rest_route($this->namespace, '/dash/autosync_details', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'dashGetAutoSyncDetails'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));
        // Get the names of items based on their UUID
        register_rest_route($this->namespace, '/dash/autosync_items_names', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'dashGetAutoSyncItemsNames'),
                'permission_callback' => array( $this, 'permissionCheck' )
            )
        ));



    }

    /**
     * @param $request
     * @return array|WP_Error
     */
    public function dashGetCategory( $request ){

        $response = array();
        if (empty( $request["cat_id"] )) {
            return new WP_Error( 'category_id_required', 'Category id not found', array( 'status' => 404 ) );
        }
        $category = $this->model->getCategory($request["cat_id"]);

        if($category === null )
            return new WP_Error( 'category_not_found', 'Category not found', array( 'status' => 404 ) );

        $response["uuid"]           = $category->uuid;
        $response["name"]           = stripslashes((string)$category->name);
        $response["alternate_name"] = stripslashes((string)$category->alternate_name);
        $response["image_url"]      = $category->image_url;
        $response["description"]    = stripslashes((string)$category->description);
        $response["sort_order"]     = intval($category->sort_order);
        $response["custom_hours"]   = $category->custom_hours;
        $response["time_availability"]     = $category->time_availability;
        $response["items"]= array();

        $items = $this->model->getItemsByCategory($category,false);

        foreach ($items as $item) {

            $final_item = array();

            $final_item["uuid"]         =   $item->uuid;
            $final_item["alternate_name"]      =   stripslashes((string)$item->alternate_name);
            $final_item["description"]         =   stripslashes((string)$item->description);
            $final_item["price"]        =   $item->price;
            $final_item["price_type"]   =   $item->price_type;
            $final_item["unit_name"]    =   $item->unit_name;
            $final_item["custom_hours"]    =   $item->custom_hours;
            $final_item["sort_order"]   =   intval($item->sort_order);
            $final_item["visible"]   =   intval($item->visible);
            $final_item["available"]   =   intval($item->available);

            if(!empty($item->soo_name)){
                $final_item["name"] = stripslashes((string)$item->soo_name) . " (Name on Clover : ".stripslashes((string)$item->name).")";
            } else {
                if($this->useAlternateNames && isset($item->alternate_name) && trim($item->alternate_name)!== ""){
                    $final_item["name"] = stripslashes((string)$item->alternate_name);
                } else {
                    $final_item["name"] = stripslashes((string)$item->name);
                }
            }

            $response['items'][] = $final_item;
        }
        usort($response["items"], array($this,'sortBySortOrder'));
        // Return response data.
        return $response;
    }

    /**
     * @param $request
     * @return array|WP_Error
     */
    function dashUpdateCategory( $request ) {

        if ( empty( $request["cat_id"] ) ) {
            return new WP_Error( 'category_id_required', 'Category id not found', array( 'status' => 404 ) );
        }
        $request_body   = $request->get_body_params();
        $category_name        = sanitize_text_field($request_body['cat_name']);
        $category_description = sanitize_text_field($request_body['cat_description']);
        $category_customHours = sanitize_text_field($request_body['cat_customHours']);
        //Get the category
        $category = $this->model->getCategory($request["cat_id"]);
        if ($category){
            $hoursUpdated = false;

            //Update description
            $infoUpdated = $this->model->updateCategoryNameAndDescription($request["cat_id"], $category_name, $category_description);

            //Update Hours
            if (sanitize_text_field($category->custom_hours) !== $category_customHours) {
                //Update Custom Hours for category
                $hoursUpdated = $this->model->updateCategoryTime($request["cat_id"],'custom',$category_customHours);

                //Update Custom Hours for Items
                if($hoursUpdated) {
                    $items = $this->model->getItemsUuidsPerCategory($category);
                    foreach ($items as $item_uuid) {
                        $this->model->updateItemCustomHour($item_uuid,$category_customHours);
                    }
                } else {
                    return array(
                        "status"=>"failed",
                        "message"=>"The Ordering Hours are not updated, please try again"
                    );
                }
            }
            //Return the response
            if($infoUpdated || $hoursUpdated) {
                $this->api->sendEvent([
                    'event'=>'updated-category',
                    'uuid'=>$request["cat_id"],
                ]);
                return array(
                    "status"=>"success"
                );
            } else {
                return array(
                    "status"=>"failed"
                );
            }
        } else {
            return array(
                "status"=>"failed",
                "message"=>"An error has occurred, please try again"
            );
        }
    }
    /**
     * @param $request
     * @return array|WP_Error
     */
    function dashUpdateCategoryTime( $request ) {
        $request_body   = $request->get_body_params();

        if ( !isset($request["cat_id"]) || empty( $request["cat_id"] ) ) {
            return new WP_Error( 'category_id_required', 'Category id not found', array( 'status' => 404 ) );
        }

        if ( !isset($request_body['status']) || empty( $request_body['status'] ) ) {
            return new WP_Error( 'category_time_status_required', 'Category Time Status not found', array( 'status' => 400 ) );
        }

        $category_status = sanitize_text_field($request_body['status']);

        if ( $category_status !== "all" && $category_status !== "custom"   ) {
            return new WP_Error( 'category_time_status_required', 'Category Time Must be all or custom', array( 'status' => 400 ) );
        }
        if(isset($request_body['hour'])){
            $category_hour  = sanitize_text_field($request_body['hour']);
        } else {
            $category_hour  = null;
        }

        if(!empty($category_status)) {
            $result = $this->model->updateCategoryTime($request["cat_id"], $category_status, $category_hour);
            if($result) {
                return array(
                    "status"=>"success"
                );
            } else {
                return array(
                    "status"=>"failed"
                );
            }
        }
        return array(
            "status"=>"success"
        );
    }

    function dashGetCategories( $request ){

        $categories = $this->model->getCategories();
        $response = array();
        if(@count($categories) > 0 ){
             foreach ($categories as $cat) {
                 $c = array(
                     "uuid"=>$cat->uuid,
                     "name"=>stripslashes((string)$cat->name),
                     "alternate_name" => "",
                     "description"   => stripslashes((string)$cat->description),
                     "image_url"=>$cat->image_url,
                     "sort_order"=>$cat->sort_order,
                     "show_by_default"=>$cat->show_by_default,
                 );

                 if($this->useAlternateNames && isset($cat->alternate_name) && $cat->alternate_name!==""){
                     $c["name"] = stripslashes((string)$cat->alternate_name);
                 } else {
                     $c["name"] = stripslashes((string)$cat->name);
                 }

                 array_push($response,$c);
             }
             return array(
                 "status"=>"success",
                 "data"=>$response
             );
        } else {
             return array(
                 "status"=>"failed"
             );
        }
    }
    function dashGetCategoriesHours( $request ){
        $hours = $this->api->getMerchantCustomHours("categories");
        if($hours){
             return array(
                 "status"=>"success",
                 "data"=>$hours
             );
        } else {
             return array(
                 "status"=>"failed"
             );
        }
    }
    function dashGetOrderTypesHours( $request ){

        $hours = $this->api->getMerchantCustomHours("ordertypes");
        if($hours){
             return array(
                 "status"=>"success",
                 "data"=>$hours
             );
        } else {
             return array(
                 "status"=>"failed"
             );
        }
    }
    function dashUpdateApiKey( $request ){

        if (empty( $request["api_key"] )) {
            return new WP_Error( 'api_key_required', 'New Api Key not found', array( 'status' => 400 ) );
        }
        $api_key = sanitize_text_field($request["api_key"]);
        //check token
        if($this->api->checkAnyToken($api_key)){
            //clean inventory
            global $wpdb;
            if($this->pluginSettings["api_key"] === $api_key) {
                return array(
                    "status"=>false,
                    "message"=>"The API KEY isn't changed"
                );
            }

            //-- Table `item_option`--
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_item_option` ;");
            //-- Table `item_tax_rate` --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_item_tax_rate` ;");
            // -- Table `modifier_group` --
            @$wpdb->query("DELETE FROM `{$wpdb->prefix}moo_item_order` ;");
            //*-- Table `item_tag` --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_item_tag` ;");
            //-- Table `item_modifier_group` --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_item_modifier_group` ;");
            //-- Table `order_types --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_images` ;");
            //-- Table `item` --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_item` ;");
            //-- Table `orders` --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_order` ;");
            //-- Table `option`--
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_option` ;");
            //-- Table `tag` --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_tag` ;");
            //-- Table `tax_rate` --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_tax_rate` ;");
            //-- Table `modifier` --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_modifier` ;");
            //-- Table `category` --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_category` ;");
            //-- Table `attribute` --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_attribute` ;");
            //-- Table `item_group` --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_item_group` ;");
            //-- Table `modifier_group` --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_modifier_group` ;");
            //-- Table `order_types --
            $wpdb->query("DELETE FROM `{$wpdb->prefix}moo_order_types` ;");

            //change it
            $settings = $this->pluginSettings;
            $settings["api_key"] = $api_key;
            $settings["jwt-token"] = "";

            update_option("moo_settings",$settings);
            update_option('moo_merchant_pubkey', "");
            update_option('moo_pakms_key', "");
            update_option('moo_slug', "");

            do_action('smart_online_order_import_inventory');

            $this->api->sendEvent([
                "event"=>'updated-api-key'
            ]);
            //return response
            return array(
                "status"=>true,
                "message"=>"The API KEY changed successfully and your Clover inventory has been Imported"
            );
        } else {
            return array(
                "status"=>false,
                "message"=>"This API KEY isn't correct"
            );
        }
    }
    function enableOldCheckout( $request ) {

        if (empty( $request["status"] )) {
            return new WP_Error( 'status_required', 'An error has occurred', array( 'status' => 400 ) );
        } else {
            $status = rest_sanitize_boolean($request["status"]);
            if($status){
                update_option("moo_old_checkout_enabled",'yes');
            } else {
                update_option("moo_old_checkout_enabled",'no');
            }
            //Save on Cloud
            try {
                $this->api->saveMerchantSettings("old_checkout", $status);
            } catch (Exception $e){
                // Silence is golden
            }
            return array(
                "status"=>true
            );
        }
    }
    function dashCheckApiKey( $request ) {
        if (isset($this->pluginSettings["api_key"])){
            $body = array(
                "api_key"=>$this->pluginSettings["api_key"],
                "home_url"=>get_option("home"),
                "restapi_url"=>get_rest_url(),
                "version"=>$this->version
            );
            $response = $this->api->checkApiKey($body);

            if($response && is_array($response)){
                if($response["httpCode"] === 400 ||  $response["httpCode"] === 500 ){
                    return array(
                        "status"=>"failed",
                        "message"=>"An error has occurred, please refresh the page"
                    );
                }
                if($response["httpCode"] === 404 ){
                    return array(
                        "status"=>"failed",
                        "message"=>"The API KEY isn't valid"
                    );
                }
                if($response["httpCode"] === 401 ) {
                    $result = json_decode($response["responseContent"], true);
                    if (!empty($result["name"])){
                        $message = $result["name"] . "'s api key is valid but your site isn't connected to Clover. Please check if the merchant id has changed or contact us.";
                    } else {
                        $message = "The api key is valid but your site isn't connected to Clover. Please check if the merchant id has changed or contact us.";
                    }
                    return array(
                        "status"=>"failed",
                        "message"=>$message
                    );
                }
                if($response["httpCode"] === 200 ){
                    $result = json_decode($response["responseContent"], true);

                    //check blackout status
                    $blackoutStatusResponse = $this->api->getBlackoutStatus(true);

                    $result["cloverOpeningHoursExist"] = $this->api->cloverOpeningHoursExist();

                    if(isset($blackoutStatusResponse["status"]) && $blackoutStatusResponse["status"] === "close"){
                        $result["blackoutStatus"] = "close";
                        $result["blackoutStatusResponse"] = $blackoutStatusResponse;
                    } else {
                        $result["blackoutStatus"] = "open";
                        $result["blackoutStatusResponse"] = $blackoutStatusResponse;
                    }
                    if (!empty($result["brandedApp"])){
                        update_option('sooDisableGoogleReCAPTCHA',true);
                    } else {
                        update_option('sooDisableGoogleReCAPTCHA',false);
                    }
                    return $result;
                }
            }
            return array(
                "status"=>"failed",
                "message"=>"We couldn't check the api key right now, please try again"
            );
        } else {
            return array(
                "status"=>"failed",
                "message"=>"The API KEY not found"
            );
        }
    }
    function dashGetOpeningHours( $request ){
        if (empty($_GET['sync'])) {
            return $this->api->getOpeningHours();
        } else {
            return $this->api->getOpeningHours(true);
        }
    }
    function dashGetAutoSyncStatus( $request ){
        $url = get_option("home");
        $res = $this->api->getAutoSyncStatus($url);
        if($res){
            return array(
                "status"=>($res["enabled"])?"enabled":"disabled"
            );
        }
        return array(
            "status"=>"disabled"
        );
    }
    function dashUpdateAutoSyncStatus( $request ){
        $request_body   = $request->get_body_params();
        $url = get_option("home");
        if (isset($request_body["status"])){
            $status = $request_body["status"] === "enabled";
            $res = $this->api->updateAutoSyncStatus($url,$status);
            if($res){
                return array(
                    "status"=>"success"
                );
            }
        }
        return array(
            "status"=>"failed"
        );
    }
    /**
     * @param $request
     * @return array|WP_Error
     */
    function dashSaveApiKey( $request ) {
        $request_body   = $request->get_body_params();

        if (empty( $request_body["api_key"] )) {
            return new WP_Error( 'api_key_required', 'API KEY is not found', array( 'status' => 404 ) );
        }

        $body = array(
            "api_key"=>$request_body["api_key"],
            "home_url"=>get_option("home"),
            "restapi_url"=>get_rest_url(),
            "version"=>$this->version
        );
        $response = $this->api->checkApiKey($body);
        if($response && is_array($response)){
            if($response["httpCode"] === 400 ||  $response["httpCode"] === 500 ){
                return array(
                    "status"=>"failed",
                    "message"=>"An error has occurred, please refresh the page"
                );
            }
            if($response["httpCode"] === 404 ){
                return array(
                    "status"=>"failed",
                    "message"=>"The API KEY isn't valid"
                );
            }
            if($response["httpCode"] === 401 ){
                $this->pluginSettings["api_key"] = $request_body["api_key"];
                $this->pluginSettings["jwt-token"]  = null;
                update_option("moo_settings",$this->pluginSettings);
                update_option('moo_merchant_pubkey', "");
                update_option('moo_pakms_key', "");
                return array(
                    "status"=>"failed",
                    "message"=>"The api key is valid but your site isn't connected to Clover. Please check if the merchant id has changed or contact us."
                );
            }
            if($response["httpCode"] === 200 ){
                $this->pluginSettings["api_key"]    = $request_body["api_key"];
                $this->pluginSettings["jwt-token"]  = null;
                update_option("moo_settings",$this->pluginSettings);
                update_option('moo_merchant_pubkey', "");
                $result = json_decode($response["responseContent"], true);
                return $result;
            }
        }
        return array(
            "status"=>"failed",
            "message"=>"We couldn't check the api key right now, please try again"
        );

    }

    /**
     * Handle Saving the settings to send a copy to our servers, to us ethem on the branded App
     * @param $request
     * @return array|WP_Error
     */
    function dashSaveSettings( $request ) {

        $body = json_decode($request->get_body(),true);

        foreach ($body as $item) {
            $this->pluginSettings[$item["name"]] = $item["value"];
        }

        $homeUrl = get_option("home");
        //Send Settings to the server
        $result = $this->api->saveSettings($this->pluginSettings, $homeUrl);
        $error = get_transient( 'soo_error_saving_settings' );

        if ($result || boolval($error)){
            //Save Settings
            if(update_option("moo_settings", $this->pluginSettings)){

                $this->api->sendEvent([
                    "event"=>'updated-settings'
                ]);

                return array(
                    "status"=>"success",
                    "message"=>"The settings has been updated"
                );
            } else {
                return array(
                    "status"=>"failed",
                    "message"=>"No changes have been made"
                );
            }
        } else {
            set_transient( 'soo_error_saving_settings', true, 60 );
            return array(
                "status"=>"failed",
                "message"=>"An error has occurred please, try again"
            );
        }
    }
    function dashExportSettings( $request, $returnArray = false ){
        $settings = $this->pluginSettings;

        unset($settings["api_key"]);
        unset($settings["jwt-token"]);
        unset($settings["store_page"]);
        unset($settings["checkout_page"]);
        unset($settings["cart_page"]);
        unset($settings["my_account_page"]);

        if (!$returnArray){
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename=settings.json');
            header('Pragma: no-cache');
            echo wp_json_encode($settings);
            exit();
        } else {
            return $settings;
        }

    }
    function dashExportDescriptions( $request, $returnArray = false ){
        global $wpdb;
        $data = $wpdb->get_results("SELECT uuid,name,soo_name,description FROM `{$wpdb->prefix}moo_item` where description is not null or soo_name is not null");
        if($data){
            foreach( $data as &$quote ) {
                foreach( $quote as &$field ) {
                    if ( is_string( $field ) ) {
                        $field = stripslashes( $field );
                    }
                    if ( empty( $field ) ) {
                        $field = null;
                    }
                }
            }
            if (!$returnArray){
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename=items_descriptions.json');
                header('Pragma: no-cache');
                echo wp_json_encode($data);
                exit();
            } else {
                return $data;
            }
        } else {
            return array(
                "status"=>false,
                "message"=>"An error has occurred please try again"
            );
        }
    }
    function dashExportOrdersTypes( $request, $returnArray = false ){
        global $wpdb;
        $data = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}moo_order_types`");
        if($data){
            foreach( $data as &$quote ) {
                foreach( $quote as &$field ) {
                    if ( is_string( $field ) ) {
                        $field = stripslashes( $field );
                    }
                    if ( empty( $field ) ) {
                        $field = null;
                    }
                }
            }
            if (!$returnArray){
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename=orderTypes.json');
                header('Pragma: no-cache');
                echo wp_json_encode($data);
                exit();
            } else {
                return $data;
            }
        } else {
            return array(
                "status"=>false,
                "message"=>"An error has occurred please try again"
            );
        }
    }
    function dashExportModifierGroups( $request, $returnArray = false ){
        global $wpdb;
        $data = $wpdb->get_results("SELECT uuid,name,alternate_name,show_by_default,sort_order FROM `{$wpdb->prefix}moo_modifier_group`");
        if($data){
            foreach( $data as &$quote ) {
                foreach( $quote as &$field ) {
                    if ( is_string( $field ) ) {
                        $field = stripslashes( $field );
                    }
                    if ( empty( $field ) ) {
                        $field = null;
                    }
                }
            }
            if (!$returnArray){
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename=modifierGroups.json');
                header('Pragma: no-cache');
                echo wp_json_encode($data);
                exit();
            } else {
                return $data;
            }
        } else {
            return array(
                "status"=>false,
                "message"=>"An error has occurred please try again"
            );
        }
    }
    function dashExportModifiers( $request, $returnArray = false ){
        global $wpdb;
        $data = $wpdb->get_results("SELECT uuid,name,alternate_name,show_by_default,sort_order FROM `{$wpdb->prefix}moo_modifier`");
        if($data){
            foreach( $data as &$quote ) {
                foreach( $quote as &$field ) {
                    if ( is_string( $field ) ) {
                        $field = stripslashes( $field );
                    }
                    if ( empty( $field ) ) {
                        $field = null;
                    }
                }
            }
            if (!$returnArray){
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename=modifiers.json');
                header('Pragma: no-cache');
                echo wp_json_encode($data);
                exit();
            } else {
                return $data;
            }
        } else {
            return array(
                "status"=>false,
                "message"=>"An error has occurred please try again"
            );
        }
    }
    function dashExportCustomHours( $request, $returnArray = false ){
       $data = array(
           'categories'=>$this->api->getAllCustomHours('categories'),
           'ordertypes'=>$this->api->getAllCustomHours('ordertypes'),
       );
       if($data){
            if (!$returnArray){
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename=customHours.json');
                header('Pragma: no-cache');
                echo wp_json_encode($data);
                exit();
            } else {
                return $data;
            }
        } else {
            return array(
                "status"=>false,
                "message"=>"An error has occurred please try again"
            );
        }
    }
    function dashExportImages( $request, $returnArray = false ){
        global $wpdb;
        $data = array(
            "items"=>array(),
            "categories"=>array()
        );
        //get items images

        $data["items"] = $wpdb->get_results("SELECT items.uuid,items.name,images.url,images.is_default,images.is_enabled FROM `{$wpdb->prefix}moo_item` items,`{$wpdb->prefix}moo_images` images where images.item_uuid = items.uuid");

        // get categories images

        $data["categories"] = $wpdb->get_results("SELECT uuid,name,alternate_name,sort_order,show_by_default,image_url,description,custom_hours,time_availability FROM `{$wpdb->prefix}moo_category`");

        //export
        if($data){
            if (!$returnArray){
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename=images.json');
                header('Pragma: no-cache');
                echo wp_json_encode($data);
                exit();
            } else {
                return $data;
            }
        } else {
            return array(
                "status"=>false,
                "message"=>"An error has occurred please try again"
            );
        }
    }
    function dashExportInventory( $request ){
        $body   = $request->get_json_params();
        $data = array(
            "images"=>null,
            "descriptions"=>null,
            "settings"=>null,
            "ordersTypes"=>null,
            "modifiers"=>null,
            "customHours"=>null
        );

        if (isset($body["images"]) && $body["images"]){
            $data["images"] = $this->dashExportImages($request, true);
        }

        if (isset($body["descriptions"]) && $body["descriptions"]){
            $data["descriptions"] = $this->dashExportDescriptions($request, true);
        }

        if (isset($body["settings"]) && $body["settings"]){
            $data["settings"] = $this->dashExportSettings($request, true);
        }
        if (isset($body["ordersTypes"]) && $body["ordersTypes"]){
            $data["ordersTypes"] = $this->dashExportOrdersTypes($request, true);
        }
        if (isset($body["modifiers"]) && $body["modifiers"]){
            $data["modifier_groups"] = $this->dashExportModifierGroups($request, true);
            $data["modifiers"] = $this->dashExportModifiers($request, true);
        }
        if (isset($body["customHours"]) && $body["customHours"]){
            $data["customHours"] = $this->dashExportCustomHours($request, true);
        }

        return $data;
    }

    function dashImportSettings( $request ){

        $permittedExtension = 'json';
        try {
            $files = $request->get_file_params();

            if ( !isset( $files['file'] ) || empty( $files['file'] ) ) {
                $data = json_decode($request->get_body(),true);

            } else {
                $file = $files['file'];
                // confirm no file errors
                if (! $file['error'] === UPLOAD_ERR_OK ) {
                    return new WP_Error( 'Upload error: ' . $file['error'], array( 'status' => 400 ) );
                }
                // confirm extension meets requirements
                $ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
                if ( $ext !== $permittedExtension ) {
                    return new WP_Error( 'Invalid extension. ', array( 'status' => 400 ));
                }
                $handle = fopen( $file['tmp_name'], 'r' );
                $filecontent =  fread($handle,filesize($file['tmp_name']));
                $data = json_decode($filecontent,true);
            }

            if (!isset($data)){
                return new WP_Error( 'data_required', 'New Data not found ( send file or json body)', array( 'status' => 400 ) );
            } else {
                $settings = $this->pluginSettings;
                foreach ($data as $key=>$value) {
                    $settings[$key] = $value;
                }
                update_option("moo_settings", $settings);
                return array(
                    "status"=>true,
                    "message"=>"The settings has been updated"
                );
            }

        } catch (Exception $e){
            return array(
                "status"=>false,
                "message"=>$e->getMessage()
            );
        }
    }
    function dashImportItemsDescriptions( $request ){
        $files = $request->get_file_params();
        if ( !isset( $files['file'] ) || empty( $files['file'] ) ) {
            $data = json_decode($request->get_body(),true);
        } else {
            $permittedExtension = 'json';
            $file = $files['file'];
            // confirm no file errors
            if (! $file['error'] === UPLOAD_ERR_OK ) {
                return new WP_Error( 'Upload error: ' . $file['error'], array( 'status' => 400 ) );
            }
            // confirm extension meets requirements
            $ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
            if ( $ext !== $permittedExtension ) {
                return new WP_Error( 'Invalid extension. ', array( 'status' => 400 ));
            }

            $handle = fopen( $file['tmp_name'], 'r' );
            $filecontent =  fread($handle,filesize($file['tmp_name']));

            $data = json_decode($filecontent,true);
        }
        //Check if body exist and with data
        if (!isset($data)){
            return new WP_Error( 'data_required', 'New Data not found ( send file or json body)', array( 'status' => 400 ) );
        } else {
            $counterByUuid = 0;
            $counterByName = 0;
            foreach ($data as $item){
                if(isset($item["description"]) || isset($item["soo_name"])){
                    //update item by uuid
                    $res = $this->model->updateItem($item,true);
                    if($res === 0){
                        //Uuid not found, we will update the item by name
                        $res2 = $this->model->updateItem($item,false);
                        if($res2 !== 0){
                            $counterByName++;
                        }
                    } else {
                        $counterByUuid++;
                    }
                }
            }
            return array(
                "status"=>true,
                "total"=>count($data),
                "updated_by_uuid"=>$counterByUuid,
                "updated_by_name"=>$counterByName
            );
        }
    }
    function dashImportOrdersTypes( $request ){
        $files = $request->get_file_params();
        if ( !isset( $files['file'] ) || empty( $files['file'] ) ) {
            $data = json_decode($request->get_body(),true);
        } else {
            $permittedExtension = 'json';
            $file = $files['file'];
            // confirm no file errors
            if (! $file['error'] === UPLOAD_ERR_OK ) {
                return new WP_Error( 'Upload error: ' . $file['error'], array( 'status' => 400 ) );
            }
            // confirm extension meets requirements
            $ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
            if ( $ext !== $permittedExtension ) {
                return new WP_Error( 'Invalid extension. ', array( 'status' => 400 ));
            }

            $handle = fopen( $file['tmp_name'], 'r' );
            $filecontent =  fread($handle,filesize($file['tmp_name']));

            $data = json_decode($filecontent,true);
        }
        //Check if body exist and with data
        if (!isset($data)){
            return new WP_Error( 'data_required', 'New Data not found ( send file or json body)', array( 'status' => 400 ) );
        } else {
            $counter = 0;
            if(isset($data["ordersTypes"]) && is_array($data["ordersTypes"])){
                foreach ($data["ordersTypes"] as $ot) {
                    if(isset($ot["ot_uuid"])){
                        //Save changes
                        if (  $this->model->updateOrderTypeFromArray($ot) ){
                            $counter++;
                        }
                    }
                }
            }

            return array(
                "status"=>true,
                "updated"=>$counter
            );
        }
    }
    function dashImportModifiersAndGroups( $request ){
        $files = $request->get_file_params();
        if ( !isset( $files['file'] ) || empty( $files['file'] ) ) {
            $data = json_decode($request->get_body(),true);
        } else {
            $permittedExtension = 'json';
            $file = $files['file'];
            // confirm no file errors
            if (! $file['error'] === UPLOAD_ERR_OK ) {
                return new WP_Error( 'Upload error: ' . $file['error'], array( 'status' => 400 ) );
            }
            // confirm extension meets requirements
            $ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
            if ( $ext !== $permittedExtension ) {
                return new WP_Error( 'Invalid extension. ', array( 'status' => 400 ));
            }

            $handle = fopen( $file['tmp_name'], 'r' );
            $filecontent =  fread($handle,filesize($file['tmp_name']));

            $data = json_decode($filecontent,true);
        }
        //Check if body exist and with data
        if (!isset($data)){
            return new WP_Error( 'data_required', 'New Data not found ( send file or json body)', array( 'status' => 400 ) );
        } else {
            $counterForModifiers = 0;
            $counterForModifierGroups = 0;
            if(isset($data["modifiers"]) && is_array($data["modifiers"])){
                foreach ($data["modifiers"] as $modifier) {
                    if(isset($modifier["uuid"])){
                        //Save changes
                        if (  $this->model->updateModifier($modifier) ){
                            $counterForModifiers++;
                        }
                    }
                }
            }

            if(isset($data["modifier_groups"]) && is_array($data["modifier_groups"])){
                foreach ($data["modifier_groups"] as $modifierGroup) {
                    if(isset($modifierGroup["uuid"])) {
                        //Save changes
                        if (  $this->model->updateModifierGroup($modifierGroup) ){
                            $counterForModifierGroups++;
                        }
                    }
                }
            }
            return array(
                "status"=>true,
                "modifiers"=>$counterForModifiers,
                "modifier_groups"=>$counterForModifierGroups
            );
        }
    }
    function dashImportCustomHours( $request ){
        $files = $request->get_file_params();
        if (empty( $files['file'] )) {
            $data = json_decode($request->get_body(),true);
        } else {
            $permittedExtension = 'json';
            $file = $files['file'];
            // confirm no file errors
            if (! $file['error'] === UPLOAD_ERR_OK ) {
                return new WP_Error( 'Upload error: ' . $file['error'], array( 'status' => 400 ) );
            }
            // confirm extension meets requirements
            $ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
            if ( $ext !== $permittedExtension ) {
                return new WP_Error( 'Invalid extension. ', array( 'status' => 400 ));
            }

            $handle = fopen( $file['tmp_name'], 'r' );
            $filecontent =  fread($handle,filesize($file['tmp_name']));

            $data = json_decode($filecontent,true);
        }
        //Check if body exist and with data
        if (!isset($data)){
            return new WP_Error( 'data_required', 'New Data not found ( send file or json body)', array( 'status' => 400 ) );
        } else {
           //TODO :: Import Custom Hours
        }
    }
    function dashImportImages( $request ){
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        global $wpdb;
        $count_items=0;
        $count_categories = 0;
        $skippedCategories = 0;
        $skippedItems = 0;
        $errors = [
            "items"=>0,
            "categories"=>0,
            "notFoundItems"=>0,
            "notFoundCategories"=>0,
        ];
        $upload_dir = wp_upload_dir();
        $permittedExtension = 'json';
        //Get Data from file or JSON Body
        $files = $request->get_file_params();
        if (empty( $files['file'] )) {
            $data = json_decode($request->get_body(),true);
            //Get Data from json
            if ( isset( $data["cloneImages"] ) ) {
                $cloneImages = $data["cloneImages"] !== 'false';
            } else {
                $cloneImages = true;
            }

            if ( isset( $data["skipWhenImageExist"] ) ) {
                $skipWhenImageExist = $data["skipWhenImageExist"] !== 'false';
            } else {
                $skipWhenImageExist = false;
            }

        } else {
            //Get DaTa From File
            $request_body   = $request->get_body_params();
            if ( isset( $request_body["cloneImages"] ) ) {
                $cloneImages = $request_body["cloneImages"] !== 'false';
            } else {
                $cloneImages = true;
            }
            if ( isset( $request_body["skipWhenImageExist"] ) ) {
                $skipWhenImageExist = $request_body["skipWhenImageExist"] !== 'false';
            } else {
                $skipWhenImageExist = false;
            }
            $file = $files['file'];
            // confirm no file errors
            if (! $file['error'] === UPLOAD_ERR_OK ) {
                return new WP_Error( 'Upload error: ' . $file['error'], array( 'status' => 400 ) );
            }
            // confirm extension meets requirements
            $ext = pathinfo( $file['name'], PATHINFO_EXTENSION );
            if ( $ext !== $permittedExtension ) {
                return new WP_Error( 'Invalid extension. ', array( 'status' => 400 ));
            }
            $handle = fopen( $file['tmp_name'], 'r' );
            $filecontent =  fread($handle,filesize($file['tmp_name']));

            $data = json_decode($filecontent,true);
        }

        if(isset($data["items"]) && is_array($data["items"])) {
            foreach ($data["items"] as $item) {
                if(isset($item["url"])){
                    //get item uuid based on name and uuid
                    $name = esc_sql($item["name"]);
                    $sql = "SELECT * FROM `{$wpdb->prefix}moo_item` 
                        WHERE name like '{$name}' OR alternate_name like '{$name}'
                        OR uuid = '{$item["uuid"]}';";
                    $oneItem = $wpdb->get_row($sql);
                    if ($oneItem){
                        if ($skipWhenImageExist){
                            //Count current images
                            $images = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}moo_images where item_uuid = '{$oneItem->uuid}'");
                            if(count($images) > 0){
                                $skippedItems++;
                                continue;
                            }
                        }

                        if($cloneImages){
                            try {
                                $link = $this->uploadFileByUrl($item["url"]);
                                if($link){
                                    $link_image = $link;
                                } else {
                                    throw new Exception();
                                }
                            } catch (Exception  $e){
                                $errors["items"]++;
                                continue;
                            }
                        } else {
                            $link_image = $item["url"];
                        }

                        //remove old images
                        $wpdb->delete("{$wpdb->prefix}moo_images",array(
                            "item_uuid"=>$oneItem->uuid
                        ));

                        //add new image
                        if ( $wpdb->insert("{$wpdb->prefix}moo_images",array(
                            "item_uuid"=>$oneItem->uuid,
                            "url"=>$link_image,
                            "is_default"=>($item["is_default"])?$item["is_default"]:1,
                            "is_enabled"=>($item["is_enabled"])?$item["is_enabled"]:1
                        ))) {
                            $count_items++;
                        }
                    } else {
                        $errors["notFoundItems"]++;
                    }

                }
            }
        }

        if(isset($data["categories"]) && is_array($data["categories"])) {
            foreach ($data["categories"] as $category) {
                if(isset($category["uuid"]) || isset($category["name"])){

                    if ($skipWhenImageExist){
                        //Count current images
                        $cat = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}moo_category where uuid = '{$category["uuid"]}' or name like '{$category["name"]}'or alternate_name like '{$category["name"]}' ");
                        if($cat && isset($cat->image_url) && !empty($cat->image_url)){
                            $skippedCategories++;
                            continue;
                        }
                    }

                    if($cloneImages) {
                        try{
                            $link = $this->uploadFileByUrl($category["image_url"]);
                            if($link){
                                $link_image = $link;
                            } else{
                                throw new Exception();
                            }
                        } catch (Exception  $e){
                            $errors["categories"]++;
                            continue;
                        }
                    } else {
                        $link_image =  $category["image_url"];
                    }

                    if(isset($category["name"])){
                        $name     = esc_sql($category["name"]);
                    } else {
                        $name = null;
                    }
                    if(isset($category["description"])){
                        $cat_desc     = esc_sql($category["description"]);
                    } else {
                        $cat_desc = '';
                    }
                    //Get the category

                    //Update image url
                    $category['image_url'] = $link_image;

                    //Save changes
                    $res = $this->model->updateCategory($category);
                    if($res  === 0 && $name){
                       $sql = "UPDATE `{$wpdb->prefix}moo_category` 
                        SET image_url = '{$link_image}',
                          description = '{$cat_desc}'
                        WHERE name like '{$name}' or alternate_name like '{$name}';";
                        if ($wpdb->query($sql)){
                        } else {
                            $errors["notFoundCategories"]++;
                        }
                   } else {
                       $count_categories++;
                   }
                }
            }
        }

        return array(
            "status"=>'success',
            "count_items"=>$count_items,
            "count_categories"=>$count_categories,
            "cloneImages"=>$cloneImages,
            "skipImages"=>[
                "skipWhenImageExist"=>$skipWhenImageExist,
                "skippedItems"=>$skippedItems,
                "skippedCategories"=>$skippedCategories,
            ],
            "errors"=>$errors
        );
    }
    function dashGetAutoSyncDetails( $request ){
        $url = get_option("home");
        if (isset($request["page"])){
            $page = intval($request["page"]);
        } else {
            $page = 1;
        }
        $res = $this->api->getAutoSyncDetails($url,$page);
        if($res){
            return $res;
        }

        return array(
            "status"=>"failed"
        );
    }
    function dashGetAutoSyncItemsNames( $request ){
        $request_body   = $request->get_body_params();
        if (isset($request_body["items"]) && is_array($request_body["items"])){
            $itemsString = "(";
            foreach($request_body["items"] as $item) {
                $itemsString .= "'".$item."',";
            }
            $itemsString = substr($itemsString, 0, strlen($itemsString)-1);
            $itemsString .= ")";
            if (strlen($itemsString)>1) {
                $items = $this->model->getItemsNamesByUuids($itemsString);
                $finalResult = array();
                foreach ($items as  $i){
                    $finalResult[$i->uuid] = $i->name;
                }
                return array(
                    "status"=>"success",
                    "data"=>$finalResult
                );
            }
        }

        return array(
            "status"=>"failed"
        );
    }
    function dashUpdateItemName( $request ){
        $request_body   = $request->get_body_params();

        if (empty( $request["item_uuid"] )) {
            return new WP_Error( 'item_uuid_required', 'item_uuid not found', array( 'status' => 400 ) );
        }

        if (empty( $request_body['name'] )) {
            return new WP_Error( 'name_required', 'Item name not found', array( 'status' => 400 ) );
        }
        if ( strlen((string)$request_body['name'] ) > 255 ) {
            return array(
                "status"=>"failed",
                "message"=>"The name is too long"
            );
        }

        $itemUuid = sanitize_text_field($request_body['item_uuid']);
        $newName = sanitize_text_field($request_body['name']);
        try {
            $updated = $this->model->updateItemName($itemUuid,$newName);
            if ($updated) {

                $this->api->sendEvent([
                    "event"=>'updated-item',
                    "uuid"=>$itemUuid,
                ]);

                return array(
                    "status"=>"success"
                );
            } else {
                return array(
                    "status"=>"failed",
                    "message"=>"No changes detected"
                );
            }
        } catch (Exception $e){
            return array(
                "status"=>"failed",
                "message"=>"No changes detected"
            );
        }

    }

    /**
     * @throws Exception
     */
    private function uploadFileByUrl($image_url ) {

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