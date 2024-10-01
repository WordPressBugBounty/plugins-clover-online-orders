<?php


class sooShortCode
{
    /**
     * the plugin settings
     * @var array()
     */
    protected $pluginSettings;

    /**
     * The model of this plugin (For all interaction with the DATABASE ).
     * @access   private
     * @var      Moo_OnlineOrders_Model    Object of functions that call the Database pr the API.
     */
    protected $model;

    /**
     * The model of this plugin (For all interaction with the DATABASE ).
     * @access   private
     * @var Moo_OnlineOrders_SooApi
     */
    protected $api;

    /**
     * use or not alternateNames
     * @var bool
     */
    protected $useAlternateNames;

    /**
     * checkoutPage constructor.
     */
    public function __construct()
    {
        $this->pluginSettings = (array)get_option('moo_settings');
        $this->pluginSettings = apply_filters("moo_filter_plugin_settings",$this->pluginSettings);

        $this->model = new moo_OnlineOrders_Model();
        $this->api   = new Moo_OnlineOrders_SooApi();

        if (isset($this->pluginSettings["useAlternateNames"])) {
            $this->useAlternateNames = ($this->pluginSettings["useAlternateNames"] !== "disabled");
        } else {
            $this->useAlternateNames = true;
        }

    }

    protected function enqueueFontAwesome() {
        //Font Awesome Styles
        wp_register_style( 'sooFontAwesome', '//cdnjs.cloudflare.com/ajax/libs/font-awesome/5.5.0/css/all.min.css', array(), SOO_VERSION);
        wp_enqueue_style ( 'sooFontAwesome' );
    }
    protected function enqueueSweetAlerts() {
        //SweetAlerts Styles
        wp_register_style( 'moo-sweetalert-css-2',SOO_PLUGIN_URL . '/public/css/dist/sweetalert2.min.css', array(), SOO_VERSION);
        wp_enqueue_style(  'moo-sweetalert-css-2' );

        //SweetAlerts Scripts
        wp_register_script('moo-sweetalert-js-2', SOO_PLUGIN_URL .'/public/js/dist/sweetalert2.min.js',array(), SOO_VERSION);
        wp_enqueue_script('moo-sweetalert-js-2',array('jquery','moo-bluebird'));
    }

    protected function enqueueSweetAlerts11Css() {
        wp_register_style('SooSweetalerts',  '//cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css');
        wp_enqueue_style('SooSweetalerts');
    }

    protected function enqueueSweetAlerts11Js() {
        //use the new version of Sweetalerts
        wp_register_script('SooSweetalerts', SOO_PLUGIN_URL . '/public/js/dist/sweetalert2-v11-7-10.all.min.js',array(),SOO_VERSION);
        wp_enqueue_script('SooSweetalerts');
    }
    protected function enqueueRecaptchaJs($reCAPTCHA_site_key) {
        wp_register_script('SooGoogleRecaptcha',  '//www.google.com/recaptcha/api.js?render='.$reCAPTCHA_site_key,array(),false);
        wp_enqueue_script('SooGoogleRecaptcha');
    }

    protected function enqueueModifiersPopUp() {
        //Modifiers Styles
        wp_register_style( 'sooModifiersPopUp',SOO_PLUGIN_URL . '/public/css/dist/sooModifiersSelector.min.css', array(), SOO_VERSION);
        wp_enqueue_style( 'sooModifiersPopUp' ,array('moo-grid-css'));

        //Modifiers Scripts
        wp_register_script('sooModifiersPopUp', SOO_PLUGIN_URL .  '/public/js/dist/sooModifiersSelector.min.js', array(), SOO_VERSION);
        wp_enqueue_script('sooModifiersPopUp',array('jquery'));
    }
    protected function enqueueCssGrid() {
        //Soo Css Grid
        wp_register_style( 'SooCssGrid',SOO_PLUGIN_URL .  '/public/css/dist/grid12.min.css',array(), SOO_VERSION);
        wp_enqueue_style( 'SooCssGrid' );
    }
    protected function enqueuePublicCss() {
        //Public Soo Css
        wp_enqueue_style( 'sooPublic', SOO_PLUGIN_URL . '/public/css/dist/moo-OnlineOrders-public.min.css', array(), SOO_VERSION );
        wp_enqueue_style( 'sooPublic' );
    }
    protected function enqueueCartJs() {
        //Cart JS
        wp_register_script('moo-script-cart-v3', SOO_PLUGIN_URL . '/public/js/dist/sooCartPage.min.js',array(), SOO_VERSION);
        wp_enqueue_script('moo-script-cart-v3', array( 'jquery' ));
    }
    protected function enqueueGiftCardsJs() {
        //GiftCards JS
        wp_register_script('sooGiftCards', SOO_PLUGIN_URL . '/public/js/sooGiftCards.js',array(), SOO_VERSION);
        wp_enqueue_script('sooGiftCards', array( 'jquery' ));
    }
    protected function enqueueCloverSDK()
    {
        $cloverSkd = (defined('SOO_ENV') && (SOO_ENV === "DEV"))? 'https://checkout.sandbox.dev.clover.com/sdk.js' : 'https://checkout.clover.com/sdk.js';

        //Clover iframe SDK
        wp_register_script('sooCloverSdk', $cloverSkd, array('jquery'));
        wp_enqueue_script('sooCloverSdk');
    }
}