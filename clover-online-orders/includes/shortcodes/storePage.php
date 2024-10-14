<?php
require_once "sooShortCode.php";

class storePage extends sooShortCode
{

    public function htmCode($atts, $content) {
        $api   = $this->api;
        $mooOptions = $this->pluginSettings;
        $oppening_msg = "";

        if(isset($mooOptions['accept_orders']) && $mooOptions['accept_orders'] === "disabled"){
            if(isset($mooOptions["closing_msg"]) && $mooOptions["closing_msg"] !== '') {
                $oppening_msg .= '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'.$mooOptions["closing_msg"].'</div>';
            } else  {
                $oppening_msg .= '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">We are currently closed and will open again soon</div>';

            }
            if(isset($mooOptions["hide_menu_w_closed"]) && $mooOptions["hide_menu_w_closed"] === "on") {
                return '<div id="moo_OnlineStoreContainer" >'.$oppening_msg.'</div>';
            }
        } else {
            //Get blackout status
            $blackoutStatusResponse = $api->getBlackoutStatus();
            if(isset($blackoutStatusResponse["status"]) && $blackoutStatusResponse["status"] === "close"){
                if(!empty($blackoutStatusResponse["custom_message"])){
                    $oppening_msg .= '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'.$blackoutStatusResponse["custom_message"].'</div>';
                } else {
                    $oppening_msg .= '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">We are currently closed and will open again soon</div>';
                }

                if(isset($blackoutStatusResponse["hide_menu"]) && $blackoutStatusResponse["hide_menu"]){
                    return $oppening_msg;
                }
            } else {
                $arrayOs = $api->getOpeningStatus(4,30);
                $oppening_status = json_decode(wp_json_encode($arrayOs));

                if(isset($mooOptions['hours']) && $mooOptions['hours'] != 'all') {
                    if (isset($oppening_status->status) && $oppening_status->status == 'close'){
                        if(isset($mooOptions["closing_msg"]) && $mooOptions["closing_msg"] !== '') {
                            $oppening_msg .= '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'.$mooOptions["closing_msg"].'</div>';
                        } else  {
                            $oppening_msg .= '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">';
                            if($oppening_status->store_time !== ''){
                                $oppening_msg .= "<strong>".__("Today's Online Ordering Hours","moo_OnlineOrders")."</strong><br/> ".$oppening_status->store_time."<br/> ";
                            }
                            $oppening_msg .= __("Online Ordering Currently Closed","moo_OnlineOrders");
                            if(isset($mooOptions['accept_orders_w_closed']) && $mooOptions['accept_orders_w_closed'] == 'on' && $mooOptions['hide_menu'] != 'on'){
                                $oppening_msg .= "<br/><p style='color: #006b00'>";
                                $oppening_msg .= __("You may schedule your order in advance","moo_OnlineOrders");
                                $oppening_msg .= "</p>";
                            }
                            $oppening_msg .= '</div>';
                        }
                    }
                    if (isset($oppening_status->status) && $oppening_status->status == 'not_found'){
                        $oppening_msg .= '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">Please contact the Store to update their Online Ordering Hours</div>';
                    }
                }

                if(isset($mooOptions['hours']) && $mooOptions['hours'] != 'all' && $mooOptions['hide_menu'] == 'on' && $oppening_status->status == 'close') {
                    return '<div id="moo_OnlineStoreContainer" >'.$oppening_msg.'</div>';
                }
            }
        }



        $html_code  = '';

        $theme_id = (isset($mooOptions["default_style"])) ? $mooOptions["default_style"]:"onePage";
        $custom_css = (isset($mooOptions["custom_css"])) ? $mooOptions["custom_css"]:"";
        $custom_js  = (isset($mooOptions["custom_js"])) ? $mooOptions["custom_js"]:"";

        //Remove the old themes on force the default
        if($theme_id === 'style3' || $theme_id === 'style1'){
            $theme_id = "onePage";
        }

        $website_width = (isset($mooOptions[$theme_id."_width"]))?intval($mooOptions[$theme_id."_width"]):0;

        if($website_width === 0 || trim($website_width) == "") {
            $website_width = "100%";
        } else {
            $website_width=trim($website_width)."px;";
        }
        $custom_css .= '@media only screen and (min-width: 1024px) {#moo_OnlineStoreContainer,.moo-shopping-cart-container,.sooCopyright {width: '.$website_width.'}}';

        $custom_css .= $this->getCustomisedCssForThemes($theme_id);

        $html_code .=  $oppening_msg;

        $html_code .=  '<div id="moo_OnlineStoreContainer">';

        if( $theme_id == "style2" ) {
            $html_code .= $this->enqueueStoreCssAndJsForInterface3($atts, $custom_css);
        } else {

            $html_code .=  '<div id="sooOnlineStoreLoader" style="min-height: 300px;">';
            $html_code .=  '<div class="dot-flashing-2"></div>';
            $html_code .=  '</div>';

            $html_code .= $this->enqueueStoreCssAndJs($atts, $custom_css, $theme_id);
        }

        $html_code .=  '</div>';

        if(!empty($mooOptions["copyrights"])){
            $html_code .=  '</div><div class="row sooCopyright" style="display: none">'.$mooOptions["copyrights"].'</div>';
        }

        //Include custom js
        if($custom_js != null)
            $html_code .= '<script type="text/javascript">'.$custom_js.'</script>';

        return $html_code;
    }

