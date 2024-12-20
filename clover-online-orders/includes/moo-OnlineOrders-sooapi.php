<?php

class Moo_OnlineOrders_SooApi
{
    public  $apiKey;
    public  $url_api;
    public  $url_api_v2;
    public  $urlInventoryApi;
    public  $hours_url_api;
    private $debugMode = false;
    private $isSandbox;
    private $session;
    public  $settings;
    private $jwt_token;
    public $last_error = array();

    function __construct() {

        $this->isSandbox = (defined('SOO_ENV') && (SOO_ENV === "DEV"));

        $this->setApiLinks();
        $this->getApiKey();
        $this->getSession();

    }

    public function setApiLinks() {
        if ($this->isSandbox) {
            $this->url_api = "https://api-sandbox.smartonlineorders.com/";
            $this->url_api_v2 = "https://api-v2-sandbox.smartonlineorders.com/v2/";
            $this->urlInventoryApi = $this->url_api_v2;
        } else {
            $this->url_api = "https://api.smartonlineorders.com/";
            $this->url_api_v2 = "https://api-v2.smartonlineorders.com/v2/";
            $this->urlInventoryApi = "https://api-inventory.smartonlineorders.com/v2/";
        }

        $this->hours_url_api = "https://smh.smartonlineorder.com/v1/api/";

    }
    public function getApiKey() {
        $mooSettings = (array) get_option('moo_settings');
        if (isset($mooSettings['api_key'])) {
            $this->apiKey = $mooSettings['api_key'];
        } else {
            $this->apiKey = '';
        }
        if (isset($mooSettings['jwt-token'])) {
            $this->jwt_token = $mooSettings['jwt-token'];
        } else {
            if($this->apiKey !== ""){
                $this->getJwtToken();
            } else {
                $this->jwt_token = "";
            }
        }
        $this->settings = $mooSettings;
    }
    public function getSession() {
        $this->session = MOO_SESSION::instance();
    }

