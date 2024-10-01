<?php

require_once "sooShortCode.php";

class myOrdersPage extends sooShortCode
{
    private $businessSettings;

    public function __construct(){

        parent::__construct();

        //Get Business Settings
        $this->businessSettings = $this->api->getBusinessSettings();
    }

    public function basedOnLocalStorage($atts, $content) {

        $this->enqueueStylesAndScripts();

        ob_start();

        if ( !empty($this->pluginSettings['fb_appid']) && !empty($this->pluginSettings['fb_appsecret']) ) {
            $columnCssClass = 'moo-col-md-6';
        } else {
            $columnCssClass = 'moo-col-md-6 moo-col-md-offset-3';
        }

        ?>

        <div id="sooMyOrdersContainer">
            <div class="moo-row sooMyOrders">
                <div class="errors-section"></div>
                <div id="sooAuthSection" data-soo-auth-page="myOrders">
                    <div class="sooAuthContainer">
                        <!--   login   -->
                        <div id="moo-login-section" class="moo-col-md-12 moo-section" style="min-height: 300px;">
                            <!--   Loading Section   -->
                            <div class="sooOverlayLoader" style="background-color: #fff;position: absolute;min-height: 286px;width: 100%;z-index: 10000000;">
                                <div class="dot-flashing-2"></div>
                            </div>

                            <?php if ( !empty($this->pluginSettings['fb_appid']) && !empty($this->pluginSettings['fb_appsecret']) ) { ?>
                                <div class="moo-col-md-6" tabindex="0">
                                    <div class="moo-row login-social-section">
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
                                                <button class="sooCheckoutSecondaryButtonInput" onclick="sooAuth.showOrHideASection('signing-section',true)" tabindex="0">
                                                    <?php _e("Create An Account", "moo_OnlineOrders"); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="login-separator moo-hidden-xs moo-hidden-sm">
                                        <div class="separator">
                                            <span><?php _e("or", "moo_OnlineOrders"); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php }  ?>
                            <div class="<?php echo $columnCssClass; ?>" tabindex="0" >
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
                                </form>
                                <?php if ( empty($this->pluginSettings['fb_appid']) || empty($this->pluginSettings['fb_appsecret']) ) { ?>
                                    <div class="moo-col-xs-12" style="padding: 0">
                                        <div class="login-or">
                                            <hr class="hr-or">
                                            <span class="span-or"><?php _e("or", "moo_OnlineOrders"); ?></span>
                                        </div>
                                        <button  class="sooCreateAnAccountButton" onclick="sooAuth.showOrHideASection('signing-section',true)">
                                            <?php _e("Create An Account", "moo_OnlineOrders"); ?>
                                        </button>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <!--   Register   -->
                        <div id="moo-signing-section" class="moo-col-md-12  moo-section" style="display: none">
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
                                        <label for="sooSignupInputEmail"><?php _e("Email", "moo_OnlineOrders"); ?></label>
                                        <input type="email" class="moo-form-control sooCheckoutTextInput" id="sooSignupInputEmail" autocomplete="email" onkeyup="sooAuth.onInputChange(this)">
                                        <span class="moo-error-section">
                                    <?php _e("Please enter your email", "moo_OnlineOrders"); ?>
                                </span>
                                    </div>
                                    <div class="moo-form-group">
                                        <label for="sooSignupInputPhone"><?php _e("Phone number", "moo_OnlineOrders"); ?></label>
                                        <input type="text" class="moo-form-control sooCheckoutTextInput" id="sooSignupInputPhone" autocomplete="phone" onkeyup="sooAuth.onInputChange(this)">
                                        <span class="moo-error-section">
                                    <?php _e("Please enter your phone", "moo_OnlineOrders"); ?>
                                </span>
                                    </div>
                                    <div class="moo-form-group">
                                        <label for="sooSignupInputPassword"><?php _e("Password", "moo_OnlineOrders"); ?></label>
                                        <input type="password" class="moo-form-control sooCheckoutTextInput" id="sooSignupInputPassword" autocomplete="new-password" onkeyup="sooAuth.onInputChange(this)">
                                        <span class="moo-error-section">
                                            <?php _e("Please choose a password", "moo_OnlineOrders"); ?>
                                        </span>
                                    </div>
                                    <div class="moo-form-group">
                                        <label for="sooSignupInputConfirmPassword"><?php _e("Password Confirmation", "moo_OnlineOrders"); ?></label>
                                        <input type="password" class="moo-form-control sooCheckoutTextInput" id="sooSignupInputConfirmPassword" autocomplete="new-password" onkeyup="sooAuth.onInputChange(this)">
                                        <span class="moo-error-section">
                                            <?php _e("Please confirm your password", "moo_OnlineOrders"); ?>
                                        </span>
                                    </div>
                                    <button class="sooCheckoutPrimaryButtonInput" type="submit" aria-label="<?php _e("Submit", "moo_OnlineOrders"); ?>">
                                        <?php _e("Submit", "moo_OnlineOrders"); ?>
                                    </button>
                                    <br />
                                    <p style="padding-top: 10px;">
                                        <?php
                                        /* translators: %s represent our tos link */
                                        printf(__('By creating an account and placing orders, you indicate your agreement to our <a class="sooTosLinkButton" href="%s" target="_blank">Terms Of Service</a>', 'moo_OnlineOrders'), "https://www.zaytech.com/zaytech-eula");
                                        ?>
                                    </p>
                                    <p style="padding-top: 10px;"> <?php _e("Have an account already?", "moo_OnlineOrders"); ?><a class="sooLoginLinkButton" href="#" onclick="sooAuth.showOrHideASection('login-section',true)">  <?php _e("Click here", "moo_OnlineOrders"); ?></a> </p>
                                </form>
                            </div>

                        </div>
                        <!--   Reset Password   -->
                        <div  id="moo-forgotpassword-section" class="moo-col-md-12  moo-section" style="display: none">
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
                <!--            customerPanel      -->
                <div id="moo-customerPanel" style="display:none;" class="moo-col-md-12 moo-section">
                    <div id="moo-customerPanelContent" class="moo-row">
                        <div class="moo_cp_wrap moo-row moo-row-no-padding-on-sm">
                            <nav class="moo_cp_nav moo-col-md-4" id="moo_cp_nav" tabindex="0">
                                <ul>
                                    <li id="sooTrendingNavElm" class="moo-col-xs-12 moo_nav_cpanel" onclick="sooMyOrders.clickOnFeatured(event)">
                                        <a href="#">
                                            <i class="fas fa-fire"></i>
                                            <span>Featured Items</span>
                                        </a>
                                    </li>
                                    <li style="display: none" id="sooMostPurchasedNavElm" class="moo-col-xs-12 moo_nav_cpanel" onclick="sooMyOrders.clickOnMostPurchased(event)">
                                        <a href="#">
                                            <i class="far fa-heart"></i>
                                            <span>Most Purchased</span>
                                        </a>
                                    </li>
                                    <li id="sooPreviousOrdersNavElm" class="moo-col-xs-12 moo_nav_cpanel" onclick="sooMyOrders.clickOnPreviousOrders(event)">
                                        <a href="#">
                                            <i class="fab fa-buromobelexperte"></i>
                                            <span>Previous Orders</span>
                                        </a>
                                    </li>
                                    <li  id="sooProfileNavElm" class="moo-col-xs-12 moo_nav_cpanel" onclick="sooMyOrders.clickOnProfile(event)">
                                        <a href="#">
                                            <i class="far fa-user"></i>
                                            <span>My profile</span>
                                        </a>
                                    </li>
                                    <li id="sooAddressesNavElm" class="moo-col-xs-12 moo_nav_cpanel" onclick="sooMyOrders.clickOnAddresses(event)">
                                        <a href="#">
                                            <i class="far fa-address-card"></i>
                                            <span>My address</span>
                                        </a>
                                    </li>
                                    <?php if (isset($this->businessSettings['loyaltySetting'])) { ?>
                                        <li id="sooPointsNavElm" class="moo-col-xs-12 moo_nav_cpanel" onclick="sooMyOrders.clickOnPointsHistory(event)">
                                            <a href="#">
                                                <i class="far fa-star"></i>
                                                <span> <?php echo $this->businessSettings['loyaltySetting']['label']; ?> History</span>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <li id="sooLogoutNavElm" class="moo-col-xs-12 moo_nav_cpanel" onclick="sooMyOrders.clickOnLogout(event)">
                                        <a href="#">
                                            <i class="far fa-window-close"></i>
                                            <span>Logout</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <section class="moo_cp_content moo-col-md-8" id="moo_cp_content"></section>
                            <section class="moo_cp_add_address moo-col-md-8" style="display: none">
                                <div class="soo-add-address-wrapper sooBorderedArea">
                                    <form id="soo-add-address-form" action="#" method="post">
                                        <div class="moo-row">
                                            <div class="moo-form-group moo-col-md-12 soo-margin-bottom-10">
                                                <label for="sooAddAddressName"><?php _e("Full Name", "moo_OnlineOrders"); ?></label>
                                                <input type="text" class="sooTextInput" id="sooAddAddressName" onkeyup="sooMyOrders.onInputChange(this)">
                                                <span class="moo-error-section">
                                                    <?php _e("Please enter the name of who will receive the order", "moo_OnlineOrders"); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="moo-row">
                                            <div class="moo-form-group moo-col-md-12 soo-margin-bottom-10">
                                                <label for="sooAddAddressLine1"><?php _e("Address", "moo_OnlineOrders"); ?></label>
                                                <input type="text" class="sooTextInput" id="sooAddAddressLine1" onkeyup="sooMyOrders.onInputChange(this)">
                                                <span class="moo-error-section">
                                                    <?php _e("Please enter your address", "moo_OnlineOrders"); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="moo-row">
                                            <div class="moo-form-group moo-col-md-12 soo-margin-bottom-10">
                                                <label for="sooAddAddressLine2"><?php _e("Suite / Apt #", "moo_OnlineOrders"); ?></label>
                                                <input type="text" class="sooTextInput" id="sooAddAddressLine2" onkeyup="sooMyOrders.onInputChange(this)">
                                                <span class="moo-error-section">
                                                    <?php _e("Please enter your Suite or Apt Number", "moo_OnlineOrders"); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="moo-row">
                                            <div class="moo-form-group moo-col-md-12 soo-margin-bottom-10">
                                                <label for="sooAddAddressCity"><?php _e("City", "moo_OnlineOrders"); ?></label>
                                                <input type="text" class="sooTextInput" id="sooAddAddressCity" onkeyup="sooMyOrders.onInputChange(this)">
                                                <span class="moo-error-section">
                                                    <?php _e("Please enter your City", "moo_OnlineOrders"); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="moo-row">
                                            <div class="moo-form-group moo-col-md-6">
                                                <label for="sooAddAddressState"><?php _e("State", "moo_OnlineOrders"); ?></label>
                                                <input type="text" class="sooTextInput" id="sooAddAddressState" onkeyup="sooMyOrders.onInputChange(this)">
                                                <span class="moo-error-section">
                                                    <?php _e("Please enter your State", "moo_OnlineOrders"); ?>
                                                </span>
                                            </div>
                                            <div class="moo-form-group moo-col-md-6">
                                                <label for="sooAddAddressZipCode"><?php _e("Zip Code", "moo_OnlineOrders"); ?></label>
                                                <input type="text" class="sooTextInput" id="sooAddAddressZipCode" onkeyup="sooMyOrders.onInputChange(this)">
                                                <span class="moo-error-section">
                                                    <?php _e("Please enter your Zip Code", "moo_OnlineOrders"); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="moo-row">
                                            <div class="moo-col-md-12 soo-text-right">
                                                <button class="sooPrimaryButtonInput" type="submit" aria-label="<?php _e("Add", "moo_OnlineOrders"); ?>">
                                                    <?php _e("Add", "moo_OnlineOrders"); ?>
                                                </button>
                                                <button  type="reset" class="sooSecondaryButtonInput" onclick="sooMyOrders.cancelAddingAddress(event)">
                                                    <?php _e("Cancel", "moo_OnlineOrders"); ?>
                                                </button>
                                            </div>

                                        </div>
                                    </form>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        if(!empty($this->pluginSettings["custom_js"])) {
            echo '<script type="text/javascript">'.$this->pluginSettings["custom_js"].'</script>';
        }
        /*
        if(! $session->isEmpty("moo_customer_token"))
            echo '<script type="text/javascript"> jQuery( document ).ready(function($) { moo_showCustomerPanel() });</script>';
        */
        return ob_get_clean();
    }

    /**
     * @param $atts
     * @param $content
     * @return string
     */
    public function render($atts, $content)
    {
        return $this->basedOnLocalStorage($atts, $content);
    }
    private function enqueueStylesAndScripts()
    {
        $this->enqueueFontAwesome();

        $this->enqueueCssGrid();

        $this->enqueuePublicCss();

        $this->enqueueModifiersPopUp();

        //$this->enqueueSweetAlerts11Css();

        $this->enqueueSweetAlerts11Js();

        wp_register_style('sooAuthStyles', SOO_PLUGIN_URL . '/public/css/dist/sooAuth-light.min.css', array(), SOO_VERSION);
        wp_enqueue_style('sooAuthStyles');

        wp_register_style('sooMyOrders', SOO_PLUGIN_URL . '/public/css/dist/sooMyOrders-light.min.css', array(), SOO_VERSION);
        wp_enqueue_style( 'sooMyOrders' );


        wp_register_script('sooMyOrdersScript', SOO_PLUGIN_URL . '/public/js/dist/sooMyOrders.min.js', array(), SOO_VERSION);
        wp_enqueue_script('sooMyOrdersScript', array('jquery'));


        wp_register_script('sooAuthScript', SOO_PLUGIN_URL . '/public/js/dist/sooAuth.min.js', array(), SOO_VERSION);
        wp_enqueue_script('sooAuthScript',array('sooMyOrdersScript','SooSweetalerts'));

        $this->localizeScripts();

        if (!empty($this->pluginSettings["custom_css"])){
            //Include custom css
            wp_add_inline_style( "sooMyOrders", $this->pluginSettings["custom_css"] );
        }
    }
    private function localizeScripts(){
        $authOptionsJsOptions  = [
            'restApiUrl' =>  get_rest_url(),
            "fbAppId"=>$this->pluginSettings['fb_appid']
        ];

        $sooOptions = array(
            'restApiUrl' =>  get_rest_url(),
            "loyaltySetting"=>(isset($this->businessSettings) && isset($this->businessSettings['loyaltySetting']) ) ? $this->businessSettings['loyaltySetting'] : null,
            "isSandbox"=>defined('SOO_ENV') && (SOO_ENV === "DEV")
        );

        wp_localize_script("sooMyOrdersScript", "sooMyOrdersOptions",$sooOptions);

        wp_localize_script("sooAuthScript", "sooAuthOptions", $authOptionsJsOptions);
    }
}