    /**
     * @param $atts
     * @param $content
     * @return string
     */
    public function render($atts, $content)
    {
        $this->enqueueStylesAndScripts();
        return $this->htmCode($atts, $content);
    }
    private function enqueueStylesAndScripts()
    {

        $this->enqueueCssGrid();

        $this->enqueueFontAwesome();

        $this->enqueueSweetAlerts();

        $this->enqueueModifiersPopUp();

        $this->enqueuePublicCss();

    }
    public function getCustomisedCssForThemes($theme_id) {
        $mooOptions = (array)get_option( 'moo_settings' );
        $path = SOO_PLUGIN_PATH . "/public/themes/";
        $css = '';
        if(file_exists($path."/".$theme_id."/manifest.json")){
            $theme_settings = json_decode(file_get_contents($path."/".$theme_id."/manifest.json"));
            if(!isset($theme_settings->name) || $theme_settings->name === ''){
                return;
            }
            if(isset($theme_settings->settings)) {
                foreach ($theme_settings->settings as $setting) {
                    if(isset($setting->css)){
                        if(is_array($setting->css)) {
                            foreach ($setting->css as $oneCssConfig) {
                                if(isset($oneCssConfig->cssSelector) && isset($oneCssConfig->cssProperty) && isset($mooOptions[$theme_id."_".$setting->id])) {
                                    $css .= $oneCssConfig->cssSelector;
                                    $css .= '{';
                                    $css .= $oneCssConfig->cssProperty.':'.$mooOptions[$theme_id."_".$setting->id].';';
                                    $css .= '}';
                                }
                            }
                        } else {
                            if(isset($setting->css->cssSelector) && isset($setting->css->cssProperty) && isset($mooOptions[$theme_id."_".$setting->id])) {
                                $css .= $setting->css->cssSelector;
                                $css .= '{';
                                $css .= $setting->css->cssProperty.':'.$mooOptions[$theme_id."_".$setting->id].';';
                                $css .= '}';
                            }
                        }
                    }
                }
            }

        }
        return $css;
    }
    public function enqueueStoreCssAndJs($atts, $custom_css, $theme_id) {

        $path = SOO_PLUGIN_PATH . "/public/themes/";

        $categories = array();
        if(!empty($atts["categories"])){
            $categoriesIds = esc_attr($atts["categories"]);
            $categories = explode(",",strtoupper($categoriesIds));
        }

        if(!empty($atts["force_theme"])){
            $theme_id = esc_attr($atts["force_theme"]);
        }

        $files = scandir($path.$theme_id);
        $jsFileName = '';
        foreach ($files as $file) {
            $f = explode(".",$file);
            if(count($f) >= 2) {
                $extPos = count($f) - 1;
                $file_extension = $f[$extPos];
                if(strtoupper($file_extension) === "CSS") {
                    wp_register_style( 'moo-'.$file.'-style' ,SOO_PLUGIN_URL . '/public/themes/'.$theme_id.'/'.$file, array(), SOO_VERSION);
                    wp_enqueue_style(  'moo-'.$file.'-style' );
                    wp_add_inline_style( 'moo-'.$file.'-style', $custom_css );
                } else {
                    if(strtoupper($file_extension) === "JS")
                    {
                        $jsFileName = 'moo-'.$file.'-js';
                        wp_register_script( $jsFileName , SOO_PLUGIN_URL . '/public/themes/'.$theme_id.'/'.$file, array(), SOO_VERSION);
                        wp_enqueue_script( $jsFileName  );
                    }
                }
            }
        }
        if ($jsFileName !== '' ) {
            if (is_array($categories) && count($categories) > 0){
                wp_localize_script($jsFileName,"attr_categories",$categories);
            }

            wp_localize_script($jsFileName,"sooStoreOptions",array(
                "nbItemsInCart"=>$this->getNbItemsInCart(),
                "themeSettings"=>$this->getThemeSettings($theme_id),
                "modifiersSettings"=>$this->getModifiersSettings()
            ));
        }

        ob_start();
        //Get the content from the manifest and insert it
        if(file_exists($path.$theme_id."/manifest.json")){
            $theme_settings = json_decode(file_get_contents($path.$theme_id."/manifest.json"));
            if(!empty($theme_settings->content)){
                echo wp_kses_post($theme_settings->content);
            }
        }
        return ob_get_clean();
    }
    public function enqueueStoreCssAndJsForInterface3($atts, $custom_css) {
        $categories = array();

        if(!empty($atts["categories"])){
            $categoriesIds = esc_attr($atts["categories"]);
            $categories = explode(",",strtoupper($categoriesIds));
        }

        wp_register_style( 'mooStyle-style3',  SOO_PLUGIN_URL .'/public/css/dist/sooStoreInterface3.min.css',array(), SOO_VERSION );
        wp_enqueue_style ( 'mooStyle-style3', array( 'SooCssGrid','sooModifiersPopUp' ) );

        wp_register_script('mooScript-style3', SOO_PLUGIN_URL . '/public/js/dist/sooStoreInterface3.min.js',array(),SOO_VERSION);
        wp_enqueue_script( 'mooScript-style3', array( 'jquery','sooModifiersPopUp' ) );

        wp_add_inline_style( "mooStyle-style3", $custom_css );


        $mooOptions = (array)get_option( 'moo_settings' );

        $cart_page_id  = $mooOptions['cart_page'];
        $checkout_page_id = $mooOptions['checkout_page'];
        $store_page_id = $mooOptions['store_page'];

        $cart_page_url      =  get_page_link($cart_page_id);
        $checkout_page_url  =  get_page_link($checkout_page_id);
        $store_page_url     =  get_page_link($store_page_id);

        $params = array(
            'plugin_img' =>  SOO_PLUGIN_URL . '/public/img',
            'cartPage' =>  $cart_page_url,
            'checkoutPage' =>  $checkout_page_url,
            'storePage' =>  $store_page_url,
            'moo_RestUrl' =>  get_rest_url(),
            'custom_sa_title' =>  (isset($mooOptions["custom_sa_title"]) && trim($mooOptions["custom_sa_title"]) !== "")?trim($mooOptions["custom_sa_title"]):"",
            'custom_sa_content' =>  (isset($mooOptions["custom_sa_content"]) && trim($mooOptions["custom_sa_content"]) !== "")?trim($mooOptions["custom_sa_content"]):"",
            'custom_sa_onCheckoutPage' =>  (isset($mooOptions["custom_sa_onCheckoutPage"]))?trim($mooOptions["custom_sa_onCheckoutPage"]):"off"
        );
        wp_localize_script("mooScript-style3", "moo_params",$params);

        if(is_array($categories) && count($categories) > 0) {
            wp_localize_script("mooScript-style3", "attr_categories",$categories);
        }
        ob_start();
        ?>
        <div>
            <div class="moo-col-md-7" id="moo-onlineStore-categories"></div>
            <div class="moo-col-md-5" id="moo-onlineStore-cart"></div>
        </div>

        <?php
        return ob_get_clean();
    }

