<?php

require_once 'sooShortCode.php';

class giftCardBalance extends sooShortCode
{

    public function giftCardBalanceHtml($atts, $content)
    {

        $this->enqueueStyles();
        $this->enqueueScripts();
        $jsOptions = array(
            'restApiUrl' =>  get_rest_url(),
            "pakmsKey"=>$this->api->getPakmsKey(),
            "locale"=>str_replace("_", "-", get_locale()),
        );
        wp_localize_script("sooGiftCards", "sooGiftCardsOptions", $jsOptions);
        ob_start();
        echo $this->giftCardSection();
        return ob_get_clean();
    }

    /**
     * @param $atts
     * @param $content
     * @return string
     */
    public function render($atts, $content)
    {
        return $this->giftCardBalanceHtml($atts, $content);
    }
    private function enqueueStyles()
    {
        $this->enqueueFontAwesome();

        $this->enqueuePublicCss();

       // wp_register_style( 'soo-gift-cards',SOO_PLUGIN_URL . '/public/css/dist/sooCartStyle.min.css', array(), SOO_VERSION );
       // wp_enqueue_style( 'soo-gift-cards');
        wp_register_style('sooGiftCardsStyles', SOO_PLUGIN_URL . '/public/css/dist/sooGiftCards-light.min.css', array(), SOO_VERSION);
        wp_enqueue_style('sooGiftCardsStyles',array());


    }
    private function enqueueScripts(){
        $this->enqueueCloverSDK();
        $this->enqueueSweetAlerts();
        $this->enqueueGiftCardsJs();
    }
    private function giftCardSection()
    {
        $htmBegin   = '<div id="moo-cloverGiftCardPanel"><input type="hidden" name="cloverToken" id="moo-CloverToken">';
        $htmEnd     = ' <div class="clover-errors"></div></div>';
        $giftCard   = '<div class="moo-row giftCardsSection"><div class="moo-col-md-12"><div class="moo-form-group"><div id="moo_CloverGiftCard"></div><div class="giftCard-error"><div class="clover-error"></div></div><div class="bottom-section"><div class="result"></div><div class="sendButton"><div class="primaryButtonInput" onclick="sooGiftCards.clickOnCheckBalance()" aria-label="Check Balance">Check Balance</div></div></div></div></div></div>';
        $html = $htmBegin . $giftCard . $htmEnd;
        $html = apply_filters('moo_filter_giftCardSection', $html);
        return $html;
    }
}