<?php

require_once 'sooShortCode.php';

class cartPage extends sooShortCode
{

    public function cart($atts, $content)
    {

        $this->enqueueStyles();
        $this->enqueueScripts();
        ob_start();
        $session = MOO_SESSION::instance();

        /* empty the cart
        $session->delete("items");
        $session->delete("itemsQte");
        $session->delete("coupon");
        */

        $checkout_page_id  = $this->pluginSettings['checkout_page'];
        $store_page_id     = $this->pluginSettings['store_page'];


        $store_page_url    =  get_page_link($store_page_id);
        $checkout_page_url =  get_page_link($checkout_page_id);

        //check teh store availibilty
        if(isset($MooOptions['accept_orders']) && $MooOptions['accept_orders'] === "disabled"){
            if(isset($MooOptions["closing_msg"]) && $MooOptions["closing_msg"] !== '') {
                $oppening_msg = '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">'.$MooOptions["closing_msg"].'</div>';
            } else  {
                $oppening_msg = '<div class="moo-alert moo-alert-danger" role="alert" id="moo_checkout_msg">';
                $oppening_msg .= __("We are currently closed and will open again soon", "moo_OnlineOrders");
                $oppening_msg .= '</div>';

            }
            return '<div id="moo_OnlineStoreContainer" >'.$oppening_msg.'</div>';

        }

        $totals =   $session->getTotals();

        if($totals === false){
            return '<div class="moo_emptycart"><p>'.__("Your cart is empty", "moo_OnlineOrders").'</p><span><a class="moo-btn moo-btn-default" href="'.$store_page_url.'" style="margin-top: 30px;">'.__("Back to Main Menu", "moo_OnlineOrders").'</a></span></div>';
        };

        $track_stock = $this->api->getTrackingStockStatus();

        if($track_stock) {
            $itemStocks = $this->api->getItemStocks();
        }

        ?>
        <div class="moo-shopping-cart-container">
            <div class="moo-shopping-cart">
                <div class="moo-column-labels">
                    <label class="moo-product-image">Image</label>
                    <label class="moo-product-details">
                        <?php _e("Product","moo_OnlineOrders"); ?>
                    </label>
                    <label class="moo-product-price"><?php _e("Price","moo_OnlineOrders"); ?></label>
                    <label class="moo-product-quantity"><?php _e("Qty","moo_OnlineOrders"); ?></label>
                    <label class="moo-product-removal"><?php _e("Remove","moo_OnlineOrders"); ?></label>
                    <label class="moo-product-line-price"><?php _e("Total","moo_OnlineOrders"); ?></label>
                </div>
                <?php foreach ($session->get("items") as $key=>$line) {

                    if(!$line)
                        continue;

                    $modifiers_price = 0;
                    $item_image = $this->model->getDefaultItemImage($line['item']->uuid);
                    $no_image_url =  SOO_PLUGIN_URL . "/public/img/no-image.jpg";
                    $default_image = ($item_image === null)?$no_image_url:$item_image->url;

                    if(isset($line['item']->soo_name) && !empty($line['item']->soo_name)){
                        $item_name=stripslashes((string)$line['item']->soo_name);
                    } else {
                        if($this->useAlternateNames && isset($line['item']->alternate_name) && $line['item']->alternate_name!==""){
                            $item_name=stripslashes((string)$line['item']->alternate_name);
                        } else {
                            $item_name=stripslashes((string)$line['item']->name);
                        }
                    }

                    if($track_stock)
                        $itemStock = $this->getItemStock($itemStocks,$line['item']->uuid);
                    else
                        $itemStock = false;
                    ?>
                    <div class="moo-product">
                        <div class="moo-product-image">
                            <img alt="Item image" src="<?php echo $default_image ?>" tabindex="0">
                        </div>
                        <div class="moo-product-details">
                            <div class="moo-product-title" tabindex="0">
                                <?php echo $item_name; ?>
                            </div>
                            <p class="moo-product-description">
                                <?php
                                foreach($line['modifiers'] as $modifier) {

                                    if(isset($modifier['qty']) && intval($modifier['qty'])>0) {
                                        echo '<span tabindex="0">'.$modifier['qty'].'x ';
                                        $modifiers_price += $modifier['price']*$modifier['qty'];
                                    } else {
                                        echo '<span tabindex="0"> 1x ';
                                        $modifiers_price += $modifier['price'];
                                    }

                                    $modifier_name = "";
                                    if($this->useAlternateNames && isset($modifier["alternate_name"]) && $modifier["alternate_name"]!==""){
                                        $modifier_name =stripslashes((string)$modifier["alternate_name"]);
                                    } else {
                                        $modifier_name =stripslashes((string)$modifier["name"]);
                                    }

                                    if($modifier['price']>0)
                                        echo $modifier_name .'- $'.number_format(($modifier['price']/100),2)."</span><br/>";
                                    else
                                        echo $modifier_name ."</span><br/>";

                                }
                                if($line['special_ins'] != "")
                                    echo '<span tabindex="0">SI: '.$line['special_ins']."</span>";
                                ?>
                            </p>
                        </div>
                        <div class="moo-product-price" tabindex="0"><?php $line_price = $line['item']->price+$modifiers_price; echo number_format(($line_price/100),2)?></div>
                        <div class="moo-product-quantity">
                            <input aria-label="item qty" type="number" value="<?php echo $line['quantity']?>" min="1" max="<?php if($itemStock) echo $itemStock["stockCount"]; else echo '';?>" onchange="moo_updateQuantity(this,'<?php echo $key?>')">
                        </div>
                        <div class="moo-product-removal">
                            <a role="button" class="moo-remove-product" onclick="moo_removeItem(this,'<?php echo $key?>')" tabindex="0">
                                <?php _e("Remove","moo_OnlineOrders"); ?>
                            </a>
                        </div>
                        <div tabindex="0" class="moo-product-line-price"><?php echo '$'.number_format(($line_price*$line['quantity']/100),2)?></div>
                    </div>
                <?php } ?>

                <div class="moo-totals">
                    <a role="button" href="#" style="color: #337ab7;" onclick="moo_emptyCart(event)"><?php _e("Empty the cart","moo_OnlineOrders"); ?></a>
                    <div class="moo-totals-item">
                        <label tabindex="0"><?php _e("Subtotal","moo_OnlineOrders"); ?></label>
                        <div class="moo-totals-value" id="moo-cart-subtotal" tabindex="0">$<?php echo  number_format($totals['sub_total']/100,2); ?></div>
                    </div>
                    <div class="moo-totals-item">
                        <label tabindex="0"><?php _e("Tax","moo_OnlineOrders"); ?></label>
                        <div class="moo-totals-value" id="moo-cart-tax" tabindex="0">
                            <?php echo  number_format($totals['total_of_taxes']/100,2); ?>
                        </div>
                    </div>
                    <div class="moo-totals-item moo-totals-item-total">
                        <label tabindex="0"><?php _e("Total","moo_OnlineOrders"); ?></label>
                        <div class="moo-totals-value" id="moo-cart-total" tabindex="0">$<?php echo  number_format($totals['total']/100,2); ?></div>
                    </div>
                </div>
                <a href="<?php echo $checkout_page_url?>" ><button class="moo-checkout"><?php _e("Checkout","moo_OnlineOrders"); ?></button></a>
                <a href="<?php echo $store_page_url?>" ><button class="moo-continue-shopping"><?php _e("Continue shopping","moo_OnlineOrders"); ?></button></a>


            </div>
        </div>
        <?php
        if(isset($this->pluginSettings["custom_js"])) {
            echo '<script type="text/javascript">'.$this->pluginSettings["custom_js"].'</script>';
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
        return $this->cart($atts, $content);
    }
    private function enqueueStyles()
    {
        $this->enqueueFontAwesome();

        $this->enqueuePublicCss();

        wp_register_style( 'cartStyle-css',SOO_PLUGIN_URL . '/public/css/dist/sooCartStyle.min.css', array(), SOO_VERSION );
        wp_enqueue_style( 'cartStyle-css');

        if (!empty($this->pluginSettings["custom_css"])){
            //Include custom css
            wp_add_inline_style( "cartStyle-css", $this->pluginSettings["custom_css"] );
        }

    }
    private function enqueueScripts(){
        $this->enqueueSweetAlerts();
        $this->enqueueCartJs();
    }
    private function getItemStock($items,$item_uuid) {
        foreach ($items as $i) {
            if(isset($i["item"]["id"]) && $i["item"]["id"] == $item_uuid) {
                return $i;
            }
        }
        return false;
    }
}