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

        register_rest_route( $this->v3Namespace, '/cart/price', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'priceCartDocument' ),
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
        $response["fb_appid"] = (isset($this->pluginSettings['fb_appid']) ? $this->pluginSettings['fb_appid'] : '');
        $response["order_types"] = $this->orderTypes($request);
        $response["opening_status"] = $this->openingStatus($request);
        $response["special_instructions"] = array(
            "accept_special_instructions"=> isset($this->pluginSettings['use_special_instructions']) && $this->pluginSettings['use_special_instructions'] == "enabled",
            "text"=>$this->pluginSettings['text_under_special_instructions'],
            "is_required"=> isset($this->pluginSettings['special_instructions_required']) && $this->pluginSettings['special_instructions_required'] === "yes",
        );
        $response["utensils_checkbox"] = array(
            "enabled"=> isset($this->pluginSettings['utensils_checkbox_enabled']) && $this->pluginSettings['utensils_checkbox_enabled'] === "on",
            "text"=> !empty($this->pluginSettings['utensils_checkbox_text'])
                ? wp_unslash($this->pluginSettings['utensils_checkbox_text'])
                : __("Request utensils, napkins, etc.", "moo_OnlineOrders"),
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
        // Offer a site key unless a provider has cleared this merchant's clients from the
        // check, or no key is configured to begin with.
        $verdict = $this->verifyRequest(array('surface' => 'options', 'action' => 'createOrder'));
        $response["recaptchaKey"] = ($verdict['outcome'] !== 'pass' && !empty($this->pluginSettings['reCAPTCHA_site_key']))
            ? $this->pluginSettings['reCAPTCHA_site_key']
            : null;
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
     * Thin wrapper over Moo_OnlineOrders_Helpers::throttle() that shapes a REST error.
     *
     * @return true|WP_Error
     */
    private function throttle($bucket, $identity, array $windows, $message) {
        if (Moo_OnlineOrders_Helpers::throttle($bucket, $identity, $windows)) {
            return true;
        }

        return new WP_Error('too_many_requests', $message, array('status' => 429));
    }

    /**
     * Throttle checkout attempts per client IP.
     *
     * /checkout is registered with permission_callback => '__return_true', so this is one
     * of only two channel-agnostic brakes on automated order creation.
     *
     * REMOTE_ADDR is used deliberately: forwarding headers are caller-supplied and would
     * make the bucket key unreliable. Sites behind a proxy or CDN should map the real
     * client IP through the soo_checkout_rate_limit_ip filter.
     *
     * @return true|WP_Error
     */
    private function checkoutRateLimit() {
        $ip = apply_filters('soo_checkout_rate_limit_ip', Moo_OnlineOrders_Helpers::clientIp());
        if (empty($ip)) {
            return true;
        }

        // window length in seconds => attempts allowed within that window. Every
        // attempt counts, refused ones included (a declined card, a full pickup
        // slot, a backend refusal), and one IP is often a whole office, campus or
        // carrier NAT, so the caps are set for a busy lunch rush rather than a
        // single customer: 5/8/50 tripped real customers within one session.
        // Tune per merchant volume via the filter.
        $windows = apply_filters('soo_checkout_rate_limit_windows', array(
            600   => 15,
            3600  => 40,
            86400 => 200,
        ));

        return $this->throttle(
            'cort',
            $ip,
            $windows,
            'Too many checkout attempts from this location. Please wait a few minutes and try again.'
        );
    }

    /**
     * Throttle for pricing a cart document.
     *
     * Deliberately a separate bucket from checkoutRateLimit(). Pricing is something a
     * customer does repeatedly while building an order — every quantity change re-prices —
     * whereas placing an order is a handful of attempts at most. Sharing one bucket meant a
     * customer who adjusted a quantity twice and applied a coupon had spent the checkout
     * budget and was refused their own order.
     *
     * The caps are therefore generous: this exists to stop an anonymous caller hammering an
     * unauthenticated endpoint, not to ration ordinary use. A shared address — a household,
     * an office, a carrier NAT — must still be able to build several carts at once. Raise
     * them per merchant through the filter if a busy store ever needs it.
     *
     * @return true|WP_Error
     */
    private function cartPriceRateLimit() {
        $ip = apply_filters('soo_checkout_rate_limit_ip', Moo_OnlineOrders_Helpers::clientIp());
        if (empty($ip)) {
            return true;
        }

        $windows = apply_filters('soo_cart_price_rate_limit_windows', array(
            600   => 120,
            3600  => 600,
            86400 => 3000,
        ));

        return $this->throttle(
            'cprice',
            $ip,
            $windows,
            'Too many cart updates from this location. Please wait a moment and try again.'
        );
    }

    /**
     * Whether guest (unauthenticated) checkout is allowed for this merchant.
     *
     * Resolved the same way as getCheckoutOptions() and the checkout page, so all three
     * agree. Defaults to allowed: a settings payload that fails to load, or predates the
     * flag, must not start turning away real orders on its own.
     *
     * @return bool
     */
    private function guestCheckoutEnabled() {
        if (SooSettingsSource::current() === 'global') {
            $dash = SooSettingsSource::instance($this->api)->dashboardClient()->fetch();
            $checkoutSettings = (new DashboardCheckoutMapper($dash))->mapCheckoutSettings();
        } else {
            $checkoutSettings = $this->api->getCheckoutSettings();
        }

        if ( ! is_array($checkoutSettings) || ! array_key_exists('guestModeEnabled', $checkoutSettings) ) {
            return true;
        }

        $raw = $checkoutSettings['guestModeEnabled'];
        if ($raw === null || $raw === '') {
            return true;
        }

        // Only an unambiguous false disables guest checkout. checkoutPage.php resolves ''
        // to false, which is harmless when it merely hides the "Continue As Guest" button,
        // but here it would reject every guest order on an empty or malformed value. The
        // two diverge only in that case, and only in the direction that still takes the
        // order.
        $parsed = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return ($parsed === null) ? true : $parsed;
    }

    /**
     * Stable fingerprint of a cart, or null when there is nothing usable to fingerprint.
     *
     * Built from item ids and their counts plus the order type — deliberately not from the
     * whole payload, because a per-line uuid or timestamp would make every cart unique and
     * silently disable duplicateCartGuard().
     *
     * @param array $body decoded request body
     * @return string|null
     */
    private function cartSignature($body) {
        $counts = $this->cartItemCounts($body);

        if (empty($counts)) {
            return null;
        }

        ksort($counts);
        $parts = array();
        foreach ($counts as $id => $n) {
            $parts[] = $id . 'x' . $n;
        }
        $parts[] = 'ot:' . (isset($body['order_type']) ? (string) $body['order_type'] : '');

        return sha1(implode('|', $parts));
    }

    /**
     * Reject an identical guest cart arriving far more often than real demand explains.
     *
     * Keyed on the order rather than the source identity, and sized from real guest
     * ordering patterns so that normal repeat demand stays well inside the limits.
     *
     * Signed-in requests are not counted: a regular reordering their usual cart is exactly
     * the false positive worth avoiding.
     *
     * @param array       $body          decoded request body
     * @param string|null $customerToken set when the customer is signed in
     * @return true|WP_Error
     */
    /**
     * Quantity ordered per item id, from whichever cart this request actually carries.
     *
     * The guard runs before any branch has populated $body['cart'], so reading only that
     * key fingerprinted the branded app and nothing else — the website sends no cart in its
     * body at all, and a browser cart sends a document. That left the guard inert for the
     * one channel the fraud arrived on.
     *
     * All three sources are counted the same way — item id against total quantity — so the
     * same basket produces the same signature however it was submitted. A caller cannot get
     * a fresh bucket by changing which representation it sends.
     *
     * Precedence matters: a document wins over a cart.items in the same request, because
     * otherwise a caller could attach a decoy cart.items to randomise the key while the
     * document did the ordering.
     *
     * @param array $body decoded request body
     * @return array item id => quantity
     */
    private function cartItemCounts($body) {
        $counts = array();

        if ( isset($body['cart_document']) ) {
            $document = SooCartDocument::parse($body['cart_document']);
            foreach ($document['lines'] as $line) {
                $id = (string) $line['item_uuid'];
                $counts[$id] = (isset($counts[$id]) ? $counts[$id] : 0) + (int) $line['quantity'];
            }
            return $counts;
        }

        if ( isset($body['cart']['items']) && is_array($body['cart']['items']) ) {
            // Already expanded: a quantity of three arrives as three entries.
            foreach ($body['cart']['items'] as $line) {
                if ( ! is_array($line) || ! isset($line['item']['id']) ) {
                    continue;
                }
                $id = (string) $line['item']['id'];
                $counts[$id] = isset($counts[$id]) ? $counts[$id] + 1 : 1;
            }
            return $counts;
        }

        // The website posts no cart at all — its basket is in the session.
        $sessionItems = $this->session->get("items");
        if ( is_array($sessionItems) ) {
            foreach ($sessionItems as $line) {
                if ( ! is_array($line) || ! isset($line['item']->uuid) ) {
                    continue;
                }
                $id = (string) $line['item']->uuid;
                $qty = isset($line['quantity']) ? (int) $line['quantity'] : 0;
                if ($qty > 0) {
                    $counts[$id] = (isset($counts[$id]) ? $counts[$id] : 0) + $qty;
                }
            }
        }

        return $counts;
    }

    private function duplicateCartGuard($body, $customerToken) {
        if ( ! empty($customerToken) ) {
            return true;
        }

        if ( ! apply_filters('soo_duplicate_cart_guard_enabled', true) ) {
            return true;
        }

        $signature = $this->cartSignature($body);
        if ($signature === null) {
            return true;
        }

        // Thresholds carry roughly 2-3x headroom over the busiest guest demand observed for
        // a single cart. High-volume merchants selling one dominant combo will need these
        // raised — the cost of getting it wrong is turning away a real order.
        $windows = apply_filters('soo_duplicate_cart_windows', array(
            3600  => 6,
            86400 => 15,
        ));

        $result = $this->throttle(
            'dupcart',
            $signature,
            $windows,
            'We are seeing an unusual number of identical orders right now. Please try again shortly, or sign in to continue.'
        );

        if (is_wp_error($result)) {
            error_log('SOO duplicate-cart guard rejected a checkout. signature=' . $signature);
        }

        return $result;
    }

    /**
     * @param $request
     * @body json
     * @return array
     */
    public function checkout( $request ) {
        $rateLimited = $this->checkoutRateLimit();
        if (is_wp_error($rateLimited)) {
            return $rateLimited;
        }

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
        // The upstream API does not honour idempotency_key (verified: a replay
        // with the same key attempts a second order), so the plugin keeps its
        // own record per key. A client that lost the response to a proxy
        // timeout re-sends the same request and gets the stored answer back
        // instead of a second order.
        $idempotencyState = $this->idempotencyState($body["idempotency_key"]);
        if ($idempotencyState !== null) {
            return $idempotencyState;
        }
        $customer_token =  (!empty($body["customer_token"])) ?  $body["customer_token"] : null;

        // guestModeEnabled used to be honoured only by the checkout page, which hid the
        // "Continue As Guest" button while this route kept accepting guest orders — so a
        // merchant who switched it off was still taking them. Enforce it here, where it
        // counts: with guest mode off, an order needs a signed-in customer.
        if ( empty($customer_token) && ! $this->guestCheckoutEnabled() ) {
            return array(
                'status'  => 'failed',
                'code'    => 'login_required',
                'message' => __('Please sign in to place your order.', 'moo_OnlineOrders')
            );
        }

        $duplicateCart = $this->duplicateCartGuard($body, $customer_token);
        if (is_wp_error($duplicateCart)) {
            return $duplicateCart;
        }

        if (SooSettingsSource::current() === 'global') {
            $_dashForOverride = SooSettingsSource::instance($this->api)->dashboardClient()->fetch();
            if (is_array($_dashForOverride) && isset($_dashForOverride['checkout_settings']) && is_array($_dashForOverride['checkout_settings'])) {
                $this->applyDashboardCheckoutOverrides($_dashForOverride['checkout_settings']);
            }
        }

        // Verify the request. Resolved after the settings overrides above, which are the
        // last thing to touch the plugin settings the provider reads.
        $recaptchaVerdict = $this->verifyRequest(array(
            'token'         => isset($body["reCAPTCHA_token"]) ? $body["reCAPTCHA_token"] : null,
            'action'        => 'createOrder',
            'body'          => $body,
            'authenticated' => !empty($customer_token),
            'surface'       => 'checkout',
        ));

        if ($recaptchaVerdict['outcome'] === 'fail') {
            // The upstream response is not echoed back to the caller.
            return array(
                'status'	=> 'failed',
                'message'	=> __("Oops! It seems there was an issue with the reCAPTCHA. Don't worry, these things happen. Please try submitting the form again. We apologize for any inconvenience caused.", 'moo_OnlineOrders')
            );
        }

        // The order carries how it was cleared: a pass, an unreachable verifier the backend
        // should hold to stricter limits, or nothing to verify against.
        if ($recaptchaVerdict['outcome'] === 'pass') {
            $body["reCAPTCHA_token"] = 'isValid';
        } elseif ($recaptchaVerdict['outcome'] === 'unavailable') {
            $body["reCAPTCHA_token"] = 'unverified';
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
                'message'	=> __("Payment method is required", 'moo_OnlineOrders')
            );
        } else {
            if($body["payment_method"]  === "clover") {
                if(!isset($body["token"])){
                    return array(
                        'status'	=> 'failed',
                        'message'	=> __("Payment Token is required", 'moo_OnlineOrders')
                    );
                }
            }
        }
        if (! isset($body["customer"]) ) {
            return array(
                'status'	=> 'failed',
                'message'	=> __("Customer is required", 'moo_OnlineOrders')
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

        //flag the utensils request on the ticket
        if(!empty($body['utensils_requested']) && $body['utensils_requested'] !== 'false'){
            $note .=' | '.__("Utensils requested", "moo_OnlineOrders");
        }

        if(isset($body['scheduled_time'])){
            $note .=' | '.$body['scheduled_time'];
        }

        // A cart_document request is priced entirely server-side, so tax removal must come from
        // the order type below and never from the request. The order-type block only ever sets
        // this to true, so a client value would otherwise survive on a taxable order.
        if ( isset( $body["cart_document"] ) ) {
            unset( $body["tax_removed"] );
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

        // Remembered for the order's metainfo: cart_document is consumed and
        // removed below, so this is the last place the cart mode is visible.
        $cartVersion = isset( $body["cart_document"] ) ? 'modern' : 'legacy';

        // A browser-held cart. The document carries ids and quantities only, so every
        // figure below is computed here from the local tables — anything the client said
        // about amounts is ignored. See the spec, §7.
        if ( isset( $body["cart_document"] ) ) {
            $document = SooCartDocument::parse( $body["cart_document"] );
            if ( ! empty( $document['errors'] ) ) {
                return array(
                    'status'  => 'failed',
                    'code'    => $document['errors'][0],
                    'message' => __( 'Your cart could not be read. Please refresh the page and try again.', 'moo_OnlineOrders' ),
                );
            }

            // Item notes only reach the kitchen when the merchant accepts them
            // (the same per-theme + checkout-level switches the UI gates on).
            // A note stored before the switch was turned off would otherwise
            // still ride along on the order.
            if ( ! $this->itemNotesEnabled() ) {
                foreach ( $document['lines'] as $i => $line ) {
                    $document['lines'][ $i ]['note'] = '';
                }
            }

            $resolver = new SooCartResolver( $this->model, $this->api );
            $resolved = $resolver->resolve( $document );

            // An unorderable line stops the order here. The cart route reports problems so
            // the UI can mark them; this route refuses, because a bad line must never
            // become an order. Checked before the empty-items case below, because a document
            // whose only line is an unknown item resolves to both: an empty $resolved['items']
            // AND $resolved['valid'] === false, and the more specific, more informative
            // cart_invalid response must win.
            if ( ! $resolved['valid'] ) {
                $firstProblem = null;
                foreach ( $resolved['lines'] as $line ) {
                    if ( ! $line['ok'] ) { $firstProblem = $line; break; }
                }
                return array(
                    'status'  => 'failed',
                    'code'    => 'cart_invalid',
                    'lines'   => $resolved['lines'],
                    'message' => $firstProblem['problems'][0]['message'],
                );
            }

            if ( empty( $resolved['items'] ) ) {
                return array(
                    'status'  => 'failed',
                    'message' => __("It looks like your cart is empty", 'moo_OnlineOrders')
                );
            }

            // The whole point of a cart_document request is that the server decides every
            // figure; a client-supplied totals block, a client-supplied coupon, and the raw
            // document itself (up to 100 lines of dead weight) must never reach the upstream
            // API. The coupon this route sends, if any, is derived below from the document's
            // own code, never from what the client put in $body["coupon"].
            unset( $body["totals"] );
            unset( $body["cart_document"] );
            unset( $body["coupon"] );

            $serviceFee      = isset( $body["service_fee"] ) ? intval( $body["service_fee"] ) : 0;
            $deliveryAmount  = isset( $body["delivery_amount"] ) ? intval( $body["delivery_amount"] ) : 0;
            if ( $serviceFee < 0 )     { $serviceFee = 0; }
            if ( $deliveryAmount < 0 ) { $deliveryAmount = 0; }

            // A merchant with coupons switched off never has a code resolved against
            // the backend here either -- the cart is priced (and charged) at full
            // value, and a stored code is ignored, never honoured. See
            // couponsEnabled()'s doc comment; applyDashboardCheckoutOverrides() has
            // already run for this request in Global mode, at :859 above.
            $coupon      = $this->couponsEnabled() ? $this->resolveDocumentCoupon( $document['coupon'] ) : null;
            $cartTotals  = SooCartPricer::totals( $resolved['items'], $coupon, $deliveryAmount, $serviceFee );

            if ( ! $cartTotals ) {
                return array(
                    'status'  => 'failed',
                    'message' => __("It looks like your cart is empty", 'moo_OnlineOrders')
                );
            }

            $body["cart"]            = SooCartPricer::lineItems( $resolved['items'] );
            $body["service_fee"]     = $serviceFee;
            $body["delivery_amount"] = $deliveryAmount;

            // Loyalty points: the client sends only a raw point count (use_points), never a
            // dollar amount. The remote validates that count against the customer's real
            // balance for its own order/receipt record, but charges the payment split using
            // the amount this route computes — so a discount left out here is a real
            // overcharge, not just a display mismatch. Verify the count against the real
            // balance and price it from the merchant's own rate settings before it ever
            // touches $body["amount"]; never trust a client-sent dollar figure for it.
            $rawUsePoints   = $this->normalizeUsePoints( isset( $body["use_points"] ) ? $body["use_points"] : 0 );
            $pointsDiscount = 0;

            if ( $rawUsePoints > 0 ) {
                if ( empty( $customer_token ) ) {
                    // canAcceptPoints() in sooCheckout.js keeps guests from ever setting
                    // use_points, so a raw count with no customer_token is not a real
                    // redemption this route can verify. No discount, and the bogus count
                    // is not forwarded upstream.
                    $rawUsePoints = 0;
                } else {
                    $loyaltySetting = $this->resolveLoyaltySetting();
                    $pointsResult   = $this->resolveDocumentPointsDiscount( $rawUsePoints, $customer_token, $loyaltySetting, $cartTotals );

                    if ( ! $pointsResult['ok'] ) {
                        // Refuse the order rather than complete it without the discount
                        // (undercounts the redemption) or trust the raw count (reopens the
                        // fraud hole this verification exists to close).
                        return array(
                            'status'  => 'failed',
                            'code'    => 'points_verification_failed',
                            'message' => $pointsResult['message'],
                        );
                    }

                    $rawUsePoints   = $pointsResult['points'];
                    $pointsDiscount = $pointsResult['discount'];
                }
            }
            $body["use_points"] = $rawUsePoints;

            $taxRemoved = isset( $body["tax_removed"] ) && is_bool( $body["tax_removed"] ) && $body["tax_removed"];
            $documentAmount = $this->computeDocumentAmount( $cartTotals, $serviceFee, $deliveryAmount, $taxRemoved, $pointsDiscount );
            $body["tax_amount"] = $documentAmount["tax_amount"];
            $body["amount"]     = $documentAmount["amount"];

            if ( ! empty( $cartTotals["coupon_name"] ) ) {
                $body["coupon"] = array( "code" => $document['coupon'] );
            }
        }

        //Get the cart from the session if isn't sent from the frontend
        if(!isset($body["cart"]["items"]) && !isset($body["cart_document"])){

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
                        'message'=> __("It looks like your cart is empty", 'moo_OnlineOrders')
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
                        'message'=> __("It looks like your cart is empty", 'moo_OnlineOrders')
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
                                'message'	=> sprintf(__('The item %1$s is low on stock. Please go back and change the quantity in your cart %2$s', 'moo_OnlineOrders'), $this->getItemName($cartLine), (($itemStock["stockCount"]>0) ? sprintf(__('as we have only %s left', 'moo_OnlineOrders'), $itemStock["stockCount"]) : ""))
                            );
                        } else {
                            if($itemStock["stockCount"] < 1) {
                                return array(
                                    'status'	=> 'failed',
                                    'code'	=> 'low_stock',
                                    'item'	=> $cartLine['item']["id"],
                                    'message'	=> sprintf(__('The item %s is out of stock', 'moo_OnlineOrders'), $this->getItemName($cartLine))
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
        $body["utensils_requested"] = ! empty( $body["utensils_requested"] ) && $body["utensils_requested"] !== 'false';
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
          ["name"=>"settingsSource","value"=>SooSettingsSource::current()],
          // 'modern' = the browser-held cart (Modern Cart beta), 'legacy' = the PHP session cart
          ["name"=>"cartVersion","value"=>$cartVersion]
        );

        // Carry the reCAPTCHA verdict through to the backend. Until now the raw token was
        // overwritten with 'isValid' before the order was sent, so the score — the only part
        // of v3 that says anything about the caller — never left the site.
        if (is_array($recaptchaVerdict)) {
            $metaData[] = ["name"=>"recaptchaOutcome","value"=>$recaptchaVerdict['outcome']];
            $metaData[] = ["name"=>"recaptchaScore","value"=>($recaptchaVerdict['score'] === null) ? '' : (string) $recaptchaVerdict['score']];
            $metaData[] = ["name"=>"recaptchaAction","value"=>(string) $recaptchaVerdict['action']];
            $metaData[] = ["name"=>"recaptchaHostname","value"=>(string) $recaptchaVerdict['hostname']];
            $metaData[] = ["name"=>"recaptchaReason","value"=>$recaptchaVerdict['reason']];
        }
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

        // Loyalty points: send use_points only when points are actually being
        // spent. A zero count is not "no points" to the remote -- it runs the
        // merchant's minimum-points rule on it and refuses the whole order with
        // "Minimum points to order are: N", for a customer who never touched
        // the points box. Both paths reach here: the cart_document branch sets
        // $body["use_points"] from the verified count (0 when none), the legacy
        // branch forwards the client's own field.
        if (isset($body["use_points"]) && intval($body["use_points"]) <= 0) {
            unset($body["use_points"]);
        }

        //send request to the Api
        // The upstream call can outlive what sits in front of PHP (a proxy or
        // FPM limit answers 502/504 to the browser while the order is still
        // being created). Keep running so the order finishes and its result
        // is recorded under the idempotency key for the client's retry.
        ignore_user_abort(true);
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }
        $this->idempotencyStart($body["idempotency_key"]);
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
                // confirmation_message comes from the API response as-is; there
                // is no local setting for it.
                $response = apply_filters("moo_filter_order_creation_response",$orderCreated);
                $this->idempotencyFinish($body["idempotency_key"], $response);
                return $response;
            } else {
                $this->idempotencyFinish($body["idempotency_key"], null);
                return $this->orderRefusedResponse($body["idempotency_key"]);
            }
        } catch (Exception  $e){
            $this->idempotencyFinish($body["idempotency_key"], null);
            return array(
                "status"=>"failed",
                "message"=>__("An error has occurred please try again","moo_OnlineOrders")
            );
        }
    }

    /**
     * Idempotency record for a checkout key. Three states: none (proceed),
     * in progress (the first request is still creating the order -- tell the
     * client to wait and ask again), done (an order exists -- hand back the
     * same response). Kept ten minutes; long enough to cover a proxy timeout
     * and the customer's retries, short enough that the same cart ordered
     * again later is a new order. Only responses carrying an order id are
     * kept: a refusal leaves no record, so an honest retry goes through.
     */
    private function idempotencyKeyName($key) {
        return 'soo_idem_' . md5((string) $key);
    }

    private function idempotencyState($key) {
        if (empty($key)) {
            return null;
        }
        $state = get_transient($this->idempotencyKeyName($key));
        if (!is_array($state) || empty($state['status'])) {
            return null;
        }
        if ($state['status'] === 'done' && is_array($state['response'])) {
            $response = $state['response'];
            $response['replayed'] = true;
            return $response;
        }
        if ($state['status'] === 'in_progress') {
            return array(
                'status'  => 'pending',
                'code'    => 'order_in_progress',
                'message' => __("Your order is still being sent to the store, please wait a moment.", 'moo_OnlineOrders'),
            );
        }
        return null;
    }

    private function idempotencyStart($key) {
        if (!empty($key)) {
            set_transient($this->idempotencyKeyName($key), array('status' => 'in_progress', 'started' => time()), 10 * MINUTE_IN_SECONDS);
        }
    }

    private function idempotencyFinish($key, $response) {
        if (empty($key)) {
            return;
        }
        if (is_array($response) && !empty($response['id'])) {
            set_transient($this->idempotencyKeyName($key), array('status' => 'done', 'response' => $response), 10 * MINUTE_IN_SECONDS);
        } elseif ($this->lastRequestTimedOut()) {
            // The API never answered but may still be creating the order:
            // keep the record so a retry waits it out instead of ordering
            // twice. Nothing will mark it done, so the client ends on the
            // 'check your email before ordering again' message.
            set_transient($this->idempotencyKeyName($key), array('status' => 'in_progress', 'started' => time(), 'timed_out' => true), 10 * MINUTE_IN_SECONDS);
        } else {
            delete_transient($this->idempotencyKeyName($key));
        }
    }

    /**
     * Did the last API call end in a transport timeout (cURL 28), as opposed
     * to a refusal or a connection that never opened?
     */
    private function lastRequestTimedOut() {
        $lastError = is_array($this->api->last_error) ? $this->api->last_error : array();
        if (!isset($lastError['error_code']) || $lastError['error_code'] !== 'http_request_failed') {
            return false;
        }
        $message = isset($lastError['message']) ? strtolower((string) $lastError['message']) : '';
        return strpos($message, 'timed out') !== false || strpos($message, 'timeout') !== false || strpos($message, 'error 28') !== false;
    }
    /**
     * createOrderV2() returned false. When the backend refused the order
     * itself (a 4xx with its own JSON, e.g. 409 pickup_slot_full), show the
     * customer that reason; anything else -- transport error, 5xx, an empty
     * body -- keeps the generic message.
     *
     * @return array
     */
    private function orderRefusedResponse($idempotencyKey = '') {
        $lastError = is_array($this->api->last_error) ? $this->api->last_error : array();
        // One line in the PHP error log per refusal, with the key, so a
        // refusal can be matched against what the backend did with it.
        error_log(sprintf('[moo_OnlineOrders] order refused: http=%s key=%s body=%s',
            isset($lastError['httpCode']) ? $lastError['httpCode'] : (isset($lastError['error_code']) ? $lastError['error_code'] : '-'),
            $idempotencyKey,
            isset($lastError['responseContent']) ? substr((string) $lastError['responseContent'], 0, 300) : (isset($lastError['message']) ? $lastError['message'] : '')
        ));
        if (isset($lastError['httpCode']) && $lastError['httpCode'] >= 400 && $lastError['httpCode'] <= 499
            && !empty($lastError['responseContent'])) {
            $refusal = json_decode($lastError['responseContent'], true);
            if (is_array($refusal) && !empty($refusal['message']) && is_string($refusal['message'])) {
                $response = array(
                    "status"  => "failed",
                    "message" => sanitize_text_field($refusal['message'])
                );
                if (!empty($refusal['error']) && is_string($refusal['error'])) {
                    $response["code"] = sanitize_key($refusal['error']);
                }
                return $response;
            }
        }
        return array(
            "status"=>"failed",
            "message"=>__("An error has occurred please try again","moo_OnlineOrders")
        );
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
                'message'   => __('Please send the phone number', 'moo_OnlineOrders')
            );
        }
        if ( ! Moo_OnlineOrders_Helpers::smsSendAllowed($phone_number) ) {
            return array(
                'status'	=> 'error',
                'message'   => __('Too many verification codes requested. Please wait a few minutes and try again.', 'moo_OnlineOrders')
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
                'message'   => __('Please send the code', 'moo_OnlineOrders')
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
                'message'   => __('Please send the coupon code', 'moo_OnlineOrders')
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
                    "message" =>__("Coupon not found", 'moo_OnlineOrders')
                );
            }
        } else {
            $response = array(
                'status'	=> 'failed',
                "message" =>__("Please enter the coupon code", 'moo_OnlineOrders')
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
                'message'   => __('Please send all required fields', 'moo_OnlineOrders')
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
     * Price a browser-held cart document.
     *
     * Stateless and read-only: no session is written and no row is stored. Every price
     * comes from the local tables via SooCartResolver and every total from
     * SooCartPricer — the same arithmetic the session cart uses, so the two can never
     * disagree. The document carries no money and none is read from it.
     *
     * @param WP_REST_Request $request
     * @return array
     */
    public function priceCartDocument( $request ) {
        // Registered with permission_callback => '__return_true', and resolving a document
        // is not free (one or more DB queries per line and per modifier), so it is
        // throttled. Its own bucket, not checkout()'s: a customer re-prices repeatedly
        // while building an order, and sharing one budget would refuse them the checkout
        // their own cart edits had already spent.
        $rateLimited = $this->cartPriceRateLimit();
        if ( is_wp_error( $rateLimited ) ) {
            return $rateLimited;
        }

        // Global mode maps use_coupons (and everything else
        // applyDashboardCheckoutOverrides() maps) from the dashboard's
        // checkout_settings, same as checkout() does at ~line 859 before reading any
        // plugin setting. Without this, this route would keep reading the *local*
        // use_coupons value even in Global mode -- the same partial-coverage gap
        // that previously hit the wallet flags and the cart flag.
        //
        // Cached across requests, unlike checkout(). dashboardClient()->fetch() is
        // memoized only for the current PHP request, and this route fires on every
        // debounced quantity change -- so a live fetch here would put one dashboard
        // round trip on the critical path of every re-price. This route reads only
        // *settings* from the payload (never store status or hours, which the checkout
        // page and checkout() still fetch live), and a setting being up to a minute
        // stale on a cart re-price costs nothing: the authoritative read happens again,
        // live, in checkout() before anything is charged. Keyed per blog, since the
        // payload is per merchant.
        if ( SooSettingsSource::current() === 'global' ) {
            $_dashForOverride = $this->cachedDashboardSettingsForPricing();
            if ( is_array( $_dashForOverride ) ) {
                $this->applyDashboardCheckoutOverrides( $_dashForOverride );
            }
        }

        $document = SooCartDocument::parse( json_decode( $request->get_body(), true ) );

        if ( ! empty( $document['errors'] ) ) {
            return array(
                'status'  => 'failed',
                'code'    => $document['errors'][0],
                'message' => __( 'Your cart could not be read. Please refresh the page and try again.', 'moo_OnlineOrders' ),
            );
        }

        $resolver = new SooCartResolver( $this->model, $this->api );
        $resolved = $resolver->resolve( $document );

        if ( empty( $resolved['items'] ) ) {
            return array(
                'status'  => 'failed',
                'code'    => 'empty_cart',
                'message' => __( 'It looks like your cart is empty', 'moo_OnlineOrders' ),
            );
        }

        // A merchant with coupons switched off never has a code resolved against the
        // backend, and the cart is priced at full value -- a stored code is ignored,
        // never honoured. See couponsEnabled()'s doc comment.
        $couponsEnabled = $this->couponsEnabled();
        $coupon = $couponsEnabled ? $this->resolveDocumentCoupon( $document['coupon'] ) : null;
        $totals = SooCartPricer::totals( $resolved['items'], $coupon );

        // Derived from the same $coupon/$totals the pricer already produced, before
        // '_coupon_writeback' is stripped below -- see deriveCouponOutcome()'s own doc
        // comment for why this is safe without touching SooCartPricer itself.
        $couponOutcome = null;
        if ( is_array( $totals ) ) {
            $couponOutcome = $this->deriveCouponOutcome( $document['coupon'], $coupon, $totals, $couponsEnabled );
        }

        // The pricer hands back a coupon to persist; this route stores nothing, so it is
        // dropped rather than leaked to the client. See SooCartPricer::totals().
        if ( is_array( $totals ) ) {
            unset( $totals['_coupon_writeback'] );
        }

        return array(
            'status' => 'success',
            'valid'  => $resolved['valid'],
            'lines'  => $resolved['lines'],
            'totals' => $totals,
            'coupon' => $couponOutcome,
        );
    }

    /**
     * Builds the client-facing `coupon` block for /v3/cart/price: why the code the
     * cart is carrying did or did not discount this order. Pure computation over the
     * coupon SooCartPricer::totals() was given and the totals it returned -- it does
     * not re-run or duplicate any of the pricer's arithmetic, only reads the outcome
     * the pricer already reached, so it cannot drift from what actually happened
     * there. This is deliberate: SooCartPricer.php is golden-master verified by
     * check-equivalence.php and shared with the session cart, so the reason is
     * derived here instead of adding any new branch to it.
     *
     * $coupon is safe to read after totals() runs: PHP arrays are value types, so
     * SooCartPricer::totals() setting its own local $coupon to null (on the
     * minimum-spend rule, SooCartPricer.php:210-213) never touches this caller's
     * copy -- it is still resolveDocumentCoupon()'s original result, minAmount and
     * all.
     *
     * @param string|null $code    The coupon code as sent in the document
     *                             (SooCartDocument-parsed, so already trimmed/
     *                             truncated). Empty/null means no coupon on the cart.
     * @param array|null  $coupon  resolveDocumentCoupon()'s result: the API's coupon
     *                             record when the code validated, or null when it
     *                             didn't (not found, expired, or any other non-
     *                             "success" status -- resolveDocumentCoupon() does
     *                             not distinguish these, so neither does this).
     * @param array       $totals  SooCartPricer::totals()'s return array (still
     *                             carrying '_coupon_writeback' at this point).
     * @param bool        $couponsEnabled Whether the merchant currently accepts
     *                             coupons at all (couponsEnabled()). Defaults to
     *                             true so every pre-existing call site (and this
     *                             harness's pre-existing assertions) keeps its
     *                             exact prior behaviour unchanged. When false,
     *                             this short-circuits to the 'disabled' reason
     *                             before ever looking at $coupon/$totals -- a
     *                             cart carrying a stale code (applied before the
     *                             merchant turned coupons off) must read as
     *                             "coupons aren't available", never as "this code
     *                             is invalid", even though the caller also skips
     *                             resolveDocumentCoupon() entirely in that case
     *                             (so $coupon is always null here too).
     * @return array|null null when the cart carries no coupon code at all.
     */
    private function deriveCouponOutcome( $code, $coupon, $totals, $couponsEnabled = true ) {
        if ( empty( $code ) ) {
            return null;
        }

        if ( ! $couponsEnabled ) {
            return array(
                'code'      => $code,
                'applied'   => false,
                'reason'    => 'disabled',
                'message'   => __("Coupons aren't available for this store", 'moo_OnlineOrders'),
                'shortfall' => 0,
                'capped'    => false,
            );
        }

        $subTotal    = isset( $totals['sub_total'] ) ? intval( $totals['sub_total'] ) : 0;
        $couponValue = isset( $totals['coupon_value'] ) ? intval( $totals['coupon_value'] ) : 0;

        // Not found, expired, or any other non-"success" API status -- the code
        // itself never resolved to a real coupon.
        if ( empty( $coupon ) ) {
            return array(
                'code'      => $code,
                'applied'   => false,
                'reason'    => 'invalid',
                'message'   => __('This coupon code is not valid.', 'moo_OnlineOrders'),
                'shortfall' => 0,
                'capped'    => false,
            );
        }

        $minAmountCents = isset( $coupon['minAmount'] ) ? intval( round( floatval( $coupon['minAmount'] ) * 100 ) ) : 0;

        // A valid coupon that SooCartPricer::totals() zeroed out because this order
        // doesn't reach its minimum spend (SooCartPricer.php:210-213). $couponValue
        // is only ever 0 here for that reason once $minAmountCents > $subTotal is
        // also true -- checked explicitly rather than inferred from $couponValue
        // alone, since a $0-value coupon or a $0 subtotal can also produce
        // $couponValue <= 0 without being a minimum-spend failure (see the fallback
        // below).
        if ( $couponValue <= 0 && $minAmountCents > $subTotal ) {
            $shortfall = $minAmountCents - $subTotal;
            return array(
                'code'      => $code,
                'applied'   => false,
                'reason'    => 'below_minimum',
                'message'   => sprintf( 'Spend %s more to use %s.', self::formatCentsForMessage( $shortfall ), $code ),
                'shortfall' => $shortfall,
                'capped'    => false,
            );
        }

        if ( $couponValue > 0 ) {
            // '_coupon_writeback' is non-null only when SooCartPricer::totals()
            // capped a percentage coupon to its configured maxValue
            // (SooCartPricer.php:214-221) -- read here, not recomputed, so this can
            // never disagree with what the pricer actually capped.
            $capped  = ! empty( $totals['_coupon_writeback'] );
            $message = $capped
                ? sprintf( '%s applied — %s maximum discount applied.', $code, self::formatCentsForMessage( $couponValue ) )
                : sprintf( '%s applied.', $code );
            return array(
                'code'      => $code,
                'applied'   => true,
                'reason'    => 'applied',
                'message'   => $message,
                'shortfall' => 0,
                'capped'    => $capped,
            );
        }

        // Defensive fallback: a coupon that validated and isn't below its minimum
        // spend but still discounts nothing at all (e.g. a $0 subtotal, or a coupon
        // configured with a $0 value). Not one of the two failure reasons above, but
        // a client must never see applied=false with no reason at all.
        return array(
            'code'      => $code,
            'applied'   => false,
            'reason'    => 'no_effect',
            'message'   => sprintf( '%s could not be applied to this order.', $code ),
            'shortfall' => 0,
            'capped'    => false,
        );
    }

    /**
     * Formats an integer cents amount as a US-dollar string for a human-readable
     * coupon message, e.g. 450 -> "$4.50". Matches how every other client-facing
     * message in this file is plain, non-localized English (see
     * SooCartResolver.php's problem messages) rather than routed through __().
     *
     * @param int $cents
     * @return string
     */
    private static function formatCentsForMessage( $cents ) {
        return '$' . number_format( intval( $cents ) / 100, 2 );
    }

    /**
     * True when the merchant currently accepts coupons. Reads $this->pluginSettings,
     * the same array every other setting in this file reads (BaseRoute's constructor
     * filters it through moo_filter_plugin_settings; Global mode further overwrites it
     * via applyDashboardCheckoutOverrides()) -- so a caller only sees the merchant's
     * real, currently-active choice as long as applyDashboardCheckoutOverrides() has
     * already run for this request when in Global mode (checkout() does this at
     * ~line 859; priceCartDocument() mirrors it). 'enabled' is the only truthy value
     * 'use_coupons' ever takes (SooSettingsRegistry: type bool_toggle), matching
     * checkoutPage.php's own `== "enabled"` checks that hide the legacy page's coupon
     * UI on the same setting.
     *
     * @return bool
     */
    /**
     * checkout_settings from the dashboard payload, cached for the cart-price route.
     *
     * A short transient rather than the client's per-request memo: /v3/cart/price is
     * called on every debounced quantity change, and each call is its own PHP request,
     * so without this every re-price in Global mode paid for a live dashboard round
     * trip. Only the settings block is cached -- store status and hours are not read
     * here -- and checkout() still fetches live before charging, so a stale setting on
     * a re-price can never reach a charge.
     *
     * A failed fetch is not cached: the next re-price retries rather than pinning a
     * failure for the TTL. Returns null when nothing usable is available, in which case
     * the caller leaves the local settings untouched -- the same fallback checkout()
     * has always used when the dashboard is unreachable.
     *
     * @return array|null checkout_settings, or null
     */
    private function cachedDashboardSettingsForPricing() {
        $key = 'soo_price_cs_' . get_current_blog_id();
        $cached = get_transient( $key );
        if ( is_array( $cached ) ) {
            return $cached;
        }
        $dash = SooSettingsSource::instance( $this->api )->dashboardClient()->fetch();
        if ( ! is_array( $dash ) || ! isset( $dash['checkout_settings'] ) || ! is_array( $dash['checkout_settings'] ) ) {
            return null;
        }
        set_transient( $key, $dash['checkout_settings'], 60 );
        return $dash['checkout_settings'];
    }

    private function couponsEnabled() {
        return isset( $this->pluginSettings['use_coupons'] ) && $this->pluginSettings['use_coupons'] === 'enabled';
    }

    /**
     * Per-item special instructions: the same decision the cart UI's
     * "Add note" is gated on (sooShortCode::newCartParams()).
     */
    private function itemNotesEnabled() {
        if ( ! class_exists( 'sooShortCode' ) ) {
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'shortcodes/sooShortCode.php';
        }
        $params = sooShortCode::newCartParams( $this->pluginSettings );
        return ! empty( $params['itemNotesEnabled'] );
    }

    /**
     * Validate a coupon code against the backend and return it in the shape the pricer
     * expects, or null. The client sends a code, never a discount — the amount is always
     * decided here.
     *
     * @param string|null $code
     * @return array|null
     */
    private function resolveDocumentCoupon( $code ) {
        if ( empty( $code ) ) {
            return null;
        }
        $response = json_decode( $this->api->moo_checkCoupon( $code ), true );
        if ( ! is_array( $response ) || ! isset( $response['status'] ) || $response['status'] !== 'success' ) {
            return null;
        }
        return $response;
    }

    /**
     * Coerces a client-sent use_points value to a sane non-negative int. Garbage
     * (non-numeric strings, floats, negative numbers) all become 0 — no redemption,
     * rather than an error, since a malformed count is not something the customer can
     * fix by retrying the same click.
     *
     * @param mixed $raw
     * @return int
     */
    private function normalizeUsePoints( $raw ) {
        $n = intval( $raw );
        if ( $n < 0 ) { $n = 0; }
        return $n;
    }

    /**
     * The cart_document branch's amount/tax_amount formula, with the points discount
     * (already verified and priced by resolveDocumentPointsDiscount()) folded in.
     * $pointsDiscount of 0 reproduces the pre-fix formula exactly.
     *
     * @param array $cartTotals     SooCartPricer::totals() result.
     * @param int   $serviceFee
     * @param int   $deliveryAmount
     * @param bool  $taxRemoved
     * @param int   $pointsDiscount Non-negative, already capped to the order.
     * @return array {amount:int, tax_amount:int}
     */
    private function computeDocumentAmount( $cartTotals, $serviceFee, $deliveryAmount, $taxRemoved, $pointsDiscount ) {
        if ( $taxRemoved ) {
            $taxAmount = 0;
            $amount    = $cartTotals["sub_total"] + $serviceFee + $deliveryAmount - $cartTotals["coupon_value"] - $pointsDiscount;
        } else {
            $taxAmount = $cartTotals["total_of_taxes"];
            $amount    = $cartTotals["total"] + $serviceFee + $deliveryAmount - $pointsDiscount;
        }
        if ( $amount < 0 ) {
            $amount = 0;
        }
        return array( "amount" => $amount, "tax_amount" => $taxAmount );
    }

    /**
     * The merchant's own loyalty rate/limit settings (per_x_usd, nb_points,
     * percent_to_cover, ...) — resolved the same way checkoutPage.php resolves them for the
     * widget the customer used to pick a point count: Global mode reads the dashboard's
     * checkout_settings.loyaltySetting, Customized mode reads the public business-settings
     * endpoint. Never anything the client sent on the request.
     *
     * @return array|null
     */
    private function resolveLoyaltySetting() {
        if ( SooSettingsSource::current() === 'global' ) {
            $dash = SooSettingsSource::instance( $this->api )->dashboardClient()->fetch();
            if ( is_array( $dash ) && isset( $dash['checkout_settings']['loyaltySetting'] ) && is_array( $dash['checkout_settings']['loyaltySetting'] ) ) {
                return $dash['checkout_settings']['loyaltySetting'];
            }
            return null;
        }
        $businessSettings = $this->api->getBusinessSettings();
        if ( is_array( $businessSettings ) && isset( $businessSettings['loyaltySetting'] ) && is_array( $businessSettings['loyaltySetting'] ) ) {
            return $businessSettings['loyaltySetting'];
        }
        return null;
    }

    /**
     * Verifies a redeemed points count against the customer's real balance
     * (merchants/customers/points) and prices the discount from the merchant's own
     * server-fetched rate/limit settings — never from anything the client sent beyond the
     * raw count. Called only when use_points > 0 and a customer_token is present
     * (canAcceptPoints() in sooCheckout.js is what keeps guests from ever setting it).
     *
     * On any failure to verify — the balance call errors, times out, or returns something
     * unusable, or the merchant's own rate settings cannot be resolved — this returns
     * ok=false so the caller refuses the order outright. Completing the order trusting the
     * raw count, or silently dropping the discount, both reproduce the money defect this
     * exists to close.
     *
     * Pure aside from the one $this->api->customerRequestsWrapper() call, so it is testable
     * by constructing CheckoutRoutes with a fake $api — no live request needed.
     *
     * The returned 'points' is always exactly consistent with 'discount' under the
     * merchant's rate: when the subtotal/percent_to_cover cap binds, the point count is
     * re-derived from the capped discount (floored, never rounded up — see the inline
     * comment below) rather than forwarding the balance-clamped count as-is. Forwarding it
     * unmodified there is the money defect this method exists to close: it would spend the
     * customer's full count for less than its value. When the cap does not bind, 'points'
     * is simply the balance-clamped raw count, unchanged.
     *
     * @param int        $rawPoints      Client-sent point count, already > 0.
     * @param string     $customerToken  Always present when this is called.
     * @param array|null $loyaltySetting Merchant's server-fetched rate/limit settings.
     * @param array      $cartTotals     SooCartPricer::totals() result, for the subtotal cap.
     * @return array {ok:true, points:int, discount:int} or {ok:false, message:string}
     */
    private function resolveDocumentPointsDiscount( $rawPoints, $customerToken, $loyaltySetting, $cartTotals ) {
        $failure = array(
            'ok'      => false,
            'message' => __( "We couldn't verify your loyalty points balance right now. Please try again.", 'moo_OnlineOrders' ),
        );

        if ( ! is_array( $loyaltySetting )
            || ! isset( $loyaltySetting['per_x_usd'] ) || ! is_numeric( $loyaltySetting['per_x_usd'] )
            || ! isset( $loyaltySetting['nb_points'] ) || ! is_numeric( $loyaltySetting['nb_points'] )
            || floatval( $loyaltySetting['per_x_usd'] ) <= 0
            || floatval( $loyaltySetting['nb_points'] ) <= 0
        ) {
            return $failure;
        }

        $balanceResponse = $this->api->customerRequestsWrapper( 'merchants/customers/points', null, $customerToken, false );
        if ( $balanceResponse === false
            || ! is_array( $balanceResponse )
            || ! isset( $balanceResponse['valid_points'] )
            || ! is_numeric( $balanceResponse['valid_points'] )
        ) {
            return $failure;
        }

        $realBalance = intval( $balanceResponse['valid_points'] );
        if ( $realBalance < 0 ) { $realBalance = 0; }

        $clampedPoints = min( $rawPoints, $realBalance );
        if ( $clampedPoints <= 0 ) {
            return array( 'ok' => true, 'points' => 0, 'discount' => 0 );
        }

        $perXUsd  = floatval( $loyaltySetting['per_x_usd'] );
        $nbPoints = floatval( $loyaltySetting['nb_points'] );
        $discount = intval( round( $clampedPoints * $perXUsd / $nbPoints ) );
        if ( $discount < 0 ) { $discount = 0; }

        // Cap to the same base the redemption UI caps against — subtotal minus coupon —
        // and to the merchant's own percent-of-order limit, if one is set.
        $subTotalWithoutCoupon = $cartTotals['sub_total'] - $cartTotals['coupon_value'];
        if ( $subTotalWithoutCoupon < 0 ) { $subTotalWithoutCoupon = 0; }

        $percentToCover = 100;
        if ( isset( $loyaltySetting['percent_to_cover'] ) && is_numeric( $loyaltySetting['percent_to_cover'] ) ) {
            $percentToCover = floatval( $loyaltySetting['percent_to_cover'] );
        }
        if ( $percentToCover < 0 ) { $percentToCover = 0; }

        $maxDiscount = intval( round( $subTotalWithoutCoupon * $percentToCover / 100 ) );

        if ( $discount > $maxDiscount ) {
            // The balance-clamped count is worth more than the order can absorb (subtotal
            // and/or percent_to_cover). Re-derive the point count from the capped discount
            // instead of forwarding $clampedPoints as-is — forwarding it here is exactly the
            // bug this method exists to close: it would spend the customer's full count for
            // only $maxDiscount of value and silently lose the rest.
            //
            // Round the point count DOWN, never up: rounding up would spend a whole extra
            // point for a fraction of its value (the same class of loss, just smaller).
            // Rounding down means the recomputed discount below can come out a hair under
            // $maxDiscount (never over — see the inequality note below) — that residual is
            // cents the customer keeps as order total instead of as discount, which is a
            // fair trade against keeping the fractional point in their balance. It never
            // costs the merchant anything they didn't already offer.
            $discount = $maxDiscount;
            if ( $discount <= 0 ) {
                $clampedPoints = 0;
                $discount      = 0;
            } else {
                $clampedPoints = (int) floor( $discount * $nbPoints / $perXUsd );
                if ( $clampedPoints <= 0 ) {
                    // Less than one point's worth of value survived the cap: spend no
                    // points at all rather than floor to some point count that buys
                    // nothing (or a point whose value would exceed the cap).
                    $clampedPoints = 0;
                    $discount      = 0;
                } else {
                    // Recompute the discount from the rounded-down count with the exact
                    // same forward formula used above, so use_points and discount are
                    // exactly consistent under the merchant's rate. Because $clampedPoints
                    // = floor($discount * $nbPoints / $perXUsd), algebraically
                    // $clampedPoints * $perXUsd / $nbPoints <= $discount always, so this
                    // round() can equal $maxDiscount but never exceed it.
                    $discount = intval( round( $clampedPoints * $perXUsd / $nbPoints ) );
                }
            }
        }
        if ( $discount < 0 ) { $discount = 0; }

        // The merchant's per-order minimum applies to the count actually spent, and
        // the cap above can push that count below it: a customer redeems 240 against a
        // large cart (passing the client-side minimum check in usePoints()), then edits
        // the cart down, and the capped count comes out at, say, 60. Forwarding 60 with
        // a minimum of 100 makes the backend reject the whole order -- "Minimum points
        // to order are: 100" -- at the moment of payment, for a rule the customer
        // satisfied when they redeemed.
        //
        // Below the minimum, points are simply not used: count and discount both go
        // to zero, the order proceeds at full price, and the customer keeps every
        // point. The client mirrors this in getAppliedPoints() so the widget and the
        // total agree with what is charged. A minimum of 0 (or absent) disables the
        // rule, matching the client.
        $minPoints = isset( $loyaltySetting['min_points_to_order'] ) ? intval( $loyaltySetting['min_points_to_order'] ) : 0;
        if ( $minPoints > 0 && $clampedPoints > 0 && $clampedPoints < $minPoints ) {
            $clampedPoints = 0;
            $discount      = 0;
        }

        return array( 'ok' => true, 'points' => $clampedPoints, 'discount' => $discount );
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
        // Order scheduling — see the matching block in checkoutPage so the page and this
        // route resolve the same values. Without it the route keeps the local scheduling
        // settings while its slots come from the dashboard.
        $this->pluginSettings['order_later'] = !empty($checkoutSettings['scheduleOrder']) ? 'on' : 'off';
        $this->pluginSettings['order_later_mandatory'] = !empty($checkoutSettings['isScheduledTimeRequired']) ? 'on' : 'off';
        if (isset($checkoutSettings['scheduleDayRange'])) {
            $this->pluginSettings['order_later_days'] = (int) $checkoutSettings['scheduleDayRange'];
            $this->pluginSettings['order_later_days_delivery'] = (int) $checkoutSettings['scheduleDayRange'];
        }
        if (isset($checkoutSettings['leadTime'])) {
            $this->pluginSettings['order_later_minutes'] = (int) $checkoutSettings['leadTime'];
            $this->pluginSettings['order_later_minutes_delivery'] = (int) $checkoutSettings['leadTime'];
        }
        $dashAsap = !empty($checkoutSettings['allowAsap']) ? 'on' : 'off';
        $this->pluginSettings['order_later_asap_for_p'] = $dashAsap;
        $this->pluginSettings['order_later_asap_for_d'] = $dashAsap;

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
        $this->pluginSettings['utensils_checkbox_enabled'] = !empty($checkoutSettings['utensilsCheckboxEnabled']) ? 'on' : 'off';
        $this->pluginSettings['utensils_checkbox_text'] = $this->dashboardScalarToString(
            isset($checkoutSettings['utensilsCheckboxText']) ? $checkoutSettings['utensilsCheckboxText'] : null
        );
        $this->pluginSettings['new_cart_enabled'] = !empty($checkoutSettings['newCartEnabled']) ? 'on' : 'off';
        $this->pluginSettings['new_cart_expiry_hours'] = isset($checkoutSettings['newCartExpiryHours'])
            ? (int) $checkoutSettings['newCartExpiryHours']
            : 24;
        $this->pluginSettings['use_coupons'] = !empty($checkoutSettings['useCoupons']) ? 'enabled' : 'disabled';
        $this->pluginSettings['track_stock'] = !empty($checkoutSettings['enableStockTracking']) ? 'enabled' : 'disabled';
        $this->pluginSettings['track_stock_hide_items'] = !empty($checkoutSettings['hideUnavailableItems']) ? 'on' : 'off';
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

        // Lowercase first: the dashboard sends camelCase ("giftCard", "googlePay"), and
        // stripping with a lowercase-only class dropped the capitals ("giftard").
        return preg_replace('/[^a-z0-9]+/', '', strtolower($raw));
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
