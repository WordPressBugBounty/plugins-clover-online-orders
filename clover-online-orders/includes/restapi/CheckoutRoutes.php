<?php
/**
 * Created by Mohammed EL BANYAOUI.
 * Sync route to handle all requests to sync the inventory with Clover
 * User: Smart MerchantApps
 * Date: 3/5/2019
 * Time: 12:23 PM
 */
require_once "BaseRoute.php";

class  CheckoutRoutes extends BaseRoute {
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
     * The SESSION
     * @since    1.3.2
     * @access   private
     * @var MOO_SESSION
     */
    private $session;

    /**
     * CustomerRoutes constructor.
     *
     */

    public function __construct($model, $api){

        parent::__construct();

        $this->model          = $model;
        $this->api            = $api;

        $this->session  =     MOO_SESSION::instance();
    }


    // Register our routes.
    public function register_routes(){
        register_rest_route( $this->namespace, '/checkout', array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'getCheckoutOptions' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/checkout', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'checkout' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/checkout/delivery_areas', array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'deliveryAreas' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/checkout/order_types', array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'orderTypes' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/checkout/opening_status', array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'openingStatus' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/checkout/verify_number', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'sendSmsVerification' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/checkout/check_verif_code', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'checkVerificationCode' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/checkout/check_coupon', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'checkCouponCode' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/checkout/order_totals', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'getOrderTotals' ),
                'permission_callback' => '__return_true'
            )
        ) );

        register_rest_route( $this->v3Namespace, '/checkout/order_totals', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'getOrderTotalsV2' ),
                'permission_callback' => '__return_true'
            )
        ) );

        register_rest_route( $this->namespace, '/check-merchant', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'checkMerchant' ),
                'permission_callback' => '__return_true'
            )
        ) );

    }

    /**
     * @param $request
     * @body json
     * @return array
     */
    public function getCheckoutOptions( $request ) {
        $response = array();

        $response["use_sms_verification"] = isset($this->pluginSettings['use_sms_verification']) && $this->pluginSettings['use_sms_verification'] == "enabled";
        $response["use_coupons"] = isset($this->pluginSettings['use_coupons']) && $this->pluginSettings['use_coupons'] == "enabled";
        $response["schedule_orders"] = isset($this->pluginSettings['order_later']) && $this->pluginSettings['order_later'] == "on";
        $response["schedule_orders_required"] = isset($this->pluginSettings['order_later_mandatory']) && $this->pluginSettings['order_later_mandatory'] == "on";
        $response["fb_appid"] = $this->pluginSettings['fb_appid'];
        $response["order_types"] = $this->orderTypes($request);
        $response["opening_status"] = $this->openingStatus($request);
        $response["special_instructions"] = array(
            "accept_special_instructions"=> isset($this->pluginSettings['use_special_instructions']) && $this->pluginSettings['use_special_instructions'] == "enabled",
            "text"=>$this->pluginSettings['text_under_special_instructions'],
            "is_required"=> isset($this->pluginSettings['special_instructions_required']) && $this->pluginSettings['special_instructions_required'] === "yes",
        );
        $suggestedTips = array();
        $tipsValues = explode(",",$this->pluginSettings['tips_selection']);

        foreach ($tipsValues as $tipValue) {
            $suggestedTips[] = floatval($tipValue);
        }

        $response["tips"] = array(
            "accept_tips" => isset($this->pluginSettings['tips']) && $this->pluginSettings['tips'] == "enabled",
            "values"=>explode(",",$this->pluginSettings['tips_selection']),
            "suggestedTips"=>$suggestedTips,
            "default_value"=>$this->pluginSettings['tips_default'],
            "default"=>$this->pluginSettings['tips_default'] !== "" ? floatval($this->pluginSettings['tips_default']) : null,
        );

        $response["payment_methods"]["clover_form"] = $this->pluginSettings["clover_payment_form"];
        $response["payment_methods"]["standard_form"] = $this->pluginSettings["payment_creditcard"];
        $response["payment_methods"]["cash_pickup"] = $this->pluginSettings["payment_cash"];
        $response["payment_methods"]["cash_delivery"] = $this->pluginSettings["payment_cash_delivery"];

        if(isset($this->pluginSettings["service_fees"]) && $this->pluginSettings["service_fees"] !==""){
            $response["services_fees"] = array(
                "name"=>$this->pluginSettings["service_fees_name"],
                "amount"=>$this->pluginSettings["service_fees"],
                "type"=>$this->pluginSettings["service_fees_type"],
            );
        } else {
            $response["services_fees"] = null;
        }

        if(isset($this->pluginSettings["custom_sa_title"]) && ($this->pluginSettings["custom_sa_title"] !=="" || $this->pluginSettings["custom_sa_content"] !== "")){
            $response["announcement"] = array(
                "title"=>$this->pluginSettings["custom_sa_title"],
                "content"=>$this->pluginSettings["custom_sa_content"],
                "showOnCheckout"=>$this->pluginSettings["custom_sa_onCheckoutPage"] === 'on',
            );
        } else {
            $response["announcement"] = null;
        }

        if(isset($this->pluginSettings["track_stock"]) && $this->pluginSettings["track_stock"] === "enabled"){
            $response["stock"] = array(
                "track_stock"=>true,
                "hide_items"=>false,
            );
        } else {
            $response["stock"] = array(
                "track_stock"=>false,
                "hide_items"=>$this->pluginSettings["track_stock_hide_items"] === 'on',
            );
        }


        //check if the store makes as closed from the settings
        if(isset($this->pluginSettings['accept_orders']) && $this->pluginSettings['accept_orders'] === "disabled"){
            $response["store_is_open"] = false;
            if(isset($this->pluginSettings["closing_msg"]) && $this->pluginSettings["closing_msg"] !== '') {
                $response["closing_msg"] = $this->pluginSettings["closing_msg"];
            } else  {
                $response["closing_msg"] = "We are currently closed and will open again soon";
            }
            if(isset($this->pluginSettings["hide_menu_w_closed"]) && $this->pluginSettings["hide_menu_w_closed"] === "on") {
                $response["hide_menu"] = true;
            } else {
                $response["hide_menu"] = false;
            }
        } else {
            $response["store_is_open"] = true;
        }

        //Get blackout status
        $blackoutStatusResponse = $this->api->getBlackoutStatus();

        if(isset($blackoutStatusResponse["status"]) && $blackoutStatusResponse["status"] === "close") {
            $response["store_is_open"] = false;
            if(!empty($blackoutStatusResponse["custom_message"])){
                $response["closing_msg"] = $blackoutStatusResponse["custom_message"];
            } else {
                $response["closing_msg"] = "We are currently closed and will open again soon";
            }
            if (!empty($response["opening_status"])){
                $response["opening_status"]["status"] =  "close";
                $response["opening_status"]["message"] =  $response["closing_msg"];
                $response["hide_menu"] =  boolval($blackoutStatusResponse["hide_menu"]);
                $response["opening_status"]["hide_menu"] =  boolval($blackoutStatusResponse["hide_menu"]);
            }
        }

        //delivery areas
        $response["delivery_areas"]["merchant_lat"] = $this->pluginSettings['lat'];
        $response["delivery_areas"]["merchant_lng"] = $this->pluginSettings['lng'];
        $response["delivery_areas"]["areas"]        = json_decode($this->pluginSettings['zones_json']);
        $response["delivery_areas"]["other_zones"]  = $this->pluginSettings['other_zones_delivery'];
        $response["delivery_areas"]["free_after"]   = $this->pluginSettings['free_delivery'];
        $response["delivery_areas"]["fixed_fees"]   = $this->pluginSettings['fixed_delivery'];
        $response["delivery_areas"]["errorMsg"]     = $this->pluginSettings['delivery_errorMsg'];

        // payment keys
        if(isset($this->pluginSettings["clover_payment_form"]) && $this->pluginSettings["clover_payment_form"] == "on") {
            $response["cloverPakmsPaymentKey"] = $this->api->getPakmsKey();
        }

        $response["pubkey"] = $this->api->getMerchantPubKey();

        return $response;
    }

    /**
     * @param $request
     * @return array
     */
    public function orderTypes( $request ) {
        $response = array();
        $visibleOrderTypes = $this->model->getVisibleOrderTypes();
        $HoursResponse = $this->api->getMerchantCustomHoursStatus("ordertypes");
        if( $HoursResponse ){
            $merchantCustomHoursStatus = $HoursResponse;
            $merchantCustomHours = array_keys($merchantCustomHoursStatus);
        } else {
            $merchantCustomHoursStatus = array();
            $merchantCustomHours = array();
        }

        foreach ($visibleOrderTypes as $orderType){
            $tempo = array();
            $tempo["uuid"]=$orderType->ot_uuid;
            $tempo["name"]=$orderType->label;
            $tempo["unavailable_message"]=$orderType->custom_message;
            $tempo["taxable"]= $orderType->taxable == "1";
            $tempo["is_delivery"]= $orderType->show_sa == "1";
            $tempo["use_coupons"]= $orderType->use_coupons == "1";
            $tempo["allow_sc_order"]= $orderType->allow_sc_order == "1";

            if(
                $orderType->allow_service_fee === 1 ||
                $orderType->allow_service_fee === "1" ||
                $orderType->allow_service_fee === true ||
                $orderType->allow_service_fee === "true"
            ){
                $tempo["allow_service_fee"] = true;
            } else {
                $tempo["allow_service_fee"] = false;
            }
            $tempo["minAmount"]=floatval($orderType->minAmount );
            $tempo["maxAmount"]=floatval($orderType->maxAmount );
            $tempo["available"] = true;
            if(isset($orderType->custom_hours) && !empty($orderType->custom_hours)) {
                if(in_array($orderType->custom_hours, $merchantCustomHours)){
                    $isNotAvailable = $merchantCustomHoursStatus[$orderType->custom_hours] === "close";
                    if ($isNotAvailable){
                        $tempo["available"] = false;
                    }
                }
            }
            $response[] = $tempo;
        }
        return $response;
    }
    public function deliveryAreas( $request ) {
        $response = array();
        $response["merchant_lat"] = $this->pluginSettings['lat'];
        $response["merchant_lng"] = $this->pluginSettings['lng'];
        $response["areas"] = json_decode($this->pluginSettings['zones_json']);
        $response["other_zones"] = $this->pluginSettings['other_zones_delivery'];
        $response["free_after"] = $this->pluginSettings['free_delivery'];
        $response["fixed_fees"] = $this->pluginSettings['fixed_delivery'];
        return $response;
    }
    public function openingStatus( $request ) {

        if($this->pluginSettings["order_later"] == "on") {
            $inserted_nb_days = $this->pluginSettings["order_later_days"];
            $inserted_nb_mins = $this->pluginSettings["order_later_minutes"];

            $inserted_nb_days_d = $this->pluginSettings["order_later_days_delivery"];
            $inserted_nb_mins_d = $this->pluginSettings["order_later_minutes_delivery"];

            if($inserted_nb_days === "") {
                $nb_days = 4;
            } else {
                $nb_days = intval($inserted_nb_days);
            }

            if($inserted_nb_mins === "") {
                $nb_minutes = 20;
            } else {
                $nb_minutes = intval($inserted_nb_mins);
            }

            if( $inserted_nb_days_d === "") {
                $nb_days_d = 4;
            } else {
                $nb_days_d = intval($inserted_nb_days_d);
            }

            if($inserted_nb_mins_d === "") {
                $nb_minutes_d = 60;
            } else {
                $nb_minutes_d = intval($inserted_nb_mins_d);
            }

        } else {
            $nb_days = 0;
            $nb_minutes = 0;
            $nb_days_d = 0;
            $nb_minutes_d = 0;
        }
        if($this->pluginSettings['hours'] === 'all' && $this->pluginSettings["order_later"] !== "on"){
                return [
                    "status" => 'open',
                    "store_time" => "",
                    "time_zone" => null,
                    "current_time" => null,
                    "pickup_time" => null,
                    "delivery_time" => null,
                    "accept_orders_when_closed" => true,
                    "schedule_orders" => false,
                    "hide_menu" => false,
                    "message" => "",
                ];
        }
        $oppening_status = $this->api->getOpeningStatus($nb_days,$nb_minutes);
        if($nb_days != $nb_days_d || $nb_minutes != $nb_minutes_d) {
            $oppening_status_d = $this->api->getOpeningStatus($nb_days_d,$nb_minutes_d);
            if(isset($oppening_status_d["pickup_time"])){
                $oppening_status["delivery_time"]=$oppening_status_d["pickup_time"];
            } else {
                $oppening_status["delivery_time"] = null;
            }
        } else {
            $oppening_status["delivery_time"]=$oppening_status["pickup_time"];
        }
        //remove times if schedule_orders disabled
        if($this->pluginSettings["order_later"] != "on") {
            $oppening_status["pickup_time"] = null;
            $oppening_status["delivery_time"] = null;
        } else {
            //Adding asap to pickup time
            if(isset($oppening_status["pickup_time"])) {
                if(isset($this->pluginSettings['order_later_asap_for_p']) && $this->pluginSettings['order_later_asap_for_p'] == 'on')
                {
                    if(isset($oppening_status["pickup_time"]["Today"])) {
                        array_unshift($oppening_status["pickup_time"]["Today"],'ASAP');
                    }
                }
                if(isset($oppening_status["pickup_time"]["Today"])) {
                    array_unshift($oppening_status["pickup_time"]["Today"],'Select a time');
                }

            }
            //Adding asap to delivery time
            if(isset($oppening_status["delivery_time"])) {
                if(isset($this->pluginSettings['order_later_asap_for_d']) && $this->pluginSettings['order_later_asap_for_d'] == 'on')
                {
                    if(isset($oppening_status["delivery_time"]["Today"])) {
                        array_unshift($oppening_status["delivery_time"]["Today"],'ASAP');
                    }
                }
                if(isset($oppening_status["delivery_time"]["Today"])) {
                    array_unshift($oppening_status["delivery_time"]["Today"],'Select a time');
                }

            }
        }


        $oppening_msg = "";

        if($this->pluginSettings['hours'] != 'all'){
            if ($oppening_status["status"] == 'close'){
                if(isset($this->pluginSettings["closing_msg"]) && $this->pluginSettings["closing_msg"] !== '') {
                    $oppening_msg = $this->pluginSettings["closing_msg"];
                } else  {
                    if($oppening_status["store_time"] == '')
                        $oppening_msg = 'Online Ordering Currently Closed'.(($this->pluginSettings['accept_orders_w_closed'] == 'on' )?" You may schedule your order in advance ":"");
                    else
                        $oppening_msg = 'Today\'s Online Ordering Hours '.$oppening_status["store_time"] .' Online Ordering Currently Closed'.(($this->pluginSettings['accept_orders_w_closed'] == 'on' )?" You may schedule your order in advance ":"");
                }
            }
            $oppening_status["accept_orders_when_closed"] = $this->pluginSettings['accept_orders_w_closed'] == 'on';
        } else {
            $oppening_status["status"] = 'open';
            $oppening_status["accept_orders_when_closed"] = true;
        }
        $oppening_status["message"] = $oppening_msg;
        $oppening_status["schedule_orders"] = isset($this->pluginSettings['order_later']) && $this->pluginSettings['order_later'] == "on";
        $oppening_status["hide_menu"] = isset($this->pluginSettings['hide_menu']) && $this->pluginSettings['hide_menu'] == "on";

        return $oppening_status;
    }
    /**
     * @param $request
     * @body json
     * @return array
     */
    public function checkout( $request ) {

        $body = json_decode($request->get_body(),true);
        $customer_token =  (isset($body["customer_token"]) && !empty($body["customer_token"])) ?  $body["customer_token"] : null;
        $googleReCAPTCHADisabled =  (bool) get_option('sooDisableGoogleReCAPTCHA',false);

        if (get_option('moo_old_checkout_enabled') === 'yes'){
            $googleReCAPTCHADisabled = true;
        }

        //Check Google recaptcha
        if (!$googleReCAPTCHADisabled && !empty($this->pluginSettings['reCAPTCHA_site_key']) && !empty($this->pluginSettings['reCAPTCHA_secret_key'])) {
            $reCaptchaErrorMessage = "Oops! It seems there was an issue with the reCAPTCHA. Don't worry, these things happen. Please try submitting the form again. We apologize for any inconvenience caused.";
            $args = array(
                'method'    => 'POST',
                'body'=>array(
                    'secret'   => $this->pluginSettings['reCAPTCHA_secret_key'],
                    'response' => $body["reCAPTCHA_token"]
                )
            );
            $gcaptcha = wp_remote_post( SOO_G_RECAPTCHA_URL, $args );
            if ( is_wp_error( $gcaptcha ) ) {
                return array(
                    'status'	=> 'failed',
                    'message'	=> $reCaptchaErrorMessage
                );
            }
            $gcaptchaBody = wp_remote_retrieve_body( $gcaptcha );
            if ( empty( $gcaptchaBody ) ) {
                return array(
                    'status'	=> 'failed',
                    'message'	=> $reCaptchaErrorMessage
                );
            }
            $result = json_decode( $gcaptchaBody );
            if ( empty( $result ) ) {
                return array(
                    'status'	=> 'failed',
                    'message'	=> $reCaptchaErrorMessage
                );
            }
            if ( ! isset( $result->success ) ) {
                return array(
                    'status'	=> 'failed',
                    'message'	=> $reCaptchaErrorMessage
                );
            }
            if ($result->success){
                $body["reCAPTCHA_token"] = 'isValid';
            } else {
                return array(
                    'status'	=> 'failed',
                    'message'	=> $reCaptchaErrorMessage,
                    'data'=>$result
                );
            }
        } else {
            $body["reCAPTCHA_token"] = 'disabled';
        }

        //Check blackout status
        //Get blackout status
        $blackoutStatusResponse = $this->api->getBlackoutStatus();
        if(isset($blackoutStatusResponse["status"]) && $blackoutStatusResponse["status"] === "close") {

            if(isset($blackoutStatusResponse["custom_message"]) && !empty($blackoutStatusResponse["custom_message"])){
                $errorMsg = $blackoutStatusResponse["custom_message"];
            } else {
                $errorMsg = 'We are currently closed and will open again soon';

            }
            return array(
                'status'	=> 'failed',
                'message'	=> $errorMsg
            );
        }

        //check some required fields
        if (!isset($body["payment_method"])) {
            return array(
                'status'	=> 'failed',
                'message'	=> "Payment method is required"
            );
        } else {
            if($body["payment_method"]  === "clover") {
                if(!isset($body["token"])){
                    return array(
                        'status'	=> 'failed',
                        'message'	=> "Payment Token is required"
                    );
                }
            }
        }
        if (! isset($body["customer"]) ) {
            return array(
                'status'	=> 'failed',
                'message'	=> "Customer is required"
            );
        }

        //service Fee and delivery fees Names
        if(isset($this->pluginSettings['service_fees_name']) && !empty($this->pluginSettings['service_fees_name'])) {
            $body["service_fee_name"] = $this->pluginSettings['service_fees_name'];
        } else {
            $body["service_fee_name"] = "Service Charge";
        }

        if(isset($this->pluginSettings['delivery_fees_name']) && !empty($this->pluginSettings['delivery_fees_name'])) {
            $body["delivery_name"] = $this->pluginSettings['delivery_fees_name'];
        } else {
            $body["delivery_name"] = "Delivery Charge";
        }

        //check Scheduled time
        if(!empty($body['pickup_day'])) {
            $pickup_time = sanitize_text_field($body['pickup_day']);
        }
        // check hour
        if(isset($pickup_time) && !empty($body['pickup_hour'])) {
            $pickup_time .= ' at '.$body['pickup_hour'];
        }
        // concat day and hour
        if(isset($pickup_time)) {
            $body["scheduled_time"] = ' Scheduled for '.$pickup_time;
        }

        //start preparing the note
        $note = 'SOO' ;

        //check the customer
        if(is_array($body["customer"])){
            $customer  = $body["customer"];
            if (!empty($customer["first_name"])){
                $note .= ' | ' .  $customer["first_name"];

                if(!empty($customer["last_name"])){
                    $note .= ' ' .  $customer["last_name"];
                }

            } else {
                if(!empty($customer["name"])){
                    $note .= ' | ' .  $customer["name"];
                }
            }
        } else {
            $customer = array();
        }

        //add special instruction to the note
        if(!empty($body['special_instructions'])){
            $note .=' | '.$body['special_instructions'];
        }

        if(isset($body['scheduled_time'])){
            $note .=' | '.$body['scheduled_time'];
        }
        //check the order type
        if(!empty($body["order_type"]) && $body["order_type"] !== "onDemandDelivery") {
            $orderTypeUuid = sanitize_text_field($body['order_type']);
            $orderType = $this->api->GetOneOrdersTypes($orderTypeUuid);
            $orderTypeFromClover = json_decode(wp_json_encode($orderType),true);
            $orderTypeFromLocal  = (array)$this->model->getOneOrderTypes($orderTypeUuid);

            if(isset($orderTypeFromClover["code"]) && $orderTypeFromClover["code"] == 998) {
                return array(
                    'status'	=> 'failed',
                    'code'	=> 'maintenance',
                    'message'=> "Sorry, we are having a brief maintenance. Please check back in a few minutes"
                );
            }

            if(isset($orderTypeFromClover["message"]) && $orderTypeFromClover["message"] == "401 Unauthorized") {
                return array(
                    'status'	=> 'failed',
                    'code'	=> '401',
                    'message'=> "Internal error, please contact us. If you are the site owner, please verify your API Key."
                );
            }
            //TODO : Improve that
            if( ! isset($orderTypeFromClover["label"]) ) {
                return array(
                    'status'	=> 'failed',
                    'code'	=> 'ordertype_not_found',
                    'message'=> "Referenced order type does not exist or currently experiencing maintenance. Please try again or contact us."
                );
            }

            $isDelivery = ( isset($orderTypeFromLocal['show_sa']) && $orderTypeFromLocal['show_sa'] == "1" )?"Delivery":"Pickup";

            $note .= ' | '.$orderTypeFromClover["label"];

            if($isDelivery === 'Delivery' && isset($customer["full_address"])) {
                $note .= ' | '.$customer["full_address"];
            }

            if(isset($orderTypeFromLocal['taxable']) && !$orderTypeFromLocal['taxable']) {
                $body["tax_removed"] = true;
            }

        } else {
            if(isset($body["order_type"]) && $body["order_type"] === "onDemandDelivery") {
                $isDelivery = 'Delivery';
                $note .= ' | On-Demand Delivery';
                if(isset($customer["full_address"])) {
                    $note .= ' | '.$customer["full_address"];
                }
            }
        }

        //Get the cart from the session if isn't sent from the frontend
        if(!isset($body["cart"]["items"])){

            //Add service fees and delivery fees to the body
            if(!isset($body["service_fee"])){
                $body["service_fee"] = 0;
            } else {
                $body["service_fee"] = intval($body["service_fee"]);
                if($body["service_fee"] < 0 ){
                    $body["service_fee"] = 0;
                }
            }
            if(!isset($body["delivery_amount"])){
                $body["delivery_amount"] = 0;
            } else {
                $body["delivery_amount"] = intval($body["delivery_amount"]);
                if($body["delivery_amount"] < 0 ) {
                    $body["delivery_amount"] = 0;
                }
            }

            $body["cart"] = $this->session->getCart();

            if (isset($body["totals"])){

                $discountsAmount = $body["totals"]["discounts"];
                $notTaxableCharges = $body["totals"]["deliveryAmount"] + $body["totals"]["serviceFee"];
                $cartTotals = $this->session->getTotalsV2($notTaxableCharges, $discountsAmount);

                if(  ! $cartTotals ){
                    return array(
                        'status'	=> 'failed',
                        'message'=> "It looks like your cart is empty"
                    );
                }
                //Get The Order Total Amount and Tax Amount
                if (isset($body["tax_removed"]) && $body["tax_removed"] === true){
                    $body["tax_amount"]  = 0;
                } else {
                    $body["tax_amount"] = $body["totals"]["discounts"] > 0  ? $cartTotals['taxes_after_discount'] :  $cartTotals['taxes'];
                }

                $body["amount"]  = $cartTotals['sub_total'] +  $body["tax_amount"] + $body["totals"]["deliveryAmount"] + $body["totals"]["serviceFee"] - $body["totals"]["discounts"];

            } else {
                $notTaxableCharges = $body["delivery_amount"] + $body["service_fee"];
                $cartTotals = $this->session->getTotals($notTaxableCharges);

                if( ! $cartTotals ){
                    return array(
                        'status'	=> 'failed',
                        'message'=> "It looks like your cart is empty"
                    );
                } else {
                    if (isset($body["tax_removed"]) && is_bool($body["tax_removed"]) && $body["tax_removed"]){
                        $body["amount"] = $cartTotals["sub_total"] +  $body["service_fee"]  + $body["delivery_amount"];
                        $body["tax_amount"] = 0;
                    } else {
                        $body["amount"] = $cartTotals["total"] +  $body["service_fee"] + $body["delivery_amount"];
                        $body["tax_amount"] = $cartTotals["total_of_taxes"];
                    }
                }
                //Apply coupon
                if(! $this->session->isEmpty("coupon")) {
                    $coupon = $this->session->get("coupon");
                    $body["coupon"] = array(
                        "code"=>$coupon["code"]
                    );
                    //Update the totals if there is a coupon and the order isn't taxable
                    if(isset($cartTotals["coupon_value"])) {
                        if (isset($body["tax_removed"]) && is_bool($body["tax_removed"]) && $body["tax_removed"]){
                            $body["amount"] = $body["amount"] - $cartTotals["coupon_value"];
                        }
                    }
                }
            }

        }

        //Check the stock
        if( $this->api->getTrackingStockStatus() ) {
            $itemStocks = $this->api->getItemStocks();
            $itemsQte = array();
            if(count($itemStocks)>0 && isset($body["cart"]) && isset($body["cart"]["items"])){
                //count items
                foreach ($body["cart"]["items"] as $line) {
                    if(isset($line["item"]["id"])){
                        if(isset($itemsQte[$line["item"]["id"]])){
                            $itemsQte[$line["item"]["id"]]++;
                        } else {
                            $itemsQte[$line["item"]["id"]] = 1;
                        }
                    }
                }

                //check stock
                foreach ($body["cart"]["items"] as $cartLine) {
                    if(isset($cartLine['item']["id"])){
                        $itemStock = $this->getItemStock($itemStocks,$cartLine['item']["id"]);

                        if(!$itemStock) {
                            continue;
                        }

                        if(isset($itemsQte[$cartLine['item']["id"]]) && $itemsQte[$cartLine['item']["id"]] > $itemStock["stockCount"]) {
                            return array(
                                'status'	=> 'failed',
                                'code'	=> 'low_stock',
                                'item'	=> $cartLine['item']["id"],
                                'message'	=> 'The item '. $this->getItemName($cartLine).' is low on stock. Please go back and change the quantity in your cart '.(($itemStock["stockCount"]>0)?"as we have only ".$itemStock["stockCount"]." left":"")
                            );
                        } else {
                            if($itemStock["stockCount"] < 1) {
                                return array(
                                    'status'	=> 'failed',
                                    'code'	=> 'low_stock',
                                    'item'	=> $cartLine['item']["id"],
                                    'message'	=> 'The item '.$this->getItemName($cartLine).' is out off stock'
                                );
                            }
                        }
                    }
                }
            }
        }

        //show Order number
        if(isset($this->pluginSettings["show_order_number"]) && $this->pluginSettings["show_order_number"] === "on") {
            $nextNumber = intval(get_option("moo_next_order_number"));
            if($nextNumber){
                if(isset($this->pluginSettings["rollout_order_number"]) && $this->pluginSettings["rollout_order_number"] === "on"){
                    if(isset($this->pluginSettings["rollout_order_number_max"]) && $nextNumber > $this->pluginSettings["rollout_order_number_max"] ){
                        $nextNumber = 1;
                    }
                }
            } else {
                $nextNumber = 1;
            }
            $showOrderNumber   = "SOO-".str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $body["show_order_number"] = true;
        } else {
            $showOrderNumber = false;
            $body["show_order_number"] = false;
            $nextNumber = 0;
        }

        //add order title
        if($showOrderNumber !== false) {
            $body["title"] = $showOrderNumber;
            if ($body["payment_method"] === "cash"){
                if(isset($isDelivery) && $isDelivery === 'Delivery'){
                    $body["title"] .= " (Will pay upon delivery)";
                } else {
                    $body["title"] .= " (Will pay at location)";
                }
            }
        } else {
            if ($body["payment_method"] === "cash"){
                if(isset($isDelivery) && $isDelivery === 'Delivery'){
                    $body["title"] = "Will pay upon delivery";
                } else {
                    $body["title"] = "Will pay at location";
                }
            }
        }

        if( ! isset( $body["special_instructions"]) ) {
            $body["special_instructions"] = '';
        }
        if( ! isset( $body["title"]) ) {
            $body["title"] = '';
        }
        if( ! isset( $body["scheduled_time"]) ) {
            $body["scheduled_time"] = '';
        }

        //Apply filters before sending the order
        $body["note"] = apply_filters('moo_filter_order_note', $note);
        $body["special_instructions"] = apply_filters('moo_filter_special_instructions', $body["special_instructions"]);
        $body['scheduled_time'] =  apply_filters('moo_filter_scheduled_time', $body["scheduled_time"]);
        $body["title"] = apply_filters('moo_filter_title', $body["title"]);
        $body["delivery_amount"] = apply_filters('moo_filter_delivery_amount', $body["delivery_amount"]);
        $body["service_fee"] = apply_filters('moo_filter_service_fee', $body["service_fee"]);



        // add some merchant info
        $body["merchant"] = array();

        if(isset($this->pluginSettings["merchant_phone"])){
            $body["merchant"]["phone"] = $this->pluginSettings["merchant_phone"];
        }
        if(isset($this->pluginSettings["merchant_email"])){
            $body["merchant"]["emails"] = $this->pluginSettings["merchant_email"];
        }
        $metaData = array(
          ["name"=>"clientIp","value"=>$this->getClientIp()],
          ["name"=>"clientUserAgent","value"=>$_SERVER["HTTP_USER_AGENT"]],
          ["name"=>"phpVersion","value"=>phpversion()],
          ["name"=>"pluginVersion","value"=>$this->version]
        );
        //Add Few MetaData to the Order
        if (isset($body["metainfo"])  && is_array($body["metainfo"])){
            $body["metainfo"] = array_merge($body["metainfo"],$metaData);
        } else {
            $body["metainfo"] = $metaData;
        }
        //send request to the Api
        try{
            do_action("moo_action_new_order_received", $body);

            $orderCreated = $this->api->createOrderV2($body,$customer_token);
            if($orderCreated){
                //Order created successfully
                if(isset($orderCreated["id"])){

                    do_action("moo_action_order_created", $orderCreated["id"], $body["payment_method"] );

                    if(isset($orderCreated["status"]) && $orderCreated["status"] === "success"){
                        $this->session->delete("items");
                        $this->session->delete("itemsQte");
                        $this->session->delete("coupon");
                        do_action("moo_action_order_accepted", $orderCreated["id"], $body );

                        if (!empty($showOrderNumber)){
                            //increment order number
                            update_option("moo_next_order_number",++$nextNumber);
                        }
                    }
                }
                return apply_filters("moo_filter_order_creation_response",$orderCreated);
            } else {
                return array(
                    "status"=>"failed",
                    "message"=>__("An error has occurred please try again","moo_OnlineOrders")
                );
            }
        } catch (Exception  $e){
            return array(
                "status"=>"failed",
                "message"=>__("An error has occurred please try again","moo_OnlineOrders")
            );
        }
    }
    /**
     * @param $request
     * @body json
     * @return array
     */
    public function sendSmsVerification( $request ) {
        $body = json_decode($request->get_body(),true);
        $phone_number = sanitize_text_field($body['phone']);
        if(empty($phone_number)){
            return array(
                'status'	=> 'error',
                'message'   => 'Please send the phone number'
            );
        }
        if(! $this->session->isEmpty("moo_verification_code") && $phone_number == $this->session->get("moo_phone_number") ) {
            $verification_code = $this->session->get("moo_verification_code");
        } else {
            $verification_code = wp_rand(100000,999999);
            $this->session->set($verification_code,"moo_verification_code");
        }
        $this->session->set($phone_number,"moo_phone_number");
        $this->session->set(false,"moo_phone_verified");

        $res = $this->api->sendVerificationSms($verification_code,$phone_number);
        return array(
            'status'	=> $res["status"],
            'code'	=> $verification_code,
            'result'    => $res
        );
    }
    public function checkVerificationCode( $request ) {
        $body = json_decode($request->get_body(),true);
        $verification_code = sanitize_text_field($body['code']);
        if(empty($verification_code)){
            return array(
                'status'	=> 'error',
                'message'   => 'Please send the code'
            );
        }

        if($verification_code != "" && $verification_code ==  $this->session->get("moo_verification_code") )
        {
            $response = array(
                'status'	=> 'success'
            );
            $this->session->set(true,"moo_phone_verified");

            if(! $this->session->isEmpty("moo_customer_token"))
                $this->api->moo_CustomerVerifPhone($this->session->get("moo_customer_token"), $this->session->get("moo_phone_number"));
            $this->session->delete("moo_verification_code");
        } else {
            $response = array(
                'status'	=> 'error'
            );
        }

        return $response;

    }
    public function checkCouponCode( $request ) {

        $body = json_decode($request->get_body(),true);
        $coupon_code = sanitize_text_field($body['code']);

        if(empty($coupon_code)){
            return array(
                'status'	=> 'error',
                'message'   => 'Please send the coupon code'
            );
        }

        if($coupon_code != "") {

            $coupon = $this->api->moo_checkCoupon($coupon_code);
            $coupon = json_decode($coupon,true);
            if($coupon['status'] == "success") {
                $response = array(
                    'status'	=> 'success',
                    "coupon" =>$coupon
                );
            }  else {
                $response = array(
                    'status'	=> 'failed',
                    "message" =>"Coupon not found"
                );
            }
        } else {
            $response = array(
                'status'	=> 'failed',
                "message" =>"Please enter the coupon code"
            );
        }

        return $response;

    }
    public function getOrderTotals( $request ) {

        $body = json_decode($request->get_body(),true);

        $deliveryFee = isset($body['delivery_amount']) ? intval($body['delivery_amount']) : 0;
        $serviceFee = isset($body['service_fee']) ? intval($body['service_fee']) : 0;

        return $this->session->getTotals($deliveryFee,$serviceFee);

    }
    public function getOrderTotalsV2( $request ) {

        $body = json_decode($request->get_body(),true);

        $discounts = isset($body['discounts']) ? intval($body['discounts']) : 0;
        $charges = isset($body['charges']) ? intval($body['charges']) : 0;

        return $this->session->getTotalsV2($charges, $discounts);

    }

    /**
     * Check the merchant, this endpoint used on the dashboard when using this website as data source
     * @param $request
     * @return string[]
     */
    public function checkMerchant( $request ) {
        $body = json_decode($request->get_body(),true);
        $key = trim($this->pluginSettings["api_key"]);
        if (!empty($body["hash"]) && sha1($key) === $body["hash"]) {
            return [ "status"=>"success" ];
        }
        return [ "status"=>"failed" ];
    }

    /**
     * Parse items stocks and get the stock of an item passed via param
     * @param $items
     * @param $item_uuid
     * @return bool|object
     */
    private function getItemStock($items,$item_uuid) {
        foreach ($items as $i) {
            if(isset($i["item"]["id"]) && $i["item"]["id"] == $item_uuid) {
                return $i;
            }
        }
        return false;
    }
    private function getClientIp(){
        $fields = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_SUCURI_CLIENTIP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

        foreach ($fields as $ip_field) {
            if (!empty($_SERVER[$ip_field])) {
                return $_SERVER[$ip_field];
            }
        }

        return null;
    }

    private function getItemName($var) {
        if (is_array($var)){
            if (!empty($var["soo_name"])){
                return stripslashes( (string) $var["soo_name"] ) ;
            }
            if (!empty($var["alternate_name"])){
                return stripslashes(($var["alternate_name"].'('.$var["name"].')') ) ;
            }
            if (!empty($var["name"])){
                return stripslashes( (string) $var["name"] ) ;
            }

        }
        if (is_object($var)){
            if ( ! empty($var->soo_name) ){
                return stripslashes( (string) $var->soo_name ) ;
            }
            if ( !empty($var->alternate_name) ){
                return stripslashes(($var->alternate_name.'('.$var->name.')') ) ;

            }
            if ( ! empty($var->name) ){
                return stripslashes( (string) $var->name );
            }
        }
        return '';
    }
}