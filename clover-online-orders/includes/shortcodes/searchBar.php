<?php

require_once "sooShortCode.php";

class searchBar extends sooShortCode
{

    public function htmCode($atts, $content)
    {
        ob_start();
        ?>
        <div class="" id="moo-search-bar-container">
            <div class="moo-search-bar moo-row">
                <form onsubmit="mooClickonSearchButton(event)">
                    <input class="moo-col-md-10 moo-search-field" type="text" placeholder="Search" />
                    <button class="moo-col-md-2 osh-btn action" onclick="mooClickonSearchButton(event)"><?php _e("Search","moo_OnlineOrders"); ?></button>
                </form>

            </div>
            <div class="moo-search-result moo-row"></div>
        </div>
        <?php
        return ob_get_clean();
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

        wp_register_style( 'mooSearchBar-css',SOO_PLUGIN_URL . '/public/css/dist/sooSearchbar.min.css',array(), SOO_VERSION);
        wp_enqueue_style ( 'mooSearchBar-css' );

        wp_register_script('mooSearchBar-js', SOO_PLUGIN_URL .  '/public/js/dist/sooSearchbar.min.js',array(), SOO_VERSION);
        wp_enqueue_script( 'mooSearchBar-js' );

        $this->enqueuePublicCss();
    }
}