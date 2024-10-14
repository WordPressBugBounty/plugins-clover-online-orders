<?php

require_once "sooShortCode.php";

class addToCartButton extends sooShortCode
{

    public function htmCode($atts, $content)
    {
        if(isset($atts['css-class']) && $atts['css-class'] !== "") {
            $css = sprintf('class="%s"', esc_attr($atts['css-class']));
        } else {
            $css = sprintf('style="%s"', 'background-color: #4CAF50;border: none;color: white;padding: 10px 24px;text-align: center;text-decoration: none;display: inline-block;font-size: 16px;');
        }
        if(isset($atts['id']) && $atts['id'] != "") {
            $item_uuid = esc_attr($atts['id']);
            $item = $this->model->getItem($item_uuid);
            if($item) {
                if($this->model->itemHasModifiers($item_uuid)->total != '0') {
                    $action = 'moo_btn_addToCartFIWM';
                } else {
                    $action = 'moo_btn_addToCart';
                }
                if(isset($atts['hide-qty']) && $atts['hide-qty'] === "yes") {
                    $onClick = $action;
                    $action = '1';
                } else {
                    $onClick = 'moo_openQty_Window';
                }

                $html = sprintf("<a %s href='#'  onclick=\"%s(event,'%s',%s)\" >%s</a>", $css, $onClick, $item->uuid, $action, __("Add To Cart", "moo_OnlineOrders"));
                return $html;
            }
        }
        return __("Item Not Found", "moo_OnlineOrders");
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

        $this->enqueuePublicCss();

        $this->enqueueModifiersPopUp();
    }
}