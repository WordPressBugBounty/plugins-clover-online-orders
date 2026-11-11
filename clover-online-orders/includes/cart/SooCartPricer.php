<?php
/**
 * Cart pricing, with no session and no WordPress.
 *
 * The arithmetic here is a verbatim move from MOO_SESSION so that a cart which is not
 * in the session — a document sent by the browser — is priced by exactly the same code.
 * The per-tax-rate grouping and every round() position are load-bearing: they exist so
 * the totals match Clover's own recalculation to the cent.
 *
 * Cart line shape (as built by addItemToCart()):
 *   [ 'item' => object{price,uuid,name,soo_name,price_type},
 *     'quantity' => int, 'special_ins' => string,
 *     'tax_rate' => [object{uuid,rate,name}, ...],
 *     'modifiers' => [ uuid => ['uuid','name','price','qty'], ... ] ]
 */
class SooCartPricer {

    /**
     * @param array $items             Cart lines.
     * @param int   $notTaxableCharges Delivery + service fee, in cents.
     * @param int   $discountedAmount  Discount already decided, in cents.
     * @return array|false
     */
    public static function totalsV2($items, $notTaxableCharges, $discountedAmount) {
        if(!empty($items)){
            $nb_items  = 0;
            $sub_total = 0;
            $total_of_taxes = 0;
            $total_of_taxes_without_discounts = 0;
            $taxe_rates_groupping = array();
            $allTaxesRates = array();
            $allTaxesFlat = array();

            //get the subtotal,taxeRates and calculate number of items
            foreach ($items as $item) {
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
                        $allTaxesFlat[$tr->uuid]=self::flatAmount($tr);
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
                $flat_amount = $allTaxesFlat[$tax_rate_uuid];
                if($tax_rate == 0 && $flat_amount == 0)
                    continue;

                $taxItemsSubtotal = 0;
                $taxItemsUnits = 0;

                foreach ($items as $item) {
                    $taxItemsUnits += intval($item['quantity']);
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

                // Flat (fixed-amount) rates: Clover charges taxAmount on every unit,
                // untouched by order discounts, so it is added after the percentage
                // math and never scaled by the multiplier.
                if($flat_amount > 0) {
                    $flatTaxes = $flat_amount * $taxItemsUnits;
                    $taxes += $flatTaxes;
                    $taxesWithoutDiscounts += $flatTaxes;
                }

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

    /**
     * @param array      $items
     * @param array|null $coupon            The coupon as stored, or null.
     * @param int        $deliveryCharges
     * @param int        $servicesFees
     * @param string     $servicesFeesType  'amount' or anything else for percentage.
     * @return array|false Totals, plus '_coupon_writeback' => array|null. This key is
     *                     internal plumbing, not part of the public totals shape: when
     *                     not null it carries the capped coupon (code, minAmount,
     *                     maxValue, use_maxValue) that the caller is responsible for
     *                     persisting back to the session, exactly as the original code
     *                     did at this point. Callers MUST strip '_coupon_writeback'
     *                     before returning this array to any client (e.g. a REST
     *                     response) — leaving it in leaks the stored coupon's details
     *                     and changes the response shape. See
     *                     MOO_SESSION::getTotals() for the reference implementation
     *                     that strips it.
     */
    public static function totals($items, $coupon, $deliveryCharges = 0, $servicesFees = 0, $servicesFeesType = 'amount') {
        $couponWriteback = null;
        if(!empty($items)){
            $nb_items  = 0;
            $sub_total = 0;
            $couponValue = 0;
            $servicesFeesValue = 0;
            $couponName  = null;
            $total_of_taxes = 0;
            $total_of_taxes_without_discounts = 0;
            $taxe_rates_groupping = array();
            $allTaxesRates = array();
            $allTaxesFlat = array();
            $notTaxableCharges = $deliveryCharges;

            //get the subtotal,taxeRates and calculate number of items
            foreach ($items as $item) {
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
                        $allTaxesFlat[$tr->uuid]=self::flatAmount($tr);
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
            if( !empty($coupon)) {
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
                        $couponWriteback = $coupon;
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
                $flat_amount = $allTaxesFlat[$tax_rate_uuid];
                if($tax_rate == 0 && $flat_amount == 0)
                    continue;

                $taxItemsSubtotal = 0;
                $taxItemsUnits = 0;

                foreach ($items as $item) {
                    $taxItemsUnits += intval($item['quantity']);
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

                // Flat (fixed-amount) rates: Clover charges taxAmount on every unit,
                // untouched by order discounts, so it is added after the percentage
                // math and never scaled by the multiplier.
                if($flat_amount > 0) {
                    $flatTaxes = $flat_amount * $taxItemsUnits;
                    $taxes += $flatTaxes;
                    $taxesWithoutDiscounts += $flatTaxes;
                }

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
                '_coupon_writeback'                    => $couponWriteback,
            );

        } else {
            return false;
        }
    }

    /** @var bool|null Resolved once per request; null until then. */
    private static $flatTaxesEnabled = null;

    /**
     * Whether flat-fee tax rates are charged. 1.6.6 synced taxAmount but never charged
     * it, so charging it is a pricing change for existing stores: off unless the
     * flat_fee_taxes setting (or moo_filter_plugin_settings) says "enabled". When the
     * setting was never saved it follows SOO_DEFAULT_CDN, so the hosted servers --
     * which have charged flat taxes since 1.6.7 -- keep charging them.
     *
     * @return bool
     */
    public static function flatTaxesEnabled() {
        if (self::$flatTaxesEnabled === null) {
            $enabled = defined('SOO_DEFAULT_CDN') && SOO_DEFAULT_CDN === true;
            if (function_exists('get_option') && function_exists('apply_filters')) {
                $settings = apply_filters('moo_filter_plugin_settings', (array) get_option('moo_settings'));
                if (isset($settings['flat_fee_taxes']) && $settings['flat_fee_taxes'] !== '') {
                    $enabled = $settings['flat_fee_taxes'] === 'enabled';
                }
            }
            self::$flatTaxesEnabled = $enabled;
        }
        return self::$flatTaxesEnabled;
    }

    /**
     * Force the flat-tax gate (harness), or pass null to resolve it from the settings again.
     *
     * @param bool|null $enabled
     */
    public static function setFlatTaxesEnabled($enabled) {
        self::$flatTaxesEnabled = $enabled === null ? null : (bool) $enabled;
    }

    /**
     * Fixed-amount ("flat fee") tax rates come from Clover with rate 0 and the
     * per-unit amount in taxAmount (cents); taxType is not relayed, so the
     * amount is the only signal. Rows synced before the column existed, and
     * fixtures, have no such property: treat them as percentage-only. A
     * non-zero rate is a percentage rate even when taxAmount is filled in
     * (an 8.75% rate can sync with taxAmount 875), so only rate 0 is flat.
     *
     * @param object $taxRate
     * @return int Cents per unit, 0 for a percentage rate.
     */
    public static function flatAmount($taxRate) {
        if (!self::flatTaxesEnabled()) {
            return 0;
        }
        if (is_object($taxRate) && isset($taxRate->taxAmount) && $taxRate->taxAmount !== ''
            && (!isset($taxRate->rate) || intval($taxRate->rate) === 0)) {
            $amount = intval($taxRate->taxAmount);
            return $amount > 0 ? $amount : 0;
        }
        return 0;
    }

    /**
     * Expand cart lines into the POS line-item payload. A line of quantity 3 becomes
     * three entries, and a modifier of qty 2 becomes two modifications — the backend
     * expects them itemised, not multiplied.
     *
     * @param array $items
     * @return array ['items' => array, 'without_modifiers' => bool]
     */
    public static function lineItems($items) {
        $cart = [
            "items"=>array(),
            "without_modifiers"=>true
        ];
        foreach ((array) $items as $cartLine) {
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
                            $taxRatePayload = array(
                                "rate" => intval($taxRate->rate),
                                "id" => $taxRate->uuid,
                                "name" => $taxRate->name,
                            );
                            // Flat-fee rate: carry the per-unit amount so the
                            // POS line gets the tax the total was priced with,
                            // not a 0% line. Percentage rates are unchanged.
                            $flatAmount = self::flatAmount($taxRate);
                            if ($flatAmount > 0) {
                                $taxRatePayload["taxAmount"] = $flatAmount;
                                $taxRatePayload["taxType"] = "FLAT";
                            }
                            $lineItem["taxRates"][] = $taxRatePayload;
                        }

                    }
                    array_push($cart["items"], $lineItem);
                }
            }
        }
        return $cart;
    }
}
