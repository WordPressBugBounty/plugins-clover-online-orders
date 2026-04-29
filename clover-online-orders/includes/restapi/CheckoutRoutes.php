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
        register_rest_route( $this->namespace, '/checkout/finalize_3ds_payment', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'finalize3DsPayment' ),
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
        // Fail-loud when in Global settings mode but the central dashboard is unreachable.
        // Prevents returning a response that silently mixes local fallback values with
        // live API values — which would let mobile clients place orders under the wrong
        // settings with no visible error.
        if (SooSettingsSource::instance($this->api)->globalFetchFailed()) {
            return new WP_Error(
                'settings_source_unavailable',
                'The central settings dashboard is temporarily unreachable. Please try again in a moment.',
                array('status' => 503)
            );
        }

        $response = array();
        $googleReCAPTCHADisabled = (bool) get_option('sooDisableGoogleReCAPTCHA',false);

        $response["use_coupons"] = isset($this->pluginSettings['use_coupons']) && $this->pluginSettings['use_coupons'] == "enabled";
        $sooSource = SooSettingsSource::instance($this->api);
        $applePayEnabled = (bool) get_option("moo_apple_pay_enabled", false);

        // In Global mode, the unified dashboard endpoint is the single source
        // of truth. Build a mapper once; reuse for blackout, checkout
        // settings, pubkey, and address. The fail-loud guard above
        // (globalFetchFailed) has already returned 503 if the fetch failed,
        // so by the time we reach here we have a usable payload.
        $dashMapper = null;
        if (SooSettingsSource::current() === 'global') {
            $_dashForOverride = SooSettingsSource::instance($this->api)->dashboardClient()->fetch();
            $dashMapper = new DashboardCheckoutMapper($_dashForOverride);
            $_cs = isset($_dashForOverride['checkout_settings']) ? $_dashForOverride['checkout_settings'] : array();
            $this->applyDashboardCheckoutOverrides($_cs);
            if (array_key_exists('paymentMethods', $_cs)) {
                $paymentFlags = $this->dashboardPaymentMethodFlags($_cs['paymentMethods']);
                $applePayEnabled = $paymentFlags['apple_pay'];
            }
        }

        $response["use_sms_verification"] = isset($this->pluginSettings['use_sms_verification']) && $this->pluginSettings['use_sms_verification'] == "enabled";
        $response["schedule_orders"] = self::asBool($sooSource->get('order_later'));
        $response["schedule_orders_required"] = self::asBool($sooSource->get('order_later_mandatory'));
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
        $response["payment_methods"]["gift_cards"] = $this->pluginSettings["clover_giftcards"];
        $response["payment_methods"]["google_pay"] = $this->pluginSettings["clover_googlepay"];
        $response["payment_methods"]["apple_pay"] = $applePayEnabled ? 'on' : 'off';

        if(isset($this->pluginSettings["service_fees"]) && $this->pluginSettings["service_fees"] !=="") {
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
                "hide_items"=>$this->pluginSettings["track_stock_hide_items"] === 'on',
            );
        } else {
            $response["stock"] = array(
                "track_stock"=>false,
                "hide_items"=>false,
            );
        }


        //check if the store makes as closed from the settings
        if(isset($this->pluginSettings['accept_orders']) && $this->pluginSettings['accept_orders'] === "disabled") {
            $response["store_is_open"] = false;
            $closingMsg = $sooSource->get('closing_msg');
            $response["closing_msg"] = !empty($closingMsg) ? $closingMsg : "We are currently closed and will open again soon";
            $response["hide_menu"] = self::asBool($sooSource->get('hide_menu_w_closed'));
        } else {
            $response["store_is_open"] = true;
        }

        //Get blackout status — Global mode uses the mapper, Customized hits the legacy API.
        if ($dashMapper !== null) {
            $blackoutStatusResponse = $dashMapper->mapStoreStatus();
        } else {
            $blackoutStatusResponse = $this->api->getBlackoutStatus();
        }

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
        $checkoutSettings = $dashMapper !== null
            ? $dashMapper->mapCheckoutSettings()
            : $this->api->getCheckoutSettings();
        $response["checkout_settings"] = $checkoutSettings;
        $response["convenience_fee"] = (is_array($checkoutSettings) && isset($checkoutSettings["convenience_fee"])) ? intval(round(floatval($checkoutSettings["convenience_fee"]))) : 0;
        $response["fraudTools"] = (is_array($checkoutSettings) && isset($checkoutSettings["fraudTools"])) ? $checkoutSettings["fraudTools"] : null;
        $response["cloverPakmsPaymentKey"] = $dashMapper !== null
            ? $dashMapper->mapPubKey()
            : $this->api->getPakmsKey();
        $response["isApplePayEnabled"] =  $applePayEnabled;
        // Merchant pubkey: locally cached in moo_merchant_pubkey; the legacy
        // method reads from cache before falling back to the network. Safe
        // to call in both modes — there's no Global override for the
        // merchant identity pubkey itself.
        $response["pubkey"] = $this->api->getMerchantPubKey();
        $response["recaptchaKey"] = !$googleReCAPTCHADisabled && !empty($this->pluginSettings['reCAPTCHA_site_key']) ? $this->pluginSettings['reCAPTCHA_site_key'] : null;
        $response["allowScOrders"] = self::asBool($sooSource->get('order_later'));
        $response["allowAsap"] = $this->isAsapAllowed();
        $response["hideUnavailableItems"] = $this->isHideUnavailableItemsEnabled();
        $response["useCloverHours"] = $this->pluginSettings['hours'] !== 'all';


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
            $tempo["allow_service_fee"] = filter_var($orderType->allow_service_fee, FILTER_VALIDATE_BOOLEAN);
            $tempo["minAmount"]=floatval($orderType->minAmount );
            $tempo["maxAmount"]=floatval($orderType->maxAmount );
            $tempo["available"] = true;
            if( ! empty($orderType->custom_hours) ) {
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
        // Fail loud when in Global mode but the dashboard is unreachable —
        // mirrors the guard at the top of getCheckoutOptions() and checkout().
        // openingStatus is a separate REST route that can be called directly
        // by mobile clients, not just from getCheckoutOptions's internal flow.
        if (SooSettingsSource::instance($this->api)->globalFetchFailed()) {
            return new WP_Error(
                'settings_source_unavailable',
                'The central settings dashboard is temporarily unreachable. Please try again in a moment.',
                array('status' => 503)
            );
        }

        $isGlobal = SooSettingsSource::current() === 'global';
        $dashMapper = null;
        if ($isGlobal) {
            $dash = SooSettingsSource::instance($this->api)->dashboardClient()->fetch();
            $dashMapper = new DashboardCheckoutMapper($dash);
        }

        if($this->pluginSettings["order_later"] == "on") {
            $inserted_nb_days = $this->pluginSettings["order_later_days"];
            $inserted_nb_mins = $this->pluginSettings["order_later_minutes"];

            if ($isGlobal) {
                $inserted_nb_days_d = $inserted_nb_days;
                $inserted_nb_mins_d = $inserted_nb_mins;
            } else {
                $inserted_nb_days_d = $this->pluginSettings["order_later_days_delivery"];
                $inserted_nb_mins_d = $this->pluginSettings["order_later_minutes_delivery"];
            }

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
        if ($dashMapper !== null) {
            // REST mobile clients don't get a throttled map — filter out
            // unavailable slots so customers only see bookable times.
            $oppening_status = $dashMapper->mapOpeningStatus($nb_days, $nb_minutes, true);
        } else {
            $oppening_status = $this->api->getOpeningStatus($nb_days, $nb_minutes);
        }
        // In Global mode, pickup slots double as delivery slots in v1
        // (per the centralized-settings spec). Skip the second fetch.
        if ($dashMapper !== null) {
            $oppening_status["delivery_time"] = $oppening_status["pickup_time"];
        } elseif ($nb_days != $nb_days_d || $nb_minutes != $nb_minutes_d) {
            $oppening_status_d = $this->api->getOpeningStatus($nb_days_d, $nb_minutes_d);
            if (isset($oppening_status_d["pickup_time"])) {
                $oppening_status["delivery_time"] = $oppening_status_d["pickup_time"];
            } else {
                $oppening_status["delivery_time"] = null;
            }
        } else {
            $oppening_status["delivery_time"] = $oppening_status["pickup_time"];
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
                $allowAsapForDelivery = $isGlobal
                    ? (isset($this->pluginSettings['order_later_asap_for_p']) && $this->pluginSettings['order_later_asap_for_p'] == 'on')
                    : (isset($this->pluginSettings['order_later_asap_for_d']) && $this->pluginSettings['order_later_asap_for_d'] == 'on');
                if($allowAsapForDelivery)
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

        // Global mode: when the dashboard reports manual close, use the
        // closure message as-is and skip the hours-close boilerplate.
        $dashClose = SooDashboardSummary::manualCloseState();
        if ($dashClose !== null) {
            $oppening_msg = $dashClose['message'];
            $oppening_status["status"] = 'close';
            $oppening_status["accept_orders_when_closed"] = false;
        } elseif($this->pluginSettings['hours'] != 'all'){
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
        // Fail loud when in Global mode but the dashboard is unreachable —
        // mirrors the guard at the top of getCheckoutOptions(). Without it,
        // the order would be processed against a mix of dashboard overrides
        // (where they exist on the singleton's prior fetch) and legacy data.
        if (SooSettingsSource::instance($this->api)->globalFetchFailed()) {
            return new WP_Error(
                'settings_source_unavailable',
                'The central settings dashboard is temporarily unreachable. Please try again in a moment.',
                array('status' => 503)
            );
        }

        $body = json_decode($request->get_body(),true);
        $idempotencyKey = $request->get_header('Idempotency-Key');
        if (!empty($idempotencyKey)) {
            $body["idempotency_key"] = sanitize_text_field($idempotencyKey);
        } elseif (isset($body["idempotency_key"])) {
            $body["idempotency_key"] = sanitize_text_field($body["idempotency_key"]);
        } else {
            $body["idempotency_key"] = wp_generate_uuid4();
        }
        $customer_token =  (!empty($body["customer_token"])) ?  $body["customer_token"] : null;
        $googleReCAPTCHADisabled =  (bool) get_option('sooDisableGoogleReCAPTCHA',false);

        if (get_option('moo_old_checkout_enabled') === 'yes') {
            $googleReCAPTCHADisabled = true;
        }

        if (SooSettingsSource::current() === 'global') {
            $_dashForOverride = SooSettingsSource::instance($this->api)->dashboardClient()->fetch();
            if (is_array($_dashForOverride) && isset($_dashForOverride['checkout_settings']) && is_array($_dashForOverride['checkout_settings'])) {
                $this->applyDashboardCheckoutOverrides($_dashForOverride['checkout_settings']);
            }
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

        //Check blackout status — Global mode uses the mapper, Customized hits the legacy API.
        if (SooSettingsSource::current() === 'global') {
            $_dashForBlackout = SooSettingsSource::instance($this->api)->dashboardClient()->fetch();
            $blackoutStatusResponse = (new DashboardCheckoutMapper($_dashForBlackout))->mapStoreStatus();
        } else {
            $blackoutStatusResponse = $this->api->getBlackoutStatus();
        }
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
        if (! empty($body["special_instructions"]) ) {
            if (strlen($body["special_instructions"]) > 1000)
            return array(
                'status'	=> 'failed',
                'message'	=> "special instructions too long"
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

        //Convenience fee (display only, online payments only)
        if (!isset($body["convenience_fee"])) {
            $body["convenience_fee"] = 0;
        } else {
            $body["convenience_fee"] = intval($body["convenience_fee"]);
            if ($body["convenience_fee"] < 0) {
                $body["convenience_fee"] = 0;
            }
        }
        if ($body["payment_method"] === "cash") {
            $body["convenience_fee"] = 0;
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
        if(is_array($body["customer"])) {
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
            $orderTypeFromLocal  = (array)$this->model->getOneOrderTypes($orderTypeUuid);

            $isDelivery = ( isset($orderTypeFromLocal['show_sa']) && $orderTypeFromLocal['show_sa'] == "1" )?"Delivery":"Pickup";

            $note .= ' | '.$orderTypeFromLocal["label"];

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
        $body["convenience_fee"] = apply_filters('moo_filter_convenience_fee', $body["convenience_fee"]);

        $body = apply_filters('moo_filter_pre_create_order_body', $body);

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
          ["name"=>"pluginVersion","value"=>$this->version],
          ["name"=>"settingsSource","value"=>SooSettingsSource::current()]
        );
        //Add Few MetaData to the Order
        if (isset($body["metainfo"])  && is_array($body["metainfo"])){
            $body["metainfo"] = array_merge($body["metainfo"],$metaData);
        } else {
            $body["metainfo"] = $metaData;
        }

        //Add Ip to 3Ds
        if (!empty($body["threeds"]["browser_info"])) {
            $body["threeds"]["browser_info"]["browser_ip"] = $this->getClientIp();
        }

        //send request to the Api
        try {
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
                $orderResponse = apply_filters("moo_filter_order_creation_response",$orderCreated);
                if (is_array($orderResponse)) {
                    $orderResponse["confirmation_message"] = isset($this->pluginSettings["confirmation_message"])
                        ? $this->pluginSettings["confirmation_message"]
                        : null;
                }
                return $orderResponse;
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
        if(isset($request["moo_customer_token"]) && !empty($request["moo_customer_token"])){
            $token = $request["moo_customer_token"];
        }
        //TODO : Verify this
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

    public function finalize3DsPayment( $request ) {
        $body = json_decode($request->get_body(),true);

        $charge_id = sanitize_text_field($body['charge_id']);
        $flow_status = sanitize_text_field($body['flow_status']);

        if(empty($charge_id) || empty($flow_status)){
            return array(
                'status'	=> 'error',
                'message'   => 'Please send all required fields'
            );
        }
        $payload = array(
            "flow_status" => $flow_status,
            "charge_id" => $charge_id,
        );
        $result = $this->api->finalize3DsPayment($payload);
        var_dump($result);
        return array(
            'status'	=> 'failed',
        );
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
    private function getClientIp() {
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

    private function isAsapAllowed()
    {
        if(isset($this->pluginSettings['order_later_asap_for_p']) && $this->pluginSettings['order_later_asap_for_p'] == 'on')
        {
            return true;
        }
        if(isset($this->pluginSettings['order_later_asap_for_d']) && $this->pluginSettings['order_later_asap_for_d'] == 'on')
        {
            return true;
        }
        return false;
    }

    private function isHideUnavailableItemsEnabled()
    {
        if(isset($this->pluginSettings['track_stock_hide_items']) && $this->pluginSettings['track_stock_hide_items'] == 'on')
        {
            return true;
        }
        if(isset($this->pluginSettings['hide_category_ifnotavailable']) && $this->pluginSettings['hide_category_ifnotavailable'] == 'on')
        {
            return true;
        }
        return false;
    }

    private function applyDashboardCheckoutOverrides(array $checkoutSettings) {
        $this->pluginSettings['tips'] = !empty($checkoutSettings['tipsEnabled']) ? 'enabled' : 'disabled';
        $this->pluginSettings['tips_selection'] = $this->dashboardListToCsv(
            isset($checkoutSettings['tipsSuggested']) ? $checkoutSettings['tipsSuggested'] : array()
        );
        $this->pluginSettings['tips_default'] = $this->dashboardScalarToString(
            isset($checkoutSettings['tipsDefault']) ? $checkoutSettings['tipsDefault'] : null
        );
        $this->pluginSettings['service_fees'] = $this->dashboardScalarToString(
            isset($checkoutSettings['serviceFeeAmount']) ? $checkoutSettings['serviceFeeAmount'] : null
        );
        $this->pluginSettings['service_fees_type'] = $this->dashboardScalarToString(
            isset($checkoutSettings['serviceFeeType']) ? $checkoutSettings['serviceFeeType'] : 'amount',
            'amount'
        );
        $this->pluginSettings['service_fees_name'] = $this->dashboardScalarToString(
            isset($checkoutSettings['serviceFeeName']) ? $checkoutSettings['serviceFeeName'] : null
        );
        $this->pluginSettings['use_sms_verification'] = !empty($checkoutSettings['smsVerificationEnabled']) ? 'enabled' : 'disabled';
        $this->pluginSettings['use_special_instructions'] = !empty($checkoutSettings['specialInstructionsEnabled']) ? 'enabled' : 'disabled';
        $this->pluginSettings['special_instructions_required'] = !empty($checkoutSettings['specialInstructionsRequired']) ? 'yes' : 'no';
        $this->pluginSettings['text_under_special_instructions'] = $this->dashboardScalarToString(
            isset($checkoutSettings['specialInstructionsText']) ? $checkoutSettings['specialInstructionsText'] : null
        );
        $this->pluginSettings['marketing_checkbox_enabled'] = !empty($checkoutSettings['marketingCheckboxEnabled']) ? 'on' : 'off';
        $this->pluginSettings['marketing_checkbox_text'] = $this->dashboardScalarToString(
            isset($checkoutSettings['marketingCheckboxText']) ? $checkoutSettings['marketingCheckboxText'] : null
        );
        $this->pluginSettings['use_coupons'] = !empty($checkoutSettings['useCoupons']) ? 'enabled' : 'disabled';
        $this->pluginSettings['track_stock'] = !empty($checkoutSettings['enableStockTracking']) ? 'enabled' : 'disabled';
        $this->pluginSettings['track_stock_hide_items'] = !empty($checkoutSettings['hideUnavailableItems']) ? 'on' : 'off';
        $this->pluginSettings['confirmation_message'] = $this->dashboardScalarToString(
            isset($checkoutSettings['confirmation_message']) ? $checkoutSettings['confirmation_message'] : null
        );
        $this->pluginSettings['show_order_number'] = !empty($checkoutSettings['showOrderNumberOnReceipt']) ? 'on' : 'off';
        $this->pluginSettings['rollout_order_number_max'] = $this->dashboardScalarToString(
            isset($checkoutSettings['orderNumberRollOverLimit']) ? $checkoutSettings['orderNumberRollOverLimit'] : null,
            '999'
        );
        $this->pluginSettings['rollout_order_number'] =
            (!empty($checkoutSettings['showOrderNumberOnReceipt']) && $this->pluginSettings['rollout_order_number_max'] !== '')
                ? 'on'
                : 'off';
        $this->pluginSettings['print_ahead_time_minutes'] = $this->dashboardScalarToString(
            isset($checkoutSettings['printAheadTimeMinutes']) ? $checkoutSettings['printAheadTimeMinutes'] : null
        );
        $notifyOnNewOrders = !array_key_exists('notifyOnNewOrders', $checkoutSettings) || !empty($checkoutSettings['notifyOnNewOrders']);
        $this->pluginSettings['_dashboard_notify_on_new_orders'] = $notifyOnNewOrders ? 'on' : 'off';
        $this->pluginSettings['merchant_email'] = $notifyOnNewOrders
            ? $this->dashboardListToDelimitedString(
                isset($checkoutSettings['notificationEmailList']) ? $checkoutSettings['notificationEmailList'] : array(),
                ','
            )
            : '';
        $this->pluginSettings['merchant_phone'] = $notifyOnNewOrders
            ? $this->dashboardListToDelimitedString(
                isset($checkoutSettings['notificationPhoneList']) ? $checkoutSettings['notificationPhoneList'] : array(),
                '__'
            )
            : '';

        $announcement = isset($checkoutSettings['announcement']) && is_array($checkoutSettings['announcement'])
            ? $checkoutSettings['announcement']
            : array();
        $announcementEnabled = !array_key_exists('enabled', $announcement) || !empty($announcement['enabled']);
        if ($announcementEnabled) {
            $this->pluginSettings['custom_sa_title'] = $this->dashboardScalarToString(
                isset($announcement['title']) ? $announcement['title'] : null
            );
            $this->pluginSettings['custom_sa_content'] = $this->dashboardScalarToString(
                isset($announcement['content']) ? $announcement['content'] : null
            );
            $this->pluginSettings['custom_sa_onCheckoutPage'] = !empty($announcement['showOnCheckout']) ? 'on' : 'off';
        } else {
            $this->pluginSettings['custom_sa_title'] = '';
            $this->pluginSettings['custom_sa_content'] = '';
            $this->pluginSettings['custom_sa_onCheckoutPage'] = 'off';
        }

        if (array_key_exists('paymentMethods', $checkoutSettings)) {
            $paymentFlags = $this->dashboardPaymentMethodFlags($checkoutSettings['paymentMethods']);
            $this->pluginSettings['clover_payment_form'] = $paymentFlags['clover_payment_form'] ? 'on' : 'off';
            $this->pluginSettings['payment_cash'] = $paymentFlags['payment_cash'] ? 'on' : 'off';
            $this->pluginSettings['payment_cash_delivery'] = $paymentFlags['payment_cash_delivery'] ? 'on' : 'off';
            $this->pluginSettings['clover_giftcards'] = $paymentFlags['clover_giftcards'] ? 'on' : 'off';
            $this->pluginSettings['clover_googlepay'] = $paymentFlags['clover_googlepay'] ? 'on' : 'off';
            $this->pluginSettings['payment_creditcard'] = 'off';
        }
    }

    private function dashboardListToCsv($value) {
        return $this->dashboardListToDelimitedString($value, ',');
    }

    private function dashboardListToDelimitedString($value, $delimiter) {
        if (!is_array($value)) {
            return '';
        }

        $parts = array();
        foreach ($value as $oneValue) {
            if (is_scalar($oneValue) && $oneValue !== '') {
                $parts[] = (string) $oneValue;
            }
        }

        return implode($delimiter, $parts);
    }

    private function dashboardScalarToString($value, $default = '') {
        if ($value === null) {
            return $default;
        }
        if (is_scalar($value)) {
            return (string) $value;
        }
        return $default;
    }

    private function dashboardPaymentMethodFlags($paymentMethods) {
        $flags = array(
            'clover_payment_form' => false,
            'payment_cash' => false,
            'payment_cash_delivery' => false,
            'clover_giftcards' => false,
            'clover_googlepay' => false,
            'apple_pay' => false,
        );

        if (!is_array($paymentMethods)) {
            return $flags;
        }

        $hasGenericCash = false;

        foreach ($paymentMethods as $paymentMethod) {
            $normalized = $this->normalizeDashboardPaymentMethod($paymentMethod);
            if ($normalized === '') {
                continue;
            }

            if ($normalized === 'applepay') {
                $flags['apple_pay'] = true;
                continue;
            }

            if ($normalized === 'googlepay') {
                $flags['clover_googlepay'] = true;
                continue;
            }

            if ($normalized === 'giftcard' || $normalized === 'giftcards') {
                $flags['clover_giftcards'] = true;
                continue;
            }

            if ($normalized === 'cashondelivery' || $normalized === 'cashdelivery') {
                $flags['payment_cash_delivery'] = true;
                continue;
            }

            if ($normalized === 'cashpickup' || $normalized === 'cashatlocation' || $normalized === 'cashinstore') {
                $flags['payment_cash'] = true;
                continue;
            }

            if ($normalized === 'cash') {
                $hasGenericCash = true;
                continue;
            }

            if ($normalized === 'clover' || $normalized === 'creditcard' || $normalized === 'card' || $normalized === 'online') {
                $flags['clover_payment_form'] = true;
            }
        }

        if ($hasGenericCash) {
            $flags['payment_cash'] = true;
            $flags['payment_cash_delivery'] = true;
        }

        return $flags;
    }

    private function normalizeDashboardPaymentMethod($paymentMethod) {
        $raw = '';

        if (is_array($paymentMethod)) {
            if (array_key_exists('enabled', $paymentMethod) && empty($paymentMethod['enabled'])) {
                return '';
            }

            foreach (array('code', 'slug', 'type', 'name', 'label', 'value', 'id') as $key) {
                if (isset($paymentMethod[$key]) && is_scalar($paymentMethod[$key]) && $paymentMethod[$key] !== '') {
                    $raw = (string) $paymentMethod[$key];
                    break;
                }
            }
        } elseif (is_scalar($paymentMethod) && $paymentMethod !== '') {
            $raw = (string) $paymentMethod;
        }

        if ($raw === '') {
            return '';
        }

        return strtolower(preg_replace('/[^a-z0-9]+/', '', $raw));
    }

    /**
     * Coerce values from the resolver (which may be 'on'/'off'/bool/int) into bool.
     */
    private static function asBool($value) {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 'on' || $value === 1 || $value === '1' || $value === 'true' || $value === true) {
            return true;
        }
        return false;
    }
}
