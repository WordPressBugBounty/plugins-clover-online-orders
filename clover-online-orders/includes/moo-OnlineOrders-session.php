<?php

/**
 * Register all actions and filters for the plugin
 *
 * @link       http://zaytech.com
 * @since      1.0.0
 *
 * @package    Moo_OnlineOrders
 * @subpackage Moo_OnlineOrders/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Moo_OnlineOrders
 * @subpackage Moo_OnlineOrders/includes
 * @author     Mohammed EL BANYAOUI
 */
class MOO_SESSION {

    /**
     * Main Session Instance.
     *
     * Ensures only one instance of Session is loaded or can be loaded.
     *
     * @since 1.3.1
     * @static
     * @return MOO_SESSION - Main instance.
     */
    protected static $_instance = null;
    /**
     * @var string
     */
    private $type;

    /**
     * @var integer
     */
    private $blogId;

    /**
     * @var string
     */
    protected $sessionId;
    /**
     * @var array
     */
    protected $pluginSettings;

    /**
     * MOO_SESSION constructor.
     */
    public function __construct()
    {
        $this->type     = 'session';
        $this->blogId   = 'moo_'.get_current_blog_id();
    }

    /**
     * @return MOO_SESSION|null
     */
    public static function instance() {

        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @param $key
     * @param null $key2
     * @return array|null
     */
    function get($key, $key2 = null ){
        $data = null;
        if($this->type === 'session') {
            $this->sessionStart();
            if (isset($_SESSION)){
                if(isset($_SESSION[$this->blogId][$key])) {
                    if(isset($key2)) {
                        if(isset($_SESSION[$this->blogId][$key][$key2])) {
                            $data = $_SESSION[$this->blogId][$key][$key2];
                        }
                    } else {
                        if(isset($key) && isset($_SESSION[$this->blogId][$key])) {
                            $data = $_SESSION[$this->blogId][$key];
                        }
                    }
                }
            }
            $this->endSession();
        }
        return $data;
    }

    /**
     * @param $value
     * @param $key
     * @param  $key2
     * @return array
     */
    function set($value, $key, $key2 = null) {
        $data = array();
        if ($this->type === 'session'){
            $this->sessionStart();
            if(isset($_SESSION)) {
                if(isset($key2)) {
                    if(isset($_SESSION[$this->blogId])) {
                        if(isset($_SESSION[$this->blogId][$key]) && is_array($_SESSION[$this->blogId][$key])) {
                            $_SESSION[$this->blogId][$key][$key2] = $value;
                        } else {
                            $_SESSION[$this->blogId][$key] = array(
                                $key2=>$value
                            );
                        }
                    } else {
                        $_SESSION[$this->blogId] = array(
                            $key=>array(
                                $key2=>$value
                            )
                        );
                    }
                    $data = $_SESSION[$this->blogId][$key][$key2];
                } else {
                    if(isset($_SESSION[$this->blogId])) {
                        $_SESSION[$this->blogId][$key] = $value;
                    } else {
                        $_SESSION[$this->blogId] = array(
                            $key=>$value
                        );
                    }
                    $data = $_SESSION[$this->blogId][$key];
                }
            }
            $this->endSession();
        }

        return $data;
    }
    /**
     * @param $key
     * @param null $key2
     * @return true|false
     */
    function delete($key, $key2 = null) {
        if($this->type === 'session') {
            if (isset($_SESSION)){
                $this->sessionStart();
                if(isset($key2)) {
                    unset($_SESSION[$this->blogId][$key][$key2]);
                } else {
                    unset($_SESSION[$this->blogId][$key]);
                }
                $this->endSession();
                return true;
            }
        }
        return false;
    }

    /**
     * @param $key
     * @param null $key2
     * @return bool
     */
    function exist( $key, $key2 = null ){
        if($this->type === 'session') {
            $this->sessionStart();
            if(isset($_SESSION[$this->blogId][$key])&& isset($key2)) {
                $exist = isset($_SESSION[$this->blogId][$key][$key2]);
            } else {
                $exist = isset($_SESSION[$this->blogId][$key]);
            }
            $this->endSession();
            return $exist;
        }
        return false;
    }
    /**
     * @param $key
     * @param null $key2
     * @return bool
     */
    function isEmpty( $key, $key2 = null ){
        if($this->type === 'session') {
            $this->sessionStart();
            if(isset($key2)) {
                $empty = empty($_SESSION[$this->blogId][$key][$key2]);
            } else {
                $empty = empty($_SESSION[$this->blogId][$key]);
            }
            $this->endSession();
            return $empty;
        }
        return false;
    }

    public function myStartSession() {
        if(!session_id()) {
            @session_start();
            session_write_close();
        }
    }
    public function sessionStart() {
        session_start();
    }
    public function endSession() {
        session_write_close();
    }
    function printDump(){
        if($this->type === 'session') {
            print_r($_SESSION[$this->blogId]);
        }
    }

    /**
     * Get Cart On Simple Format without Qty
     * @return array
     */
    public function getCart() {
        $cart = [
            "items"=>array(),
            "without_modifiers"=>true
        ];
        foreach($this->get("items") as $cartLine) {
            if(isset($cartLine['quantity']) && intval($cartLine['quantity'])>=1) {
                for($i=0;$i<$cartLine['quantity'];$i++) {
                    $lineItem = array(
                        "name"=>$cartLine['item']->name,
                        "soo_name"=>$cartLine['item']->soo_name,
                        "price"=>intval($cartLine['item']->price),
                        "price_type"=>$cartLine['item']->price_type,
                        "note"=>$cartLine['special_ins'],
                        "item"=>array( "id"=>$cartLine['item']->uuid ),
                        "modifications"=>array(),
                        "taxRates"=>array(),
                    );
                    // Create line item
                    if(isset($cartLine['modifiers'])  && is_array($cartLine['modifiers']) && count($cartLine['modifiers']) > 0) {
                        $cart["without_modifiers"] = false;
                        foreach ($cartLine['modifiers'] as $modifier) {
                            if(isset($modifier["qty"]) && intval($modifier["qty"])>1) {
                                for($k=0;$k<$modifier["qty"];$k++) {
                                    $lineItem["modifications"][] = array(
                                        "amount" => intval($modifier['price']),
                                        "name" => $modifier['name'],
                                        "modifier" => array(
                                            "id" => $modifier['uuid']
                                        )
                                    );
                                }
                            } else {
                                $lineItem["modifications"][] = array(
                                    "amount" => intval($modifier['price']),
                                    "name" => $modifier['name'],
                                    "modifier" => array(
                                        "id" => $modifier['uuid']
                                    )
                                );
                            }
                        }

                    }
                    if(isset($cartLine['tax_rate'])  && is_array($cartLine['tax_rate']) && count($cartLine['tax_rate']) > 0) {
                        foreach ($cartLine['tax_rate'] as $taxRate) {
                            $lineItem["taxRates"][] = array(
                                "rate" => intval($taxRate->rate),
                                "id" => $taxRate->uuid,
                                "name" => $taxRate->name,
                            );
                        }

                    }
                    array_push($cart["items"], $lineItem);
                }
            }
        }
        return $cart;
    }

    /**
     * Get the totals
     * @param int $deliveryCharges
     * @param int $servicesFees
     * @param string $servicesFeesType
     * @return array|bool
     */
    public function getTotals($deliveryCharges = 0,$servicesFees = 0, $servicesFeesType = 'amount'){
        if(! $this->isEmpty("items")){
            $nb_items  = 0;
            $sub_total = 0;
            $couponValue = 0;
            $servicesFeesValue = 0;
            $couponName  = null;
            $total_of_taxes = 0;
            $total_of_taxes_without_discounts = 0;
            $taxe_rates_groupping = array();
            $allTaxesRates = array();
            $notTaxableCharges = $deliveryCharges;

            //get the subtotal,taxeRates and calculate number of items
            foreach ($this->get("items") as $item) {
                if(!$item)
                    continue;
                $nb_items += 1 * $item['quantity'];
                //Grouping taxe rates
                foreach ($item['tax_rate'] as $tr) {
                    if(isset($taxe_rates_groupping[$tr->uuid])) {
                        array_push($taxe_rates_groupping[$tr->uuid],$item);
                    } else {
                        $taxe_rates_groupping[$tr->uuid] = array();
                        array_push($taxe_rates_groupping[$tr->uuid],$item);
                        $allTaxesRates[$tr->uuid]=$tr->rate;
                    }
                }
                $price = $item['item']->price *  $item['quantity'];
                $sub_total += $price;
                if(isset($item['modifiers']) && count($item['modifiers'])>0) {
                    foreach ($item['modifiers'] as $m) {
                        if(isset($m['qty'])) {
                            $m_price = $item['quantity'] * $m['price'] * intval($m['qty']);
                        } else {
                            $m_price = $item['quantity'] * $m['price'];
                        }

                        $sub_total += $m_price;
                    }
                }
            }

            //Calculate Service fees
            if($servicesFees > 0) {
                if($servicesFeesType === "amount"){
                    $servicesFeesValue = $servicesFees;
                } else {
                    $servicesFeesValue = $servicesFees * $sub_total / 100;
                }
                $servicesFeesValue = intval(round($servicesFeesValue,0));
                $notTaxableCharges += $servicesFeesValue;
            }

            //Coupons
            if( !$this->isEmpty("coupon")) {
                $coupon = $this->get("coupon");
                //Apply coupon
                if(isset($coupon)) {
                    $couponMinAmount = (isset($coupon["minAmount"])) ? floatval($coupon["minAmount"]) * 100 : 0;
                    $couponMaxValue = (isset($coupon["maxValue"]))  ? floatval($coupon["maxValue"]) * 100 : 0;
                    $couponName  = $coupon['name'];
                    if(strtoupper($coupon['type']) == "PERCENTAGE" ) {
                        $couponValue =  $coupon['value']*$sub_total/100;
                    } else {
                        $couponValue = $coupon['value']*100;
                    }
                    $couponValue = round($couponValue,0);
                    //Check min amount
                    if($couponMinAmount > $sub_total) {
                        $coupon = null;
                        $couponValue = 0;
                    }
                    //Check the coupon Max value
                    if($couponMaxValue > 0 && $couponValue > $couponMaxValue) {
                        $couponValue = round($couponMaxValue,0);
                        $coupon['type'] = 'AMOUNT';
                        $coupon['use_maxValue'] = true;
                        $coupon['value'] = $couponValue;
                        $this->set($coupon,"coupon");
                    }

                } else {
                    $couponValue = 0;
                }
            } else {
                $coupon      = null;
                $couponValue = 0;
                $couponName  = null;
            }
            if (($sub_total+$notTaxableCharges)>0){
                $orderDiscountMultiplier = ($sub_total+$notTaxableCharges-$couponValue)/($sub_total+$notTaxableCharges);
            } else {
                $orderDiscountMultiplier = 0 ;
            }

            //calculate taxes
            foreach ($taxe_rates_groupping as $tax_rate_uuid=>$items) {
                $tax_rate = $allTaxesRates[$tax_rate_uuid];
                if($tax_rate == 0)
                    continue;

                $taxItemsSubtotal = 0;

                foreach ($items as $item) {
                    $lineSubtotal = $item['item']->price * $item['quantity'];
                    if(isset($item['modifiers']) && is_array($item['modifiers']) && count($item['modifiers'])>0){
                        foreach ($item['modifiers'] as $m) {
                            if(isset($m['qty']))
                                $m_price = $item['quantity'] * $m['price'] * intval($m['qty']);
                            else
                                $m_price = $item['quantity'] * $m['price'];

                            $lineSubtotal += $m_price;
                        }
                    }
                    $taxItemsSubtotal += $lineSubtotal;
                }

                $discountedTaxItemsSubtotal = $taxItemsSubtotal * $orderDiscountMultiplier;
                $discountedTaxItemsSubtotal = round($discountedTaxItemsSubtotal);

                $taxesWithoutDiscounts = round($taxItemsSubtotal) * $tax_rate/10000000;
                $taxes = $discountedTaxItemsSubtotal * $tax_rate/10000000;

                $taxes = round($taxes,0,PHP_ROUND_HALF_UP);
                $taxesWithoutDiscounts = round($taxesWithoutDiscounts,0,PHP_ROUND_HALF_UP);

                $total_of_taxes += $taxes;
                $total_of_taxes_without_discounts += $taxesWithoutDiscounts;
            }

            $total_of_taxes = ($total_of_taxes<0)?0:$total_of_taxes;
            $total_of_taxes_without_discounts = ($total_of_taxes_without_discounts<0)?0:$total_of_taxes_without_discounts;

            $FinalSubTotal = round($sub_total,0,PHP_ROUND_HALF_UP);
            $FinalTaxTotal = round($total_of_taxes,0,PHP_ROUND_HALF_UP);
            $FinalTaxTotalWithoutDiscounts = round($total_of_taxes_without_discounts,0,PHP_ROUND_HALF_UP);
            $DiscountedSubTotal = $FinalSubTotal;

            if($couponValue>0){
                $FinalTotal = $FinalSubTotal + $FinalTaxTotal - $couponValue;
            } else {
                $FinalTotal = $FinalSubTotal + $FinalTaxTotalWithoutDiscounts;
            }

            if($FinalTotal<0)
                $FinalTotal = 0;

            $FinalTotalWithoutDiscounts = $FinalSubTotal + $FinalTaxTotalWithoutDiscounts;

            return array(
                'sub_total'      	                    => intval($FinalSubTotal),
                'total_of_taxes'	                    => intval($FinalTaxTotal),
                'total_of_taxes_without_discounts'	    => intval($FinalTaxTotalWithoutDiscounts),
                'discounted_subtotal'	                => intval($DiscountedSubTotal),
                'total'	                                => intval($FinalTotal),
                'total_without_discounts'	            => intval($FinalTotalWithoutDiscounts),
                'coupon_value'	                        => intval($couponValue),
                'coupon_name'	                        => (isset($couponName) && !empty($couponName))?$couponName:null,
                'nb_items'	                            => $nb_items,
                'service_fee'	                        => $servicesFeesValue,
                'delivery_charges'	                    => intval($deliveryCharges),
            );

        } else {
            return false;
        }
    }

    public function getTotalsV2($notTaxableCharges, $discountedAmount){
        if(! $this->isEmpty("items")){
            $nb_items  = 0;
            $sub_total = 0;
            $total_of_taxes = 0;
            $total_of_taxes_without_discounts = 0;
            $taxe_rates_groupping = array();
            $allTaxesRates = array();

            //get the subtotal,taxeRates and calculate number of items
            foreach ($this->get("items") as $item) {
                if(!$item)
                    continue;
                $nb_items += 1 * $item['quantity'];
                //Grouping tax rates
                foreach ($item['tax_rate'] as $tr) {
                    if(isset($taxe_rates_groupping[$tr->uuid])) {
                        $taxe_rates_groupping[$tr->uuid][] = $item;
                    } else {
                        $taxe_rates_groupping[$tr->uuid] = array();
                        $taxe_rates_groupping[$tr->uuid][] = $item;
                        $allTaxesRates[$tr->uuid]=$tr->rate;
                    }
                }
                $price = $item['item']->price *  $item['quantity'];
                $sub_total += $price;
                if(isset($item['modifiers']) && count($item['modifiers'])>0) {
                    foreach ($item['modifiers'] as $m) {
                        if(isset($m['qty'])) {
                            $m_price = $item['quantity'] * $m['price'] * intval($m['qty']);
                        } else {
                            $m_price = $item['quantity'] * $m['price'];
                        }
                        $sub_total += $m_price;
                    }
                }
            }

            if (($sub_total+$notTaxableCharges)>0){
                $orderDiscountMultiplier = ($sub_total+$notTaxableCharges-$discountedAmount)/($sub_total+$notTaxableCharges);
            } else {
                $orderDiscountMultiplier = 0 ;
            }

            //calculate taxes
            foreach ($taxe_rates_groupping as $tax_rate_uuid=>$items) {
                $tax_rate = $allTaxesRates[$tax_rate_uuid];
                if($tax_rate == 0)
                    continue;

                $taxItemsSubtotal = 0;

                foreach ($items as $item) {
                    $lineSubtotal = $item['item']->price * $item['quantity'];
                    if(isset($item['modifiers']) && is_array($item['modifiers']) && count($item['modifiers'])>0){
                        foreach ($item['modifiers'] as $m) {
                            if(isset($m['qty']))
                                $m_price = $item['quantity'] * $m['price'] * intval($m['qty']);
                            else
                                $m_price = $item['quantity'] * $m['price'];

                            $lineSubtotal += $m_price;
                        }
                    }
                    $taxItemsSubtotal += $lineSubtotal;
                }

                $discountedTaxItemsSubtotal = $taxItemsSubtotal * $orderDiscountMultiplier;
                $discountedTaxItemsSubtotal = round($discountedTaxItemsSubtotal);

                $taxesWithoutDiscounts = round($taxItemsSubtotal) * $tax_rate/10000000;
                $taxes = $discountedTaxItemsSubtotal * $tax_rate/10000000;

                $taxes = round($taxes,0,PHP_ROUND_HALF_UP);
                $taxesWithoutDiscounts = round($taxesWithoutDiscounts,0,PHP_ROUND_HALF_UP);

                $total_of_taxes += $taxes;
                $total_of_taxes_without_discounts += $taxesWithoutDiscounts;
            }

            $total_of_taxes = ($total_of_taxes<0)?0:$total_of_taxes;
            $total_of_taxes_without_discounts = ($total_of_taxes_without_discounts<0)?0:$total_of_taxes_without_discounts;

            $FinalSubTotal = round($sub_total,0,PHP_ROUND_HALF_UP);
            $FinalTaxTotal = round($total_of_taxes,0,PHP_ROUND_HALF_UP);
            $FinalTaxTotalWithoutDiscounts = round($total_of_taxes_without_discounts,0,PHP_ROUND_HALF_UP);

            return array(
                'sub_total'             => intval($FinalSubTotal),
                'taxes'                 => intval($FinalTaxTotalWithoutDiscounts),
                'taxes_after_discount'  => intval($FinalTaxTotal),
                'nb_items'	            => $nb_items,
            );

        } else {
            return false;
        }
    }

}
