<?php

require_once "sooShortCode.php";

class checkoutPage extends sooShortCode
{
    /**
     * Display or not the header in checkoutPage
     *  Change this to false if you want to hide our header that contains information about the benefits of using an account
     * @var bool
     */
    private $displayPageHeader = true;

    /**
     * use or not alternateNames
     * @var bool
     */
    private $showStreetAddressFieldOnPaymentForm;


    public function standardCheckout($atts, $content)
    {
        $this->enqueueStyles();
        $this->enqueueScripts();

        ob_start();
        $session = MOO_SESSION::instance();
        //check store availibilty

        if (isset($this->pluginSettings['accept_orders']) && $this->pluginSettings['accept_orders'] === "disabled") {
            if (isset($this->pluginSettings["closing_msg"]) && $this->pluginSettings["closing_msg"] !== '') {
                $oppening_msg = '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'.$this->pluginSettings["closing_msg"].'</div>';
            } else {
                $oppening_msg = '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'.__("We are currently closed and will open again soon", "moo_OnlineOrders").'</div>';
            }
            return '<div id="moo_CheckoutContainer" >'.$oppening_msg.'</div>';
        }

        //Get blackout status
        $blackoutStatusResponse = $this->api->getBlackoutStatus();
        if (isset($blackoutStatusResponse["status"]) && $blackoutStatusResponse["status"] === "close") {
            if (isset($blackoutStatusResponse["custom_message"]) && !empty($blackoutStatusResponse["custom_message"])) {
                $oppening_msg = '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'.$blackoutStatusResponse["custom_message"].'</div>';
            } else {
                $oppening_msg = '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'.__("We are currently closed and will open again soon", "moo_OnlineOrders").'</div>';
            }
            return '<div id="moo_CheckoutContainer" >'.$oppening_msg.'</div>';
        }

        $orderTypes = $this->model->getVisibleOrderTypes();
        if (!is_array($orderTypes)) {
            $orderTypes = array();
        }
        // Get ordertypes times
        $counter = $this->model->getOrderTypesWithCustomHours();
        if (isset($counter->nb) && $counter->nb > 0) {
            $HoursResponse = $this->api->getMerchantCustomHoursStatus("ordertypes");
            if ($HoursResponse) {
                $merchantCustomHoursStatus = $HoursResponse;
                $merchantCustomHours = array_keys($HoursResponse);
            } else {
                $merchantCustomHoursStatus = array();
                $merchantCustomHours = array();
            }
        } else {
            $merchantCustomHoursStatus = array();
            $merchantCustomHours = array();
        }

        $nbOfOrderTypes = count($orderTypes);
        $nbOfUnvailableOrderTypes = null;
        if (@count($merchantCustomHours) > 0 && $nbOfOrderTypes > 0) {
            $nbOfUnvailableOrderTypes = 0;
            for ($i=0; $i<$nbOfOrderTypes; $i++) {
                $orderType  = $orderTypes[$i];
                $orderTypes[$i]->available = true;
                if (isset($orderType->custom_hours) && !empty($orderType->custom_hours)) {
                    if (in_array($orderType->custom_hours, $merchantCustomHours)) {
                        $isNotAvailable = $merchantCustomHoursStatus[$orderType->custom_hours] === "close";
                        if ($isNotAvailable) {
                            //unset($orderTypes[$i]);
                            $orderTypes[$i]->available = false;
                            $nbOfUnvailableOrderTypes++;
                        }
                    }
                }
            }
        }
        /*
        if($nbOfOrderTypes === $nbOfUnvailableOrderTypes ){
            echo '<div id="moo_checkout_msg">This store cannot accept orders right now, please come back later</div>';
            return ob_get_clean();
        }
        */

        //Force disabling payment_creditcard payment method
        if (isset($this->pluginSettings["payment_creditcard"]) && $this->pluginSettings["payment_creditcard"] == "on") {
            $this->pluginSettings["clover_payment_form"] = "on";
            $this->pluginSettings["payment_creditcard"] = "off";
        }


        if (isset($this->pluginSettings["clover_payment_form"]) && $this->pluginSettings["clover_payment_form"] == "on") {
            $cloverPakmsKey = $this->api->getPakmsKey();
            if ($cloverPakmsKey) {
                $cloverCodeExist = true;
            } else {
                $cloverPakmsKey = null;
            }
        } else {
            $cloverPakmsKey = null;
        }

        $custom_js  = $this->pluginSettings["custom_js"];


        if (is_double($this->pluginSettings['fixed_delivery']) && $this->pluginSettings['fixed_delivery'] > 0) {
            $fixedDeliveryFees = floatval($this->pluginSettings['fixed_delivery']) * 100;
        } else {
            $fixedDeliveryFees = 0;
        }

        if (isset($this->pluginSettings['service_fees'])  && floatval($this->pluginSettings['service_fees']) > 0) {
            if (isset($this->pluginSettings['service_fees_type']) && $this->pluginSettings['service_fees_type'] === "percent") {
                $serviceFees = floatval($this->pluginSettings['service_fees']);
                $serviceFeesType = "percent";
            } else {
                $serviceFees = intval(round(floatval($this->pluginSettings['service_fees']) * 100));
                $serviceFeesType = "amount";
            }
        } else {
            $serviceFees = 0;
            $serviceFeesType = "amount";
        }


        $totals = $session->getTotals($fixedDeliveryFees, $serviceFees, $serviceFeesType);

        $merchant_proprites = (json_decode($this->api->getMerchantProprietes())) ;

        //Coupons
        if (!$session->isEmpty("coupon")) {
            $coupon = $session->get("coupon");
            if ($coupon['minAmount']>$totals['sub_total']) {
                $coupon = null;
            }
        } else {
            $coupon = null;
        }

        if ($this->pluginSettings["order_later"] == "on") {
            $inserted_nb_days = $this->pluginSettings["order_later_days"];
            $inserted_nb_mins = $this->pluginSettings["order_later_minutes"];

            $inserted_nb_days_d = $this->pluginSettings["order_later_days_delivery"];
            $inserted_nb_mins_d = $this->pluginSettings["order_later_minutes_delivery"];

            if ($inserted_nb_days === "") {
                $nb_days = 4;
            } else {
                $nb_days = intval($inserted_nb_days);
            }

            if ($inserted_nb_mins === "") {
                $nb_minutes = 20;
            } else {
                $nb_minutes = intval($inserted_nb_mins);
            }

            if ($inserted_nb_days_d === "") {
                $nb_days_d = 4;
            } else {
                $nb_days_d = intval($inserted_nb_days_d);
            }

            if ($inserted_nb_mins_d === "") {
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


        $oppening_status = $this->api->getOpeningStatus($nb_days, $nb_minutes);
        $this->showStreetAddressFieldOnPaymentForm = $this->checkCloverFraudTools($oppening_status);

        if ($nb_days != $nb_days_d || $nb_minutes != $nb_minutes_d) {
            $oppening_status_d = $this->api->getOpeningStatus($nb_days_d, $nb_minutes_d);
        } else {
            $oppening_status_d = $oppening_status;
        }

        $oppening_msg = $this->getOpeningMessage($oppening_status);

        //Adding asap to pickup time
        if (isset($oppening_status["pickup_time"])) {
            if (isset($this->pluginSettings['order_later_asap_for_p']) && $this->pluginSettings['order_later_asap_for_p'] == 'on') {
                if (isset($oppening_status["pickup_time"]["Today"])) {
                    array_unshift($oppening_status["pickup_time"]["Today"], 'ASAP');
                }
            }
            if (isset($oppening_status["pickup_time"]["Today"])) {
                array_unshift($oppening_status["pickup_time"]["Today"], __("Select a time", "moo_OnlineOrders"));
            }
        }

        if (isset($oppening_status_d["pickup_time"])) {
            if (isset($this->pluginSettings['order_later_asap_for_d']) && $this->pluginSettings['order_later_asap_for_d'] == 'on') {
                if (isset($oppening_status_d["pickup_time"]["Today"])) {
                    array_unshift($oppening_status_d["pickup_time"]["Today"], 'ASAP');
                }
            }
            if (isset($oppening_status_d["pickup_time"]["Today"])) {
                array_unshift($oppening_status_d["pickup_time"]["Today"], __("Select a time", "moo_OnlineOrders"));
            }
        }

        if ($this->pluginSettings['hours'] != 'all' && $this->pluginSettings['accept_orders_w_closed'] != 'on' && $oppening_msg != "") {
            echo '<div id="moo_CheckoutContainer">'.$oppening_msg.'</div>';
            return ob_get_clean();
        }

        //show or hide the choose time section
        if (isset($this->pluginSettings['order_later']) && $this->pluginSettings['order_later'] == 'on') {
            if (is_array($oppening_status["pickup_time"]) && @count($oppening_status["pickup_time"])>0) {
                $showTimeSection = true;
            } else {
                if (isset($this->pluginSettings['order_later_mandatory']) && $this->pluginSettings['order_later_mandatory'] === "on") {
                    $showTimeSection = true;
                } else {
                    $showTimeSection = false;
                }
            }
        } else {
            $showTimeSection = false;
        }



        $merchant_address =  $this->api->getMerchantAddress();
        $store_page_id     = $this->pluginSettings['store_page'];
        $cart_page_id     = $this->pluginSettings['cart_page'];
        $checkout_page_id     = $this->pluginSettings['checkout_page'];

        $store_page_url    =  get_page_link($store_page_id);
        $cart_page_url    =  get_page_link($cart_page_id);
        $checkout_page_url    =  get_page_link($checkout_page_id);

        if (isset($this->pluginSettings['thanks_page_wp']) && !empty($this->pluginSettings['thanks_page_wp'])) {
            $this->pluginSettings['thanks_page'] = get_page_link($this->pluginSettings['thanks_page_wp']);
        }

        if (!isset($this->pluginSettings['save_cards'])) {
            $this->pluginSettings['save_cards'] = null;
        }
        if (!isset($this->pluginSettings['save_cards_fees'])) {
            $this->pluginSettings['save_cards_fees'] = null;
        }
        if (!isset($this->pluginSettings['delivery_errorMsg']) || empty($this->pluginSettings['delivery_errorMsg'])) {
            $this->pluginSettings['delivery_errorMsg'] = __("Sorry, zone not supported. We do not deliver to this address at this time", "moo_OnlineOrders");
        }
        if (!isset($this->pluginSettings['special_instructions_required']) || empty($this->pluginSettings['special_instructions_required'])) {
            $this->pluginSettings['special_instructions_required'] = false;
        }
        $mooCheckoutJsOptions = array(
            'moo_RestUrl' =>  get_rest_url(),
            "moo_OrderTypes"=>$orderTypes,
            "totals"=>$totals,
            "moo_Key"=>array(),
            "moo_thanks_page"=>$this->pluginSettings['thanks_page'],
            "moo_cash_upon_delivery"=>$this->pluginSettings['payment_cash_delivery'],
            "moo_cash_in_store"=>$this->pluginSettings['payment_cash'],
            "moo_pay_online"=>$this->pluginSettings['payment_creditcard'],
            "moo_pickup_time"=>$oppening_status["pickup_time"],
            "moo_pickup_time_for_delivery"=>$oppening_status_d["pickup_time"],
            "moo_fb_app_id"=>$this->pluginSettings['fb_appid'],
            "moo_scp"=>$this->pluginSettings['scp'],
            "moo_use_sms_verification"=>$this->pluginSettings['use_sms_verification'],
            "moo_checkout_login"=>$this->pluginSettings['checkout_login'],
            "moo_save_cards"=>$this->pluginSettings['save_cards'],
            "moo_save_cards_fees"=>$this->pluginSettings['save_cards_fees'],
            "moo_clover_payment_form"=>$this->pluginSettings['clover_payment_form'],
            "moo_clover_key"=>$cloverPakmsKey,
            "special_instructions_required"=>$this->pluginSettings['special_instructions_required'],
            "locale"=>str_replace("_", "-", get_locale()),
            "showStreetAddressField"=>$this->showStreetAddressFieldOnPaymentForm,
        );
        $mooDeliveryJsOptions = array(
            "moo_merchantLat"=>$this->pluginSettings['lat'],
            "moo_merchantLng"=>$this->pluginSettings['lng'],
            "moo_merchantAddress"=>$merchant_address,
            "zones"=>$this->pluginSettings['zones_json'],
            "other_zone_fee"=>$this->pluginSettings['other_zones_delivery'],
            "free_amount"=>$this->pluginSettings['free_delivery'],
            "fixed_amount"=>$this->pluginSettings['fixed_delivery'],
            "errorMsg"=>$this->pluginSettings['delivery_errorMsg']
        );



        wp_localize_script("sooCheckoutScript", "mooCheckoutOptions", $mooCheckoutJsOptions);
        wp_localize_script("sooCheckoutScript", "mooDeliveryOptions", $mooDeliveryJsOptions);


        if ($totals === false || !isset($totals['nb_items']) || $totals['nb_items'] < 1) {
            return $this->cartIsEmpty();
        };

        if ((isset($_GET['logout']) && $_GET['logout'])) {
            $session->delete("moo_customer_token");
            wp_redirect($checkout_page_url);
        }
        if ($this->pluginSettings['checkout_login'] == "disabled") {
            $session->delete("moo_customer_token");
        }
        ?>

        <div id="moo_CheckoutContainer">
            <div class="moo-row" id="moo-checkout">
                <div class="errors-section"></div>
                <?php echo $oppening_msg; ?>
                <div id="moo_merchantmap"></div>
                <!--            login               -->
                <div id="moo-login-form" <?php if ((!$session->isEmpty("moo_customer_token"))) {
                    echo 'style="display:none;"';
                                         }?> class="moo-col-md-12 ">
                    <?php if ($this->displayPageHeader) { ?>
                        <div class="moo-row login-top-section" tabindex="-1">
                            <div class="login-header" >
                                <?php
                                /* translators: %s represent our link */
                                $t = __("Why create a <a href='%s' target='_blank'>Smart Online Order</a> account?", 'moo_OnlineOrders');
                                printf($t, "https://www.smartonlineorder.com");
                                ?>
                            </div>
                            <div class="moo-col-md-6">
                                <ul>
                                    <li><?php _e("Save your address", "moo_OnlineOrders"); ?></li>
                                    <li><?php _e("Faster Checkout!", "moo_OnlineOrders"); ?></li>
                                </ul>
                            </div>
                            <div class="moo-col-md-6">
                                <ul>
                                    <li><?php _e("View your past orders", "moo_OnlineOrders"); ?></li>
                                    <li><?php _e("Get exclusive deals and coupons", "moo_OnlineOrders"); ?></li>
                                </ul>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="moo-col-md-6" tabindex="0">
                        <div class="moo-row login-social-section">
                            <?php if (!empty($this->pluginSettings['fb_appid'])) { ?>
                                <p>

                                    <strong><?php _e("Sign in with your social account", "moo_OnlineOrders"); ?></strong>
                                    <br />
                                    <small><?php _e("No posts on your behalf, promise!", "moo_OnlineOrders"); ?></small>
                                </p>
                                <div class="moo-row">
                                    <div class="moo-col-xs-12 moo-col-sm-6 moo-col-md-7 moo-col-md-offset-3 moo-col-sm-offset-3" >
                                        <a href="#" class="moo-btn moo-btn-lg moo-btn-primary moo-btn-block" onclick="moo_loginViaFacebook(event)" style="margin-top: 12px;" tabindex="0" aria-label="Sign in with your Facebook account">Facebook</a>
                                    </div>
                                    <div class="moo-col-xs-12 moo-col-sm-12 moo-col-md-7 moo-col-md-offset-3" tabindex="0">
                                        <div class="login-or">
                                            <hr class="hr-or">
                                            <span class="span-or"><?php _e("or", "moo_OnlineOrders"); ?></span>
                                        </div>
                                        <a role="button" class="moo-btn moo-btn-danger" onclick="moo_loginAsguest(event)" tabindex="0">
                                            <?php _e("Continue As Guest", "moo_OnlineOrders"); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <p>
                                    <?php _e("Don't want an account?", "moo_OnlineOrders"); ?>
                                    <br />
                                    <small><?php _e("You can checkout without registering", "moo_OnlineOrders"); ?></small>
                                </p>
                                <div class="moo-row">
                                    <div class="moo-col-xs-12 moo-col-sm-6 moo-col-md-9 moo-col-md-offset-2 moo-col-sm-offset-3">
                                        <a  role="button" tabindex="0" href="#" class="moo-btn moo-btn-lg moo-btn-primary moo-btn-block" onclick="moo_loginAsguest(event)" style="margin-top: 12px;"> <?php _e("Continue As Guest", "moo_OnlineOrders"); ?></a>
                                    </div>
                                    <div class="moo-col-xs-12 moo-col-sm-12 moo-col-md-9 moo-col-md-offset-2">
                                        <div class="login-or">
                                            <hr class="hr-or">
                                            <span class="span-or"><?php _e("or", "moo_OnlineOrders"); ?></span>
                                        </div>
                                        <a  class="moo-btn moo-btn-danger" onclick="moo_show_sigupform(event)">
                                            <?php _e("Create An Account", "moo_OnlineOrders"); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php  } ?>
                        </div>
                        <div class="login-separator moo-hidden-xs moo-hidden-sm">
                            <div class="separator">
                                <span><?php _e("or", "moo_OnlineOrders"); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="moo-col-md-6" tabindex="0" >
                        <form action="#" method="post" onsubmit="moo_login(event)" aria-label="Sign in with your account">
                            <div class="form-group">
                                <label for="inputEmail"><?php _e("Email", "moo_OnlineOrders"); ?></label>
                                <input type="text" id="inputEmail" class="moo-form-control" autocomplete="email" aria-label="your email">
                            </div>
                            <div class="moo-form-group">
                                <label for="inputPassword"><?php _e("Password", "moo_OnlineOrders"); ?></label>
                                <input type="password"  id="inputPassword" class="moo-form-control" autocomplete="current-password" aria-label="your password">
                                <a class="pull-right" href="#" onclick="moo_show_forgotpasswordform(event)" aria-label="Click here if you forgotten your password"><?php _e("Forgot password?", "moo_OnlineOrders"); ?></a>
                            </div>
                            <button id="mooButonLogin" class="moo-btn" onclick="moo_login(event)" aria-label="log in">
                                <?php _e("Log In", "moo_OnlineOrders"); ?>
                            </button>
                            <p style="padding: 10px;"> <?php _e("Don't have an account", "moo_OnlineOrders"); ?><a  href="#" onclick="moo_show_sigupform(event)" aria-label="Don't have an account Sign-up"> <?php _e("Sign-up", "moo_OnlineOrders"); ?></a> </p>
                        </form>
                    </div>
                </div>
                <!--            Register            -->
                <div id="moo-signing-form" class="moo-col-md-12">
                    <div class="moo-col-md-8 moo-col-md-offset-2">
                        <form action="#" method="post" onsubmit="moo_signin(event)">
                            <div class="moo-form-group">
                                <label for="inputMooFullName"><?php _e("Full Name", "moo_OnlineOrders"); ?></label>
                                <input type="text" class="moo-form-control" id="inputMooFullName" autocomplete="fullName">
                            </div>

                            <div class="moo-form-group">
                                <label for="inputMooEmail"><?php _e("Email", "moo_OnlineOrders"); ?></label>
                                <input type="text" class="moo-form-control" id="inputMooEmail" autocomplete="email">
                            </div>
                            <div class="moo-form-group">
                                <label for="inputMooPhone"><?php _e("Phone number", "moo_OnlineOrders"); ?></label>
                                <input type="text" class="moo-form-control" id="inputMooPhone" autocomplete="phone">
                            </div>
                            <div class="moo-form-group">
                                <label for="inputMooPassword"><?php _e("Password", "moo_OnlineOrders"); ?></label>
                                <input type="password" class="moo-form-control" id="inputMooPassword" autocomplete="current-password">
                            </div>
                            <p>
                                <?php
                                /* translators: %s represent our tos link */
                                printf(__('By clicking the button below, you agree to our <a href="%s" target="_blank">Terms Of Service</a>', 'moo_OnlineOrders'), "https://www.zaytech.com/zaytech-eula");
                                ?>
                            </p>
                            <button class="moo-btn moo-btn-primary" onclick="moo_signin(event)">
                                <?php _e("Submit", "moo_OnlineOrders"); ?>
                            </button>
                            <p style="padding: 10px;"> <?php _e("Have an account already?", "moo_OnlineOrders"); ?><a  href="#" onclick="moo_show_loginform()">  <?php _e("Click here", "moo_OnlineOrders"); ?></a> </p>
                        </form>
                    </div>

                </div>
                <!--            Reset Password      -->
                <div   id="moo-forgotpassword-form" class="moo-col-md-12">
                    <div class="moo-col-md-8 moo-col-md-offset-2">
                        <form action="#" method="post" onsubmit="moo_resetpassword(event)">
                            <div class="moo-form-group">
                                <label for="inputEmail4Reset"><?php _e("Email", "moo_OnlineOrders"); ?></label>
                                <input type="text" class="moo-form-control" id="inputEmail4Reset">
                            </div>
                            <button class="moo-btn moo-btn-primary" onclick="moo_resetpassword(event)">
                                <?php _e("Reset", "moo_OnlineOrders"); ?>
                            </button>
                            <button class="moo-btn moo-btn-default" onclick="moo_cancel_resetpassword(event)">
                                <?php _e("Cancel", "moo_OnlineOrders"); ?>
                            </button>
                        </form>
                    </div>
                </div>
                <!--            Choose address      -->
                <div id="moo-chooseaddress-form" class="moo-col-md-12">
                    <div id="moo-chooseaddress-formContent" class="moo-row">
                    </div>
                    <div class="MooAddressBtnActions">
                        <a class="MooSimplButon" href="#" onclick="moo_show_form_adding_address()">
                            <?php _e("Add Another Address", "moo_OnlineOrders"); ?>
                        </a>
                        <a class="MooSimplButon" href="#" onclick="moo_pickup_the_order(event)">
                            <?php _e("Click here if this Order is for Pick Up", "moo_OnlineOrders"); ?>
                        </a>
                    </div>
                </div>
                <!--            Add new address      -->
                <div id="moo-addaddress-form" class="moo-col-md-12">
                    <form method="post" onsubmit="moo_addAddress(event)">
                        <h1 tabindex="0" aria-level="1">
                            <?php _e("Add new Address to your account", "moo_OnlineOrders"); ?>
                        </h1>
                        <div class="moo-col-md-8 moo-col-md-offset-2">
                            <div class="mooFormAddingAddress">
                                <div class="moo-form-group">
                                    <label for="inputMooAddress"><?php _e("Address", "moo_OnlineOrders"); ?></label>
                                    <input type="text" class="moo-form-control" id="inputMooAddress">
                                </div>
                                <div class="moo-form-group">
                                    <label for="inputMooAddress2"><?php _e("Suite / Apt #", "moo_OnlineOrders"); ?></label>
                                    <input type="text" class="moo-form-control" id="inputMooAddress2">
                                </div>
                                <div class="moo-form-group">
                                    <label for="inputMooCity"><?php _e("City", "moo_OnlineOrders"); ?></label>
                                    <input type="text" class="moo-form-control" id="inputMooCity">
                                </div>
                                <div class="moo-form-group">
                                    <label for="inputMooState"><?php _e("State", "moo_OnlineOrders"); ?></label>
                                    <input type="text" class="moo-form-control" id="inputMooState">
                                </div>
                                <div class="moo-form-group">
                                    <label for="inputMooZipcode"><?php _e("Zip code", "moo_OnlineOrders"); ?></label>
                                    <input type="text" class="moo-form-control" id="inputMooZipcode">
                                </div>
                                <p class="moo-centred">
                                    <button href="#" class="moo-btn moo-btn-warning" onclick="moo_ConfirmAddressOnMap(event)"><?php _e("Next", "moo_OnlineOrders"); ?></button>
                                </p>
                            </div>
                            <div class="mooFormConfirmingAddress">
                                <div id="MooMapAddingAddress" tabindex="-1">
                                    <p style="margin-top: 150px;"><?php _e("Loading the MAP...", "moo_OnlineOrders"); ?></p>
                                </div>
                                <input type="hidden" class="moo-form-control" id="inputMooLat">
                                <input type="hidden" class="moo-form-control" id="inputMooLng">
                                <div class="form-group">
                                    <button id="mooButonAddAddress" onclick="moo_addAddress(event)" aria-label="Confirm and add address">
                                        <?php _e("Confirm and add address", "moo_OnlineOrders"); ?>
                                    </button>
                                    <button id="mooButonChangeAddress" onclick="moo_changeAddress(event)" aria-label="Change address">
                                        <?php _e("Change address", "moo_OnlineOrders"); ?>
                                    </button>
                                </div>
                            </div>
                            <p style="padding: 10px;">
                                <?php _e("If you want to skip this step and add your address later ", "moo_OnlineOrders"); ?>
                                <a role="button" href="#" onclick="moo_pickup_the_order(event)" style="color:blue">
                                    <?php _e("Click here", "moo_OnlineOrders"); ?>
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
                <!--            Checkout form        -->
                <div id="moo-checkout-form" class="moo-col-md-12" <?php if ($this->pluginSettings['checkout_login']=="disabled") {
                    echo 'style="display:block;"';
                                                                  }?>>
                    <form action="#" method="post" onsubmit="moo_finalize_order(event)">
                        <!--            Checkout form - Informaton section       -->
                        <div class="moo-col-md-7 moo-checkout-form-leftside" tabindex="0" aria-label="the checkout form">
                            <div id="moo-checkout-form-customer" tabindex="0" aria-label="your information">
                                <div class="moo-checkout-bloc-title moo-checkoutText-contact">
                                    <?php _e("contact", "moo_OnlineOrders"); ?>
                                    <span class="moo-checkout-edit-icon" onclick="moo_checkout_edit_contact()">
                                        <img src="<?php echo  SOO_PLUGIN_PATH."/public/img/edit-pen.png"?>" alt="edit">
                                    </span>
                                </div>
                                <div class="moo-checkout-bloc-content">
                                    <div id="moo-checkout-contact-content">
                                    </div>
                                    <div id="moo-checkout-contact-form">
                                        <div class="moo-row">
                                            <div class="moo-form-group">
                                                <label for="MooContactName" class="moo-checkoutText-fullName"><?php _e("Full Name", "moo_OnlineOrders"); ?>:*</label>
                                                <input class="moo-form-control" name="name" id="MooContactName" autocomplete="name">
                                            </div>
                                        </div>
                                        <div class="moo-row">
                                            <div class="moo-form-group">
                                                <label for="MooContactEmail" class="moo-checkoutText-email"><?php _e("Email", "moo_OnlineOrders"); ?>:*</label>
                                                <input class="moo-form-control" id="MooContactEmail" autocomplete="email">
                                            </div>
                                        </div>
                                        <div class="moo-row">
                                            <div class="moo-form-group">
                                                <label for="MooContactPhone" class="moo-checkoutText-phoneNumber"><?php _e("Phone number", "moo_OnlineOrders"); ?>:*</label>
                                                <input class="moo-form-control" name="phone" id="MooContactPhone" onchange="moo_phone_changed()" autocomplete="phone">
                                            </div>
                                        </div>
                                        <?php wp_nonce_field('moo-checkout-form');?>
                                    </div>
                                </div>
                            </div>
                            <div class="moo_checkout_border_bottom"></div>
                            <?php if (count($orderTypes)>0) {?>
                                <div id="moo-checkout-form-ordertypes" tabindex="0" aria-label="the ordering method">
                                    <div class="moo-checkout-bloc-title moo-checkoutText-orderingMethod">
                                        <?php _e("ORDERING METHOD", "moo_OnlineOrders"); ?>*
                                    </div>
                                    <div class="moo-checkout-bloc-content">
                                        <?php
                                        foreach ($orderTypes as $ot) {
                                            echo '<div class="moo-checkout-form-ordertypes-option">';
                                            if (isset($ot->available) && $ot->available === false) {
                                                echo '<input class="moo-checkout-form-ordertypes-input" type="radio" name="ordertype" value="'.esc_attr($ot->ot_uuid).'" id="moo-checkout-form-ordertypes-'.esc_attr($ot->ot_uuid).'" disabled>';
                                                echo '<label for="moo-checkout-form-ordertypes-'.esc_attr($ot->ot_uuid).'" style="display: inline;margin-left:15px;font-size: 16px; vertical-align: sub;">'.wp_kses_post(wp_unslash($ot->label)).' ( '.wp_kses_post(wp_unslash($ot->custom_message)).' )</label></div>';
                                            } else {
                                                echo '<input class="moo-checkout-form-ordertypes-input" type="radio" name="ordertype" value="'.esc_attr($ot->ot_uuid).'" id="moo-checkout-form-ordertypes-'.esc_attr($ot->ot_uuid).'">';
                                                echo '<label for="moo-checkout-form-ordertypes-'.esc_attr($ot->ot_uuid).'" style="display: inline;margin-left:15px;font-size: 16px; vertical-align: sub;">'.wp_kses_post(wp_unslash($ot->label)).'</label></div>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <div class="moo-checkout-bloc-message" id="moo-checkout-form-ordertypes-message">
                                    </div>
                                </div>
                                <div class="moo_checkout_border_bottom"></div>
                            <?php  } ?>
                            <?php
                            if ($showTimeSection) {?>
                                <div id="moo-checkout-form-orderdate" tabindex="0" aria-label="Choose a time if you want schedule the order">
                                    <div class="moo-checkout-bloc-title moo-checkoutText-ChooseATime">
                                        <?php _e("CHOOSE A TIME", "moo_OnlineOrders"); ?>
                                    </div>
                                    <div class="moo-checkout-bloc-content">
                                        <div class="moo-row">
                                            <div class="moo-col-md-6">
                                                <div class="moo-form-group">
                                                    <label for="moo_pickup_day"></label>
                                                    <select class="moo-form-control" name="moo_pickup_day" id="moo_pickup_day" onchange="moo_pickup_day_changed(this)">
                                                        <?php
                                                        foreach ($oppening_status["pickup_time"] as $key => $val) {
                                                            echo '<option value="'.esc_attr($key).'">'.esc_attr($key).'</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="moo-col-md-6">
                                                <div class="moo-form-group">
                                                    <label for="moo_pickup_hour"></label>
                                                    <select class="moo-form-control" name="moo_pickup_hour" id="moo_pickup_hour" >
                                                        <?php
                                                        foreach ($oppening_status["pickup_time"] as $val) {
                                                            foreach ($val as $h) {
                                                                echo '<option value="'.esc_attr($h).'">'.esc_attr($h).'</option>';
                                                            }
                                                            break;
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($oppening_status["store_time"] != '') { ?>
                                            <div class="moo-row">
                                                <div class="moo-col-md-12">
                                                    <?php _e("Today's Online Ordering Hours", "moo_OnlineOrders"); ?> : <?php echo $oppening_status["store_time"]  ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="moo_checkout_border_bottom"></div>
                            <?php } ?>
                            <div id="moo-checkout-form-payments" tabindex="0" aria-label="the payments method">
                                <div class="moo-checkout-bloc-title moo-checkoutText-payment" >
                                    <?php _e("PAYMENT METHOD", "moo_OnlineOrders"); ?>*
                                </div>
                                <div class="moo-checkout-bloc-content">
                                    <?php if (isset($cloverCodeExist) && $cloverCodeExist && isset($this->pluginSettings['clover_payment_form']) && $this->pluginSettings['clover_payment_form'] == 'on') {
                                        $this->creditCardSelectorOld();
                                    }
                                    if ($this->pluginSettings['payment_cash'] == 'on' || $this->pluginSettings['payment_cash_delivery'] == 'on') {
                                        $this->cashSelectorOld();
                                    }
                                    if (isset($cloverCodeExist) && $cloverCodeExist  && isset($this->pluginSettings['clover_payment_form']) && $this->pluginSettings['clover_payment_form'] == 'on') {
                                        $this->cloverCardSectionOld();
                                    }
                                    if ($this->pluginSettings['payment_cash'] == 'on' || $this->pluginSettings['payment_cash_delivery'] == 'on') { ?>
                                        <div id="moo_cashPanel">
                                            <div class="moo-row"  id="moo_verifPhone_verified">
                                                <img src="<?php echo  SOO_PLUGIN_URL ."/public/img/check.png"?>" width="60px" style="display: inline-block;" alt="">
                                                <p>
                                                    <?php _e("Your phone number has been verified", "moo_OnlineOrders"); ?>
                                                    <br/>
                                                    <?php _e("Please finalize your order below", "moo_OnlineOrders"); ?>
                                                </p>
                                            </div>
                                            <div class="moo-row" id="moo_verifPhone_sending">
                                                <div class="moo-form-group moo-form-inline">
                                                    <label for="Moo_PhoneToVerify moo-checkoutText-yourPhone"><?php _e("Your Phone", "moo_OnlineOrders"); ?></label>
                                                    <input class="moo-form-control" id="Moo_PhoneToVerify" style="margin-bottom: 10px" onchange="moo_phone_to_verif_changed()"/>
                                                    <a class="moo-btn moo-btn-primary" href="#" style="margin-bottom: 10px" onclick="moo_verifyPhone(event)">
                                                        <?php _e("Verify via SMS", "moo_OnlineOrders"); ?>
                                                    </a>
                                                    <label for="Moo_PhoneToVerify" class="error" style="display: none;"></label>
                                                </div>
                                                <p>
                                                    <?php _e("We will send a verification code via SMS to number above", "moo_OnlineOrders"); ?>
                                                </p>
                                            </div>
                                            <div class="moo-row" id="moo_verifPhone_verificatonCode">
                                                <p style='font-size:18px;color:green'>
                                                    <?php _e("Please enter the verification that was sent to your phone, if you didn't receive a code,", "moo_OnlineOrders"); ?>
                                                    <a href="#" onclick="moo_verifyCodeTryAgain(event)">
                                                        <?php _e("click here to try again", "moo_OnlineOrders"); ?>
                                                    </a>
                                                </p>
                                                <div class="moo-form-group moo-form-inline">
                                                    <input class="moo-form-control" id="Moo_VerificationCode" style="margin-bottom: 10px" autocomplete="off" />
                                                    <a class="moo-btn moo-btn-primary" href="#" style="margin-bottom: 10px" onclick="moo_verifyCode(event)">
                                                        <?php _e("Submit", "moo_OnlineOrders"); ?>
                                                    </a>
                                                    <label for="Moo_VerificationCode" class="error" style="display: none;"></label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="moo_checkout_border_bottom"></div>

                            <?php
                            if ($this->pluginSettings['tips'] == 'enabled' && isset($merchant_proprites->tipsEnabled) && $merchant_proprites->tipsEnabled) {
                                $this->tipsSectionOld();
                                $this->borderBottom();
                            }
                            if ($this->pluginSettings['use_special_instructions']=="enabled") {
                                ?>
                                <div id="moo-checkout-form-instruction">
                                    <div class="moo-checkout-bloc-title moo-checkoutText-instructions">
                                        <label for="Mooinstructions">
                                            <?php _e("Special instructions", "moo_OnlineOrders"); ?>
                                        </label>
                                    </div>
                                    <div class="moo-checkout-bloc-content">
                                        <?php
                                        if (isset($this->pluginSettings['text_under_special_instructions']) && $this->pluginSettings['text_under_special_instructions']!=='') {
                                            echo '<div class="moo-special-instruction-title">'.$this->pluginSettings['text_under_special_instructions'].'</div>';
                                        }
                                        if (isset($this->pluginSettings['special_instructions_required']) && $this->pluginSettings['special_instructions_required']==='yes') {
                                            echo '<textarea class="moo-form-control" cols="100%" rows="5" id="Mooinstructions" required></textarea>';
                                        } else {
                                            echo '<textarea class="moo-form-control" cols="100%" rows="5" id="Mooinstructions"></textarea>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php
                            }
                            //Check if coupons are enabled
                            if ($this->pluginSettings['use_coupons']=="enabled") {
                                ?>
                                <div class="moo_checkout_border_bottom"></div>
                                <div id="moo-checkout-form-coupon">
                                    <div class="moo-checkout-bloc-title moo-checkoutText-couponCode">
                                        <label for="moo_coupon">
                                            <?php _e("Coupon code", "moo_OnlineOrders"); ?>
                                        </label>
                                    </div>
                                    <div class="moo-checkout-bloc-content" id="moo_enter_coupon" style="<?php if ($coupon !== null) {
                                        echo 'display:none';
                                                                                                        }?>">
                                        <div class="moo-col-md-8">
                                            <div class="moo-form-group">
                                                <input onchange="mooCouponValueChanged(event)" type="text" class="moo-form-control" id="moo_coupon" style="background-color: #ffffff">
                                            </div>
                                        </div>
                                        <div class="moo-col-md-4">
                                            <div class="moo-form-group">
                                                <a href="#" class="moo-btn moo-btn-primary" onclick="mooCouponApply(event)" style="height: 40px;line-height: 24px;">
                                                    <?php _e("Apply", "moo_OnlineOrders"); ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="moo-checkout-bloc-content" id="moo_remove_coupon" style="<?php if ($coupon === null) {
                                        echo 'display:none';
                                                                                                         } ?>">
                                        <div class="moo-col-md-8">
                                            <div class="moo-form-group">
                                                <p style="font-size: 20px" id="moo_remove_coupon_code">
                                                    <?php
                                                        if ($coupon != null) {
                                                            echo sanitize_text_field($coupon['code']);
                                                        }
                                                    ?></p>
                                            </div>
                                        </div>
                                        <div class="moo-col-md-4">
                                            <div class="moo-form-group">
                                                <a href="#" class="moo-btn moo-btn-primary" onclick="mooCouponRemove(event)">
                                                    <?php _e("Remove", "moo_OnlineOrders"); ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php  }?>
                        </div>
                        <!--            Checkout form - Cart section       -->
                        <div class="moo-col-md-5 moo-checkout-cart">
                            <div class="moo-shopping-cart MooCartInCheckout" tabindex="0" aria-label="the cart">
                                <div class="moo-column-labels-checkout">
                                    <label class="moo-product-quantity moo-product-quantity-checkou moo-checkoutText-qtyt" style="width: 20%"><?php _e("Qty", "moo_OnlineOrders"); ?></label>
                                    <label class="moo-product-details moo-product-details-checkout moo-checkoutText-product" style="width: 60%"><?php _e("Product", "moo_OnlineOrders"); ?></label>
                                    <label class="moo-product-price moo-product-price-checkout moo-checkoutText-price" style="width: 20%"><?php _e("Price", "moo_OnlineOrders"); ?></label>
                                </div>
                                <?php foreach ($session->get("items") as $key => $line) {
                                    $modifiers_price = 0;
                                    if (isset($line['item']->soo_name) && !empty($line['item']->soo_name)) {
                                        $item_name=wp_unslash((string)$line['item']->soo_name);
                                    } else {
                                        if ($this->useAlternateNames && isset($line['item']->alternate_name) && $line['item']->alternate_name!=="") {
                                            $item_name=wp_unslash((string)$line['item']->alternate_name);
                                        } else {
                                            $item_name=wp_unslash((string)$line['item']->name);
                                        }
                                    }

                                    ?>
                                    <div class="moo-product" tabindex="0" aria-label="<?php echo sanitize_text_field($line['quantity'])." of ".$line['item']->name."" ?>">
                                        <div class="moo-product-quantity" style="width: 20%">
                                            <strong><?php echo sanitize_text_field($line['quantity']); ?></strong>
                                        </div>
                                        <div class="moo-product-details moo-product-details-checkout" style="width: 60%">
                                            <div class="moo-product-title"><strong><?php echo $item_name; ?></strong></div>
                                            <p class="moo-product-description">
                                                <?php
                                                foreach ($line['modifiers'] as $modifier) {
                                                    $modifier_name = "";
                                                    if ($this->useAlternateNames && isset($modifier["alternate_name"]) && $modifier["alternate_name"]!=="") {
                                                        $modifier_name =wp_unslash((string)$modifier["alternate_name"]);
                                                    } else {
                                                        $modifier_name =wp_unslash((string)$modifier["name"]);
                                                    }
                                                    if (isset($modifier['qty']) && intval($modifier['qty'])>0) {
                                                        echo '<small tabindex="0">'.$modifier['qty'].'x ';
                                                        $modifiers_price += $modifier['price']*$modifier['qty'];
                                                    } else {
                                                        echo '<small tabindex="0">1x ';
                                                        $modifiers_price += $modifier['price'];
                                                    }

                                                    if ($modifier['price']>0) {
                                                        echo ''.$modifier_name.'- $'.number_format(($modifier['price']/100), 2)."</small><br/>";
                                                    } else {
                                                        echo ''.$modifier_name."</small><br/>";
                                                    }
                                                }
                                                if ($line['special_ins'] != "") {
                                                    echo '<span tabindex="0" aria-label="your special instructions">SI:<span><span tabindex="0"> '.sanitize_text_field($line['special_ins'])."<span>";
                                                }
                                                ?>
                                            </p>
                                        </div>
                                        <?php $line_price = $line['item']->price+$modifiers_price;?>
                                        <div class="moo-product-line-price" tabindex="0"><strong>$<?php echo number_format(($line_price*$line['quantity']/100), 2)?></strong></div>
                                    </div>
                                <?php } ?>

                                <div class="moo-totals" style="padding-right: 10px;">
                                    <div class="moo-totals-item">
                                        <label class="moo-checkoutText-subtotal"  tabindex="0"><?php _e("Subtotal", "moo_OnlineOrders"); ?></label>
                                        <div class="moo-totals-value" id="moo-cart-subtotal"  tabindex="0">
                                            <?php echo number_format(($totals['sub_total']/100), 2)?>
                                        </div>
                                    </div>
                                    <?php if ($this->pluginSettings['use_coupons']=="enabled") { //check if coupons are enabled ?>
                                        <div class="moo-totals-item" id="MooCouponInTotalsSection" style="<?php if ($totals['coupon_value'] === 0) {
                                            echo 'display:none;';
                                                                                                          }?>;color: green;">
                                            <label id="mooCouponName" tabindex="0"><?php echo $totals['coupon_name'];?></label>
                                            <div class="moo-totals-value" id="mooCouponValue" tabindex="0">
                                                <?php  echo '- $'.number_format($totals['coupon_value']/100, 2); ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <div class="moo-totals-item">
                                        <label class="moo-checkoutText-tax"  tabindex="0" ><?php _e("Tax", "moo_OnlineOrders"); ?></label>
                                        <div class="moo-totals-value" id="moo-cart-tax"  tabindex="0">
                                            <?php
                                            if ($totals['coupon_value'] === 0) {
                                                echo  '$'.number_format($totals['total_of_taxes_without_discounts']/100, 2);
                                            } else {
                                                echo  '$'.number_format($totals['total_of_taxes']/100, 2);
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="moo-totals-item" id="MooDeliveryfeesInTotalsSection">
                                        <label class="moo-checkoutText-deliveryFees"  tabindex="0">
                                            <?php echo ($this->pluginSettings["delivery_fees_name"] === "")?"Delivery Charge":$this->pluginSettings["delivery_fees_name"];?>
                                        </label>
                                        <div class="moo-totals-value" id="moo-cart-delivery-fee"  tabindex="0">
                                            <?php
                                            echo '$'.number_format(($totals['delivery_charges']/100), 2);
                                            ?>
                                        </div>
                                    </div>
                                    <div class="moo-totals-item" id="MooServiceChargesInTotalsSection"  style="<?php if ($totals['service_fee'] <= 0) {
                                        echo 'display:none;';
                                                                                                               }?>">
                                        <label id="MooServiceChargesName" tabindex="0">
                                            <?php
                                            if (isset($this->pluginSettings['service_fees_name']) && !empty($this->pluginSettings['service_fees_name'])) {
                                                echo $this->pluginSettings['service_fees_name'];
                                            } else {
                                                echo "Service Fees";
                                            }
                                            ?>
                                        </label>
                                        <div class="moo-totals-value" id="moo-cart-service-fee"  tabindex="0">
                                            <?php
                                            echo '$'.number_format($totals['service_fee']/100, 2);
                                            ?>
                                        </div>
                                    </div>
                                    <?php if ($this->pluginSettings['tips']=='enabled') {?>
                                        <div class="moo-totals-item" id="MooTipsInTotalsSection">
                                            <label class="moo-checkoutText-tipAmount" tabindex="0" ><?php _e("Tip", "moo_OnlineOrders"); ?></label>
                                            <div class="moo-totals-value" id="moo-cart-tip" tabindex="0">
                                                $0.00
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <div class="moo-totals-item moo-totals-item-total" style="font-weight: 700;" >
                                        <label class="moo-checkoutText-grandTotal" tabindex="0" ><?php _e("Total", "moo_OnlineOrders"); ?></label>
                                        <div class="moo-totals-value" id="moo-cart-total" tabindex="0" >
                                            <?php
                                            if ($totals['coupon_value'] === 0) {
                                                $grandTotal = $totals['total'] + $totals['service_fee'] + $totals['delivery_charges'];
                                            } else {
                                                $grandTotal = $totals['total_without_discounts'] + $totals['service_fee'] + $totals['delivery_charges'] - $totals['coupon_value'];
                                            }
                                            echo '$'.number_format($grandTotal/100, 2);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!--   Checkout form - Link section     -->
                            <div style="text-align: center;text-decoration: none;">
                                <a href="<?php echo $cart_page_url?>" class="moo-checkoutText-updateCart">
                                    <?php _e("Update cart", "moo_OnlineOrders"); ?>
                                </a>
                                <a href="<?php echo $store_page_url?>" class="moo-checkoutText-continueShopping">
                                    <?php _e("Continue shopping", "moo_OnlineOrders"); ?>
                                </a>
                            </div>
                        </div>

                        <!--            Checkout form - Buttons section       -->
                        <div id="moo-checkout-form-btnActions">
                            <div id="moo_checkout_loading" style="display: none; width: 100%;text-align: center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="44px" height="44px" viewBox="0 0 100 100"
                                     preserveAspectRatio="xMidYMid" class="uil-default">
                                    <rect x="0" y="0" width="100" height="100" fill="none" class="bk"></rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(0 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0s"
                                                 repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(30 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.08333333333333333s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(60 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.16666666666666666s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(90 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.25s"
                                                 repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(120 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.3333333333333333s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(150 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.4166666666666667s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(180 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.5s"
                                                 repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(210 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.5833333333333334s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(240 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.6666666666666666s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(270 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.75s"
                                                 repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(300 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.8333333333333334s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(330 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.9166666666666666s" repeatCount="indefinite"></animate>
                                    </rect>
                                </svg>
                            </div>
                            <button type="submit"  id="moo_btn_submit_order" onclick="moo_finalize_order(event)" class="moo-btn moo-btn-primary moo-finalize-order-btn moo-checkoutText-finalizeOrder">
                                <?php _e("FINALIZE ORDER", "moo_OnlineOrders"); ?>
                            </button>
                        </div>
                    </form>
                </div>
                <!--            Logout Button      -->
                <div class="moologoutButton moo-col-md-12" <?php if (($session->isEmpty("moo_customer_token"))) {
                    echo 'style="display:none;"';
                                                           }?> >
                    <a class="moologoutlabel" href="?logout=true">
                        <?php _e("Logout", "moo_OnlineOrders"); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        if ($custom_js != null) {
            echo '<script type="text/javascript">'.$custom_js.'</script>';
        }
        if (!$session->isEmpty("moo_customer_token")) {
            echo '<script type="text/javascript"> jQuery( document ).ready(function() { moo_show_chooseaddressform() });</script>';
        }

        return ob_get_clean();
    }

    public function advancedCheckout($atts, $content)
    {

        $this->enqueueStyles(true);
        $this->enqueueScripts(true);

        ob_start();

        if (! $this->checkOpenning() ){
            return ob_get_clean();
        }

        if (! $this->checkBlackout() ){
            return ob_get_clean();
        }

        //Get Session
        $session = MOO_SESSION::instance();
        $counter  = 0;
        $showServiceFee  = true;
        $serviceFeeAmount  = 0;
        $nbOfOrderTypes = 0;
        $nbOfUnvailableOrderTypes = null;

        //Get Settings And Cart Total
        $businessSettings = $this->api->getBusinessSettings();


        //Get Totals
        $totals = $session->getTotalsV2(0, 0);


        $orderTypes = $this->model->getVisibleOrderTypes(true);
        if (!is_array($orderTypes)) {
            $orderTypes = array();
        }
        $nbOfOrderTypes = count($orderTypes);
        //Check onDemandDelivery
        if (isset($businessSettings['onDemandDeliveriesEnabled']) && $businessSettings['onDemandDeliveriesEnabled']) {
            $onDemandDeliveryOrderType = [
                "ot_uuid" => 'onDemandDelivery',
                "label" => $businessSettings['onDemandDeliveriesLabel'],
                "allow_sc_order" => true,
                "allow_service_fee" => true,
                "custom_hours" => null,
                "custom_message" => null,
                "minAmount" => null,
                "maxAmount" => null,
                "show_sa" => true,
                "sort_order" => 99,
                "status" => true,
                "taxable" => true,
                "time_availability" => null,
                "type" => null,
                "use_coupons" => true,
            ];
            $orderTypes[] = apply_filters('moo_filter_on_demand_delivery', $onDemandDeliveryOrderType);
        }

        foreach ($orderTypes as $oneOrderType) {
            if (isset($oneOrderType["custom_hours"]) && !empty($oneOrderType["custom_hours"])) {
                $counter++;
            }
            if (isset($oneOrderType["allow_service_fee"]) && intval($oneOrderType["allow_service_fee"]) !== 1) {
                $showServiceFee =  false;
            }
        }

        // Get ordertypes times
        if ($counter > 0) {
            $HoursResponse = $this->api->getMerchantCustomHoursStatus("ordertypes");
            if ($HoursResponse) {
                $merchantCustomHoursStatus = $HoursResponse;
                $merchantCustomHours = array_keys($HoursResponse);
                if (@count($merchantCustomHours) > 0 && $nbOfOrderTypes > 0) {
                    $nbOfUnvailableOrderTypes = 0;
                    for ($i=0; $i<$nbOfOrderTypes; $i++) {
                        $orderType  = $orderTypes[$i];
                        $orderTypes[$i]['available'] = true;
                        if (isset($orderType['custom_hours']) && !empty($orderType['custom_hours'])) {
                            if (in_array($orderType['custom_hours'], $merchantCustomHours)) {
                                $isNotAvailable = $merchantCustomHoursStatus[$orderType['custom_hours']] === "close";
                                if ($isNotAvailable) {
                                    $orderTypes[$i]['available'] = false;
                                    $nbOfUnvailableOrderTypes++;
                                }
                            }
                        }
                    }
                }
            }
        }

        if($nbOfOrderTypes === $nbOfUnvailableOrderTypes ) {
            echo '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">This store cannot accept orders right now, please come back later</div>';
            return ob_get_clean();
        }

        if (isset($this->pluginSettings["clover_payment_form"]) && $this->pluginSettings["clover_payment_form"] == "on") {
            $cloverPakmsKey = $this->api->getPakmsKey();
        } else {
            $cloverPakmsKey = null;
        }

        $custom_js  = $this->pluginSettings["custom_js"];

        //Calc Service Fess
        if (isset($this->pluginSettings['service_fees'])  && floatval($this->pluginSettings['service_fees']) > 0) {
            if (isset($this->pluginSettings['service_fees_type']) && $this->pluginSettings['service_fees_type'] === "percent") {
                $serviceFees = floatval($this->pluginSettings['service_fees']);
                $serviceFeesType = "percent";
                $serviceFeeAmount =  intval(round($totals['sub_total'] * $serviceFees / 100));

            } else {
                $serviceFees = intval(round(floatval($this->pluginSettings['service_fees']) * 100));
                $serviceFeesType = "amount";
                $serviceFeeAmount =  $serviceFees;
            }
        } else {
            $serviceFees = 0;
            $serviceFeesType = "amount";
            $showServiceFee = false;
        }

       // $merchant_proprites = (json_decode($this->api->getMerchantProprietes())) ;

        //Coupons
        if (!$session->isEmpty("coupon")) {
            $coupon = $session->get("coupon");
            if ($coupon['minAmount']>$totals['sub_total']) {
                $coupon = null;
            }
        } else {
            $coupon = null;
        }

        if ($this->pluginSettings["order_later"] == "on") {
            $inserted_nb_days = $this->pluginSettings["order_later_days"];
            $inserted_nb_mins = $this->pluginSettings["order_later_minutes"];

            $inserted_nb_days_d = $this->pluginSettings["order_later_days_delivery"];
            $inserted_nb_mins_d = $this->pluginSettings["order_later_minutes_delivery"];

            if ($inserted_nb_days === "") {
                $nb_days = 4;
            } else {
                $nb_days = intval($inserted_nb_days);
            }

            if ($inserted_nb_mins === "") {
                $nb_minutes = 20;
            } else {
                $nb_minutes = intval($inserted_nb_mins);
            }

            if ($inserted_nb_days_d === "") {
                $nb_days_d = 4;
            } else {
                $nb_days_d = intval($inserted_nb_days_d);
            }

            if ($inserted_nb_mins_d === "") {
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


        $oppening_status = $this->api->getOpeningStatus($nb_days, $nb_minutes);



        $this->showStreetAddressFieldOnPaymentForm = $this->checkCloverFraudTools($oppening_status);


        if ($nb_days != $nb_days_d || $nb_minutes != $nb_minutes_d) {
            $oppening_status_d = $this->api->getOpeningStatus($nb_days_d, $nb_minutes_d);
        } else {
            $oppening_status_d = $oppening_status;
        }

        $oppening_msg = $this->getOpeningMessage($oppening_status);

        //Adding asap to pickup time
        if (isset($oppening_status["pickup_time"])) {
            if (isset($this->pluginSettings['order_later_asap_for_p']) && $this->pluginSettings['order_later_asap_for_p'] == 'on') {
                if (isset($oppening_status["pickup_time"]["Today"])) {
                    array_unshift($oppening_status["pickup_time"]["Today"], 'ASAP');
                }
            }
        }

        if (isset($oppening_status_d["pickup_time"])) {
            if (isset($this->pluginSettings['order_later_asap_for_d']) && $this->pluginSettings['order_later_asap_for_d'] == 'on') {
                if (isset($oppening_status_d["pickup_time"]["Today"])) {
                    array_unshift($oppening_status_d["pickup_time"]["Today"], 'ASAP');
                }
            }
        }

        if ($this->pluginSettings['hours'] != 'all' && $oppening_msg != "") {
           if ($this->pluginSettings['accept_orders_w_closed'] != 'on' || (!empty($oppening_status["status"] ) && $oppening_status["status"] === 'not_found')){
               echo '<div id="moo_CheckoutContainer">'.$oppening_msg.'</div>';
               return ob_get_clean();
           }
        }


        $merchant_address =  $this->api->getMerchantAddress();
        $store_page_id     = $this->pluginSettings['store_page'];
        $cart_page_id     = $this->pluginSettings['cart_page'];
        $checkout_page_id     = $this->pluginSettings['checkout_page'];

        $store_page_url    =  get_page_link($store_page_id);
        $cart_page_url    =  get_page_link($cart_page_id);
        $checkout_page_url    =  get_page_link($checkout_page_id);

        if (isset($this->pluginSettings['thanks_page_wp']) && !empty($this->pluginSettings['thanks_page_wp'])) {
            $this->pluginSettings['thanks_page'] = get_page_link($this->pluginSettings['thanks_page_wp']);
        }

        if (!isset($this->pluginSettings['save_cards'])) {
            $this->pluginSettings['save_cards'] = null;
        }
        if (!isset($this->pluginSettings['save_cards_fees'])) {
            $this->pluginSettings['save_cards_fees'] = null;
        }
        if (!isset($this->pluginSettings['delivery_errorMsg']) || empty($this->pluginSettings['delivery_errorMsg'])) {
            $this->pluginSettings['delivery_errorMsg'] = __("Sorry, zone not supported. We do not deliver to this address at this time", "moo_OnlineOrders");
        }
        if (!isset($this->pluginSettings['special_instructions_required']) || empty($this->pluginSettings['special_instructions_required'])) {
            $this->pluginSettings['special_instructions_required'] = false;
        }
        $jsOptions = array(
            'restApiUrl' =>  get_rest_url(),
            "orderTypes"=>$orderTypes,
            "totals"=>$totals,
            "thanksPage"=>$this->pluginSettings['thanks_page'],
            "cashUponDelivery"=>$this->pluginSettings['payment_cash_delivery'] === 'on',
            "cashInStore"=>$this->pluginSettings['payment_cash'] === 'on',
            "creditCard"=>$this->pluginSettings['clover_payment_form'] === 'on',
            "giftCards"=>(!empty(SOO_ACCEPT_GIFTCARDS) && !empty($this->pluginSettings['clover_giftcards']) && $this->pluginSettings['clover_giftcards'] === 'on'),
            "googlePay"=>(!empty($this->pluginSettings['clover_googlepay']) && $this->pluginSettings['clover_googlepay'] === 'on'),
            "pickupTimes"=>(!empty($oppening_status["pickup_time"])) ? $oppening_status["pickup_time"]:null,
            "deliveryTimes"=>(!empty($oppening_status_d["pickup_time"])) ? $oppening_status_d["pickup_time"] : null,
            "fbAppId"=>$this->pluginSettings['fb_appid'],
            "useSmsVerification"=>$this->pluginSettings['use_sms_verification'] === 'enabled',
            "feeType"=>$serviceFeesType,
            "feeAmount"=>$serviceFees,
            "calculatedFeeAmount"=>$serviceFeeAmount,
            "pakmsKey"=>$cloverPakmsKey,
            "specialInstructionsRequired"=>$this->pluginSettings['special_instructions_required'] === 'yes',
            "locale"=>str_replace("_", "-", get_locale()),
            "showStreetAddressField"=>$this->showStreetAddressFieldOnPaymentForm,
            "allowScOrder"=>$this->pluginSettings['order_later'] === 'on',
            "orderingTimeRequired"=>$this->pluginSettings['order_later_mandatory'] === "on" && $this->pluginSettings['order_later'] === 'on',
            "loyaltySetting"=>(isset($businessSettings) && isset($businessSettings['loyaltySetting']) ) ? $businessSettings['loyaltySetting'] : null,
            "isSandbox"=>defined('SOO_ENV') && (SOO_ENV === "DEV"),
            "storeLink"=>$this->getOrderingPageLink(),
            "reCAPTCHA_site_key"=>false,
        );
        $deliveryJsOptions = array(
            "moo_merchantLat"=>$this->pluginSettings['lat'],
            "moo_merchantLng"=>$this->pluginSettings['lng'],
            "moo_merchantAddress"=>$merchant_address,
            "zones"=>$this->pluginSettings['zones_json'],
            "other_zone_fee"=>$this->pluginSettings['other_zones_delivery'],
            "free_amount"=>$this->pluginSettings['free_delivery'],
            "fixed_amount"=>$this->pluginSettings['fixed_delivery'],
            "errorMsg"=>$this->pluginSettings['delivery_errorMsg']
        );

        $googleReCAPTCHADisabled = (bool) get_option('sooDisableGoogleReCAPTCHA',false);

        //Add Google reCAPTCHA
        if ( $googleReCAPTCHADisabled === false && !empty($this->pluginSettings['reCAPTCHA_site_key']) && !empty($this->pluginSettings['reCAPTCHA_secret_key'])){
            $jsOptions['reCAPTCHA_site_key'] = $this->pluginSettings['reCAPTCHA_site_key'];
        }
        //If Store is closed, make time required
        if(!empty($oppening_status["status"])){
            if ($oppening_status["status"] === 'close'){
                $jsOptions['orderingTimeRequired'] = true;
            }
        }

        $checkoutJsOptions  = [
            "options"=>$jsOptions,
            "delivery"=>$deliveryJsOptions,
        ];

        $authOptionsJsOptions  = [
            'restApiUrl' =>  get_rest_url(),
            "fbAppId"=>$this->pluginSettings['fb_appid']
        ];


        wp_localize_script("sooCheckoutScript", "sooCheckout", $checkoutJsOptions);
        wp_localize_script("sooAuthScript", "sooAuthOptions", $authOptionsJsOptions);


        if ($totals === false || !isset($totals['nb_items']) || $totals['nb_items'] < 1) {
            return $this->cartIsEmpty();
        };

        if ((isset($_GET['logout']) && $_GET['logout'])) {
            $session->delete("moo_customer_token");
            wp_redirect($checkout_page_url);
        }

        ?>

        <div id="moo_CheckoutContainer">
            <div class="moo-row" id="moo-checkout">
                <div class="errors-section"></div>
                <?php echo $oppening_msg; ?>
                <div id="moo_merchantmap"></div>
                <!--   Accounts Function (login, signUp, reset password)   -->
                <div id="sooAuthSection" data-soo-auth-page="checkout">
                    <div class="sooAuthContainer">
                        <!--   login   -->
                        <div id="moo-login-section" class="moo-col-md-12 moo-section" style="min-height: 300px;">
                            <!--   Loading Section   -->
                            <div class="sooOverlayLoader" style="background-color: #fff;position: absolute;height: 100%;width: 100%;z-index: 10000000;">
                                <div class="dot-flashing-2"></div>
                            </div>
                            <div class="moo-row login-top-section" tabindex="-1">
                                <div class="login-header" style="padding-left: 15px;">
                                    <?php
                                    printf("Why create a <a href='%s' target='_blank'>Smart Online Order</a> account?", "https://www.smartonlineorder.com");
                                    ?>
                                </div>
                                <div class="moo-col-md-6">
                                    <ul>
                                        <li><?php _e("Save your address", "moo_OnlineOrders"); ?></li>
                                        <li><?php _e("Faster Checkout!", "moo_OnlineOrders"); ?></li>
                                    </ul>
                                </div>
                                <div class="moo-col-md-6">
                                    <ul>
                                        <li><?php _e("View your past orders", "moo_OnlineOrders"); ?></li>
                                        <li><?php _e("Get exclusive deals and coupons", "moo_OnlineOrders"); ?></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="moo-col-md-6" tabindex="0">
                                <div class="moo-row login-social-section">
                                    <?php if ( !empty($this->pluginSettings['fb_appid']) && !empty($this->pluginSettings['fb_appsecret']) ) { ?>
                                        <p>
                                            <?php _e("Sign in with your Facebook account", "moo_OnlineOrders"); ?>
                                            <br />
                                            <small><?php _e("No posts on your behalf, promise!", "moo_OnlineOrders"); ?></small>
                                        </p>
                                        <div class="moo-row">
                                            <div class="moo-col-xs-12" >
                                                <button class="sooFbLoginButton" onclick="sooAuth.loginViaFacebook()" tabindex="0" aria-label="Sign in with your Facebook account">
                                            <span class="fbIconSvg">
                                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0,0,256,256" width="20px" height="20px" fill-rule="nonzero"><g fill="#ffffff" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal"><g transform="scale(5.12,5.12)"><path d="M25,3c-12.15,0 -22,9.85 -22,22c0,11.03 8.125,20.137 18.712,21.728v-15.897h-5.443v-5.783h5.443v-3.848c0,-6.371 3.104,-9.168 8.399,-9.168c2.536,0 3.877,0.188 4.512,0.274v5.048h-3.612c-2.248,0 -3.033,2.131 -3.033,4.533v3.161h6.588l-0.894,5.783h-5.694v15.944c10.738,-1.457 19.022,-10.638 19.022,-21.775c0,-12.15 -9.85,-22 -22,-22z"></path></g></g></svg>
                                            </span>
                                                    <span>Continue with Facebook</span>
                                                </button>
                                            </div>
                                            <div class="moo-col-xs-12" tabindex="0">
                                                <div class="login-or">
                                                    <hr class="hr-or">
                                                    <span class="span-or"><?php _e("or", "moo_OnlineOrders"); ?></span>
                                                </div>
                                                <button class="sooCheckoutSecondaryButtonInput" onclick="mooCheckout.continueAsGuest(event)" tabindex="0">
                                                    <?php _e("Continue As Guest", "moo_OnlineOrders"); ?>
                                                </button>
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <p>
                                            <?php _e("Don't want an account?", "moo_OnlineOrders"); ?>
                                            <br />
                                            <small><?php _e("You can checkout without registering", "moo_OnlineOrders"); ?></small>
                                        </p>
                                        <div class="moo-row" id="moo-login-left">
                                            <div class="moo-col-xs-12">
                                                <button  role="button" tabindex="0" href="#" class="sooContinueAsGuestButton" onclick="mooCheckout.continueAsGuest(event)" style="margin-top: 12px;">
                                                    <?php _e("Continue As Guest", "moo_OnlineOrders"); ?>
                                                </button>
                                            </div>
                                            <div class="moo-col-xs-12">
                                                <div class="login-or">
                                                    <hr class="hr-or">
                                                    <span class="span-or"><?php _e("or", "moo_OnlineOrders"); ?></span>
                                                </div>
                                                <button  class="sooCreateAnAccountButton" onclick="sooAuth.showOrHideASection('signing-section',true)">
                                                    <?php _e("Create An Account", "moo_OnlineOrders"); ?>
                                                </button>
                                            </div>
                                        </div>
                                    <?php  } ?>
                                </div>
                                <div class="login-separator moo-hidden-xs moo-hidden-sm">
                                    <div class="separator">
                                        <span><?php _e("or", "moo_OnlineOrders"); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="moo-col-md-6" tabindex="0" >
                                <div class="sooLoginTitle moo-hidden-md moo-hidden-lg">
                                    <div>Log in</div>
                                </div>
                                <form id="moo-login-form" action="#" method="post" autocomplete="on" aria-label="Sign in with your account">
                                    <div class="moo-form-group">
                                        <label for="sooInputEmail"><?php _e("Email", "moo_OnlineOrders"); ?></label>
                                        <input type="email" id="sooInputEmail" class="moo-form-control sooCheckoutTextInput" autocomplete="email" aria-label="your email" onkeyup="sooAuth.onInputChange(this)">
                                        <span class="moo-error-section">
                                    <?php _e("Please enter your email", "moo_OnlineOrders"); ?>
                                </span>
                                    </div>
                                    <div class="moo-form-group">
                                        <label for="sooInputPassword"><?php _e("Password", "moo_OnlineOrders"); ?></label>
                                        <input type="password"  id="sooInputPassword" class="moo-form-control  sooCheckoutTextInput" autocomplete="current-password" aria-label="your password" onkeyup="sooAuth.onInputChange(this)">
                                        <span class="moo-error-section">
                                    <?php _e("Please enter your password", "moo_OnlineOrders"); ?>
                                </span>
                                        <a class="pull-right sooForgetPasswordButton" href="#" onclick="sooAuth.showOrHideASection('forgotpassword-section',true)" aria-label="Click here if you forgotten your password"><?php _e("Forgot password?", "moo_OnlineOrders"); ?></a>
                                    </div>
                                    <div class="moo-form-group">
                                        <button class="sooLoginButton" type="submit" aria-label="<?php _e("Log In", "moo_OnlineOrders"); ?>">
                                            <?php _e("Log In", "moo_OnlineOrders"); ?>
                                        </button>
                                    </div>
                                    <?php if ( !empty($this->pluginSettings['fb_appid']) && !empty($this->pluginSettings['fb_appsecret']) ) { ?>
                                        <p style="padding: 10px"> <?php _e("Don't have an account", "moo_OnlineOrders"); ?><a  class="sooSignUpLinkButton"  href="#" onclick="sooAuth.showOrHideASection('signing-section',true)" aria-label="Don't have an account Sign-up"> <?php _e("Sign-up", "moo_OnlineOrders"); ?></a> </p>
                                    <?php } ?>
                                </form>
                            </div>
                        </div>
                        <!--   Register   -->
                        <div id="moo-signing-section" class="moo-col-md-12 moo-section" style="display: none">
                            <div class="moo-col-md-8 moo-col-md-offset-2 sooBorderedArea">
                                <form id="moo-signup-form" action="#" method="post" aria-label="Create an account">
                                    <div class="moo-row">
                                        <div class="moo-col-md-6">
                                            <div class="moo-form-group">
                                                <label for="sooSignupInputFirstName"><?php _e("First Name", "moo_OnlineOrders"); ?></label>
                                                <input type="text" class="moo-form-control sooCheckoutTextInput" id="sooSignupInputFirstName" autocomplete="firstName" onkeyup="sooAuth.onInputChange(this)">
                                                <span class="moo-error-section">
                                            <?php _e("Please enter your first name", "moo_OnlineOrders"); ?>
                                        </span>
                                            </div>
                                        </div>
                                        <div class="moo-col-md-6">
                                            <div class="moo-form-group">
                                                <label for="sooSignupInputLastName"><?php _e("Last Name", "moo_OnlineOrders"); ?></label>
                                                <input type="text" class="moo-form-control sooCheckoutTextInput" id="sooSignupInputLastName" autocomplete="lastName" onkeyup="sooAuth.onInputChange(this)">
                                                <span class="moo-error-section">
                                            <?php _e("Please enter your last name", "moo_OnlineOrders"); ?>
                                        </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="moo-form-group">
                                        <label for="sooSignupInputPhone"><?php _e("Phone number", "moo_OnlineOrders"); ?></label>
                                        <input type="text" class="moo-form-control sooCheckoutTextInput" id="sooSignupInputPhone" autocomplete="phone" onkeyup="sooAuth.onInputChange(this)">
                                        <span class="moo-error-section">
                                    <?php _e("Please enter your phone", "moo_OnlineOrders"); ?>
                                </span>
                                    </div>

                                    <div class="moo-form-group">
                                        <label for="sooSignupInputEmail"><?php _e("Email", "moo_OnlineOrders"); ?></label>
                                        <input type="email" class="moo-form-control sooCheckoutTextInput" id="sooSignupInputEmail" autocomplete="email" onkeyup="sooAuth.onInputChange(this)">
                                        <span class="moo-error-section">
                                    <?php _e("Please enter your email", "moo_OnlineOrders"); ?>
                                </span>
                                    </div>
                                    <div class="moo-form-group">
                                        <label for="sooSignupInputPassword"><?php _e("Password", "moo_OnlineOrders"); ?></label>
                                        <input type="password" class="moo-form-control sooCheckoutTextInput" id="sooSignupInputPassword" autocomplete="new-password" onkeyup="sooAuth.onInputChange(this)">
                                        <span class="moo-error-section"><?php _e("Please choose a password", "moo_OnlineOrders"); ?></span>
                                    </div>
                                    <div class="moo-form-group">
                                        <label for="sooSignupInputConfirmPassword"><?php _e("Password Confirmation", "moo_OnlineOrders"); ?></label>
                                        <input type="password" class="moo-form-control sooCheckoutTextInput" id="sooSignupInputConfirmPassword" autocomplete="new-password" onkeyup="sooAuth.onInputChange(this)">
                                        <span class="moo-error-section">
                                            <?php _e("Please confirm your password", "moo_OnlineOrders"); ?>
                                        </span>
                                    </div>

                                    <button class="sooCheckoutPrimaryButtonInput sooSignupButton" type="submit" aria-label="<?php _e("Submit", "moo_OnlineOrders"); ?>">
                                        <?php _e("Submit", "moo_OnlineOrders"); ?>
                                    </button>
                                    <br />
                                    <p style="padding-top: 10px;">
                                        <?php
                                        /* translators: %s represent our tos link */
                                        printf(__('By creating an account and placing orders, you indicate your agreement to our <a class="sooTosLinkButton" href="%s" target="_blank">Terms Of Service</a>', 'moo_OnlineOrders'), "https://www.zaytech.com/zaytech-eula");
                                        ?>
                                    </p>
                                    <p><?php _e("Have an account already?", "moo_OnlineOrders"); ?><a class="sooLoginLinkButton" href="#" onclick="sooAuth.showOrHideASection('login-section',true)">  <?php _e("Click here", "moo_OnlineOrders"); ?></a> </p>
                                </form>
                            </div>

                        </div>
                        <!--   Reset Password   -->
                        <div  id="moo-forgotpassword-section" class="moo-col-md-12 moo-section" style="display: none">
                            <div class="moo-col-md-8 moo-col-md-offset-2 sooBorderedArea">
                                <form  id="moo-resetPassword-form" action="#" method="post" aria-label="reset your password">
                                    <div class="moo-form-group moo-min-height-80">
                                        <label for="sooInputEmail4Reset"><?php _e("Email", "moo_OnlineOrders"); ?></label>
                                        <input type="text" class="moo-form-control sooCheckoutTextInput" id="sooInputEmail4Reset" onkeyup="sooAuth.onInputChange(this)">
                                        <span class="moo-error-section">
                                    <?php _e("Please enter your email", "moo_OnlineOrders"); ?>
                                </span>
                                    </div>

                                    <button name="submit" class="sooResetPasswordButton sooCheckoutPrimaryButtonInput" type="submit" aria-label="<?php _e("Reset", "moo_OnlineOrders"); ?>">
                                        <?php _e("Reset", "moo_OnlineOrders"); ?>
                                    </button>

                                    <button name="cancel" class="sooCheckoutSecondaryButtonInput" onclick="sooAuth.cancelResetPassword(event)">
                                        <?php _e("Cancel", "moo_OnlineOrders"); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!--   Checkout form   -->
                <div id="moo-checkout-form" class="moo-col-md-12 moo-section" style="display: none">
                    <div class="moo-row">
                        <!--            Checkout form - information section       -->
                        <div class="moo-col-sm-7 moo-checkout-form-leftside" tabindex="0" aria-label="the checkout form">
                            <div id="moo-checkout-form-customer" tabindex="0" aria-label="your information">
                                <div class="moo-checkout-bloc-title moo-checkoutText-contact">
                                    <?php _e("Contact", "moo_OnlineOrders"); ?>
                                </div>
                                <div class="moo-checkout-bloc-content">
                                    <div id="moo-checkout-contact-content">
                                        <div class="moo-row soo-contact-info">
                                            <div class="moo-col-xs-9">
                                                <div class="soo-contact-info-name"></div>
                                                <div class="soo-contact-info-email"></div>
                                                <div class="soo-contact-info-phone"></div>
                                                <div class="soo-contact-edit" onclick="mooCheckout.editCustomerInfo(this)">
                                                    Edit
                                                </div>
                                            </div>
                                            <div class="moo-col-xs-3 soo-contact-logout" onclick="mooCheckout.logout(event)">
                                                Logout
                                            </div>
                                        </div>
                                    </div>
                                    <div id="moo-checkout-contact-form">
                                        <div class="moo-row">
                                            <div class="moo-col-md-12 soo-contact-loginButtonContainer">
                                               <span class="soo-contact-login" onclick="mooCheckout.showLoginSection()">Login</span>
                                            </div>
                                        </div>
                                        <div class="moo-row">
                                            <div class="moo-form-group moo-col-md-6">
                                                <label for="MooContactFirstName" class="moo-checkoutText-fullName">
                                                    <?php _e("First Name", "moo_OnlineOrders"); ?>:*
                                                </label>
                                                <input type="text" class="moo-form-control sooCheckoutTextInput" name="name" id="MooContactFirstName" autocomplete="first_name" onkeyup="mooCheckout.onInputChange(this)">
                                                <span class="moo-error-section">
                                                    <?php _e("Please enter your first name", "moo_OnlineOrders"); ?>
                                                </span>
                                            </div>
                                            <div class="moo-form-group moo-col-md-6">
                                                <label for="MooContactLastName" class="moo-checkoutText-fullName">
                                                    <?php _e("Last Name", "moo_OnlineOrders"); ?>:*
                                                </label>
                                                <input type="text" class="moo-form-control sooCheckoutTextInput" name="name" id="MooContactLastName" autocomplete="last_name" onkeyup="mooCheckout.onInputChange(this)">
                                                <span class="moo-error-section">
                                                    <?php _e("Please enter your last name", "moo_OnlineOrders"); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="moo-row">
                                            <div class="moo-form-group moo-col-md-12">
                                                <label for="MooContactEmail" class="moo-checkoutText-email"><?php _e("Email", "moo_OnlineOrders"); ?>:*</label>
                                                <input type="email" class="moo-form-control sooCheckoutTextInput" id="MooContactEmail" autocomplete="email" onkeyup="mooCheckout.onInputChange(this)">
                                                <span class="moo-error-section">
                                                    <?php _e("Please enter your Email", "moo_OnlineOrders"); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="moo-row">
                                            <div class="moo-form-group moo-col-md-12">
                                                <label for="MooContactPhone" class="moo-checkoutText-phoneNumber"><?php _e("Phone number", "moo_OnlineOrders"); ?>:*</label>
                                                <input type="text" class="moo-form-control sooCheckoutTextInput" name="phone" id="MooContactPhone" onkeyup="mooCheckout.onInputChange(this)" autocomplete="phone">
                                                <span class="moo-error-section">
                                                    <?php _e("Please enter your phone", "moo_OnlineOrders"); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php wp_nonce_field('moo-checkout-form');?>
                                    </div>
                                </div>
                            </div>
                            <?php if (count($orderTypes)>0) {?>
                                <div id="moo-checkout-form-ordertypes" tabindex="0" aria-label="the ordering method">
                                    <div class="moo_checkout_border_bottom"></div>
                                    <div class="moo-checkout-bloc-title moo-checkoutText-orderingMethod">
                                        <?php _e("Ordering Method", "moo_OnlineOrders"); ?>s
                                    </div>
                                    <div class="moo-checkout-bloc-content">
                                        <?php
                                        foreach ($orderTypes as $ot) {
                                            echo '<div class="soo-checkout-radio-input-option">';
                                            if (isset($ot['available']) && $ot['available'] === false) {
                                                echo '<input class="soo-checkout-radio-input" type="radio" name="ordertype" value="'.esc_attr($ot['ot_uuid']).'" id="moo-checkout-form-ordertypes-'.esc_attr($ot['ot_uuid']).'" disabled>';
                                                echo '<label for="moo-checkout-form-ordertypes-'.esc_attr($ot['ot_uuid']).'" class="moo-checkout-radio-input-label">'.wp_kses_post(wp_unslash($ot['label'])).' ( '.wp_kses_post(wp_unslash($ot['custom_message'])).' )</label></div>';
                                            } else {
                                                echo '<input onchange="mooCheckout.orderTypeChanged()" class="soo-checkout-radio-input" type="radio" name="ordertype" value="'.esc_attr($ot['ot_uuid']).'" id="moo-checkout-form-ordertypes-'.esc_attr($ot['ot_uuid']).'">';
                                                echo '<label for="moo-checkout-form-ordertypes-'.esc_attr($ot['ot_uuid']).'" class="moo-checkout-radio-input-label">'.wp_kses_post(wp_unslash($ot['label'])).'</label></div>';
                                            }
                                        }
                                        ?>
                                        <div class="soo-address-section">
                                            <div class="soo-add-delivery-address-form">
                                                <div class="soo-add-address-wrapper">
                                                    <form id="soo-add-address-form" action="#" method="post" onsubmit="mooCheckout.addNewAddress(event)">
                                                        <div class="moo-row">
                                                            <div class="moo-form-group moo-col-md-12 soo-margin-bottom-10">
                                                                <label for="sooAddAddressName"><?php _e("Full Name", "moo_OnlineOrders"); ?></label>
                                                                <input type="text" class="sooCheckoutTextInput" id="sooAddAddressName" onkeyup="mooCheckout.onInputChange(this)">
                                                                <span class="moo-error-section">
                                                                    <?php _e("Please enter the name of who will receive the order", "moo_OnlineOrders"); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="moo-row">
                                                            <div class="moo-form-group moo-col-md-12 soo-margin-bottom-10">
                                                                <label for="sooAddAddressLine1"><?php _e("Address", "moo_OnlineOrders"); ?></label>
                                                                <input type="text" class="sooCheckoutTextInput" id="sooAddAddressLine1" onkeyup="mooCheckout.onInputChange(this)">
                                                                <span class="moo-error-section">
                                                                <?php _e("Please enter your address", "moo_OnlineOrders"); ?>
                                                            </span>
                                                            </div>
                                                        </div>
                                                        <div class="moo-row">
                                                            <div class="moo-form-group moo-col-md-12 soo-margin-bottom-10">
                                                                <label for="sooAddAddressLine2"><?php _e("Suite / Apt #", "moo_OnlineOrders"); ?></label>
                                                                <input type="text" class="sooCheckoutTextInput" id="sooAddAddressLine2" onkeyup="mooCheckout.onInputChange(this)">
                                                                <span class="moo-error-section">
                                                                <?php _e("Please enter your Suite or Apt Number", "moo_OnlineOrders"); ?>
                                                            </span>
                                                            </div>
                                                        </div>
                                                        <div class="moo-row">
                                                            <div class="moo-form-group moo-col-md-12 soo-margin-bottom-10">
                                                                <label for="sooAddAddressCity"><?php _e("City", "moo_OnlineOrders"); ?></label>
                                                                <input type="text" class="sooCheckoutTextInput" id="sooAddAddressCity" onkeyup="mooCheckout.onInputChange(this)">
                                                                <span class="moo-error-section">
                                                                <?php _e("Please enter your City", "moo_OnlineOrders"); ?>
                                                            </span>
                                                            </div>
                                                        </div>
                                                        <div class="moo-row">
                                                            <div class="moo-form-group moo-col-md-6">
                                                                <label for="sooAddAddressState"><?php _e("State", "moo_OnlineOrders"); ?></label>
                                                                <input type="text" class="sooCheckoutTextInput" id="sooAddAddressState" onkeyup="mooCheckout.onInputChange(this)">
                                                                <span class="moo-error-section">
                                                                    <?php _e("Please enter your State", "moo_OnlineOrders"); ?>
                                                                </span>
                                                            </div>
                                                            <div class="moo-form-group moo-col-md-6">
                                                                <label for="sooAddAddressZipCode"><?php _e("Zip Code", "moo_OnlineOrders"); ?></label>
                                                                <input type="text" class="sooCheckoutTextInput" id="sooAddAddressZipCode" onkeyup="mooCheckout.onInputChange(this)">
                                                                <span class="moo-error-section">
                                                                <?php _e("Please enter your Zip Code", "moo_OnlineOrders"); ?>
                                                            </span>
                                                            </div>
                                                        </div>
                                                        <div class="moo-row">
                                                            <div class="moo-col-md-12 soo-text-right">
                                                                <button class="sooCheckoutPrimaryButtonInput" type="submit" aria-label="<?php _e("Add", "moo_OnlineOrders"); ?>">
                                                                    <?php _e("Add", "moo_OnlineOrders"); ?>
                                                                </button>
                                                                <button  type="reset" class="sooCheckoutSecondaryButtonInput" onclick="mooCheckout.showListOfAddresses()">
                                                                    <?php _e("Cancel", "moo_OnlineOrders"); ?>
                                                                </button>
                                                            </div>

                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="soo-list-of-addresses">
                                                List Of Addresses
                                            </div>
                                        </div>
                                    </div>
                                    <div class="moo-checkout-bloc-message" id="moo-checkout-form-ordertypes-message"></div>
                                </div>
                            <?php
                            }
                            //Ordering Time Section
                            if (isset($this->pluginSettings['order_later']) && $this->pluginSettings['order_later'] === 'on') {
                                $this->orderingTimesSection($oppening_status);
                            }
                            //Payment Methods Section
                            $this->paymentMethodsSection($cloverPakmsKey);

                            //Loyalty Section
                            if (isset($businessSettings['loyaltySetting'])) {
                               $this->loyaltySection($businessSettings['loyaltySetting']['label']);
                            }
                            //Tips Section
                            if ($this->pluginSettings['tips'] == 'enabled') {
                               $this->tipsSection();
                            }
                            //Special Instructions
                            if ($this->pluginSettings['use_special_instructions'] === "enabled") {
                               $this->specialInstructionsSection();
                            }
                            //Coupons
                            if ($this->pluginSettings['use_coupons']  === "enabled") {
                                $this->couponsSection();
                            }

                        ?>
                        </div>
                        <!--            Checkout form - Cart section       -->
                        <div class="moo-col-sm-5 moo-checkout-cart soo-sticky-cart">
                            <div class="cartContainer">
                                <div class="moo-shopping-cart sooCartInCheckout" tabindex="0" aria-label="the cart">
                                    <div class="moo-shopping-cart-title">
                                        <?php _e("Order Summary", "moo_OnlineOrders"); ?>
                                    </div>
                                    <?php foreach ($session->get("items") as $key => $line) {
                                        $modifiers_price = 0;
                                        if (isset($line['item']->soo_name) && !empty($line['item']->soo_name)) {
                                            $item_name=stripslashes((string)$line['item']->soo_name);
                                        } else {
                                            if ($this->useAlternateNames && isset($line['item']->alternate_name) && $line['item']->alternate_name!=="") {
                                                $item_name=stripslashes((string)$line['item']->alternate_name);
                                            } else {
                                                $item_name=stripslashes((string)$line['item']->name);
                                            }
                                        }

                                        ?>
                                        <div class="moo-row moo-shopping-cart-line" tabindex="0" aria-label="<?php echo $line['quantity']." of ".$line['item']->name; ?>">
                                            <div class="moo-scl-qty moo-col-xs-2">
                                                <?php echo $line['quantity']?>
                                            </div>
                                            <div class="moo-scl-item moo-col-xs-7">
                                                <div class="moo-scl-itemName"><?php echo $item_name; ?></div>
                                                <p class="moo-scl-itemModifiers">
                                                    <?php
                                                    foreach ($line['modifiers'] as $modifier) {
                                                        $modifier_name = "";
                                                        if ($this->useAlternateNames && isset($modifier["alternate_name"]) && $modifier["alternate_name"]!=="") {
                                                            $modifier_name =stripslashes((string)$modifier["alternate_name"]);
                                                        } else {
                                                            $modifier_name =stripslashes((string)$modifier["name"]);
                                                        }
                                                        if (isset($modifier['qty']) && intval($modifier['qty'])>0) {
                                                            echo '<small tabindex="0">'.$modifier['qty'].'<small>x</small> ';
                                                            $modifiers_price += $modifier['price']*$modifier['qty'];
                                                        } else {
                                                            echo '<small tabindex="0">1x ';
                                                            $modifiers_price += $modifier['price'];
                                                        }

                                                        if ($modifier['price']>0) {
                                                            echo ''.$modifier_name.'- $'.number_format(($modifier['price']/100), 2)."</small><br/>";
                                                        } else {
                                                            echo ''.$modifier_name."</small><br/>";
                                                        }
                                                    }
                                                    if ($line['special_ins'] != "") {
                                                        echo '<span tabindex="0" aria-label="your special instructions">SI:<span><span tabindex="0"> '.$line['special_ins']."<span>";
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                            <?php $line_price = $line['item']->price+$modifiers_price; ?>
                                            <div class="moo-scl-itemPrice moo-col-xs-3" tabindex="0"><strong>$<?php echo number_format(($line_price*$line['quantity']/100), 2)?></strong></div>
                                        </div>
                                    <?php } ?>
                                    <div class="moo-totals" style="padding-right: 10px;">
                                        <div class="moo-totals-item">
                                            <label class="moo-checkoutText-subtotal"  tabindex="0"><?php _e("Subtotal", "moo_OnlineOrders"); ?></label>
                                            <div class="moo-totals-value" id="moo-cart-subtotal"  tabindex="0">
                                                <?php echo '$'.number_format(($totals['sub_total']/100), 2)?>
                                            </div>
                                        </div>
                                        <div class="moo-totals-item" id="sooCouponTotalSection" style="color: green;">
                                            <label id="sooCouponName" tabindex="0" for="sooCouponValue"></label>
                                            <div id="sooCouponValue" class="moo-totals-value"  tabindex="0"></div>
                                        </div>
                                        <div class="moo-totals-item" id="sooPointsTotalSection" style="color: green;">
                                            <label id="sooPointslabel" tabindex="0" for="sooPointsValue"></label>
                                            <div id="sooPointsValue" class="moo-totals-value" tabindex="0"></div>
                                        </div>
                                        <div class="moo-totals-item">
                                            <label class="moo-checkoutText-tax"  tabindex="0" ><?php _e("Tax", "moo_OnlineOrders"); ?></label>
                                            <div class="moo-totals-value" id="moo-cart-tax"  tabindex="0">
                                                <?php
                                                echo  '$'.number_format($totals['taxes']/100, 2);
                                                ?>
                                            </div>
                                        </div>
                                        <div class="moo-totals-item" id="sooServiceFeeTotalSection"
                                             style="<?php echo ($showServiceFee) ? 'display:block;' : '' ; ?>">
                                            <label id="MooServiceChargesName" tabindex="0">
                                                <?php
                                                if (isset($this->pluginSettings['service_fees_name']) && !empty($this->pluginSettings['service_fees_name'])) {
                                                    echo $this->pluginSettings['service_fees_name'];
                                                } else {
                                                    echo "Service Fees";
                                                }
                                                ?>
                                            </label>
                                            <div class="moo-totals-value" id="moo-cart-service-fee"  tabindex="0">
                                                <?php
                                                echo '$'. number_format($serviceFeeAmount/100,2);
                                                ?>
                                            </div>
                                        </div>

                                        <div class="moo-totals-item" id="sooDeliveryFeeTotalSection">
                                            <label class="moo-checkoutText-deliveryFees"  tabindex="0">
                                                <?php echo ($this->pluginSettings["delivery_fees_name"] === "")?"Delivery Charge":$this->pluginSettings["delivery_fees_name"];?>
                                            </label>
                                            <div class="moo-totals-value" id="moo-cart-delivery-fee"  tabindex="0"></div>
                                        </div>

                                        <?php if ($this->pluginSettings['tips'] === 'enabled') {?>
                                            <div class="moo-totals-item" id="sooTipsTotalSection">
                                                <label class="moo-checkoutText-tipAmount" tabindex="0" ><?php _e("Tip", "moo_OnlineOrders"); ?></label>
                                                <div class="moo-totals-value" id="moo-cart-tip" tabindex="0">
                                                    $0.00
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <div class="moo-totals-item moo-totals-item-total" style="font-weight: 700;" >
                                            <label class="moo-checkoutText-grandTotal" tabindex="0" ><?php _e("Total", "moo_OnlineOrders"); ?></label>
                                            <div class="moo-totals-value" id="moo-cart-total" tabindex="0" >
                                                <?php
                                                $grandTotal = $totals['sub_total'] + $totals['taxes'];

                                                if ($showServiceFee) {
                                                    $grandTotal += $serviceFeeAmount;
                                                }


                                                echo '$'.number_format($grandTotal/100, 2);
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="moo-shopping-links" style="text-align: center;text-decoration: none;">
                                    <a href="<?php echo $cart_page_url?>" class="moo-checkoutText-updateCart">
                                        <?php _e("Update cart", "moo_OnlineOrders"); ?>
                                    </a>
                                    <a href="<?php echo $store_page_url?>" class="moo-checkoutText-continueShopping">
                                        <?php _e("Continue shopping", "moo_OnlineOrders"); ?>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="moo-row">
                        <!--  Checkout form - Buttons section     -->
                        <div class="moo-col-sm-7" id="moo-checkout-form-btnActions">
                            <div id="moo_checkout_loading" style="display: none; width: 100%;text-align: center">
                                <svg xmlns="http://www.w3.org/2000/svg" width="44px" height="44px" viewBox="0 0 100 100"
                                     preserveAspectRatio="xMidYMid" class="uil-default">
                                    <rect x="0" y="0" width="100" height="100" fill="none" class="bk"></rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(0 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0s"
                                                 repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(30 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.08333333333333333s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(60 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.16666666666666666s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(90 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.25s"
                                                 repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(120 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.3333333333333333s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(150 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.4166666666666667s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(180 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.5s"
                                                 repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(210 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.5833333333333334s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(240 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.6666666666666666s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(270 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s" begin="0.75s"
                                                 repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(300 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.8333333333333334s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="46.5" y="40" width="7" height="20" rx="5" ry="5" fill="#00b2ff"
                                          transform="rotate(330 50 50) translate(0 -30)">
                                        <animate attributeName="opacity" from="1" to="0" dur="1s"
                                                 begin="0.9166666666666666s" repeatCount="indefinite"></animate>
                                    </rect>
                                </svg>
                            </div>
                            <div id="moo-cloverGooglePay" class=""></div>
                            <button type="submit"  id="moo_btn_submit_order" onclick="mooCheckout.finalizeOrder(event);" class="sooCheckoutPrimaryButtonInput moo-finalize-order-btn moo-checkoutText-finalizeOrder">
                                <?php _e("FINALIZE ORDER", "moo_OnlineOrders"); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php

        if ($custom_js != null) {
            echo '<script type="text/javascript">'.$custom_js.'</script>';
        }

        return ob_get_clean();
    }
    /**
     * @param $atts
     * @param $content
     * @return string
     */
    public function render($atts, $content)
    {
        if (get_option('moo_old_checkout_enabled') === 'yes') {
            return $this->standardCheckout($atts, $content);
        }
        return $this->advancedCheckout($atts, $content);
    }

    /**
     * @param $atts
     * @param $content
     * @return string
     */
    public function renderReceiptLink($atts, $content)
    {
        $defaultText = __("Click Here", "moo_OnlineOrders");
        $defaultTitle = __("Click Here", "moo_OnlineOrders");
        $linkText = (!empty($atts['text'])) ? esc_attr($atts['text']) : $defaultText;
        $linkTitle = (!empty($atts['title'])) ? esc_attr($atts['title']) : $defaultTitle;
        $orderId = (!empty($_GET['order_id'])) ? esc_attr($_GET['order_id']) : null;
        if ($orderId) {
            if (defined('SOO_ENV') && SOO_ENV === 'DEV') {
                $html = '<a href="https://dev.clover.com/r/';
            } else {
                $html = '<a href="https://www.clover.com/r/';
            }
            $html .= $orderId.'" target="_blank" title="'.$linkTitle.'">';
            $html .= $linkText;
            $html .= '</a>';
            $html = apply_filters('moo_filter_receipt_link', $html);
            return  $html;
        }
        return null;
    }

    private function checkOpenning(){
        //check store availability
        if (isset($this->pluginSettings['accept_orders']) && $this->pluginSettings['accept_orders'] === "disabled") {
            if (isset($this->pluginSettings["closing_msg"]) && $this->pluginSettings["closing_msg"] !== '') {
                $oppening_msg = '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'.$this->pluginSettings["closing_msg"].'</div>';
            } else {
                $oppening_msg = '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'.__("We are currently closed and will open again soon", "moo_OnlineOrders").'</div>';
            }
            echo '<div id="moo_CheckoutContainer" >'.$oppening_msg.'</div>';
            return false;
        }
        return true;
    }
    private function checkBlackout(){
        //Get blackout status
        $blackoutStatusResponse = $this->api->getBlackoutStatus();
        if (isset($blackoutStatusResponse["status"]) && $blackoutStatusResponse["status"] === "close") {
            if (isset($blackoutStatusResponse["custom_message"]) && !empty($blackoutStatusResponse["custom_message"])) {
                $oppening_msg = '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'.$blackoutStatusResponse["custom_message"].'</div>';
            } else {
                $oppening_msg = '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'.__("We are currently closed and will open again soon", "moo_OnlineOrders").'</div>';
            }
            echo '<div id="moo_CheckoutContainer" >'.$oppening_msg.'</div>';
            return false;
        }
        return true;
    }
    private function enqueueStyles($isAdvancedCheckout = false)
    {

        $this->enqueueCssGrid();


        if ($isAdvancedCheckout) {

           // $this->enqueueSweetAlerts11Css();

            $this->enqueuePublicCss();

            wp_register_style('sooAuthStyles', SOO_PLUGIN_URL . '/public/css/dist/sooAuth-light.min.css', array(), SOO_VERSION);
            wp_enqueue_style('sooAuthStyles');

            wp_register_style('sooCheckoutStyles', SOO_PLUGIN_URL . '/public/css/dist/sooCheckout-light.min.css', array(), SOO_VERSION);
            wp_enqueue_style('sooCheckoutStyles',array('sooAuthStyles'));



        } else {

            $this->enqueueFontAwesome();

            $this->enqueueSweetAlerts();

            $this->enqueuePublicCss();

            wp_register_style('sooCheckoutStyles', SOO_PLUGIN_URL . '/public/css/dist/mooCheckoutSd.min.css', array(), SOO_VERSION);
            wp_enqueue_style('sooCheckoutStyles');

            wp_register_style('moo-icheck-css', SOO_PLUGIN_URL . '/public/css/dist/icheck-blue.min.css', array(), SOO_VERSION);
            wp_enqueue_style('moo-icheck-css');

            wp_register_style( 'sooCart-css', SOO_PLUGIN_URL . '/public/css/dist/sooCartStyle.min.css',array(), SOO_VERSION );
            wp_enqueue_style('sooCart-css');
        }

        if (!empty($this->pluginSettings["custom_css"])){
            //Include custom css
            wp_add_inline_style( "sooCheckoutStyles", $this->pluginSettings["custom_css"] );
        }
    }
    private function enqueueScripts($isAdvancedCheckout = false)
    {
        $cloverSkd = (defined('SOO_ENV') && (SOO_ENV === "DEV"))? 'https://checkout.sandbox.dev.clover.com/sdk.js' : 'https://checkout.clover.com/sdk.js';

        //Clover iframe SDK
        wp_register_script('sooCloverSdk', $cloverSkd, array('jquery'));
        wp_enqueue_script('sooCloverSdk');

        if ($isAdvancedCheckout) {

            $this->enqueueSweetAlerts11Js();

            //New Checkout JS
            wp_register_script('sooCheckoutScript', SOO_PLUGIN_URL . '/public/js/dist/sooCheckout.min.js', array(), SOO_VERSION);
            wp_enqueue_script('sooCheckoutScript',array('jquery','sooCloverSdk','SooSweetalerts'));

            //Soo Auth Module
            wp_register_script('sooAuthScript', SOO_PLUGIN_URL . '/public/js/dist/sooAuth.min.js', array(), SOO_VERSION);
            wp_enqueue_script('sooAuthScript',array('sooCheckoutScript'));

            //Add Google reCAPTCHA
            $googleReCAPTCHADisabled = (bool) get_option('sooDisableGoogleReCAPTCHA',false);
            if ($googleReCAPTCHADisabled === false && !empty($this->pluginSettings['reCAPTCHA_site_key']) && !empty($this->pluginSettings['reCAPTCHA_secret_key'])){
                $this->enqueueRecaptchaJs($this->pluginSettings['reCAPTCHA_site_key']);
            }

        } else {
            //Checkout JS
            wp_register_script('sooCheckoutScript', SOO_PLUGIN_URL . '/public/js/dist/moo_checkout.min.js', array('jquery','sooCloverSdk'), SOO_VERSION);
            wp_enqueue_script('sooCheckoutScript');

            wp_register_script('moo-icheck-js', SOO_PLUGIN_URL . '/public/js/dist/icheck.min.js', array('jquery'), SOO_VERSION);
            wp_enqueue_script('moo-icheck-js');
        }

        //Google Maps
        wp_register_script('moo-google-map', 'https://maps.googleapis.com/maps/api/js?libraries=geometry&key=AIzaSyBv1TkdxvWkbFaDz2r0Yx7xvlNKe-2uyRc');
        wp_enqueue_script('moo-google-map');

        wp_register_script('display-merchant-map', SOO_PLUGIN_URL . '/public/js/dist/moo_map.min.js',array(), SOO_VERSION);
        wp_enqueue_script('display-merchant-map');
    }

    private function cartIsEmpty()
    {
        $message =  '<div class="moo_emptycart"><p>';
        $message .=  __("Your cart is empty", "moo_OnlineOrders");
        $message .=  '</p><span><a class="moo-btn moo-btn-default" href="'.get_page_link($this->pluginSettings['store_page']).'" style="margin-top: 30px;">';
        $message .=  __("Back to Main Menu", "moo_OnlineOrders");
        $message .=  '</a></span></div>';
        return $message;
    }
    private function paymentMethodsSection($cloverPakmsKey)
    {

        $cacheNotAvailable = true;
        $ccNotAvailable = true;
        $giftCardNotAvailable = true;
        $googlePayNotAvailable = true;

        $html = '<div id="moo-checkout-form-payments" tabindex="0" aria-label="the payments method">';
        $html .=  '<div class="moo_checkout_border_bottom"></div>';
        $html .=  '<div class="moo-checkout-bloc-title moo-checkoutText-payment" >';
        $html .=  __("Payment Methods", "moo_OnlineOrders");
        $html .=  '</div>';
        $html .=  '<div class="moo-checkout-bloc-content">';
        $html .=  '';

        if (isset($cloverPakmsKey) && isset($this->pluginSettings['clover_googlepay']) && $this->pluginSettings['clover_googlepay'] == 'on') {
            $html .=  $this->googlePaySelector();
            $googlePayNotAvailable = false;
        }

        if (isset($cloverPakmsKey) && isset($this->pluginSettings['clover_payment_form']) && $this->pluginSettings['clover_payment_form'] == 'on') {
            $html .=  $this->creditCardSelector();
            $ccNotAvailable = false;
        }

        if (!empty(SOO_ACCEPT_GIFTCARDS) && isset($cloverPakmsKey) && isset($this->pluginSettings['clover_giftcards']) && $this->pluginSettings['clover_giftcards'] == 'on') {
            $html .=  $this->giftCardSelector();
            $giftCardNotAvailable = false;
        }

        if ($this->pluginSettings['payment_cash'] == 'on' || $this->pluginSettings['payment_cash_delivery'] == 'on') {
            $html .= $this->cashSelector();
            $cacheNotAvailable = false;
        }
        if ( !$ccNotAvailable ) {
            $html .= $this->cloverCardSection();
        }
        if ( !$giftCardNotAvailable ) {
            $html .= $this->giftCardSection();
        }
        if ( !$cacheNotAvailable ) {
            $html .= $this->cashSection();
        }
        if ( !$googlePayNotAvailable ) {
            $html .= $this->googlePaySection();
        }
        if( $ccNotAvailable && $cacheNotAvailable && $giftCardNotAvailable && $googlePayNotAvailable){
            $html .=  '<div>There are no payment methods available. Please try again later. Thank you for your understanding and patience.</div>';
        }
        $html .=  '</div></div>';
        $html = apply_filters('moo_filter_checkout_paymentMethodsSection', $html);
         echo  $html;
    }
    private function orderingTimesSection($oppening_status)
    {
       if (!$oppening_status){
           return;
       }
        //Start Checkout Ordering time Section
        $html  =  '<div id="moo-checkout-form-orderdate" tabindex="0" aria-label="Choose a time if you want schedule the order">';
        $html .=  '<div class="moo_checkout_border_bottom"></div>';


        //Start Title Section
        $html .=  '<div class="moo-checkout-bloc-title moo-checkoutText-chooseTime" >';
        $html .=  __("Choose a time", "moo_OnlineOrders");
        $html .=  '</div>';
        //End Title Section

        $html .=  '<div class="moo-checkout-bloc-content">';//Start Bloc Content
        $html .=  '<div class="moo-row">';//Start Container

        //Start Day Selector
        $html .=  '<div class="moo-col-xs-6"><div class="moo-form-group">';
        $html .=  '<select class="moo-form-control sooCheckoutTextInput" name="moo_pickup_day" id="moo_pickup_day" onchange="mooCheckout.orderingDayChanged(this)">';
        foreach ($oppening_status["pickup_time"] as $key => $val) {
            $html .= '<option value="'.$key.'">'.$key.'</option>';
        }
        $html .=  '</select>';
        $html .=  '</div></div>';
        //End Day Selector

        //Start Time Selector
        $html .=  '<div class="moo-col-xs-6"><div class="moo-form-group">';
        $html .=  '';
        $html .=  '<select class="moo-form-control sooCheckoutTextInput" name="moo_pickup_hour" id="moo_pickup_hour" onchange="mooCheckout.orderingTimeChanged(this)">';
        foreach ($oppening_status["pickup_time"] as $val) {
            $html .= '<option value="Select a time">Select a time</option>';
            foreach ($val as $h) {
                $html .= '<option value="'.$h.'">'.$h.'</option>';
            }
            break;
        }
        $html .=  '</select>';
        $html .=  '</div></div>';
        //End Time Selector

        //Opening Hours
        if ($oppening_status["store_time"] !== '') {
            $html .=  '<div class="moo-col-xs-12" style="margin-top: 10px">';
            $html .= __("Today's Online Ordering Hours", "moo_OnlineOrders");
            $html .=  ' : ';
            $html .=  $oppening_status["store_time"];
            $html .= '</div>';
         }

        $html .=  '<div class="moo-col-xs-12 extra-info" style="color:red;margin-top: 10px"></div>';

        $html .=  '</div">';//End Container
        $html .=  '</div>';//Start Bloc Content

        $html .=  '</div>';//Start Bloc Content

        //End Checkout Ordering time Section
        $html .=  '</div>';

        $html = apply_filters('moo_filter_checkout_orderingTimesSection', $html);
        echo  $html;
    }
    private function tipsSection()
    {
        $tipString = __("Tip", "moo_OnlineOrders");
        $addtipString = __("Add a tip to this order", "moo_OnlineOrders");
        $html = <<<HTML
        <div id="moo-checkout-form-tips">
            <div class="moo_checkout_border_bottom"></div>
            <div class="moo-checkout-bloc-title moo-checkoutText-tip">
                $tipString
            </div>
            <div class="moo-checkout-bloc-content">
                <div class="moo-row"  style="margin-top: 13px;">
                    <div class="moo-col-xs-6">
                        <div class="moo-form-group">
                            <select class="moo-form-control sooCheckoutTextInput" name="moo_tips_select" id="moo_tips_select" onchange="mooCheckout.tipPercentageChanged()" aria-label="list of tips">
                                <option value="cash">$addtipString</option>
HTML;
        if (isset($this->pluginSettings["tips_default"]) && !empty($this->pluginSettings["tips_default"])) {
            $defaultTips = floatval(trim($this->pluginSettings["tips_default"]));
        } else {
            $defaultTips = null;
        }
        if (isset($this->pluginSettings["tips_selection"]) && !empty($this->pluginSettings["tips_selection"])) {
            $vals = explode(",", $this->pluginSettings["tips_selection"]);
            if (is_array($vals) && count($vals) > 0) {
                foreach ($vals as $k => $v) {
                    if (floatval(trim($v)) === $defaultTips) {
                        $html.= '<option value="'.floatval(trim($v)).'" selected>'. floatval(trim($v)) .'%</option>';
                    } else {
                        $html.= '<option value="'.floatval(trim($v)).'">'. floatval(trim($v)) .'%</option>';
                    }
                }
            }
        } else {
            $html.= '<option value="10" '.(($defaultTips == 10)?"selected":"").'>10%</option>';
            $html.= '<option value="15" '.(($defaultTips == 15)?"selected":"").'>15%</option>';
            $html.= '<option value="20" '.(($defaultTips == 20)?"selected":"").'>20%</option>';
            $html.= '<option value="25" '.(($defaultTips == 25)?"selected":"").'>25%</option>';
        }
        $html .= <<<HTML
                            <option value="other">Custom $</option>
                        </select>
                        </div>
                    </div>
                    <div class="moo-col-xs-6">
                        <div class="moo-form-group">
                            <input class="moo-form-control sooCheckoutTextInput" name="tip" id="moo_tips" value="0" onchange="mooCheckout.tipAmountChanged()">
                        </div>
                    </div>
                </div>
            </div>
        </div>
HTML;
        $html = apply_filters('moo_filter_checkout_tipsSection', $html);
         echo  $html;
    }
    private function loyaltySection($label = 'points')
    {
        $tipString = __("Loyalty", "moo_OnlineOrders");
        $balance = __("Balance", "moo_OnlineOrders");
        $use = __("Use ", "moo_OnlineOrders"). $label;
        $dontUse = __("Remove ", "moo_OnlineOrders"). $label;
        $imgLink = SOO_PLUGIN_URL . "/public/img/loyaltyIcon.svg";
        $html = <<<HTML
        <div id="moo-checkout-form-loyalty">
            <div class="moo_checkout_border_bottom"></div>
            <div class="moo-checkout-bloc-title moo-checkoutText-tip">
                $tipString
            </div>
            <div class="moo-checkout-bloc-content">
                <div class="moo-row" style="margin-top: 13px;">
                    <div class="moo-col-xs-2 loyaltyIcon">
                        <img src="$imgLink" alt="Loyalty">
                    </div>
                    <div class="moo-col-xs-6 loyaltyBalance">
                        <div class="balanceTitle">$balance</div>
                        <div class="balance">
                            <span class="balancePoints"></span> = $<span class="balanceAmount"></span>
                            <div class="canUseUpTo"></div>
                        </div>
                    </div>
                    <div class="moo-col-xs-4 loyaltyButtons">
                        <button class="sooUsePointsButton sooCheckoutSecondaryButtonInput" onclick="mooCheckout.clickOnUsePoints()">
                           $use
                        </button> 
                        <button class="sooRemovePointsButton sooCheckoutSecondaryButtonInput" onclick="mooCheckout.clickOnRemovePoints()">
                           $dontUse
                        </button>
                    </div>
                </div>
                <div class="moo-row usePointsInput">
                  <div class="moo-col-md-9 moo-col-xs-6">
                      <div class="moo-row">
                           <div class="moo-col-md-9 moo-col-xs-12">
                            <input id="sooSelectedPointsValue" type="number" class="sooCheckoutTextInput" onchange="mooCheckout.changePointsValue()">
                          </div>
                          <div class="moo-col-md-3 moo-col-xs-12 sooToPayInfo">To Pay: <span class="sooToPayAmount">$0.00</span></div>
                     </div>
                  </div>
                  <div class="moo-col-md-3 moo-col-xs-6 sooValidatePoints">
                    <button class="sooValidatePointsButton sooCheckoutPrimaryButtonInput" onclick="mooCheckout.usePoints()">Use</button>
                    <button class="sooEditPointsButton sooCheckoutPrimaryButtonInput" onclick="mooCheckout.editPoints()">Edit</button>
                    <span class="sooLoyaltyDetailsButton" >
                        <svg onclick="mooCheckout.openLoyaltyDetails()" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path _ngcontent-vfa-c105="" d="M9.99984 5.83342C9.77883 5.83342 9.56687 5.92121 9.41059 6.07749C9.25431 6.23377 9.16651 6.44573 9.16651 6.66675V10.0001C9.16651 10.2211 9.25431 10.4331 9.41059 10.5893C9.56687 10.7456 9.77883 10.8334 9.99984 10.8334C10.2209 10.8334 10.4328 10.7456 10.5891 10.5893C10.7454 10.4331 10.8332 10.2211 10.8332 10.0001V6.66675C10.8332 6.44573 10.7454 6.23377 10.5891 6.07749C10.4328 5.92121 10.2209 5.83342 9.99984 5.83342ZM10.7665 13.0167C10.7483 12.9636 10.723 12.9132 10.6915 12.8667L10.5915 12.7417C10.4743 12.6261 10.3255 12.5478 10.1639 12.5166C10.0022 12.4855 9.83493 12.5029 9.68317 12.5667C9.58219 12.6089 9.48918 12.6681 9.40817 12.7417C9.33094 12.8196 9.26984 12.912 9.22837 13.0135C9.18689 13.115 9.16587 13.2237 9.16651 13.3334C9.16782 13.4423 9.19047 13.5499 9.23317 13.6501C9.2706 13.7535 9.33031 13.8474 9.40808 13.9252C9.48584 14.0029 9.57976 14.0627 9.68317 14.1001C9.78292 14.1442 9.89078 14.1669 9.99984 14.1669C10.1089 14.1669 10.2168 14.1442 10.3165 14.1001C10.4199 14.0627 10.5138 14.0029 10.5916 13.9252C10.6694 13.8474 10.7291 13.7535 10.7665 13.6501C10.8092 13.5499 10.8319 13.4423 10.8332 13.3334C10.8373 13.2779 10.8373 13.2222 10.8332 13.1667C10.8188 13.1136 10.7963 13.063 10.7665 13.0167ZM9.99984 1.66675C8.35166 1.66675 6.7405 2.15549 5.37009 3.07117C3.99968 3.98685 2.93158 5.28834 2.30084 6.81105C1.67011 8.33377 1.50509 10.0093 1.82663 11.6258C2.14817 13.2423 2.94185 14.7272 4.10728 15.8926C5.27272 17.0581 6.75758 17.8517 8.37409 18.1733C9.9906 18.4948 11.6662 18.3298 13.1889 17.6991C14.7116 17.0683 16.0131 16.0002 16.9288 14.6298C17.8444 13.2594 18.3332 11.6483 18.3332 10.0001C18.3332 8.90573 18.1176 7.8221 17.6988 6.81105C17.28 5.80001 16.6662 4.88135 15.8924 4.10752C15.1186 3.3337 14.1999 2.71987 13.1889 2.30109C12.1778 1.8823 11.0942 1.66675 9.99984 1.66675ZM9.99984 16.6667C8.6813 16.6667 7.39237 16.2758 6.29604 15.5432C5.19971 14.8107 4.34523 13.7695 3.84064 12.5513C3.33606 11.3331 3.20404 9.99269 3.46127 8.69948C3.71851 7.40627 4.35345 6.21839 5.2858 5.28604C6.21815 4.35369 7.40603 3.71875 8.69924 3.46151C9.99245 3.20428 11.3329 3.3363 12.5511 3.84088C13.7692 4.34547 14.8104 5.19995 15.543 6.29628C16.2755 7.39261 16.6665 8.68154 16.6665 10.0001C16.6665 11.7682 15.9641 13.4639 14.7139 14.7141C13.4636 15.9644 11.768 16.6667 9.99984 16.6667Z" fill="#BFBFBF" class="ng-tns-c105-44"></path></svg>
                    </span>
                  </div>
                   <div class="moo-col-xs-12 moo-error-section"></div>
                </div> 
                <div class="moo-row sooEarningSection">
                  <div class="moo-col-xs-12"></div>
                </div>
            </div>
        </div>
HTML;
        $html = apply_filters('moo_filter_checkout_loyaltySection', $html);
         echo  $html;
    }
    private function tipsSectionOld()
    {
        $tipString = __("Tip", "moo_OnlineOrders");
        $addtipString = __("Add a tip to this order", "moo_OnlineOrders");
        $html = <<<HTML
        <div id="moo-checkout-form-tips">
            <div class="moo_checkout_border_bottom"></div>
            <div class="moo-checkout-bloc-title moo-checkoutText-tip">
                $tipString
            </div>
            <div class="moo-checkout-bloc-content">
                <div class="moo-row"  style="margin-top: 13px;">
                    <div class="moo-col-md-6">
                        <div class="moo-form-group">
                            <select class="moo-form-control" name="moo_tips_select" id="moo_tips_select" onchange="moo_tips_select_changed()" aria-label="list of tips">
                                <option value="cash">$addtipString</option>
HTML;
        if (isset($this->pluginSettings["tips_default"]) && !empty($this->pluginSettings["tips_default"])) {
            $defaultTips = floatval(trim($this->pluginSettings["tips_default"]));
        } else {
            $defaultTips = null;
        }
        if (isset($this->pluginSettings["tips_selection"]) && !empty($this->pluginSettings["tips_selection"])) {
            $vals = explode(",", $this->pluginSettings["tips_selection"]);
            if (is_array($vals) && count($vals) > 0) {
                foreach ($vals as $k => $v) {
                    if (floatval(trim($v)) === $defaultTips) {
                        $html.= '<option value="'.floatval(trim($v)).'" selected>'. floatval(trim($v)) .'%</option>';
                    } else {
                        $html.= '<option value="'.floatval(trim($v)).'">'. floatval(trim($v)) .'%</option>';
                    }
                }
            }
        } else {
            $html.= '<option value="10" '.(($defaultTips == 10)?"selected":"").'>10%</option>';
            $html.= '<option value="15" '.(($defaultTips == 15)?"selected":"").'>15%</option>';
            $html.= '<option value="20" '.(($defaultTips == 20)?"selected":"").'>20%</option>';
            $html.= '<option value="25" '.(($defaultTips == 25)?"selected":"").'>25%</option>';
        }
        $html .= <<<HTML
                            <option value="other">Custom $</option>
                        </select>
                        </div>
                    </div>
                    <div class="moo-col-md-6">
                        <div class="moo-form-group">
                            <input class="moo-form-control" name="tip" id="moo_tips" value="0" onchange="moo_tips_amount_changed()">
                        </div>
                    </div>
                </div>
            </div>
        </div>
HTML;
        $html = apply_filters('moo_filter_checkout_tips', $html);
         echo  $html;
    }
    private function cloverCardSection()
    {
        $htmBegin   = '<div id="moo-cloverCreditCardPanel"><input type="hidden" name="cloverToken" id="moo-CloverToken">';
        $htmEnd     = ' <div class="clover-errors"></div></div>';
        $cardNumber = '<div class="moo-row"><div class="moo-col-md-12"><div class="moo-form-group"><div class="moo-form-control" id="moo_CloverCardNumber"></div><div class="card-number-error"><div class="clover-error"></div></div></div></div></div>';
        $dateAndCvv = '<div class="moo-row"><div class="moo-col-md-6"><div class="moo-form-group"><div class="moo-form-control" id="moo_CloverCardDate"></div><div class="date-error"><div class="clover-error"></div></div></div></div><div class="moo-col-md-6"><div class="moo-form-group"><div class="moo-form-control" id="moo_CloverCardCvv"></div><div class="cvv-error"><div class="clover-error"></div></div></div></div></div>';
        $address    = '<div class="moo-row"><div class="moo-col-md-12"><div class="moo-form-group"><div class="moo-form-control" id="moo_CloverStreetAddress"></div><div class="streetAddress-error"><div class="clover-error"></div></div></div></div></div>';
        $zip        = '<div class="moo-row"><div class="moo-col-md-12"><div class="moo-form-group"><div class="moo-form-control" id="moo_CloverCardZip"></div><div class="zip-error"><div class="clover-error"></div></div></div></div></div>';

        $html = $htmBegin . $cardNumber . $dateAndCvv;
        if ($this->showStreetAddressFieldOnPaymentForm) {
            $html .= $address;
        }
        $html .= $zip;
        $html .= $htmEnd;
        $html = apply_filters('moo_filter_checkout_cloverCardSection', $html);
        return $html;
    }
    private function giftCardSection()
    {
        $htmBegin   = '<div id="moo-cloverGiftCardPanel"><input type="hidden" name="cloverToken" id="moo-CloverToken">';
        $htmEnd     = ' <div class="clover-errors"></div></div>';
        $giftCard   = '<div class="moo-row"><div class="moo-col-md-12"><div class="moo-form-group"><div id="moo_CloverGiftCard"></div><div class="giftCard-error"><div class="clover-error"></div></div></div></div></div>';
        $html = $htmBegin . $giftCard . $htmEnd;
        $html = apply_filters('moo_filter_giftCardSection', $html);
        return $html;
    }
    private function cashSection()
    {
        $html    = '<div id="moo_cashPanel">';
        $html    .= ' <div class="soo-phone-verif-title">We will send a verification code via SMS to your number</div>';
        $html    .= ' <div class="soo-phone-input"><input class="sooCheckoutTextInput" type="text" placeholder="Enter 6-digit code"><button onclick="mooCheckout.verifyCodeSentToPhone()" class="sooCheckoutPrimaryButtonInput">Verify</button></div>';
        $html    .= ' <div class="soo-phone-button"><button class="sooCheckoutPrimaryButtonInput"  onclick="mooCheckout.sendCodeToVerifyPhone()">Send</button></div>';
        $html    .= ' <div class="soo-phone-verif-footer"></div>';
        $html    .= '</div>';
        return apply_filters('moo_filter_checkout_cashPanel', $html);
    }
    private function googlePaySection()
    {
        $html    = '<div id="mooGooglePaySection">';
        $html    .= ' <div class="mooGooglePaySectionText">Fast and easy checkout with Google! Simply scroll to the bottom to complete your payment</div>';
        $html    .= '</div>';
        return apply_filters('moo_filter_checkout_googlePaySection', $html);
    }
    private function creditCardSelector()
    {
        $payString = __("Credit / Debit Card", "moo_OnlineOrders");
        $html = <<<HTML
        <div class="soo-checkout-radio-input-option">
            <input onchange="mooCheckout.paymentMethodChanged()" class="soo-checkout-radio-input" type="radio" name="paymentMethod" value="clover" id="moo-checkout-form-payments-clover">
            <label for="moo-checkout-form-payments-clover" class="moo-checkout-radio-input-label" >$payString</label>
        </div>
HTML;
        $html = apply_filters('moo_filter_checkout_creditCardSelector', $html);
        return  $html;
    }
    private function cashSelector()
    {
        $payString = __("Pay at location", "moo_OnlineOrders");
        $html = <<<HTML
        <div class="soo-checkout-radio-input-option moo-checkout-form-payments-cash-container">
            <input onchange="mooCheckout.paymentMethodChanged()" class="soo-checkout-radio-input" type="radio" name="paymentMethod" value="cash" id="moo-checkout-form-payments-cash">
            <label for="moo-checkout-form-payments-cash" class="moo-checkout-radio-input-label" id="moo-checkout-form-payincash-label">$payString</label>
        </div>
HTML;
        $html = apply_filters('moo_filter_checkout_cashSelector', $html);
        return $html;
    }
    private function giftCardSelector()
    {
        $payString = __("Pay Using Gift Card", "moo_OnlineOrders");
        $html = <<<HTML
        <div class="soo-checkout-radio-input-option">
            <input onchange="mooCheckout.paymentMethodChanged()" class="soo-checkout-radio-input" type="radio" name="paymentMethod" value="giftcard" id="moo-checkout-form-payments-giftcard">
            <label for="moo-checkout-form-payments-giftcard" class="moo-checkout-radio-input-label" id="moo-checkout-form-payments-giftcard-label">$payString</label>
        </div>
HTML;
        $html = apply_filters('moo_filter_checkout_giftCardSelector', $html);
        return $html;
    }
    private function googlePaySelector()
    {
        $payString = __("Google Pay", "moo_OnlineOrders");
        $html = <<<HTML
        <div class="soo-checkout-radio-input-option">
            <input onchange="mooCheckout.paymentMethodChanged()" class="soo-checkout-radio-input" type="radio" name="paymentMethod" value="googlepay" id="moo-checkout-form-payments-google">
            <label for="moo-checkout-form-payments-google" class="moo-checkout-radio-input-label" id="moo-checkout-form-paywithgoogle-label">$payString</label>
        </div>
HTML;
        $html = apply_filters('moo_filter_checkout_googlePaySelector', $html);
        return  $html;
    }
    private function couponsSection()
    {
        $titleTxt = __("Coupon code", "moo_OnlineOrders");
        $applyTxt = __("Apply", "moo_OnlineOrders");
        $removeTxt = __("Remove", "moo_OnlineOrders");
        $html = '';
        $html .= '';

        $html  =  '<div id="moo-checkout-form-coupon">';
        $html  .= '<div class="moo_checkout_border_bottom"></div>';
        $html  .=  '<div class="moo-checkout-bloc-title moo-checkoutText-couponCod"><label for="moo_coupon">';
        $html  .=  __("Coupon code", "moo_OnlineOrders");
        $html  .=  '</label></div>';
        $html .= '<div class="moo-checkout-bloc-content">';
        $html .= '<div class="moo-row">';
        $html .= '<div class="moo-col-xs-9">';
        $html .= '<div class="moo-form-group">';
        $html .= '<input type="text" class="moo-form-control sooCheckoutTextInput" id="moo_coupon">';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="moo-col-xs-3">';
        $html .= '<div class="moo-form-group">';
        $html .= '<button class="sooApplyCouponButton sooCheckoutPrimaryButtonInput" onclick="mooCheckout.applyCoupon()">'.$applyTxt.'</button>';
        $html .= '<button class="sooDeleteCouponButton sooCheckoutPrimaryButtonInput" onclick="mooCheckout.removeCoupon()">'.$removeTxt.'</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .=  '</div></div>';
        $html = apply_filters('moo_filter_checkout_couponsSection', $html);
        echo  $html;
        $html = <<<HTML
 <div id="moo-checkout-form-coupon">
    <div class="moo_checkout_border_bottom"></div>
    <div class="moo-checkout-bloc-title moo-checkoutText-couponCode">
        <label for="moo_coupon">$titleTxt</label>
    </div>
    <div class="moo-checkout-bloc-content" id="moo_enter_coupon">
        <div class="moo-col-md-8">
            <div class="moo-form-group">
                <input onchange="mooCouponValueChanged(event)" type="text" class="moo-form-control sooCheckoutTextInput" id="moo_coupon">
            </div>
        </div>
        <div class="moo-col-md-4">
            <div class="moo-form-group">
                <a href="#" class="moo-btn moo-btn-primary" onclick="mooCouponApply(event)" style="height: 40px;line-height: 24px;">
                    $applyTxt
                </a>
            </div>
        </div>
    </div>
    <div class="moo-checkout-bloc-content" id="moo_remove_coupon" style="display:none">
        <div class="moo-col-md-8">
            <div class="moo-form-group">
                <p style="font-size: 20px" id="moo_remove_coupon_code"></p>
            </div>
        </div>
        <div class="moo-col-md-4">
            <div class="moo-form-group">
                <a href="#" class="moo-btn moo-btn-primary" onclick="mooCouponRemove(event)">
                    $removeTxt
                </a>
            </div>
        </div>
    </div>
</div>
HTML;

    }
    private function specialInstructionsSection()
    {
        $html  =  '<div id="moo-checkout-form-instruction">';
        $html  .= '<div class="moo_checkout_border_bottom"></div>';
        $html  .=  '<div class="moo-checkout-bloc-title moo-checkoutText-instructions"><label for="Mooinstructions">';
        $html  .=  __("Special Instructions", "moo_OnlineOrders");
        $html  .=  '</label></div>';

        $html .= '<div class="moo-checkout-bloc-content">';
        if (isset($this->pluginSettings['text_under_special_instructions']) && $this->pluginSettings['text_under_special_instructions']!=='') {
            $html .= '<div class="moo-special-instruction-title">'.wp_kses_post(wp_unslash($this->pluginSettings['text_under_special_instructions'])).'</div>';
        }
        if (isset($this->pluginSettings['special_instructions_required']) && $this->pluginSettings['special_instructions_required']==='yes') {
            $html .= '<textarea class="moo-form-control sooCheckoutTextInput" cols="100%" rows="5" id="Mooinstructions" style="height: 80px;" required></textarea>';
        } else {
            $html .= '<textarea class="moo-form-control sooCheckoutTextInput" cols="100%" rows="5" id="Mooinstructions" style="height: 80px;"></textarea>';
        }

        $html .=  '</div></div>';
        $html = apply_filters('moo_filter_checkout_specialInstructionsSection', $html);
        echo  $html;
    }

    private function cloverCardSectionOld()
    {
        $htmBegin   = '<div id="moo-cloverCreditCardPanel"><input type="hidden" name="cloverToken" id="moo-CloverToken">';
        $htmEnd     = ' <div class="clover-errors"></div></div>';
        $cardNumber = '<div class="moo-row"><div class="moo-col-md-12"><div class="moo-form-group"><div class="moo-form-control" id="moo_CloverCardNumber"></div><div class="card-number-error"><div class="clover-error"></div></div></div></div></div>';
        $dateAndCvv = '<div class="moo-row"><div class="moo-col-md-6"><div class="moo-form-group"><div class="moo-form-control" id="moo_CloverCardDate"></div><div class="date-error"><div class="clover-error"></div></div></div></div><div class="moo-col-md-6"><div class="moo-form-group"><div class="moo-form-control" id="moo_CloverCardCvv"></div><div class="cvv-error"><div class="clover-error"></div></div></div></div></div>';
        $address    = '<div class="moo-row"><div class="moo-col-md-12"><div class="moo-form-group"><div class="moo-form-control" id="moo_CloverStreetAddress"></div><div class="streetAddress-error"><div class="clover-error"></div></div></div></div></div>';
        $zip        = '<div class="moo-row"><div class="moo-col-md-12"><div class="moo-form-group"><div class="moo-form-control" id="moo_CloverCardZip"></div><div class="zip-error"><div class="clover-error"></div></div></div></div></div>';
        $html = $htmBegin . $cardNumber . $dateAndCvv;
        if ($this->showStreetAddressFieldOnPaymentForm) {
            $html .= $address;
        }
        $html .= $zip . $htmEnd;
        $html = apply_filters('moo_filter_checkout_cloverCard', $html);
         echo  $html;
    }
    private function creditCardSelectorOld()
    {
        $payString = __("Pay now with Credit Card", "moo_OnlineOrders");
        $html = <<<HTML
        <div class="moo-checkout-form-payments-option">
            <input class="moo-checkout-form-payments-input" type="radio" name="payments" value="clover" id="moo-checkout-form-payments-clover">
            <label for="moo-checkout-form-payments-clover" style="display: inline;margin-left:15px;font-size: 16px; vertical-align: sub;">$payString</label>
        </div>
HTML;
        $html = apply_filters('moo_filter_checkout_creditCardSelector', $html);
        echo  $html;
    }
    private function cashSelectorOld()
    {
        $payString = __("Pay at location", "moo_OnlineOrders");
        $html = <<<HTML
        <div class="moo-checkout-form-payments-option moo-checkout-form-payments-cash-container">
            <input class="moo-checkout-form-payments-input" type="radio" name="payments" value="cash" id="moo-checkout-form-payments-cash">
            <label for="moo-checkout-form-payments-cash" style="display: inline;margin-left:15px;font-size: 16px; vertical-align: sub;" id="moo-checkout-form-payincash-label">$payString</label>
        </div>
HTML;
        $html = apply_filters('moo_filter_checkout_cashSelector', $html);
        echo  $html;
    }

    private function borderBottom()
    {
        echo '<div class="moo_checkout_border_bottom"></div>';
    }
    private function checkCloverFraudTools($openingHours)
    {
        if (isset($openingHours["fraudTools"]) &&
                isset($openingHours["fraudTools"]["validateStreetAddressMatch"]) &&
                isset($openingHours["fraudTools"]["validateStreetAddressProvided"]) &&
                isset($openingHours["fraudTools"]["validateStreetAddressVerified"])
        ) {
            return $openingHours["fraudTools"]["validateStreetAddressMatch"] || $openingHours["fraudTools"]["validateStreetAddressProvided"] || $openingHours["fraudTools"]["validateStreetAddressVerified"];
        }
        return false;
    }

    private function getOpeningMessage($oppening_status)
    {
        $oppening_msg = "";
        if ($this->pluginSettings['hours'] != 'all' ) {
            if(!$oppening_status || $oppening_status["status"] == 'close'){
                if (isset($this->pluginSettings["closing_msg"]) && $this->pluginSettings["closing_msg"] !== '') {
                    $oppening_msg = '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'.$this->pluginSettings["closing_msg"].'</div>';
                } else {
                    $oppening_msg = '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">';
                    if (!empty($oppening_status["store_time"])) {
                        $oppening_msg .= "<strong>".__("Today's Online Ordering Hours", "moo_OnlineOrders")."</strong><br/> ".$oppening_status["store_time"]."<br/> ";
                    }
                    $oppening_msg .= __("Online Ordering Currently Closed", "moo_OnlineOrders");
                    if (isset($this->pluginSettings['accept_orders_w_closed']) && $this->pluginSettings['accept_orders_w_closed'] == 'on') {
                        $oppening_msg .= "<br/><p style='color: #006b00'>";
                        $oppening_msg .= __("You may schedule your order in advance", "moo_OnlineOrders");
                        $oppening_msg .= "</p>";
                    }
                    $oppening_msg .= '</div>';
                }
            }
            if (!empty($oppening_status["status"]) && $oppening_status["status"] === 'not_found'){
                $oppening_msg .= '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">Please contact the Store to update their Online Ordering Hours</div>';
            }

        }
        return $oppening_msg;
    }

    private function getOrderingPageLink() {
        if (!empty( $this->pluginSettings['store_page'] )){
            return get_page_link($this->pluginSettings['store_page']);
        }
      return null;
    }
    private function getCartPageLink() {
        if (!empty( $this->pluginSettings['cart_page'] )){
            return get_page_link($this->pluginSettings['cart_page']);
        }
        return null;
    }
}