    public function getJwtToken(){

        if($this->apiKey === ""){
            return null;
        }
        $endPoint = $this->url_api_v2 . "auth/login";
        $body = array(
            'api_key' => $this->apiKey
        );
        $response = wp_remote_post( $endPoint, array(
                'method'      => 'POST',
                'timeout'     => 60,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(
                    "Content-Type"=>"application/json",
                    "Accept"=>"application/json",
                ),
                'body'        => wp_json_encode($body),
                'cookies'     => array()
            )
        );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            if($this->debugMode){
                echo "<br> Something went wrong when getting the JWT TOKEN: $error_message";
                echo "EndPoint: $endPoint";
            }
        } else {
            $http_code = wp_remote_retrieve_response_code( $response );

            $responseContent =  json_decode(wp_remote_retrieve_body( $response ));
            if( $http_code === 200 ) {
                if(isset($responseContent->access_token)){
                    $mooSettings = (array)get_option('moo_settings');
                    $this->jwt_token =  $responseContent->access_token;
                    $mooSettings["jwt-token"] =  $responseContent->access_token;
                    update_option("moo_settings", $mooSettings);
                    return  $responseContent->access_token;
                }
            } else {
                if($this->debugMode){
                    echo "Something went wrong when getting JWT Token: $http_code =>". wp_json_encode($responseContent);
                }
            }
        }
        return null;
    }

    public function resetJwtToken(){
        $mooSettings = (array)get_option('moo_settings');
        $this->jwt_token = "";
        $mooSettings["jwt-token"] =  "";
        update_option("moo_settings", $mooSettings);
    }

    /*
     * This functions import data from Clover POS and call the save functions
     * for example : getCategories get JSON object of categories from Clover POS and call the function save_categories
     * to save the this categories in Wordpress DB
     * Updated to use the new API based on jwt tokens
     * Jan 2021
     */
    public function getCategories() {
        $res = $this->getRequest($this->urlInventoryApi."inventory/categories?expand=menuSection%2Citems%2Citems.menuItem", true);
        if (is_array($res)) {
            $count = 0;
            foreach ($res as $cat) {
                if ($this->insertOrUpdateCategory($cat)) {
                    $count++;
                }
            }
            return "$count Categories imported";
        } else {
            return "Please verify your Key in page settings";
        }

    }
    public function getItemGroups() {
        $res = $this->getRequest($this->urlInventoryApi."inventory/item_groups", true);
        if (is_array($res)) {
            $saved = $this->save_item_groups($res);
            return "$saved item_groups saved in your DB";
        } else {
            return "Please verify your Key in page settings";
        }
    }
    public function getModifierGroups() {
        $res = $this->getRequest($this->urlInventoryApi."inventory/modifier_groups", true);
        if (is_array($res)) {
            $saved = $this->save_modifier_groups($res);
            return "$saved Modifier groups imported";
        } else {
            return "Please verify your Key in page settings";
        }
    }
    public function getAndSaveItems() {
        $page = 0;
        $saved = 0;
        $received = 0;
        $savedWithErrors = 0;
        $exist = 0;
        while (true) {

            // Fetch items for the current page
            $result = $this->getItemsWithoutSaving($page);

            if (!is_array($result)) {
                // If the result is not an array, log an error and stop processing
                error_log("Error fetching items on page $page: Invalid data format");
                break;
            }

            if (count($result) === 0) {
                // No more items to process
                break;
            }

            $page++;

            // Attempt to save the fetched items
            $savingResult = $this->save_items($result);

            // Update counters
            $saved += isset($savingResult['count']) ? $savingResult['count'] : 0;
            $savedWithErrors += isset($savingResult['errors']) ? $savingResult['errors'] : 0;
            $exist += isset($savingResult['exist']) ? $savingResult['exist'] : 0;
            $received += count($result);
        }

        if ($received === 0) {
            return "No items were received.";
        } elseif ($saved > 0 && $savedWithErrors === 0 && $exist === 0) {
            return sprintf("%d items were successfully imported.", $saved);
        } elseif ($saved === 0 && $exist === $received && $savedWithErrors === 0) {
            return "All your items already exist.";
        } elseif ($saved > 0 && $exist > 0 && $savedWithErrors === 0) {
            return sprintf(
                "%d items were successfully imported, and %d items already exist.",
                $saved,
                $exist
            );
        } elseif ($saved > 0 && $savedWithErrors > 0) {
            return sprintf(
                "%d items were successfully imported, %d items already exist, but %d items encountered errors during import.",
                $saved,
                $exist,
                $savedWithErrors
            );
        } else {
            return sprintf(
                "%d items received, but %d items encountered errors during import.",
                $received,
                $savedWithErrors
            );
        }
    }
    public function getModifiers() {
        $res = $this->getRequest($this->urlInventoryApi."inventory/modifiers", true);
        if (is_array($res)) {
            $saved = $this->save_modifiers($res);
            return "$saved modifier saved in your DB";
        } else {
            return "Please verify your Key in page settings";
        }
    }
    public function getAttributes() {
        $res = $this->getRequest($this->urlInventoryApi."inventory/attributes", true);
        if (is_array($res)) {
            $saved = $this->save_attributes($res);
            return "$saved attribute saved in your DB";
        } else {
            return "Please verify your Key in page settings";
        }
    }
    public function getOptions() {
        $res = $this->getRequest($this->urlInventoryApi."inventory/options", true);
        if (is_array($res)) {
            $saved = $this->save_options($res);
            return "$saved Options imported";
        } else {
            return "Please verify your Key in page settings";
        }
    }
    public function getTags() {
        $res = $this->getRequest($this->urlInventoryApi."inventory/tags", true);

        if (is_array($res)) {
            $saved = $this->save_tags($res);
            return "$saved Labels imported";
        } else {
            return "Please verify your Key in page settings";
        }
    }
    public function getTaxRates() {
        $res = $this->getRequest($this->urlInventoryApi."inventory/tax_rates", true);
        if (is_array($res)) {
            $saved = $this->save_tax_rates($res);
            return "$saved Taxes rates imported";
        } else {
            return "Please verify your Key in page settings";
        }
    }
    public function getOrderTypes() {
        $res = $this->getRequest($this->urlInventoryApi."inventory/order_types", true);
        if (is_array($res)) {
            $saved = $this->save_order_types($res);
            return "$saved Order type saved in your DB";
        } else {
            return "Please verify your Key in page settings";
        }
    }

    /*
     * Advanced Importing functions
     */
    public function getOneModifierGroups($uuid) {
        global $wpdb;
        $modifier_groups = $this->getRequest($this->urlInventoryApi."inventory/modifier_groups/".$uuid."?expand=modifiers", true);
        if(isset($modifier_groups["id"])) {
            try {
                $wpdb->insert("{$wpdb->prefix}moo_modifier_group", array(
                    'uuid' => $modifier_groups["id"],
                    'name' => $modifier_groups["name"],
                    'alternate_name' => $modifier_groups["alternateName"],
                    'show_by_default' => $modifier_groups["showByDefault"],
                    'min_required' => $modifier_groups["minRequired"],
                    'max_allowd' => $modifier_groups["maxAllowed"],
                ));
                $this->save_modifiers($modifier_groups["modifiers"]["elements"]);
                return true;
            } catch (Exception $e){}
        }
        return false;

    }
    //Functions to call the API for make Orders and payments
    public function getPakmsKey() {
        //Get it locally when it's not found get it from Clover
        $localKey =  get_option("moo_pakms_key");
        if (!empty($localKey)){
            return $localKey;
        }
        $cloverPubKey = $this->getRequest($this->url_api_v2 ."merchants/pakms", true);
        if (!empty($cloverPubKey["key"])){
            update_option("moo_pakms_key",$cloverPubKey["key"]);
            return $cloverPubKey["key"];
        }
        return null;
    }

    //get themes
    public function getThemes() {
        return json_decode($this->callApi("themes", $this->apiKey));
    }

    public function getMerchantAddress() {
        $merchant =  $this->getRequest($this->url_api_v2 . "merchants/me", true);
        if($merchant){
            if(isset($merchant["address"])){
                return $merchant["address"];
            }
        }
        return "";
    }
    public function getBusinessSettings() {
        $headers = array(
            "Accept"=>"application/json"
        );
        $pubkey = $this->getMerchantPubKey();
        if ($pubkey){
            $response = $this->apiGet($this->url_api_v2 . "public/business-settings/".$pubkey,false, $headers);

            $resData = apply_filters("moo_filter_business_settings_response",  $response);
            if (isset($resData["code"]) && $resData["code"] === "invalid_pubkey"){
                $pubkey = $this->getMerchantPubKey();
                if ($pubkey){
                    $response = $this->apiGet($this->url_api_v2 . "public/business-settings/".$pubkey,false, $headers);
                    $resData = apply_filters("moo_filter_business_settings_response",  $response);
                }
            }
            return $resData;
        }
        return null;
    }
    public function getMerchantPubKey() {
        //Get it locally
        $merchant_pubkey =  get_option("moo_merchant_pubkey");
        if (isset($merchant_pubkey) && !empty($merchant_pubkey)){
             return $merchant_pubkey;
        }
        $merchant =  $this->getRequest($this->url_api_v2 . "merchants/me", true);
        if($merchant){
            if(isset($merchant["pubkey"])){
                update_option('moo_merchant_pubkey', $merchant["pubkey"]);
                return $merchant["pubkey"];
            }
        }
        return null;
    }
    public function getAutoSyncStatus($url) {
        return  $this->getRequest($this->url_api_v2 . "merchants/website?url=".$url, true);
    }
    public function updateAutoSyncStatus($url,$status) {
        return  $this->postRequest($this->url_api_v2 . "merchants/website?url=".$url, wp_json_encode(array("enabled"=>$status)),true);
    }

    public function getAutoSyncDetails($url,$page) {
       return  $this->getRequest($this->url_api_v2 . "merchants/website/webhooks_history?all=yes&url=".$url."&page=".$page, true);
    }

    public function getOpeningHours($sync = false) {
        $result = array();
        $days_names = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        $url = $this->url_api_v2 . "merchants/opening_hours";
        if ($sync){
            $url .= "?sync=true";
        }
        $res = $this->getRequest($url, true);
        $string = "";

        if (isset($res["elements"]) && count($res["elements"]) > 0) {
            $days = $res["elements"];
            $days = $days[0];
            foreach ($days_names as $days_name) {
                $string = "";
                $Theday = $days[$days_name];
                if (@count($Theday["elements"]) > 0) {
                    foreach ($Theday["elements"]as $time) {
                        $startTime = ($time["start"] != 0) ? substr_replace(((strlen($time["start"]) == 4) ? $time["start"] : ((strlen($time["start"]) == 2) ? '00' . $time["start"] : '0' . $time["start"])), ':', 2, 0) : '00:00';
                        $endTime = ($time["end"] != 2400) ? substr_replace(((strlen($time["end"]) == 4) ? $time["end"] : ((strlen($time["end"]) == 2) ? '00' . $time["end"] : '0' . $time["end"])), ':', 2, 0) : '24:00';
                        $string .= gmdate('h:i a', strtotime($startTime)) . ' to ' . gmdate('h:i a', strtotime($endTime)) . ' AND ';
                        $result[ucfirst($days_name)][] = gmdate('h:i a', strtotime($startTime)) . ' to ' . gmdate('h:i a', strtotime($endTime));
                        //$result[ucfirst($days_name)] = substr($string, 0, -5);
                    }
                } else {
                    $result[ucfirst($days_name)] = 'Closed';
                }
            }
            return [
                'days'=>$result,
                'timezone'=>$res["timezone"]
            ];
        }
        return "Please setup your business hours on Clover";
    }
    public function cloverOpeningHoursExist() {
        $res = $this->getRequest($this->url_api_v2 . "merchants/opening_hours", true);
        if (isset($res["elements"]) && count($res["elements"]) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getOpeningStatus($nb_days, $nb_minites) {
        $url = $this->url_api . "is_open/" . intval($nb_days) . "/" . intval($nb_minites);
        return $this->getRequest($url, false);
    }

    public function getBlackoutStatus($freshVersion  =  false) {
        $currentBo = get_transient( 'moo_blackout' );
        if( ! empty( $currentBo ) && $freshVersion === false) {
            return $currentBo;
        } else {
            $endPoint = $this->url_api_v2 . "blackouts/status";
            $responseContent = $this->getRequest($endPoint,true);
            if($responseContent){
                set_transient( 'moo_blackout', $responseContent, 300 );
                return $responseContent;
            }
        }
        return array(
            "status"=>"open",
            "hide_menu"=>"false"
        );
    }
    public function getMerchantUuid() {
        $uuid = get_option( 'moo_merchant_uuid' );
        if( ! empty( $uuid ) ) {
            return $uuid;
        } else {
            $endPoint = $this->url_api_v2 . "merchants/uuid";
            $responseContent = $this->getRequest($endPoint,true);
            if(!empty($responseContent["uuid"])){
                update_option( 'moo_merchant_uuid', $responseContent["uuid"] );
                return $responseContent["uuid"];
            }
        }
        return null;
    }

    public function getMerchantProprietes() {
        if (!$this->session->isEmpty("merchantProp")) {
            return$this->session->get("merchantProp");
        } else {
            $res = $this->callApi("properties", $this->apiKey);
            $this->session->set($res,"merchantProp");
            return $res;
        }
    }

    public function getConnectedMerchant() {
        return $this->getRequest($this->url_api_v2. "merchants/me", true);
    }

    public function getTrackingStockStatus()
    {
        $MooOptions = (array) get_option('moo_settings');
        if (isset($MooOptions["track_stock"]) && $MooOptions["track_stock"] == "enabled") {
            return true;
        } else {
            return false;
        }
    }

    public function getItemStocks() {
        $url = $this->urlInventoryApi . "item_stocks";
        $res = $this->getRequest($url, true);
        if (isset($res["elements"]))
            return $res["elements"];
        return array();
    }
    public function getOneItemStock($uuid) {
        $url = $this->urlInventoryApi . "item_stocks/".$uuid;
        $res = $this->getRequest($url, true);
        if (isset($res))
            return $res;
        return array();
    }

    //Function to update existing data
    public function updateItemGroup($uuid) {
        //get attributes by itemGroup
        $endPoint = $this->urlInventoryApi . "inventory/attributes?filter=itemGroup.id%".$uuid;
        $attributes = $this->getRequest($endPoint,true);
        if ($attributes) {
            $this->save_attributes($attributes);
            foreach ($attributes as $attribute) {
                $endPoint2 = $this->urlInventoryApi . "inventory/attributes/".$attribute["id"]."/options";
                $options = $this->getRequest($endPoint2,true);
                if($options){
                    $this->save_options($options);
                }
            }
        }
        return true;
    }

    public function getItemsWithoutSaving($page) {
        $per_page  = 100;
        if(defined("SOO_NB_ITEMS_PER_REQUEST")){
            $per_page = intval(SOO_NB_ITEMS_PER_REQUEST);
        }
        $url = $this->urlInventoryApi . "inventory/items?expand=tags%2CtaxRates%2CmodifierGroups%2CitemStock&limit=".$per_page."&page=".$page;
        //With Image & description
        $url = $this->urlInventoryApi . "inventory/items?expand=menuItem%2Ctags%2CtaxRates%2CmodifierGroups%2CitemStock&limit=".$per_page."&page=".$page;
        return $this->getRequest($url,true);
    }
    public function getCategoriesWithoutSaving(){
        $url = $this->urlInventoryApi . "inventory/categories?expand=items";
        return $this->getRequest($url,true);
    }
    public function getItemsPerCategoryWithoutSaving($cat_uuid) {
        $url = $this->urlInventoryApi  . "inventory/categories/".$cat_uuid."/items?expand=menuItem";
        return $this->getRequest($url, true);
    }

    public function getModifiersGroupsWithoutSaving(){
        $url = $this->urlInventoryApi  . "inventory/modifier_groups";
        return $this->getRequest($url, true);
    }

    public function getModifiersWithoutSaving() {
        $url = $this->urlInventoryApi  . "inventory/modifiers";
        return $this->getRequest($url, true);
    }
    public function getOneModifierWithoutSaving($group_uuid, $uuid) {
        $url = $this->urlInventoryApi  . "inventory/modifier_groups/".$group_uuid."/modifiers/".$uuid;
        return $this->getRequest($url, true);
    }
    public function getOneModifierGroupWithoutSaving($group_uuid,$withModifiers) {
        if($withModifiers){
            $url = $this->urlInventoryApi  . "inventory/modifier_groups/".$group_uuid."?expand=modifiers";
        } else {
            $url = $this->urlInventoryApi  . "inventory/modifier_groups/".$group_uuid;
        }
        return $this->getRequest($url, true);
    }

    public function updateOrderNote($orderId, $note)
    {
        return $this->callApi_Post("update_local_order/" . $orderId, $this->apiKey, 'note=' . urlencode($note));
    }


    //manage orders
    public function createOrder($options)
    {
        $string = $this->stringify($options);
        return $this->callApi_Post("create_order", $this->apiKey, $string);
    }

    public function assignCustomer($customer)
    {
        $res = $this->callApi_Post("assign_customer", $this->apiKey, 'customer=' . urlencode(wp_json_encode($customer)));
        return $res;
    }

    public function addlineToOrder($oid, $item_uuid, $qte, $special_ins)
    {
        return $this->callApi_Post("create_line_in_order", $this->apiKey, 'oid=' . $oid . '&item=' . $item_uuid . '&qte=' . $qte . '&special_ins=' . urlencode($special_ins));
    }

    public function addLinesToOrder($oid, $lines){
        return $this->callApi_Post("v2/create_lines", $this->apiKey, 'oid=' . $oid . '&lines=' . wp_json_encode($lines));
    }

    public function addlineWithPriceToOrder($oid, $item_uuid, $qte, $name, $price)
    {
        return $this->callApi_Post("create_line_in_order", $this->apiKey, 'oid=' . $oid . '&item=' . $item_uuid . '&qte=' . $qte . '&special_ins=&itemName=' . $name . '&itemprice=' . $price);
    }

    public function addModifierToLine($oid, $lineId, $modifer_uuid)
    {
        return $this->callApi_Post("add_modifier_to_line", $this->apiKey, 'oid=' . $oid . '&lineid=' . $lineId . '&modifier=' . $modifer_uuid);
    }

    //Pay the order
    public function  payOrder($oid, $taxAmount, $amount, $zip, $expMonth, $cvv, $last4, $expYear, $first6, $cardEncrypted, $tipAmount)
    {
        return $this->callApi_Post("pay_order", $this->apiKey, 'orderId=' . $oid . '&taxAmount=' . $taxAmount . '&amount=' . $amount . '&zip=' . $zip . '&expMonth=' . $expMonth .
            '&cvv=' . $cvv . '&last4=' . $last4 . '&first6=' . $first6 . '&expYear=' . $expYear . '&cardEncrypted=' . $cardEncrypted . '&tipAmount=' . $tipAmount);
    }
    public function  payOrderWithOptions($options)
    {
        $string = $this->stringify($options);
        return $this->callApi_Post("pay_order", $this->apiKey, $string);
    }

    //Pay the order using clover token
    public function payOrderUsingToken($payload)
    {
        $endPoint = $this->url_api_v2 . "payments/clover_token";
        $responseContent =  $this->postRequest($endPoint,wp_json_encode($payload),true);
        if($responseContent){
            return $responseContent;
        }
        return null;
    }
    //Create Order using v2 of the api
    public function getGiftCardBalance($source) {
        $endPoint = $this->url_api_v2 . "merchants/gift-cards/balance";
        return $this->postRequest($endPoint, wp_json_encode(['source'=>$source]),true);
    }

    //Create Order using v2 of the api
    public function createOrderV2($payload, $customerToken) {
        if(!empty($customerToken)){
            $endPoint = $this->url_api_v2 . "merchants/customers/orders";
            $extraHeaders = array(
                "customer_token" => $customerToken
            );
        } else {
            $endPoint = $this->url_api_v2 . "orders";
            $extraHeaders = null;
        }
        return $this->postRequest($endPoint, wp_json_encode($payload),true, $extraHeaders);
    }

    public function customerRequestsWrapper($endpoint, $payload, $authorization, $isDelete) {

        $endPoint = $this->url_api_v2 . $endpoint;

        $headers = array(
            "Accept"=>"application/json",
            "Content-Type"=>"application/json",
            "Authorization"=>"Bearer ".$this->jwt_token,
        );
        if (isset($authorization)){
            $headers["customer_token"] = $authorization;
        }
        if (isset($payload)){
            return $this->apiPost($endPoint,true, $headers, wp_json_encode($payload));
        } else {
            if ($isDelete){
                return $this->apiDelete($endPoint,true, $headers);
            }
            return $this->apiGet($endPoint,true, $headers);
        }
    }

    //Get List of orders by page
    public function getOrdersByPage($per_page, $page) {
        $page = intval($page);
        $per_page = intval($per_page);
        $page = $page > 0 ? $page : 1;
        $per_page = $per_page > 0 ? $per_page : 15;
        $endPoint = $this->url_api_v2 . "orders?page=".$page."&per_page=".$per_page;
        return $this->getRequest($endPoint,true);
    }
    public function getOneOrder($uuid) {
        $endPoint = $this->url_api_v2 . "orders/".$uuid;
        return $this->getRequest($endPoint,true);
    }

    //Save the plugin settings on the Cloud, to allow Branded Apps to get and use them
    public function saveSettings($settings, $homeUrl) {
        $endPoint = $this->url_api_v2 . "merchants/plugin-settings";
        $payload = [
            "settings"=>$settings,
            "home_url"=>$homeUrl
        ];
        return $this->postRequest($endPoint,wp_json_encode($payload),true);
    }
    //Save settings on the Cloud, to allow Branded Apps to get and use them
    public function saveMerchantSettings($name, $value) {
        $endPoint = $this->url_api_v2 . "merchants/settings";
        $payload = [
            "name"=>$name,
            "value"=>$value
        ];
        return $this->postRequest($endPoint,wp_json_encode($payload),true);
    }
    //Remove open Order from Clover
    public function removeOrderFromClover($uuid)
    {
        if(!$this->jwt_token){
            $this->getJwtToken();
        }
        if($this->jwt_token){
            $endPoint = $this->url_api_v2 . "orders/".$uuid;
            $response = wp_remote_post( $endPoint, array(
                    'method'      => 'DELETE',
                    'timeout'     => 60,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking'    => true,
                    'headers'     => array(
                        "Content-Type"=>"application/json",
                        "Authorization"=>"Bearer " . $this->jwt_token
                    ),
                    'cookies'     => array()
                )
            );

            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                if($this->debugMode){
                    echo "Something went wrong: $error_message";
                }
            } else {
                $http_code = wp_remote_retrieve_response_code( $response );
                if( $http_code === 200 ) {
                    return true;
                } else {
                    if($this->debugMode){
                        echo "Something went wrong when Removing an order: $http_code";
                    }
                }
            }

        }
        return false;
    }
    public function createTicket($payload)
    {
        $endPoint = $this->url_api_v2 . "tickets";
        $response = wp_remote_post( $endPoint, array(
                'method'      => 'POST',
                'timeout'     => 60,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(
                    "Content-Type"=>"application/json"
                ),
                'body'        => wp_json_encode($payload),
                'cookies'     => array()
            )
        );
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            if($this->debugMode){
                echo "Something went wrong: $error_message";
            }
        } else {
            $http_code = wp_remote_retrieve_response_code( $response );
            if( $http_code === 200 ) {
                $responseContent =  json_decode(wp_remote_retrieve_body( $response ));
                return $responseContent;
            } else {
                if($this->debugMode){
                    echo "Something went wrong when getting Creating  a ticket: $http_code";
                }
            }
        }
        return null;
    }

    //Send events to Soo, to clear the Branded App cache when needed
    public function sendEvent($payload) {
        try {
            $endPoint = $this->url_api_v2 . "merchants/events";
            return $this->postRequest($endPoint,wp_json_encode($payload),true);
        } catch (Exception $e){
            return true;
        }
    }
    //Send Notification to the merchant when a new order is registered
    public function NotifyMerchant($oid, $instructions, $pickup_time, $paymentMethode) {
        return $this->callApi_Post("notifyv2", $this->apiKey, 'orderId=' . $oid . '&instructions=' . urlencode($instructions) . '&pickup_time=' . $pickup_time . '&paymentmethod=' . $paymentMethode);
    }

    // OrderTypes
    public function GetOneOrdersTypes($uuid) {
        $url = $this->urlInventoryApi ."inventory/order_types/" . $uuid;
        return $this->getRequest($url, true);
    }

    public function GetOrdersTypes(){
        $url = $this->urlInventoryApi ."inventory/order_types";
        return $this->getRequest($url, true);
    }

    public function addOrderType($label, $taxable)
    {
        return $this->callApi_Post("order_types", $this->apiKey, 'label=' . $label . '&taxable=' . $taxable);
    }

    public function updateOrderType($uuid, $label, $taxable)
    {
        return $this->callApi_Post("order_types/" . $uuid, $this->apiKey, 'label=' . $label . '&taxable=' . $taxable);
    }

    //Create default Orders Types
    public function CreateOrdersTypes() {
        return $this->callApi("create_default_ot", $this->apiKey);
    }

    public function sendSmsTo($message, $phone) {
        if(!$this->jwt_token){
            $this->getJwtToken();
        }
       // $phone = str_replace('+', '00', $phone);
        $payload = array(
            "phone"=>$phone,
            "content"=>$message,
        );
        $endPoint = $this->url_api_v2 . "sms";
        $response = wp_remote_post( $endPoint, array(
                'method'      => 'POST',
                'timeout'     => 60,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(
                    "Content-Type"=>"application/json",
                    "Authorization"=>"Bearer " . $this->jwt_token
                ),
                'body'        => wp_json_encode($payload),
                'cookies'     => array()
            )
        );
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            if($this->debugMode){
                echo "Something went wrong: $error_message";
            }
        } else {
            $http_code = wp_remote_retrieve_response_code( $response );
            if( $http_code === 200 ) {
                return array(
                    "status"=>"success"
                );
            } else {
                if($this->debugMode){
                    echo "Something went wrong when Sending an SMS: $http_code";
                }
                if($http_code === 400){
                    $responseContent =  json_decode(wp_remote_retrieve_body( $response ));
                    if($this->debugMode){
                        echo $responseContent;
                    }
                } else {
                    if($http_code === 401){
                        if($this->debugMode){
                            echo "JWT token not valid";
                        }
                        $this->resetJwtToken();
                    }
                }
            }
        }
        return array(
            "status"=>"failed",
            "message"=>"",
        );
    }
    public function sendVerificationSms($code, $phone) {
        if(!$this->jwt_token){
            $this->getJwtToken();
        }
       // $phone = str_replace('+', '00', $phone);
        $payload = array(
            "phone"=>$phone,
            "code"=>$code,
        );
        $endPoint = $this->url_api_v2 . "sms/verif_sms";
        $response = wp_remote_post( $endPoint, array(
                'method'      => 'POST',
                'timeout'     => 60,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(
                    "Content-Type"=>"application/json",
                    "Authorization"=>"Bearer " . $this->jwt_token
                ),
                'body'        => wp_json_encode($payload),
                'cookies'     => array()
            )
        );
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            if($this->debugMode){
                echo "Something went wrong: $error_message";
            }
            return array(
                "status"=>"failed",
                "message"=>"We aren't able to send the verification code, please use a different number or contact the website owner",
            );
        } else {
            $http_code = wp_remote_retrieve_response_code( $response );
            if( $http_code === 200 ) {
                return array(
                    "status"=>"success"
                );
            } else {
                if($this->debugMode){
                    echo "Something went wrong when getting Sending Verification SMS: $http_code";
                }
                if($http_code === 400){
                    $responseContent =  json_decode(wp_remote_retrieve_body( $response ));
                    if($this->debugMode){
                        echo $responseContent;
                    }
                    return array(
                        "status"=>"failed",
                        "message"=>(isset($responseContent->message))?$responseContent->message:"We aren't able to send the verification, please use a different number or contact the website owner",
                    );
                } else {
                    if($http_code === 401){
                        if($this->debugMode){
                            echo "JWT token not valid";
                        }
                        $this->resetJwtToken();
                    }
                }
            }
        }
        return array(
            "status"=>"failed",
            "message"=>"",
        );
    }

    public function moo_CustomerVerifPhone($token, $phone)
    {
        return $this->callApi_Post("customers/verifphone", $this->apiKey, 'phone=' . $phone . '&token=' . $token);
    }

    public function moo_CustomerLogin($email, $password) {
        return $this->callApi_Post('customers/login', $this->apiKey, 'email=' . $email . '&password=' . $password);
    }

    public function moo_CustomerFbLogin($options)
    {
        $urlOptions = $this->stringify($options);
        return $this->callApi_Post('customers/fblogin', $this->apiKey, $urlOptions);
    }

    public function moo_CustomerSignup($options)
    {
        $urlOptions = $this->stringify($options);
        return $this->callApi_Post('customers/signup', $this->apiKey, $urlOptions);
    }

    public function moo_ResetPassword($email)
    {
        return $this->callApi_Post('customers/resetpassword', $this->apiKey, 'email=' . $email);
    }

    public function moo_GetAddresses($token)
    {
        return $this->callApi_Post('customers/getaddress', $this->apiKey, 'token=' . $token);
    }

    public function moo_GetCustomer($token)
    {
        return $this->callApi_Post('customers/get', $this->apiKey, 'token=' . $token);
    }

    public function moo_GetOrders($token, $page)
    {
        return $this->callApi_Post('customers/getorders/' . $page, $this->apiKey, 'token=' . $token);
    }

    public function moo_AddAddress($options)
    {
        $urlOptions = $this->stringify($options);
        return $this->callApi_Post('customers/setaddress', $this->apiKey, $urlOptions);
    }

    public function moo_updateCustomer($name, $email, $phone, $token)
    {
        return $this->callApi_Post('customers/update', $this->apiKey, 'token=' . $token . '&name=' . $name . '&phone=' . $phone . '&email=' . $email);
    }

    public function updateCustomerPassword($current_pass, $new_pass, $token)
    {
        return $this->callApi_Post('customers/change_password', $this->apiKey, 'token=' . $token . '&current_password=' . $current_pass . '&new_password=' . $new_pass);
    }

    public function moo_DeleteAddresses($address_id, $token)
    {
        return $this->callApi_Post('customers/deleteaddress', $this->apiKey, 'token=' . $token . '&address_id=' . $address_id);
    }
    public function moo_checkCoupon($couponCode)
    {
        return $this->callApi('coupons/' . $couponCode, $this->apiKey);
    }

    public function moo_checkCoupon_for_couponsApp($couponCode)
    {
        return $this->callApi('coupons_from_apps/' . $couponCode, $this->apiKey);
    }

    public function getCoupons($per_page, $page_number)
    {
        return $this->callApi('coupons/' . $page_number . "/" . $per_page, $this->apiKey);
    }

    public function getCoupon($code) {
        $code = urlencode(stripslashes($code));
        return $this->callApi('coupons/get/' . $code, $this->apiKey);
    }

    public function getNbCoupons()
    {
        return $this->callApi('coupons/count', $this->apiKey);
    }

    public function deleteCoupon($code)
    {
        $code = urlencode($code);
        return $this->callApi_Post('/coupons/' . $code . '/remove', $this->apiKey,"");
    }

    public function enableCoupon($code, $status)
    {
        $code = urlencode(stripslashes($code));
        return $this->callApi_Post('/coupons/' . $code . '/enable', $this->apiKey, 'status=' . $status);
    }

    public function addCoupon($coupon)
    {
        $params = "";
        foreach ($coupon as $key => $value) {
            $params .= $key . "=" . urlencode($value) . "&";
        }
        return $this->callApi_Post('/coupons/add', $this->apiKey, $params);
    }

    public function updateCoupon($code, $coupon)
    {
        $code = urlencode(stripslashes($code));
        $params = "";
        foreach ($coupon as $key => $value) {
            $params .= $key . "=" . urlencode($value) . "&";
        }
        return $this->callApi_Post('/coupons/' . $code . '/update', $this->apiKey, $params);
    }

    public  function getItemWithoutSaving($uuid)
    {
        $url = $this->urlInventoryApi . "inventory/items/" . $uuid . "?expand=menuItem%2Ctags%2CtaxRates%2CmodifierGroups%2CitemStock%2Ccategories%2Ccategories.menuSection";
        return $this->getRequest($url, true);
    }

    public  function getCategoryWithoutSaving($uuid)
    {
        $url = $this->urlInventoryApi . "inventory/categories/" . $uuid."?expand=menuSection%2Citems%2Citems.menuItem";
        return $this->getRequest($url, true);
    }

    public  function getModifierGroupsWithoutSaving($uuid)
    {
        if ($uuid == "")
            return false;
        $url = $this->urlInventoryApi . "inventory/modifier_groups/" . $uuid;
        return $this->getRequest($url, true);
    }

    public  function getModifierWithoutSaving($mg_uuid, $uuid) {
        if ($uuid == "" || $mg_uuid == "")
            return false;
        $url = $this->urlInventoryApi . "inventory/modifier_groups/" . $mg_uuid . '/modifiers/' . $uuid;
        return $this->getRequest($url, true);
    }

    public  function getTaxRateWithoutSaving($uuid) {
        if ($uuid == "")
            return false;
        $url = $this->urlInventoryApi . "inventory/tax_rates/" . $uuid;
        return $this->getRequest($url, true);
    }

    public  function getOrderTypesWithoutSaving() {
        $url = $this->urlInventoryApi . "inventory/order_types";
        return $this->getRequest($url, true);
    }

    function getTaxesRatesWithoutSaving() {
        $url = $this->urlInventoryApi . "inventory/tax_rates";
        return $this->getRequest($url, true);
    }


    public function delete_item($uuid) {
        if ($uuid == "")
            return false;
        global $wpdb;
        $wpdb->hide_errors();
        $wpdb->query('START TRANSACTION');

        $wpdb->delete("{$wpdb->prefix}moo_item_tax_rate", array('item_uuid' => $uuid));
        $wpdb->delete("{$wpdb->prefix}moo_item_modifier_group", array('item_id' => $uuid));
        $wpdb->delete("{$wpdb->prefix}moo_item_tag", array('item_uuid' => $uuid));
        $wpdb->delete("{$wpdb->prefix}moo_images", array('item_uuid' => $uuid));

        //TODO : delete all attribute and options if it is the only item in the group_item

        $res = $wpdb->delete("{$wpdb->prefix}moo_item", array('uuid' => $uuid));
        if ($res) {
            $wpdb->query('COMMIT'); // if the item Inserted in the DB
        } else {
            $wpdb->query('ROLLBACK'); // // something went wrong, Rollback
        }
        return $res;

    }

    /**
     * This function will take an object item as a parameter and then update it in the local database.
     * with checking of tax rate categories and modifiers
     * @param $item Array
     * @return bool
     */
    public function syncCloverItem($item) {
        global $wpdb;
        // $wpdb->show_errors();
        $withCategories = isset($item["categories"]);
        $existingSortOrders = [];
        $wpdb->query('START TRANSACTION');
        try {
            $currentItem = $this->getItem($item['id']);
            if ($currentItem) {
                $wpdb->delete("{$wpdb->prefix}moo_item_tax_rate", array('item_uuid' => $item['id']));
                $wpdb->delete("{$wpdb->prefix}moo_item_modifier_group", array('item_id' => $item['id']));
                $wpdb->delete("{$wpdb->prefix}moo_item_tag", array('item_uuid' => $item['id']));

                if ($withCategories) {
                    $existingSortOrders = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT category_uuid, sort_order FROM {$wpdb->prefix}moo_items_categories WHERE item_uuid = %s",
                            $item['id']
                        ),
                        OBJECT_K
                    );
                    $wpdb->delete("{$wpdb->prefix}moo_items_categories", array('item_uuid' => $item['id']));
                }

                $itemProps = array(
                    'name' => isset($item["name"]) ? esc_sql($item["name"]) : $currentItem["name"],
                    'alternate_name' => isset($item["alternateName"]) ? esc_sql($item["alternateName"]) : $currentItem["alternate_name"],
                    'price' => isset($item["price"]) ? esc_sql($item["price"]) : $currentItem["price"],
                    'code' => isset($item["code"]) ? esc_sql($item["code"]) : $currentItem["code"],
                    'price_type' => isset($item["priceType"]) ? esc_sql($item["priceType"]) : $currentItem["price_type"],
                    'unit_name' => isset($item["unitName"]) ? esc_sql($item["unitName"]) : $currentItem["unit_name"],
                    'default_taxe_rate' => isset($item["defaultTaxRates"]) ? esc_sql($item["defaultTaxRates"]) : $currentItem["default_taxe_rate"],
                    'sku' => isset($item["sku"]) ? esc_sql($item["sku"]) : $currentItem["sku"],
                    'hidden' => isset($item["hidden"]) ? esc_sql($item["hidden"]) : $currentItem["hidden"],
                    'is_revenue' => isset($item["isRevenue"]) ? esc_sql($item["isRevenue"]) : $currentItem["is_revenue"],
                    'cost' => isset($item["cost"]) ? esc_sql($item["cost"]) : $currentItem["cost"],
                    'available' => isset($item["available"]) ? esc_sql($item["available"]) : $currentItem["available"],
                    'modified_time' => isset($item["modifiedTime"]) ? esc_sql($item["modifiedTime"]) : $currentItem["modified_time"],
                );
                // update the Item
                 $wpdb->update("{$wpdb->prefix}moo_item", $itemProps, array('uuid' => $item['id']));
            } else {
                $itemProps = array(
                    'uuid' => esc_sql($item['id']),
                    'name' => esc_sql($item['name']),
                    'soo_name' => !empty($item['menuItem']['name']) ? esc_sql($item['menuItem']['name']) : null,
                    'description' => !empty($item['menuItem']['description']) ? esc_sql($item['menuItem']['description']) : null,
                    'visible' => !empty($item['menuItem']['enabled']) ? esc_sql($item['menuItem']['enabled']) : 1,
                    'alternate_name' => esc_sql($item['alternateName']),
                    'price' => esc_sql($item['price']),
                    'code' => esc_sql($item['code']),
                    'price_type' => esc_sql($item['priceType']),
                    'unit_name' => esc_sql($item['unitName']),
                    'default_taxe_rate' => esc_sql($item['defaultTaxRates']),
                    'sku' => esc_sql($item['sku']),
                    'hidden' => esc_sql($item['hidden']),
                    'is_revenue' => esc_sql($item['isRevenue']),
                    'cost' => esc_sql($item['cost']),
                    'available' => esc_sql($item['available']),
                    'modified_time' => esc_sql($item['modifiedTime']),
                );

                if (isset($item['itemGroup'])) {
                    $itemProps['item_group_uuid'] = $item['itemGroup']['id'];
                }
                $wpdb->insert("{$wpdb->prefix}moo_item", $itemProps);
            }

            //Save the tax rates
            if(!empty($item['taxRates']['elements'])) {
                foreach ($item['taxRates']['elements'] as $tax_rate) {
                    if (!$this->taxRateExists($tax_rate['id'])){
                        $this->save_one_tax_rate($tax_rate);
                    }

                    $wpdb->insert("{$wpdb->prefix}moo_item_tax_rate", array(
                        'tax_rate_uuid' => $tax_rate['id'],
                        'item_uuid' => $item['id']
                    ));
                }
            }

            //save modifierGroups
            if(!empty($item['modifierGroups']['elements'])) {
                foreach ($item['modifierGroups']['elements'] as $modifier_group) {
                    if ($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}moo_modifier_group where uuid='{$modifier_group['id']}'") == 0) {
                        $this->getOneModifierGroups($modifier_group['id']);
                    }
                    $wpdb->insert("{$wpdb->prefix}moo_item_modifier_group", array(
                        'group_id' => $modifier_group['id'],
                        'item_id' => $item['id']
                    ));
                }
            }

            //save Tags
            if(!empty($item['tags']['elements'])) {
                foreach ($item['tags']['elements'] as $tag) {
                    if ($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}moo_tag where uuid='{$tag['id']}'") == 0) {
                        $this->save_one_tag($tag);
                    }
                    $wpdb->insert("{$wpdb->prefix}moo_item_tag", array(
                        'tag_uuid' => $tag['id'],
                        'item_uuid' => $item['id']
                    ));
                }
            }

            //save Categories
            if ($withCategories && !empty($item['categories']['elements'])) {
                foreach ($item['categories']['elements'] as $category) {
                    if ( ! $this->categoryExists($category['id']) ) {
                        $cloverCategory = $this->getCategoryWithoutSaving($category['id']);
                        $this->insertOrUpdateCategory($cloverCategory);
                    } else {
                        // Get the preserved sort_order if it exists
                        $sortOrder = isset($existingSortOrders[$category['id']])
                            ? $existingSortOrders[$category['id']]->sort_order
                            : null;

                        $wpdb->query($wpdb->prepare(
                            "INSERT IGNORE INTO {$wpdb->prefix}moo_items_categories (category_uuid, item_uuid,sort_order) VALUES (%s, %s, %d)",
                            $category['id'],
                            $item['id'],
                            $sortOrder
                        ));
                    }
                }
            }
            $wpdb->query('COMMIT');
            return true;
        } catch (Exception $e){
            $wpdb->query('ROLLBACK'); // // something went wrong, Rollback
            return false;
        }
    }

    /*
     * Function to send Order details via email,
     * @from : v 1.2.8
     * @param : the order id
     * @param : the merchant email
     * @param : the customer email
     */
    public function sendOrderEmails($order_id, $merchant_emails, $customer_email) {
        return $this->callApi_Post("send_order_emails", $this->apiKey, "order_id=" . $order_id . "&merchant_emails=" . urlencode($merchant_emails) . "&customer_email=" . urlencode($customer_email));
    }

    public function checkToken() {
        $url = $this->url_api . "checktoken";
        return $this->getRequest($url);
    }
    public function checkAnyToken($token) {
        $endPoint = $this->url_api_v2 . "auth/login";
        $body = array(
            'api_key' => $token
        );
        $response = wp_remote_post( $endPoint, array(
                'method'      => 'POST',
                'timeout'     => 60,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array(
                    "Content-Type"=>"application/json",
                    "Accept"=>"application/json",
                ),
                'body'        => wp_json_encode($body),
                'cookies'     => array()
            )
        );
        if (  ! is_wp_error( $response ) ) {
            return  wp_remote_retrieve_response_code( $response ) === 200;
        }
        return false;
    }
    public function checkApiKey($body) {
        $url = $this->url_api_v2 . "check_api_key";
        $args = array(
            "body" => wp_json_encode($body)
        );
        return $this->sendHttpRequest($url,"POST",$args);
    }
    public function getOrderDetails($orderFromServer){
            $result['uuid_order'] = $orderFromServer->order->uuid;
            $result['amount_order'] = $orderFromServer->order->amount / 100;
            $result['order_type'] = $orderFromServer->order->order_type;
            $result['special_instruction'] = $orderFromServer->order->special_instruction;
            $result['coupon'] = $orderFromServer->coupon;

            if ($orderFromServer->order->date != "") {
                $result['date_order'] = gmdate('m/d/Y', $orderFromServer->order->date / 1000);
            }
            if ($orderFromServer->order->taxRemoved == "1") {
                $result['taxRemoved'] = true;
            } else {
                $result['taxRemoved'] = false;
            }

            if (isset($orderFromServer->order->paymentMethode) && $orderFromServer->order->paymentMethode != "") {
                $result['paymentMethode'] = $orderFromServer->order->paymentMethode;
            } else {
                $result['paymentMethode'] = "No";
            }
            if (isset($orderFromServer->order->taxAmount) && $orderFromServer->order->taxAmount != "") {
                $result['taxAmount'] = $orderFromServer->order->taxAmount / 100;
            } else {
                $result['taxAmount'] = 0;
            }

            if (isset($orderFromServer->order->deliveryAmount) && $orderFromServer->order->deliveryAmount != "") {
                $result['deliveryAmount'] = $orderFromServer->order->deliveryAmount / 100;
            } else {
                $result['deliveryAmount'] = 0;
            }
            if (isset($orderFromServer->order->serviceFee) && $orderFromServer->order->serviceFee != "") {
                $result['serviceFee'] = $orderFromServer->order->serviceFee / 100;
            } else {
                $result['serviceFee'] = 0;
            }

            if (isset($orderFromServer->order->deliveryName) && $orderFromServer->order->deliveryName != "" && $orderFromServer->order->deliveryName != "null" && $orderFromServer->order->deliveryName != null) {
                $result['deliveryName'] = $orderFromServer->order->deliveryName;
            } else {
                $result['deliveryName'] = "Delivery Charges";
            }
            if (isset($orderFromServer->order->serviceFeeName) && $orderFromServer->order->serviceFeeName != "" && $orderFromServer->order->serviceFeeName != "null" && $orderFromServer->order->serviceFeeName != null) {
                $result['serviceFeeName'] = $orderFromServer->order->serviceFeeName;
            } else {
                $result['serviceFeeName'] = "Service Charges";
            }

            if (isset($orderFromServer->order->tipAmount) && $orderFromServer->order->tipAmount != "") {
                $result['tipAmount'] = $orderFromServer->order->tipAmount / 100;
                $result['amount_order'] += $result['tipAmount'];
            } else {
                $result['tipAmount'] = 0;
                $result['amount_order'] += $result['tipAmount'];
            }
            if (isset($orderFromServer->customer->name) && $orderFromServer->customer->name != "") {
                $result['name_customer'] = $orderFromServer->customer->name;
            } else {
                $result['name_customer'] = '';
            }
            if (isset($orderFromServer->customer->email) && $orderFromServer->customer->email != "") {
                $result['email_customer'] = $orderFromServer->customer->email;
            } else {
                $result['email_customer'] = '';
            }
            if (isset($orderFromServer->customer->phone) && $orderFromServer->customer->phone != "") {
                $result['phone_customer'] = $orderFromServer->customer->phone;
            } else {
                $result['phone_customer'] = '';
            }
            if (isset($orderFromServer->customer->address) && $orderFromServer->customer->address == "") {
                $result['address_customer'] = $orderFromServer->customer->address;
            } else {
                $result['address_customer'] = '';
            }
            if (isset($orderFromServer->customer->city) && $orderFromServer->customer->city != "") {
                $result['city_customer'] = $orderFromServer->customer->city;
            } else {
                $result['city_customer'] = '';
            }
            if ($orderFromServer->customer->state && $orderFromServer->customer->state != "") {
                $result['state_customer'] = $orderFromServer->customer->state;
            } else {
                $result['state_customer'] = '';
            }
            if (isset($orderFromServer->customer->zipcode) && $orderFromServer->customer->zipcode != "") {
                $result['zipcode'] = $orderFromServer->customer->zipcode;
            } else {
                $result['zipcode'] = '';
            }
            if (isset($orderFromServer->customer->lat) && $orderFromServer->customer->lat != "") {
                $result['lat'] = $orderFromServer->customer->lat;
            } else {
                $result['lat'] = '';
            }
            if (isset($orderFromServer->customer->lng) && $orderFromServer->customer->lng != "") {
                $result['lng'] = $orderFromServer->customer->lng;
            } else {
                $result['lng'] = '';
            }
            $result['payments'] = $orderFromServer->payments;

        return $result;
    }

    //Functions to save DATA in db
    public function save_items($items) {
        global $wpdb;
        $wpdb->hide_errors();
        $count = 0;
        $exist = 0;
        $errors = 0;
        foreach ($items as $item) {
            if (!$item || !isset($item["id"])) {
                continue;
            }
            $itemUuid = esc_sql($item["id"]);

            // Check if item already exists
            $existingItem = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}moo_item WHERE uuid = %s",
                $itemUuid
            ));
            if ($existingItem) {
                $exist++;
                continue; // Skip if item already exists
            }

            $itemProps = array(
                'uuid' => $itemUuid,
                'name' => esc_sql(!empty($item["name"]) ? $item["name"] : ''),
                'soo_name' => esc_sql(!empty($item["menuItem"]["name"]) ? $item["menuItem"]["name"] : null),
                'description' => esc_sql(!empty($item["menuItem"]["description"]) ? $item["menuItem"]["description"] : null),
                'visible' => esc_sql(!empty($item["menuItem"]["enabled"]) ? (bool)$item["menuItem"]["enabled"] : true),
                'alternate_name' => esc_sql(!empty($item["alternateName"]) ? $item["alternateName"] : ''),
                'price' => esc_sql(!empty($item["price"]) ? $item["price"] : 0),
                'code' => esc_sql(!empty($item["code"]) ? $item["code"] : ''),
                'price_type' => esc_sql(!empty($item["priceType"]) ? $item["priceType"] : ''),
                'unit_name' => esc_sql(!empty($item["unitName"]) ? $item["unitName"] : ''),
                'default_taxe_rate' => esc_sql(!empty($item["defaultTaxRates"]) ? $item["defaultTaxRates"] : ''),
                'sku' => esc_sql(!empty($item["sku"]) ? $item["sku"] : ''),
                'hidden' => esc_sql(!empty($item["hidden"]) ? $item["hidden"] : 0),
                'is_revenue' => esc_sql(!empty($item["isRevenue"]) ? $item["isRevenue"] : 0),
                'cost' => esc_sql(!empty($item["cost"]) ? $item["cost"] : 0),
                'available' => esc_sql(!empty($item["available"]) ? $item["available"] : 0),
                'modified_time' => esc_sql(!empty($item["modifiedTime"]) ? $item["modifiedTime"] : ''),
            );


            if (isset($item["itemGroup"])){
                $itemProps['item_group_uuid'] = esc_sql($item["itemGroup"]["id"]);
            }
            try {
                //Save the item
                $wpdb->insert("{$wpdb->prefix}moo_item",$itemProps);

                if ($wpdb->insert_id) {
                    $count++;
                }

                //save the tax rates
                foreach ($item["taxRates"]["elements"] as $tax_rate) {
                    $wpdb->insert("{$wpdb->prefix}moo_item_tax_rate", array(
                        'tax_rate_uuid' => $tax_rate["id"],
                        'item_uuid' => $item["id"]
                    ));
                }

                //save modifierGroups
                foreach ($item["modifierGroups"]["elements"] as $modifier_group) {
                    $wpdb->insert("{$wpdb->prefix}moo_item_modifier_group", array(
                        'group_id' => $modifier_group["id"],
                        'item_id' => $item["id"]
                    ));
                }

                //save Tags
                foreach ($item["tags"]["elements"]  as $tag) {
                    $wpdb->insert("{$wpdb->prefix}moo_item_tag", array(
                        'tag_uuid' => $tag["id"],
                        'item_uuid' => $item["id"]
                    ));
                }

                //Save Item Image
                if (!empty($item["menuItem"]["imageFilename"])) {
                    $link = Moo_OnlineOrders_Helpers::uploadFileByUrl($item["menuItem"]["imageFilename"]);
                    if($link){
                        $wpdb->insert("{$wpdb->prefix}moo_images",array(
                            "item_uuid"=>$item["id"],
                            "url"=>$link,
                            "is_default"=>1,
                            "is_enabled"=>1
                        ));
                    }
                }
            } catch (Exception $e){
                $errors++;
            }
        }
        return [
            'count' => $count,
            'exist' => $exist,
            'errors' => $errors
        ];
    }
    private function save_tax_rates($taxRates) {
        global $wpdb;
        // $wpdb->show_errors();
        $wpdb->hide_errors();
        $count = 0;
        foreach ($taxRates as $tax_rate) {
            if($this->save_one_tax_rate($tax_rate)){
                $count++;
            }
        }
        return $count;
    }
    private function save_one_tax_rate($tax_rate) {
        global $wpdb;
        // $wpdb->show_errors();
        $wpdb->hide_errors();
        try {
            $result = $wpdb->insert("{$wpdb->prefix}moo_tax_rate", array(
                'uuid' => $tax_rate["id"],
                'name' => $tax_rate["name"],
                'rate' => $tax_rate["rate"],
                'is_default' => $tax_rate["isDefault"],
                'taxAmount' => $tax_rate["taxAmount"],
                'taxType' => $tax_rate["taxType"],
            ));
            return $result > 0;
        } catch (Exception $e){}
        return false;
    }
    private function save_tags($tags) {
        global $wpdb;
        // $wpdb->show_errors();
        $wpdb->hide_errors();
        $count = 0;
        foreach ($tags as $tag) {
            try {
                if ($this->save_one_tag($tag)){
                    $count++;
                }
            } catch (Exception $e){}
        }
        return $count;
    }
    private function save_one_tag($tag) {
        global $wpdb;
        // $wpdb->show_errors();
        $wpdb->hide_errors();
        try {
            $result = $wpdb->insert("{$wpdb->prefix}moo_tag", array(
                'uuid' => $tag["id"],
                'name' => $tag["name"]
            ));

            return  $result > 0;
        } catch (Exception $e){}

        return false;
    }
    private function save_options($options) {
        global $wpdb;
        //$wpdb->show_errors();
        $wpdb->hide_errors();
        $count = 0;
        foreach ($options as $option) {
            try {
                $result = $wpdb->insert("{$wpdb->prefix}moo_option", array(
                    'uuid' => $option["id"],
                    'name' => $option["name"],
                    'attribute_uuid' => $option["attribute"]["id"]
                ));
                if ($result > 0) $count++;
            } catch (Exception $e){}
        }

        return $count;
    }
    private function save_attributes($attributes) {
        global $wpdb;
        //$wpdb->show_errors();
        $wpdb->hide_errors();
        $count = 0;
        foreach ($attributes as $attribute) {
            try {
                $result = $wpdb->insert("{$wpdb->prefix}moo_attribute", array(
                    'uuid' => $attribute["id"],
                    'name' => $attribute["name"],
                    'item_group_uuid' => $attribute["itemGroup"]["id"]
                ));
                if ($result > 0) $count++;
            } catch (Exception $e){}
        }
        return $count;
    }
    private function save_modifiers($modifiers)  {
        global $wpdb;
        // $wpdb->show_errors();
        $wpdb->hide_errors();
        $count = 0;
        foreach ($modifiers as $modifier) {
            try {
                $result = $wpdb->insert("{$wpdb->prefix}moo_modifier", array(
                    'uuid' => $modifier["id"],
                    'name' => $modifier["name"],
                    'alternate_name' => (isset($modifier["alternateName"]))?$modifier["alternateName"]:"",
                    'price' => $modifier["price"],
                    'group_id' => $modifier["modifierGroup"]["id"]
                ));
                if ($result > 0) $count++;
            } catch (Exception $e){}

        }
        return $count;
    }
    private function save_modifier_groups($modifier_groups) {
        global $wpdb;
        $wpdb->hide_errors();
        $count = 0;
        foreach ($modifier_groups as $modifier_group) {
            try {
                $result = $wpdb->insert("{$wpdb->prefix}moo_modifier_group", array(
                    'uuid' => $modifier_group["id"],
                    'name' => $modifier_group["name"],
                    'alternate_name' => $modifier_group["alternateName"],
                    'show_by_default' => $modifier_group["showByDefault"],
                    'min_required' => $modifier_group["minRequired"],
                    'max_allowd' => $modifier_group["maxAllowed"]
                ));
                if ($result > 0) $count++;
            } catch (Exception $e){}
        }
        return $count;

    }
    private function save_item_groups($item_groups) {
        global $wpdb;
        $wpdb->hide_errors();
        $count = 0;
        foreach ($item_groups as $item_group) {
            try {
                $result = $wpdb->insert("{$wpdb->prefix}moo_item_group", array(
                    'uuid' => $item_group["id"],
                    'name' => $item_group["name"]
                ));
                if ($result > 0) $count++;
            } catch (Exception $e){}

        }
        return $count;
    }

    public function insertOrUpdateCategory($cat) {
        global $wpdb;
        $wpdb->hide_errors();
        if(isset($cat["items"]) && isset($cat["items"]["elements"]) && count($cat["items"]["elements"])>=100) {
            $items = $this->getItemsPerCategoryWithoutSaving($cat["id"]);
            $cat["items"] = array("elements"=>$items);
        }
        try {
            $currentCategory = $this->getCategory($cat["id"]);
            if ($currentCategory) {
                $wpdb->update("{$wpdb->prefix}moo_category", array(
                    'name' => $cat["name"],
                    'items' => null,
                   // 'alternate_name' => (!empty($cat["menuSection"]["name"])) ? $cat["menuSection"]["name"]:$currentCategory['alternate_name'],
                    'items_imported' => 1,
                ), array('uuid' => $cat["id"]));
            } else {
                $wpdb->insert("{$wpdb->prefix}moo_category", array(
                    'uuid' => $cat["id"],
                    'name' => $cat["name"],
                    'sort_order' => $cat["sortOrder"],
                    'alternate_name' => (!empty($cat["menuSection"]["name"])) ? $cat["menuSection"]["name"]:null,
                    'show_by_default' => 1,
                    'items' => null,
                    'items_imported' => 1,
                ));
            }
            foreach ($cat["items"]["elements"] as $item) {
                try {
                   if ($this->itemExists($item["id"])) {
                       $this->insertCategoryItemRelation($cat["id"], $item["id"]);
                   } else {
                       $cloverItem = $this->getItemWithoutSaving($item["id"]);
                       $this->syncCloverItem($cloverItem);
                   }
                } catch (Exception $e){}
            }
        } catch (Exception $e){}
        return true;
    }
    private function save_order_types($ordertypes) {
        global $wpdb;
        $wpdb->hide_errors();
        $count = 0;
        foreach ($ordertypes as $ot) {
            try {
                $result = $wpdb->insert("{$wpdb->prefix}moo_order_types", array(
                    'ot_uuid' => $ot["id"],
                    'label' => $ot["label"],
                    'taxable' => $ot["taxable"],
                    'minAmount' => 0,
                    'show_sa' => (trim($ot["label"]) == 'Online Order Delivery') ? 1 : 0,
                    'status' => (trim($ot["label"]) == 'Online Order Pick Up') ? 1 : 0
                ));
                if ($result > 0)
                    $count++;
            } catch (Exception $e){}

        }
        return $count;
    }
    public function save_One_orderType($uuid, $label, $taxable, $minAmount, $show_sa) {
        global $wpdb;
        try {
            $res = $wpdb->insert("{$wpdb->prefix}moo_order_types", array(
                'ot_uuid' => $uuid,
                'label' => esc_sql($label),
                'taxable' => (($taxable == "true") ? "1" : "0"),
                'status' => 1,
                'show_sa' => (($show_sa == "true") ? "1" : "0"),
                'minAmount' => floatval($minAmount),
            ));
            return $res;
        } catch (Exception $e){
            return false;
        }

    }

    //Hours endpoints
    //get hour
    public function getAllCustomHours($type){
        $url = $this->hours_url_api."allhours?type=".$type;
        $response = $this->getRequest($url,false);
        return $response;
    }
    public function getMerchantCustomHours($type){
        $url = $this->hours_url_api."hours?type=".$type;
        $response = $this->getRequest($url);
        return $response;
    }
    public function getMerchantCustomHoursStatus($type){
        $url = $this->hours_url_api."hours/check?type=".$type;
        $response = $this->getRequest($url);
        return $response;
    }


    public function goToReports() {
        $dashboard_url = admin_url('/admin.php?page=moo_index');
        $newURL = "https://dashboard.smartonlineorder.com/#/login/" . $this->apiKey . "?redirectTo=" . $dashboard_url;
        header('Location: ' . $newURL);
        die();
    }
    public function goToSooDash() {
        $home_url = get_home_url();
        $dashboard_url = admin_url('/admin.php?page=moo_index');
        $newURL = "https://v2.dashboard.smartonlineorder.com/auth/register?redirectTo=" . $dashboard_url;
        //Get Merchant
        $me = $this->getConnectedMerchant();
        if (isset($me) && is_array($me)){
            if (isset($me["groupMerchantSlug"])){
                $newURL = "https://v2.dashboard.smartonlineorder.com/auth/login?redirectTo=" . $dashboard_url;
            }
        }
        header('Referer: ' . $home_url);
        header('Location: ' . $newURL);
        die();
    }
    public function stringify($options){
        $string = '';
        foreach ($options as $key=>$value) {
            $string .= $key."=".urlencode($value)."&";
        }
        return $string;
    }

    public function getRequest($url, $withJwt = false) {
        if($withJwt) {
            if($this->jwt_token){
                $headers = array(
                    "Accept"=>"application/json",
                    "Content-Type"=>"application/json",
                    "Authorization"=>"Bearer ".$this->jwt_token,
                );
            } else {
                $this->getJwtToken();
                $headers = array(
                    "Accept"=>"application/json",
                    "Content-Type"=>"application/json",
                    "Authorization"=>"Bearer ".$this->jwt_token,
                );
            }
        } else {
            $headers = array(
                "Accept"=>"application/json",
                "X-Authorization"=>$this->apiKey,
            );
        }
        return $this->apiGet($url,$withJwt, $headers);
    }
    public function postRequest($url, $body, $withJwt = false, $extraHeaders=null) {
        if($withJwt) {
            if(!$this->jwt_token) {
                $this->getJwtToken();
            }
            $headers = array(
                "Accept"=>"application/json",
                "Content-Type"=>"application/json",
                "Authorization"=>"Bearer ".$this->jwt_token,
            );
        } else {
            $headers = array(
                "Accept"=>"application/json",
                "X-Authorization"=>$this->apiKey,
            );
        }

        if($extraHeaders && is_array($extraHeaders)) {
            $headers = array_merge($headers,$extraHeaders);
        }
        return $this->apiPost($url,$withJwt, $headers, $body);
    }

    private function callApi($url, $accesstoken) {
        $args = array(
            "headers"=> array(
                'X_CLIENT_IP' => $this->getClientIp(),
                'Accept' => "application/json",
                'X-Authorization' => $accesstoken
            ),
        );
        $endpoint = $this->url_api . $url;
        $response = $this->sendHttpRequest($endpoint,"GET",$args);
        if($response && is_array($response)) {
            if($response["httpCode"] === 200 || $response["httpCode"] === 404 ){
                return $response["responseContent"];
            }
        }
        return false;
    }
    private function callApi_Post($url, $accesstoken, $fields_string) {
        $headr = array();
        $headr[] = 'Accept: application/json';
        $headr[] = 'X-Authorization: ' . $accesstoken;
        $url = $this->url_api . $url;

        //cURL starts
        $crl = curl_init();
        curl_setopt($crl, CURLOPT_URL, $url);
        curl_setopt($crl, CURLOPT_HTTPHEADER, $headr);
        curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($crl, CURLOPT_POST, true);
        curl_setopt($crl, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($crl, CURLOPT_SSL_VERIFYPEER, (get_option('soo_ssl_verify','true') === 'true'));
        curl_setopt($crl, CURLOPT_FOLLOWLOCATION, true);

        $reply = curl_exec($crl);
        //error handling for cURL
        if ($reply === false) {
            if(curl_error($crl) === 'SSL certificate problem: certificate has expired'){
                //Expired WordPress certificate
                update_option('soo_ssl_verify','false');
            }
            if ($this->debugMode){
                print_r('Curl error: ' . curl_error($crl) . ' URL : '. $url);
            }
        }

        $info = curl_getinfo($crl);
        curl_close($crl);
        if ($this->debugMode) {
            echo "\n POST " . " " . $info['http_code'] . " " . $url . " <<";
            echo $reply;
            echo ">> ";
            echo $fields_string;
            echo "<< ";
        }
        if ($info['http_code'] == 200)
            return $reply;
        return false;
    }

    /**
     * To send get request to Zaytech's API
     * @param $url
     * @param $withJwt
     * @param $headers
     * @return bool|array
     */

    private function apiGet($url, $withJwt, $headers) {
        $headers = array_merge($headers,array(
            'X_CLIENT_IP' => $this->getClientIp()
        ));
        $args = array(
            "headers"=> $headers
        );
        $response = $this->sendHttpRequest($url,"GET",$args);
        if($response && is_array($response)) {
            if($response["httpCode"] === 200 || $response["httpCode"] === 404 ){
                return json_decode($response["responseContent"],true);
            } else {
                //Reset JWT Code for 400 errors
                if($withJwt && $response["httpCode"] > 400 && $response["httpCode"] < 500 ){
                    $this->resetJwtToken();
                    $this->getJwtToken();
                    $response = $this->sendHttpRequest($url,"POST",$args);
                    if($response && is_array($response)) {
                        if($response["httpCode"] === 200 || $response["httpCode"] === 404 ) {
                            return json_decode($response["responseContent"],true);
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * To send delete request to Zaytech's API
     * @param $url
     * @param $withJwt
     * @param $headers
     * @return bool|array
     */

    private function apiDelete($url, $withJwt, $headers) {
        $headers = array_merge($headers,array(
            'X_CLIENT_IP' => $this->getClientIp()
        ));
        $args = array(
            "headers"=> $headers
        );
        $response = $this->sendHttpRequest($url,"DELETE",$args);
        if($response && is_array($response)){
            if($response["httpCode"] === 200 || $response["httpCode"] === 404 ){
                return json_decode($response["responseContent"],true);
            } else {
                //Reset JWT Code for 400 errors
                if($withJwt && $response["httpCode"] > 400 && $response["httpCode"] < 500 ){
                    $this->resetJwtToken();
                    $this->getJwtToken();
                    $response = $this->sendHttpRequest($url,"POST",$args);
                    if($response && is_array($response)) {
                        if($response["httpCode"] === 200 || $response["httpCode"] === 404 ) {
                            return json_decode($response["responseContent"],true);
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * To send post requests to Smart Online Order api
     * @param $url
     * @param $withJwt
     * @param $headers
     * @param $body
     * @return bool|mixed
     */
    private function apiPost($url, $withJwt, $headers, $body) {

        $headers = array_merge($headers,array(
            'X_CLIENT_IP' => $this->getClientIp()
        ));

        $args = array(
            "headers"=> $headers,
            "body" => $body
        );

        $response = $this->sendHttpRequest($url,"POST",$args);
        if($response && is_array($response)){
            if($response["httpCode"] === 200 || $response["httpCode"] === 404) {
                return json_decode($response["responseContent"],true);
            } else {
                if($withJwt && $response["httpCode"] > 400 && $response["httpCode"] < 500  ){
                    $this->resetJwtToken();
                    $this->getJwtToken();
                    $args["headers"]['Authorization'] = "Bearer ".$this->jwt_token;
                    $response = $this->sendHttpRequest($url,"POST",$args);
                    if($response && is_array($response)){
                        if($response["httpCode"] === 200 || ($response["httpCode"] > 400 && $response["httpCode"] < 500)){
                            return json_decode($response["responseContent"],true);
                        }
                    }
                }
            }
        } else {
            if($this->debugMode){
                echo "Something went wrong: ";
            }
        }
        return false;
    }
    private function sendHttpRequest($url, $method, $args, $retry = 1) {
        $defaultArgs = array(
            'method'      => $method,
            'timeout'     => 60,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'cookies'     => array(),
            //'sslverify'     => (get_option('soo_ssl_verify','true') === 'true')
        );
        $allArgs = array_merge($defaultArgs, $args);
        $response = wp_remote_request($url,$allArgs);
        if(is_wp_error( $response )){
            if($this->debugMode){
                echo "Something went wrong: ".$response->get_error_message();
            }
            $this->last_error['message']    = $response->get_error_message();
            $this->last_error['error_code'] = $response->get_error_code();
            $this->last_error['url'] = $url;
            $this->last_error['method'] = $method;
            //Retry 500 errors
            if ($response->get_error_code()  === 500  && $retry <= 3){
                return $this->sendHttpRequest($url,$method,$args,++$retry);
            }
            return false;
        } else {
            $result = array(
                "httpCode"=> wp_remote_retrieve_response_code( $response ),
                "responseContent"=> wp_remote_retrieve_body( $response ),
            );
            $this->last_error = $result;
            $this->last_error['url'] = $url;
            $this->last_error['method'] = $method;
            return $result;
        }
    }
    private function getClientIp(){

        if( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && !empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ){
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if( isset( $_SERVER['HTTP_CLIENT_IP'] ) && !empty( $_SERVER['HTTP_CLIENT_IP'] ) ){
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ){
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }

    private function taxRateExists($taxRateId)
    {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}moo_tax_rate WHERE uuid = %s",
            $taxRateId
        );
        return $wpdb->get_var($query) > 0;
    }

    private function itemExists($itemId)
    {
        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}moo_item WHERE uuid = %s",
            $itemId
        );
        return $wpdb->get_var($query) > 0;
    }
    private function getItem($itemId) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moo_item WHERE uuid = %s",
            $itemId
        );

        return $wpdb->get_row($query, ARRAY_A); // Returns the category as an associative array, or null if not found.
    }

    private function categoryExists($categoryId) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}moo_category WHERE uuid = %s",
            $categoryId
        );

        return $wpdb->get_var($query) > 0;
    }

    private function getCategory($categoryId) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}moo_category WHERE uuid = %s",
            $categoryId
        );

        return $wpdb->get_row($query, ARRAY_A); // Returns the category as an associative array, or null if not found.
    }

    private function insertCategoryItemRelation($categoryId, $itemId) {
        global $wpdb;

        $table = "{$wpdb->prefix}moo_items_categories";

        $query = $wpdb->prepare(
            "INSERT IGNORE INTO {$table} (category_uuid, item_uuid) VALUES (%s, %s)",
            $categoryId,
            $itemId
        );

        return $wpdb->query($query);
    }

}