    private function getThemeSettings($theme_id) {
        $res = array();
        $settings = $this->pluginSettings;
        foreach ($settings as $key=>$val) {
            $k = (string)$key;
            if(strpos($k,$theme_id."_") === 0 && $val != "")
            {
                $res[$key]= $val;
            }
        }
        return $res;
    }
    private function getNbItemsInCart() {
        $session = MOO_SESSION::instance();
        $res = 0;
        if($session->exist("items"))
            foreach ($session->get("items") as $item) {
                $res += $item["quantity"];
            }
        return $res ;
    }

    private function getModifiersSettings() {
        $response = array();

        $res = array();
        $settings = $this->pluginSettings;

        if(isset($settings["mg_settings_displayInline"]) && $settings["mg_settings_displayInline"] == "enabled") {
            $res["inlineDisplay"] = true;
        } else {
            $res["inlineDisplay"] = false;
        }

        if(isset($settings["mg_settings_qty_for_all"]) && $settings["mg_settings_qty_for_all"] == "disabled") {
            $res["qtyForAll"] = false;
        } else {
            $res["qtyForAll"] = true;
        }

        if(isset($settings["mg_settings_qty_for_zeroPrice"]) && $settings["mg_settings_qty_for_zeroPrice"] == "disabled") {
            $res["qtyForZeroPrice"] = false;
        } else {
            $res["qtyForZeroPrice"] = true;
        }

        if(isset($settings["mg_settings_minimized"]) && $settings["mg_settings_minimized"] == "enabled") {
            $res["minimized"] = true;
        } else {
            $res["minimized"] = false;
        }
        if(isset($settings["mg_settings_primary_color"])) {
            $res["primaryColor"] = $settings["mg_settings_primary_color"];
        } else {
            $res["primaryColor"] = '#0097e6';
        }
        if(isset($settings["mg_settings_secondary_color"])) {
            $res["secondaryColor"] = $settings["mg_settings_secondary_color"];
        } else {
            $res["secondaryColor"] = '#FFFFFF';
        }
        //check if the store markes as closed from the settings
        if(isset($settings['accept_orders']) && $settings['accept_orders'] === "disabled"){
            $response["store_is_open"] = false;

            if(isset($settings["closing_msg"]) && $settings["closing_msg"] !== '') {
                $response["closing_msg"] = $settings["closing_msg"];
            } else  {
                $response["closing_msg"] = "We are currently closed and will open again soon";
            }
            if(isset($settings["hide_menu_w_closed"]) && $settings["hide_menu_w_closed"] === "on") {
                $response["hide_menu"] = true;
            } else {
                $response["hide_menu"] = false;
            }
        } else {
            $response["store_is_open"] = true;
        }

        $response["settings"]   =  $res;
        return $res;
    }

}