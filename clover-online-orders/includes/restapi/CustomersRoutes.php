<?php
/**
 * Created by Mohammed EL BANYAOUI.
 * Sync route to handle all requests to sync the inventory with Clover
 * User: Smart MerchantApps
 * Date: 3/5/2019
 * Time: 12:23 PM
 */
require_once "BaseRoute.php";

class CustomersRoutes extends BaseRoute {
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
    public function register_routes() {
        //V1 Addresses Endpoints (used on my account section)
        register_rest_route( $this->namespace, '/customers/addresses', array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'getAddresses' ),
                'permission_callback' => '__return_true'
            ),
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'addAddress' ),
                'permission_callback' => '__return_true'
            ),
            array(
                'methods'   => 'DELETE',
                'callback'  => array( $this, 'deleteAddress' ),
                'permission_callback' => '__return_true'
            )
        ) );
        //V3 Addresses Endpoints ( used on Checkout)
        register_rest_route( $this->v3Namespace, '/customers/addresses', array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'getAddressesV2' ),
                'permission_callback' => '__return_true'
            ),
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'addAddressV2' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->v3Namespace, '/customers/addresses/(?P<address_id>[a-zA-Z0-9-]+)', array(
            array(
                'methods'   => 'DELETE',
                'callback'  => array( $this, 'deleteAddressV2' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->v3Namespace, '/geocode-address', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'geoCodeAnAddress' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->v3Namespace, '/delivery-estimate', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'createDeliveryEstimate' ),
                'permission_callback' => '__return_true'
            )
        ) );

        register_rest_route( $this->namespace, '/customers/fb-login', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'customerFacebookLogin' ),
                'permission_callback' => '__return_true'
            )
        ) );

        register_rest_route( $this->namespace, '/customers/login', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'customerLogin' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/customers/signup', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'customerSignup' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/customers/fblogin', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'customerFbLogin' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/customers/reset-password', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'customerResetPassword' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/customers/check-otp', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'checkOtp' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/customers/new-password', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'chooseNewPassword' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/customers/me', array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'getProfile' ),
                'permission_callback' => '__return_true'
            ),
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'updateProfile' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/customers/points', array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'getPoints' ),
                'permission_callback' => '__return_true'
            )
        ) );

        register_rest_route( $this->namespace, '/customers/password', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'updatePassword' ),
                'permission_callback' => '__return_true'
            )
        ) );

        register_rest_route( $this->namespace, '/customers/send-code', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'sendCodeToVerifyPhone' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/customers/check-code', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'checkCodeToVerifyPhone' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/guests/send-code', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'sendCodeToVerifyGuestPhone' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/guests/check-code', array(
            array(
                'methods'   => 'POST',
                'callback'  => array( $this, 'checkCodeToVerifyGuestPhone' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/customers/orders', array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'getOrders' ),
                'permission_callback' => '__return_true'
            )
        ) );
        register_rest_route( $this->namespace, '/customers/orders/(?P<uuid>[a-zA-Z0-9-]+)', array(
            array(
                'methods'   => 'GET',
                'callback'  => array( $this, 'getOneOrder' ),
                'permission_callback' => '__return_true'
            )
        ) );
    }

    /**
     * @param $request
     * @body json
     * @return array
     */
    public function getAddresses( $request ) {
        $fromSession=false;
        // check if token sent in body
        if(isset($request["moo_customer_token"]) && !empty($request["moo_customer_token"])){
            $token = $request["moo_customer_token"];
        } else {
            if($this->session->isEmpty("moo_customer_token")) {
                return array("status"=>"failed","message"=>"not logged user");
            } else {
                $token = $this->session->get("moo_customer_token");
                $fromSession = true;
            }
        }

        if($token) {
            $res = $this->api->moo_GetAddresses($token);
            $result= json_decode($res);
            if(isset($result->status) && $result->status == 'success') {
                $res = array("status"=>"success","addresses"=>$result->addresses);
                $this->session->set($result->customer,"moo_customer");
                return $res;
            } else {
                if($fromSession){
                    $this->session->set(null,"moo_customer");
                    $this->session->set(false,"moo_customer_token");
                    $this->session->set(null,"moo_customer_email");
                }
                return
                    array(
                        "status"=>"failed",
                        "message"=>"not logged user"
                    );
            }

        } else {
            return array(
                "status"=>"failed",
                "message"=>"Customer not updated"
            );
        }

    }
    /**
     * @param $request
     * @body json
     * @return array
     */
    public function addAddress( $request ) {
        // check if token sent in body
        if(isset($request["moo_customer_token"]) && !empty($request["moo_customer_token"])){
            $token = $request["moo_customer_token"];
        } else {
            if($this->session->isEmpty("moo_customer_token")) {
                return array("status"=>"failed","message"=>"not logged user");
            } else {
                $token = $this->session->get("moo_customer_token");
            }
        }

        if($token) {
            $request_body = $request->get_json_params();
            $addressOptions = array(
                "token"     => $token,
                "address"   =>  sanitize_text_field($request_body['address']),
                "line2"     =>  sanitize_text_field($request_body['line2']),
                "city"      =>  sanitize_text_field($request_body['city']),
                "state"     =>  sanitize_text_field($request_body['state']),
                "zipcode"   =>  sanitize_text_field($request_body['zipcode']),
                "country"   =>  sanitize_text_field($request_body['country']),
                "lng"       =>  sanitize_text_field( $request_body['lng']),
                "lat"       =>  sanitize_text_field($request_body['lat'])

            );
            $res = $this->api->moo_AddAddress($addressOptions);
            $result= json_decode($res);
            if($result->status == 'success') {
                return array("status"=>"success");
            } else {
                return array("status"=>$result->status);
            }

        } else {
            return array(
                "status"=>"failed",
                "message"=>"Customer not updated"
            );
        }

    }
    public function deleteAddress( $request ) {
        // check if token sent in body
        if(isset($request["moo_customer_token"]) && !empty($request["moo_customer_token"])){
            $token = $request["moo_customer_token"];
        } else {
            if($this->session->isEmpty("moo_customer_token")) {
                return array("status"=>"failed","message"=>"not logged user");
            } else {
                $token = $this->session->get("moo_customer_token");
            }
        }

        if($token) {
            $request_body = $request->get_json_params();
            $address_id = $request_body['address_id'];
            $res = $this->api->moo_DeleteAddresses($address_id,$token);
            $result= json_decode($res);

            if($result->status == 'success') {
                return array("status"=>"success");
            } else {
                return array("status"=>$result->status);
            }

        } else {
            return array(
                "status"=>"failed",
                "message"=>"Customer not updated"
            );
        }

    }
    public function customerFbLogin( $request ) {
        $request_body = $request->get_json_params();
        $customerOptions = array(
            "gender"     => sanitize_text_field($request_body["gender"]),
            "name" => sanitize_text_field($request_body["name"]),
            "email"     => sanitize_text_field($request_body["email"]),
            "id"     => sanitize_text_field($request_body["fbid"])
        );
        $res = $this->api->moo_CustomerFbLogin($customerOptions);
        $result= json_decode($res);
        if($result->status == 'success')
        {
            $this->session->set($result->token,"moo_customer_token");
            $this->session->set($result->customer_email,"moo_customer_email");
        } else {
            $this->session->set(false,"moo_customer_token");
            $this->session->set(null,"moo_customer_email");
        }

        return $result;
    }
    public function customerFacebookLogin( $request ) {

        $request_body = $request->get_json_params();

        #Send the first accessToken returned by Facebook SDK to backend
        $userToken = $request_body['userToken'];
        if (isset($this->pluginSettings['fb_appid']) && isset($this->pluginSettings['fb_appsecret'])){
            $clientId = $this->pluginSettings['fb_appid'];
            $clientSecret =  $this->pluginSettings['fb_appsecret'] ;
            try {
                $appLink = 'https://graph.facebook.com/oauth/access_token?client_id=' . $clientId . '&client_secret=' . $clientSecret . '&grant_type=client_credentials';
                $response = wp_remote_get($appLink);

                if(!is_wp_error( $response )){
                    $body = wp_remote_retrieve_body( $response );
                    $data = json_decode( $body , true);
                    if($data['access_token']){
                        $link = 'https://graph.facebook.com/debug_token?input_token=' . $userToken . '&access_token=' . $data['access_token'];
                        $checkToken = wp_remote_get($link);
                        if(!is_wp_error( $checkToken )){
                            $body = wp_remote_retrieve_body( $checkToken );
                            $data = json_decode( $body, true );
                            if (isset($data['data']['is_valid']) && $data['data']['is_valid']){
                                if ($data['data']['user_id'] === $request_body['userId']){
                                    //Get User Email
                                    $getEmailLink = 'https://graph.facebook.com/me?fields=email,first_name,last_name&access_token=' . $userToken;
                                    $userEmailReq = wp_remote_get($getEmailLink);
                                    if(!is_wp_error( $userEmailReq )){
                                        $body = wp_remote_retrieve_body( $userEmailReq );
                                        $data = json_decode( $body, true );
                                        if (isset($data['email'])){
                                           $payload = [
                                                "email"=>$data['email'],
                                                "first_name"=>$data['first_name'],
                                                "last_name"=>$data['last_name'],
                                                "fbid"=>$data['id'],
                                           ];
                                            $request->set_body(wp_json_encode($payload));
                                            $endPoint = 'merchants/customers/fb-login';
                                            return $this->sendRequest($request, $endPoint);
                                        } else {
                                            throw new Exception('Email not found');
                                        }
                                    } else {
                                        throw new Exception('An error has occurred, try again');
                                    }

                                } else {
                                    throw new Exception('user_id not valid');
                                }
                            } else {
                                throw new Exception('Token not valid');
                            }
                        } else {
                            throw new Exception();
                        }
                    } else {
                        throw new Exception();
                    }
                } else {
                    throw new Exception();
                }
            } catch (Exception $e){
                return new WP_Error(
                    'bad_request',
                    $e->getMessage(),
                    array( 'status' => 400 )
                );
            }
        } else {
            return new WP_Error(
                'bad_request',
                __('Error',"moo_OnlineOrders"),
                array( 'status' => 500 )
            );
        }

    }
    public function customerLogin( $request ) {
        $endPoint = 'merchants/customers/login';
        return $this->sendRequest($request, $endPoint);
    }
    public function customerSignup( $request ) {
        $endPoint = 'merchants/customers/register';
        return $this->sendRequest($request, $endPoint);
    }
    public function customerResetPassword( $request ) {
        $endPoint = 'merchants/customers/password/email';
        return $this->sendRequest($request, $endPoint);
    }
    public function checkOtp( $request ) {
        $endPoint = 'merchants/customers/password/check-otp';
        return $this->sendRequest($request, $endPoint);
    }

    public function chooseNewPassword( $request ) {
        $endPoint = 'merchants/customers/password/reset';
        return $this->sendRequest($request, $endPoint);
    }

    public function getOrders( $request ) {
        if (!empty($_GET['page'])){
            $page = intval($_GET['page']);
        } else {
            $page = 1;
        }
        $endPoint = 'merchants/customers/orders?page='.$page;
        return $this->sendRequest($request, $endPoint);
    }
    public function getOneOrder( $request) {
        if ( empty( $request["uuid"] ) ) {
            return new WP_Error( 'uuid_required', 'Order Uuid not found', array( 'status' => 400 ) );
        }
        $endPoint = 'merchants/customers/orders/' . $request["uuid"];
        return $this->sendRequest($request, $endPoint);
    }
    public function getProfile( $request ) {
        $endPoint = 'merchants/customers/me';
        return $this->sendRequest($request, $endPoint);
    }
    public function updateProfile( $request ) {
        $endPoint = 'merchants/customers/me';
        return $this->sendRequest($request, $endPoint);
    }
    public function getPoints( $request ) {
        $endPoint = 'merchants/customers/points';
        return $this->sendRequest($request, $endPoint);
    }
    public function updatePassword( $request ) {
        $endPoint = 'merchants/customers/password/update';
        return $this->sendRequest($request, $endPoint);
    }
    public function sendCodeToVerifyPhone( $request ) {
        $endPoint = 'merchants/customers/phone/verify';
        return $this->sendRequest($request, $endPoint);
    }
    public function checkCodeToVerifyPhone( $request ) {
        $endPoint = 'merchants/customers/phone/check-otp';
        return $this->sendRequest($request, $endPoint);
    }
    public function sendCodeToVerifyGuestPhone( $request ) {
        $endPoint = 'merchants/guests/verify-phone';
        $request_body = $request->get_json_params();
        $phone = $request_body['phone'];

        if (!isset($phone)){
            return new WP_Error(
                'bad_request',
                __('Phone Number is required',"moo_OnlineOrders"),
                array( 'status' => 400 )
            );
        }
        //Generate a code
        $code = wp_rand(111111,999999);

        //Save it in Session
        $this->session->set($code,"otpCode");
        $this->session->set($phone,"guestPhone");
        $this->session->set(false,"guestPhoneVerified");

        $request->set_body(wp_json_encode(["code"=>$code,"phone"=>$phone]));

       //Send it to the phones
        return $this->sendRequest($request, $endPoint);
    }
    public function checkCodeToVerifyGuestPhone( $request ) {
        //Get code from request
        $request_body = $request->get_json_params();

        $phone = $request_body['phone'];
        $code = $request_body['code'];
        if (!isset($phone) || !isset($code)){
            return new WP_Error(
                'bad_request',
                __('Phone Number and Code are required',"moo_OnlineOrders"),
                array( 'status' => 400 )
            );
        }
        //Compare it with teh code in session
        $currentPhone = $this->session->get("guestPhone");
        if ($currentPhone === $phone){
            if ($this->session->get('otpCode') === intval($code)){
                $this->session->set(true,"guestPhoneVerified");
                return array( 'message' => 'Phone verified' );
            }
        }

        //return teh response
        return new WP_Error(
            'bad_request',
            __('Otp Code is not valid',"moo_OnlineOrders"),
            array( 'status' => 400 )
        );
    }
    public function geoCodeAnAddress( $request ) {
        $endPoint = 'merchants/guests/geocode-address';
        return $this->sendRequest($request, $endPoint);
    }
    public function createDeliveryEstimate( $request ) {
        $endPoint = 'merchants/delivery-estimate';
        return $this->sendRequest($request, $endPoint);
    }

    public function getAddressesV2( $request ) {
        $endPoint = 'merchants/customers/addresses';
        return $this->sendRequest($request, $endPoint);
    }
    public function addAddressV2( $request ) {
        $endPoint = 'merchants/customers/addresses';
        return $this->sendRequest($request, $endPoint);
    }
    public function deleteAddressV2( $request ) {
        if ( empty( $request["address_id"] ) ) {
            return new WP_Error( 'address_id_required', 'Address Id not found', array( 'status' => 400 ) );
        }
        $endPoint = 'merchants/customers/addresses/' . $request["address_id"];
        return $this->sendRequest($request, $endPoint,true);
    }

    private function sendRequest($request, $endPoint, $isDelete = false) {
        $authorization = $request->get_header("authorization");
        $request_body = $request->get_json_params();
        $res = $this->api->customerRequestsWrapper($endPoint, $request_body, $authorization, $isDelete);
        //var_dump($this->api->last_error);
        if ( $res === false ){
            if ( isset($this->api->last_error['httpCode']) && $this->api->last_error['httpCode'] >= 400  && $this->api->last_error['httpCode'] <= 499) {
                $errorMessage = "An error has occurred, Please check your submission";
                $responseContent = json_decode($this->api->last_error['responseContent'],true);

                if (!empty($responseContent["error"])){
                    $errorMessage = $responseContent["error"];
                }

                if (!empty($responseContent["message"])){
                    $errorMessage = $responseContent["message"];
                }

                return new WP_Error(
                    'bad_request',
                    $errorMessage,
                    array( 'status' => $this->api->last_error['httpCode'] )
                );
            } else {
                return new WP_Error(
                    'bad_request',
                    __('An error has occurred, try again',"moo_OnlineOrders"),
                    array( 'status' => 500 )
                );
            }

        }
        return $res;
    }

}