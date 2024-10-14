<?php

class Moo_OnlineOrders_Model {

    public $db;


    function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }
    function getCategories(){
        return $this->db->get_results("SELECT * FROM {$this->db->prefix}moo_category ORDER BY sort_order");
    }
    function getCategories4wigdets(){
        return $this->db->get_results("SELECT * FROM {$this->db->prefix}moo_category where show_by_default ='1' ORDER BY 4");
    }
    function getItems() {
        return $this->db->get_results("SELECT * FROM {$this->db->prefix}moo_item");
    }
    function getCategory($uuid) {
        $uuid = esc_sql($uuid);
        return $this->db->get_row("SELECT *
                                    FROM {$this->db->prefix}moo_category c
                                    WHERE c.uuid = '{$uuid}'
                                    ");
    }
    function getItemsUuidsPerCategory($category) {
        $categoryUuid = $category->uuid;
        $uuids = [];
        if(empty($category->items) || (isset($category->items_imported) && $category->items_imported) ) {
            $result = $this->db->get_results("SELECT *
                                    FROM {$this->db->prefix}moo_items_categories
                                    WHERE category_uuid = '{$categoryUuid}'
                                    ",'ARRAY_A');
            foreach ($result as $catItem) {
                $uuids[] = $catItem["item_uuid"];
            }
        } else {
            $uuids =  explode(",",$category->items);
        }
        return $uuids;
    }
    function getItemsByCategory($category, $onlyVisibleItems = false, $limit = null) {
        if(empty($category->items) || (isset($category->items_imported) && $category->items_imported) ) {
           $query = "SELECT items.* FROM {$this->db->prefix}moo_items_categories as items_categories,{$this->db->prefix}moo_item as items WHERE items_categories.item_uuid = items.uuid and items_categories.category_uuid = '{$category->uuid}'";
           if ($onlyVisibleItems){
               $query .= " AND items.visible = 1 AND items.hidden = 0 AND items.available = 1 and items.price_type != 'VARIABLE' ";
           }
           $query .= " ORDER BY items_categories.sort_order ASC";
           if($limit){
               $query .= " LIMIT {$limit}";
           }
           return $this->db->get_results($query);
        } else {
            $items_uuids = explode(",",$category->items);
            $itemsString  = "(";
            foreach ($items_uuids as $items_uuid) {
                if($items_uuid !== "" ){
                    $itemsString .= "'".$items_uuid."',";
                }
            }
            $itemsString = substr($itemsString, 0, strlen($itemsString)-1);
            $itemsString .= ")";
            if ( strlen($itemsString) > 1) {
                return $this->getVisibleItemsByCategory($itemsString,$limit);
            } else {
                return array();
            }
        }
    }
    function getItem($uuid) {
        $uuid = esc_sql($uuid);
        return $this->db->get_row("SELECT *
                                    FROM {$this->db->prefix}moo_item i
                                    WHERE i.hidden = 0 
                                    and i.uuid = '{$uuid}'
                                    ");
    }
    function getItemCategories($uuid) {
        $uuid = esc_sql($uuid);
        return $this->db->get_results("SELECT uuid as id
                                    FROM {$this->db->prefix}moo_category
                                    WHERE items like '%{$uuid}%'
                                    ",'ARRAY_A');
    }
    function getVisibleItemsByCategory($string,$limit){
        //$string = esc_sql($string);
        $sql = "SELECT *
                FROM {$this->db->prefix}moo_item i
                WHERE i.uuid in {$string}
                AND i.visible = 1
                AND i.hidden = 0
                AND i.available = 1
                AND i.price_type != 'VARIABLE'
                ORDER BY i.sort_order asc ";
        if($limit){
            $sql .= "LIMIT {$limit}";
        }
        $result  = $this->db->get_results($sql);
        return $result;
    }
    function getItemsNamesByUuids($string){
       // $string = esc_sql($string);
        $sql = "SELECT i.uuid,i.name
                FROM {$this->db->prefix}moo_item i
                WHERE i.uuid in {$string}";
        $result  = $this->db->get_results($sql);
        return $result;
    }
    function getCategoriesNamesByUuids($string){
       // $string = esc_sql($string);
        $sql = "SELECT i.uuid,i.name
                FROM {$this->db->prefix}moo_category i
                WHERE i.uuid in {$string}";
        $result  = $this->db->get_results($sql);
        return $result;
    }
    function hideItem($uuid)
    {
        $uuid = esc_sql($uuid);
        return $this->db->get_row("UPDATE {$this->db->prefix}moo_item i SET hidden = 1
                                    WHERE i.uuid = '{$uuid}'
                                    ");
    }
    function addHttpsToImages()
    {
        $this->db->get_row("UPDATE {$this->db->prefix}moo_category set image_url = Replace(image_url, 'http://', 'https://')  ;");
        return $this->db->get_row("UPDATE {$this->db->prefix}moo_images set url = Replace(url, 'http://', 'https://')  ;");
    }
    function addHttpToImages()
    {
        $this->db->get_row("UPDATE {$this->db->prefix}moo_category set image_url = Replace(image_url, 'https://', 'http://')  ;");
        return $this->db->get_row("UPDATE {$this->db->prefix}moo_images set url = Replace(url, 'https://', 'http://')  ;");
    }
    function hideCategory($uuid)
    {
        $uuid = esc_sql($uuid);
        return $this->db->get_row("UPDATE {$this->db->prefix}moo_category c SET show_by_default = 1
                                    WHERE c.uuid = '{$uuid}'
                                    ");
    }
    function getItemsByPage($per_page,$page)
    {
        $per_page = esc_sql($per_page);
        $offset = esc_sql($page) * $per_page;

        return $this->db->get_results("SELECT *
                                    FROM {$this->db->prefix}moo_item i
                                    limit {$per_page} offset {$offset}
                                    ");
    }
    function getCategoriesByPage($per_page,$page)
    {
        $per_page = esc_sql($per_page);
        $offset = esc_sql($page) * $per_page;

        return $this->db->get_results("SELECT *
                                    FROM {$this->db->prefix}moo_category
                                    limit {$per_page} offset {$offset}
                                    ");
    }
    function getModifierGroupsByPage($per_page,$page)
    {
        $per_page = esc_sql($per_page);
        $offset = esc_sql($page) * $per_page;

        return $this->db->get_results("SELECT *
                                    FROM {$this->db->prefix}moo_modifier_group
                                    limit {$per_page} offset {$offset}
                                    ");
    }
    function getModifiersByPage($per_page,$page)
    {
        $per_page = esc_sql($per_page);
        $offset = esc_sql($page) * $per_page;

        return $this->db->get_results("SELECT *
                                    FROM {$this->db->prefix}moo_modifier
                                    limit {$per_page} offset {$offset}
                                    ");
    }
    function getTaxRatesByPage($per_page,$page)
    {
        $per_page = esc_sql($per_page);
        $offset = esc_sql($page) * $per_page;

        return $this->db->get_results("SELECT *
                                    FROM {$this->db->prefix}moo_tax_rate
                                    limit {$per_page} offset {$offset}
                                    ");
    }
    function getOrderTypesByPage($per_page,$page)
    {
        $per_page = esc_sql($per_page);
        $offset = esc_sql($page) * $per_page;

        return $this->db->get_results("SELECT *
                                    FROM {$this->db->prefix}moo_order_types
                                    limit {$per_page} offset {$offset}
                                    ");
    }
    function getItemsBySearch($motCle)
    {
        $motCle = esc_sql($motCle);

        return $this->db->get_results("SELECT *
                                    FROM {$this->db->prefix}moo_item i
                                    WHERE i.soo_name like '%{$motCle}%' || i.name like '%{$motCle}%' || i.description like '%{$motCle}%'
                                    ");
    }

    function getItemTax_rate($uuid)
    {
        $item = $this->getItem($uuid);

        if($item->default_taxe_rate){
            $taxes = $this->db->get_results("SELECT name,uuid,rate
                                    FROM {$this->db->prefix}moo_tax_rate t
                                    WHERE t.is_default = 1
                                    ");
            return $taxes;
        }
        else
        {
            $taxes = $this->db->get_results("SELECT tr.name,tr.uuid,tr.rate FROM {$this->db->prefix}moo_item_tax_rate itr,{$this->db->prefix}moo_tax_rate tr
                                          WHERE itr.tax_rate_uuid=tr.uuid
                                          AND itr.item_uuid='{$uuid}'

                                    ");
            return $taxes;
        }
    }
    function getDefaultTaxRates(){
        $taxes = $this->db->get_results("SELECT name,uuid,rate
                                FROM {$this->db->prefix}moo_tax_rate t
                                WHERE t.is_default = 1
                                ");
        return $taxes;
    }

    function getModifiers($uuid_group)
    {
        $uuid_group = esc_sql($uuid_group);

        return $this->db->get_results("SELECT *
                                    FROM `{$this->db->prefix}moo_modifier` m
                                    WHERE m.group_id = '{$uuid_group}' AND m.show_by_default='1'
                                    ORDER BY m.sort_order
                                    ");
    }
    function getModifiersGroup($item)
    {
        $item = esc_sql($item);

        return $this->db->get_results("SELECT mg.*
                                    FROM `{$this->db->prefix}moo_item_modifier_group` img,  `{$this->db->prefix}moo_modifier_group` mg
                                    WHERE mg.uuid=img.group_id AND mg.show_by_default='1'
                                    AND img.item_id = '{$item}'
                                    ORDER BY mg.sort_order
                                    ");
    }
    function getModifiersGroupByItem($item_uuid){
        //$this->db->show_errors();
        $item_uuid = esc_sql($item_uuid);

        return $this->db->get_results("SELECT mg.*
                                    FROM `{$this->db->prefix}moo_item_modifier_group` img,  `{$this->db->prefix}moo_modifier_group` mg,`{$this->db->prefix}moo_item` items
                                    WHERE mg.uuid=img.group_id
                                    AND img.item_id = items.uuid
                                    AND items.uuid = '{$item_uuid}'
                                    ORDER BY mg.sort_order
                                    ");
    }
    /*
    function getAllModifiersGroup()
    {
        return $this->db->get_results("SELECT *
                                    FROM `{$this->db->prefix}moo_modifier_group`");
    }
    */
    function getAllModifiersGroup()
    {
        return $this->db->get_results("SELECT * FROM {$this->db->prefix}moo_modifier_group WHERE uuid in (SELECT group_id from {$this->db->prefix}moo_modifier) ORDER BY `sort_order`,name ASC");
    }
    function getAllModifiersGroupByItem($uuid)
    {
        $item_uuid = esc_sql($uuid);
        return $this->db->get_results("SELECT * FROM {$this->db->prefix}moo_modifier_group WHERE uuid in (SELECT group_id from {$this->db->prefix}moo_item_modifier_group where item_id = '{$item_uuid}') ORDER BY `sort_order`,name ASC");
    }
    function getAllModifiers($uuid_group)
    {
        $uuid_group = esc_sql($uuid_group);

        return $this->db->get_results("SELECT *
                                    FROM `{$this->db->prefix}moo_modifier` m
                                    WHERE m.group_id = '{$uuid_group}'
                                    ORDER BY m.sort_order
                                    ");
    }
    function itemHasModifiers($item)
    {
        $item = esc_sql($item);

        return $this->db->get_row("SELECT count(*) as total
                                    FROM `{$this->db->prefix}moo_item_modifier_group` img, `{$this->db->prefix}moo_modifier_group` mg, `{$this->db->prefix}moo_modifier` m
                                    WHERE img.group_id = mg.uuid AND img.item_id = '{$item}' AND mg.uuid=m.group_id AND mg.show_by_default='1'
                                    ");
    }
    function getModifiersGroupLimits($uuid)
    {
        $uuid = esc_sql($uuid);

        return $this->db->get_row("SELECT min_required, max_allowd, name
                                    FROM `{$this->db->prefix}moo_modifier_group` mg
                                    WHERE mg.uuid = '{$uuid}'
                                    ");
    }
    function getOneModifiersGroup($uuid)
    {
        $uuid = esc_sql($uuid);
        return $this->db->get_row("SELECT * FROM `{$this->db->prefix}moo_modifier_group` mg WHERE mg.uuid = '{$uuid}'");
    }
    function getItemModifiersGroupsRequired($uuid) {
        $uuid = esc_sql($uuid);

        return $this->db->get_results("SELECT mg.uuid
                                    FROM `{$this->db->prefix}moo_modifier_group` mg,`{$this->db->prefix}moo_item` item,`{$this->db->prefix}moo_item_modifier_group` item_mg  
                                    WHERE item_mg.item_id =  item.uuid
                                    AND item_mg.group_id =  mg.uuid
                                    AND item.uuid = '{$uuid}'
                                    AND mg.min_required >= 1
				                    AND mg.show_by_default = 1
                                    ");
    }
    function getModifier($uuid) {
        $uuid = esc_sql($uuid);

        return $this->db->get_row("SELECT m.*,mg.min_required as group_min,mg.max_allowd as group_max
                                        FROM `{$this->db->prefix}moo_modifier` m, `{$this->db->prefix}moo_modifier_group` mg
                                        WHERE m.uuid = '{$uuid}' and mg.uuid = m.group_id
                                        ");
    }
    function getItemsWithVariablePrice() {
        return $this->db->get_results("SELECT *
                                        FROM `{$this->db->prefix}moo_item` 
                                        WHERE price_type = 'VARIABLE'
                                        ");
    }
    function getOrderTypes()
    {
        return $this->db->get_results("SELECT * FROM {$this->db->prefix}moo_order_types order by sort_order,status,label");
    }
    function getOneOrderTypes($uuid) {
        $uuid = esc_sql($uuid);
        return $this->db->get_row("SELECT * FROM {$this->db->prefix}moo_order_types where ot_uuid='{$uuid}'");
    }
    function getOneOrder($orderId) {
        $orderId = esc_sql($orderId);
        return $this->db->get_row("SELECT * FROM {$this->db->prefix}moo_order where uuid='".$orderId."'");
    }
    function getItemsOrder($uuid) {
        $uuid = esc_sql($uuid);
        return $this->db->get_results("SELECT IO.* ,I.* FROM {$this->db->prefix}moo_item_order IO ,{$this->db->prefix}moo_item I WHERE I.uuid = IO.item_uuid and IO.order_uuid = '$uuid' ORDER BY IO.`_id` DESC");
    }
    function getVisibleOrderTypes($arrayOnResult = false) {
        if ($arrayOnResult){
            return $this->db->get_results("SELECT * FROM {$this->db->prefix}moo_order_types where status=1 order by sort_order,label", 'ARRAY_A');
        }else {
            return $this->db->get_results("SELECT * FROM {$this->db->prefix}moo_order_types where status=1 order by sort_order,label");
        }
    }

    function updateOrderTypes($uuid,$status) {
        $uuid = esc_sql($uuid);
        $st = ($status == "true")? 1:0;

        return $this->db->update("{$this->db->prefix}moo_order_types",
                                array(
                                    'status' => $st
                                ),
                                array( 'ot_uuid' => $uuid )
        );
    }

    function saveNewOrderOfOrderTypes($data){
        $compteur = 0;
        //Get the number OrderType
        $group_number = $this->NbOrderTypes();
        $group_number = $group_number[0]->nb;
        $this->db->query('START TRANSACTION');
        foreach ($data as $key => $value) {
            $this->db->update("{$this->db->prefix}moo_order_types",
                array(
                    'sort_order' => $key
                ),
                array( 'ot_uuid' => $value ));
            $compteur++;
        }
        if($compteur == $group_number)
        {
            $this->db->query('COMMIT');
            return true;
        }
        else {
            $this->db->query('ROLLBACK');
            return false;
        }
    }
    function updateOrderType($uuid,$name,$enable,$taxable,$type,$minAmount,$maxAmount,$customHours,$useCoupons,$customMessage,$allowScOrders,$allowServiceFee)
    {

        $uuid = sanitize_text_field($uuid);
        $label = wp_kses_post(wp_unslash($name));
        $taxable = esc_sql($taxable);
        $status = esc_sql($enable);
        $type = esc_sql($type);
        $minAmount = esc_sql($minAmount);
        $maxAmount = esc_sql($maxAmount);
        $customHours = esc_sql($customHours);
        $useCoupons = esc_sql($useCoupons);
        $allowScOrders = esc_sql($allowScOrders);
        $allowServiceFee = esc_sql($allowServiceFee);
        $customMessage = wp_kses_post(wp_unslash($customMessage));

        return $this->db->update("{$this->db->prefix}moo_order_types",
            array(
                'label' => $label,
                'taxable' => $taxable,
                'status' => $status,
                'minAmount' => $minAmount,
                'maxAmount' => $maxAmount,
                'show_sa' => $type,
                'custom_hours' => $customHours,
                'use_coupons' => $useCoupons,
                'custom_message' => $customMessage,
                'allow_sc_order' => $allowScOrders,
                'allow_service_fee' => $allowServiceFee,
            ),
            array( 'ot_uuid' => $uuid )
        );
    }

    function ChangeModifierGroupName($mg_uuid,$name)
    {
        $uuid = esc_sql($mg_uuid);
        $name = esc_sql($name);
        return $this->db->update("{$this->db->prefix}moo_modifier_group",
                                array(
                                    'alternate_name' => $name
                                ),
                                array( 'uuid' => $uuid )
        );
        
    }

    function ChangeModifierName($m_uuid,$name)
    {
        $uuid = esc_sql($m_uuid);
        $name = esc_sql($name);

        return $this->db->update("{$this->db->prefix}moo_modifier",
            array(
                'alternate_name' => $name
            ),
            array( 'uuid' => $uuid )
        );

    }

    function UpdateModifierGroupStatus($mg_uuid,$status)
    {
        $uuid = esc_sql($mg_uuid);
        $st = ($status == "true")? 1:0;

        return $this->db->update("{$this->db->prefix}moo_modifier_group",
                                array(
                                    'show_by_default' => $st
                                ),
                                array( 'uuid' => $uuid )
        );
        
    }
    function UpdateModifierStatus($mg_uuid,$status)
    {
        $uuid = esc_sql($mg_uuid);
        $st = ($status == "true")? 1:0;

        return $this->db->update("{$this->db->prefix}moo_modifier",
            array(
                'show_by_default' => $st
            ),
            array( 'uuid' => $uuid )
        );

    }
    public function update_category($category)
    {
        $items_ids = "";
        foreach ($category["items"]["elements"] as $item) {
            $items_ids .= $item['id'] . ",";
        }

        if ($this->db->get_var("SELECT COUNT(*) FROM {$this->db->prefix}moo_category where uuid='{$category["id"]}'") > 0)
            $res = $this->db->update("{$this->db->prefix}moo_category", array(
                'name' => $category["name"],
                'items' => $items_ids
            ), array('uuid' => $category["id"]));
        else
            $res = $this->db->insert("{$this->db->prefix}moo_category", array(
                'uuid' => $category["id"],
                'name' => $category["name"],
                'sort_order' => $category["sortOrder"],
                'show_by_default' => 1,
                'items' => $items_ids
            ));

        return $res > 0;
    }

    function ChangeCategoryName($cat_uuid,$name) {
        $uuid = esc_sql($cat_uuid);
        $name = esc_sql($name);
        return $this->db->update("{$this->db->prefix}moo_category",
            array(
                'name' => $name
            ),
            array( 'uuid' => $uuid )
        );

    }
    function UpdateCategoryStatus($cat_uuid,$status) {
        $uuid = esc_sql($cat_uuid);
        $st = ($status == "true")? 1:0;

        return $this->db->update("{$this->db->prefix}moo_category",
                                array(
                                    'show_by_default' => $st
                                ),
                                array( 'uuid' => $uuid )
        );

    }
    function moo_DeleteOrderType($uuid) {
        $uuid = esc_sql($uuid);
        return $this->db->delete("{$this->db->prefix}moo_order_types",
                                array( 'ot_uuid' => $uuid )
        );
    }
    function deleteCategory($uuid) {
        $uuid = esc_sql($uuid);
        return $this->db->delete("{$this->db->prefix}moo_category",
                                array( 'uuid' => $uuid )
        );
    }
    function deleteModifierGroup($uuid) {
        $uuid = esc_sql($uuid);
        if( $uuid== "" ) return;
        $this->db->query('START TRANSACTION');
        $this->db->delete("{$this->db->prefix}moo_modifier",array('group_id'=>$uuid));
        $this->db->delete("{$this->db->prefix}moo_item_modifier_group",array('group_id'=>$uuid));
        $res = $this->db->delete("{$this->db->prefix}moo_modifier_group",array('uuid'=>$uuid));
        if($res)
        {
            $this->db->query('COMMIT'); // if the item Inserted in the DB
        }
        else {
            $this->db->query('ROLLBACK'); // // something went wrong, Rollback
        }
        return $res;

    }
    function deleteTaxRate($uuid) {
        $this->db->show_errors();
        $uuid = esc_sql($uuid);
        if( $uuid== "" ) return;
        $this->db->query('START TRANSACTION');
        $this->db->delete("{$this->db->prefix}moo_item_tax_rate",array('tax_rate_uuid'=>$uuid));
        $res = $this->db->delete("{$this->db->prefix}moo_tax_rate",array('uuid'=>$uuid));
        if($res)
        {
            $this->db->query('COMMIT'); // if the item Inserted in the DB
        }
        else {
            $this->db->query('ROLLBACK'); // // something went wrong, Rollback
        }
        return $res;

    }
    function deleteModifier($uuid) {
        $uuid = esc_sql($uuid);
        if( $uuid== "" ) return;
        return $this->db->delete("{$this->db->prefix}moo_modifier",array('uuid'=>$uuid));
    }
    function deleteModifierInGroup($uuid, $group_uuid) {
        $uuid = esc_sql($uuid);
        $groupGuid = esc_sql($group_uuid);
        return $this->db->delete(
            "{$this->db->prefix}moo_modifier",
            array(
                'uuid'=>$uuid,
                'group_id'=>$groupGuid,
            ));
    }

    function addOrder($uuid,$tax,$total,$name,$address, $city,$zipcode,$phone,$email,$instructions,$state,$country,$deliveryFee,$tipAmount,$shippingFee,$customer_lat,$customer_lng,$ordertype,$datetime){
        $uuid         = esc_sql($uuid);
        $tax          = esc_sql($tax);
        $total        = esc_sql($total);
        $name         = esc_sql($name);
        $address      = esc_sql($address);
        $city         = esc_sql($city);
        $zipcode      = esc_sql($zipcode);
        $phone        = esc_sql($phone);
        $email        = esc_sql($email);
        $instructions = esc_sql($instructions);
        $ordertype    = esc_sql($ordertype);
        $datetime     = esc_sql($datetime);
        $state        = esc_sql($state);
        $country      = esc_sql($country);

        $deliveryFee     = esc_sql($deliveryFee);
        $tipAmount       = esc_sql($tipAmount);
        $shippingFee     = esc_sql($shippingFee);
        $customer_lat    = esc_sql($customer_lat);
        $customer_lng    = esc_sql($customer_lng);

        $date = gmdate('Y/m/d H:i:s', $datetime);
        $this->db->insert(
            "{$this->db->prefix}moo_order",
            array(
                'uuid' => $uuid,
                'taxAmount' => $tax,
                'amount' => $total,
                'paid' => 0,
                'refpayment' => null,
                'ordertype' => $ordertype,
                'p_name' => $name,
                'p_address' => $address,
                'p_city' => $city,
                'p_state' => $state,
                'p_country' => $country,
                'p_zipcode' => $zipcode,
                'p_phone' => $phone,
                'p_email' => $email,
                'p_lat' => $customer_lat,
                'p_lng' => $customer_lng,
                'shippingfee' => $shippingFee,
                'deliveryfee' => $deliveryFee,
                'tipAmount' => $tipAmount,
                'instructions' => $instructions,
                'date' => $date,
            ));
        return $this->db->insert_id;
    }
    function addOrderV2($order,$body,$orderTypeLabel){
        $this->db->insert(
            "{$this->db->prefix}moo_order",
            array(
                'uuid' => $order["id"],
                'taxAmount' => (isset($body["tax_amount"]))?$body["tax_amount"]/100 : 0,
                'amount' => (isset($body["amount"]))?$body["amount"]/100 : 0,
                'paid' => 0,
                'refpayment' => null,
                'ordertype' => $orderTypeLabel,
                'p_name' => (isset($body["customer"]["name"]))?$body["customer"]["name"] : ((isset($body["customer"]["full_name"]))?$body["customer"]["full_name"] : ""),
                'p_address' => (isset($body["customer"]["address"]["address"]))?$body["customer"]["address"]["address"] : "",
                'p_city' => (isset($body["customer"]["address"]["city"]))?$body["customer"]["address"]["city"] : "",
                'p_state' => (isset($body["customer"]["address"]["state"]))?$body["customer"]["address"]["state"] : "",
                'p_country' => (isset($body["customer"]["address"]["country"]))?$body["customer"]["address"]["country"] : "US",
                'p_zipcode' => (isset($body["customer"]["address"]["zipcode"]))?$body["customer"]["address"]["zipcode"] : "",
                'p_phone' => (isset($body["customer"]["phone"]))?$body["customer"]["phone"] : "",
                'p_email' => (isset($body["customer"]["email"]))?$body["customer"]["email"]: "",
                'p_lat' => (isset($body["customer"]["address"]["lat"]))?$body["customer"]["address"]["lat"] : "",
                'p_lng' => (isset($body["customer"]["address"]["lng"]))?$body["customer"]["address"]["lng"] : "",
                'shippingfee' =>  (isset($body["service_fee"]))?$body["service_fee"]/100 : 0,
                'deliveryfee' =>(isset($body["delivery_amount"]))?$body["delivery_amount"]/100 : 0,
                'tipAmount' => (isset($body["tip_amount"]))?$body["tip_amount"]/100 : 0,
                'instructions' => (isset($body["special_instructions"]))?$body["special_instructions"] : "",
                'date' => date('Y/m/d H:i:s', ($order['createdTime']/1000)),
            ));
        return $this->db->insert_id;
    }
    function addLinesOrder($order,$items){
        $order    = esc_sql($order);
        foreach ($items as $uuid=>$item) {
            if($item['item']->uuid=="delivery_fees" || $item['item']->uuid=="service_fees")
                continue;

            $string = "";
            if(count($item['modifiers'])) {
                foreach ($item['modifiers'] as $key=>$mod) {
                    for($i=0;$i<$mod['qty'];$i++)
                        $string .=$key.",";
                }
            }

            $item_id        = esc_sql($item['item']->uuid);
            $quantity       = esc_sql($item['quantity']);
            $special_ins    = esc_sql($item['special_ins']);
            $string         = esc_sql($string);

            $this->db->insert(
                "{$this->db->prefix}moo_item_order",
                array(
                    'item_uuid' => $item_id,
                    'order_uuid' => $order,
                    'quantity' => $quantity,
                    'modifiers' => $string,
                    'special_ins' => $special_ins,
                ),
                array(
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                ) );
        }
       return true;
    }
    function updateOrder($uuid,$ref){
        $uuid      = esc_sql($uuid);
        $ref       = esc_sql($ref);
        return $this->db->update(
                        "{$this->db->prefix}moo_order",
                        array(
                            'paid' => 1,
                            'refpayment' => $ref
                        ),
                        array( 'uuid' => $uuid )
                    );
    }

    function NbCats() {
        return $this->db->get_results("SELECT count(*) as nb FROM {$this->db->prefix}moo_category");
    }

    function NbLabels()
    {
        return $this->db->get_results("SELECT count(*) as nb FROM {$this->db->prefix}moo_tag");
    }

    function NbTaxes()
    {
        return $this->db->get_results("SELECT count(*) as nb FROM {$this->db->prefix}moo_tax_rate");
    }

    function NbProducts()
    {
        return $this->db->get_results("SELECT count(*) as nb FROM {$this->db->prefix}moo_item where hidden = 0");
    }
    function NbGroupModifier()
    {
        return $this->db->get_results("SELECT count(*) as nb FROM {$this->db->prefix}moo_modifier_group WHERE uuid in (SELECT group_id from {$this->db->prefix}moo_modifier)");
    }
    function NbModifierGroups()
    {
        return $this->db->get_results("SELECT count(*) as nb FROM {$this->db->prefix}moo_modifier_group");
    }
    function NbModifier($group)
    {
        return $this->db->get_results("SELECT count(*) as nb FROM {$this->db->prefix}moo_modifier where group_id = '{$group}'");
    }
    function NbOrderTypes()
    {
        return $this->db->get_results("SELECT count(*) as nb FROM {$this->db->prefix}moo_order_types");
    }

    function getBestSellingProducts($limit) {
        $limit = esc_sql($limit);
        if($limit<=0) {
            $limit = 10;
        }
        return $this->db->get_results("SELECT COUNT(*),item_uuid,item.* FROM `{$this->db->prefix}moo_item_order` ligne,{$this->db->prefix}moo_item item where item.uuid=ligne.item_uuid  GROUP by item_uuid ORDER by 1 desc limit ".$limit);
    }
    function getFeaturedProducts($limit = null) {
       if ($limit){
           return $this->db->get_results("SELECT * FROM {$this->db->prefix}moo_item item where hidden = 0 and item.featured = 1 ORDER by soo_name,name,alternate_name asc limit ".$limit);
       } else {
           return $this->db->get_results("SELECT * FROM {$this->db->prefix}moo_item item where hidden = 0 and item.featured = 1 ORDER by soo_name,name,alternate_name asc");
       }
    }
    function moo_GetBestItems4Customer($email){
        $email = esc_sql($email);
        $limit = 24;
        return $this->db->get_results("SELECT COUNT(*) as ordered,item_uuid,item.* FROM `{$this->db->prefix}moo_item_order` ligne,`{$this->db->prefix}moo_item` item,`{$this->db->prefix}moo_order` cmd where cmd.p_email = '".$email."' and cmd.uuid = ligne.order_uuid && item.uuid=ligne.item_uuid  GROUP by item_uuid ORDER by 1,item.sort_order,item.uuid desc limit ".$limit);
    }

    /*
     * Manage Item's image
     */
    function getItemWithImage($uuid)
    {
        $uuid = esc_sql($uuid);
        return $this->db->get_results("SELECT *
                                    FROM {$this->db->prefix}moo_item items
                                    LEFT JOIN {$this->db->prefix}moo_images images
                                    ON items.uuid=images.item_uuid
                                    WHERE items.uuid = '{$uuid}'
                                    ");
    }
    function getEnabledItemImages($uuid)
    {
        $uuid = esc_sql($uuid);
        return $this->db->get_results("SELECT *
                                    FROM {$this->db->prefix}moo_images images
                                    WHERE images.item_uuid = '{$uuid}' AND images.is_enabled = '1'
                                    ORDER by images.is_default desc
                                    ");
    }
    function getItemImages($uuid)
    {
        $uuid = esc_sql($uuid);
        return $this->db->get_results("SELECT *
                                    FROM {$this->db->prefix}moo_images images
                                    WHERE images.item_uuid = '{$uuid}'
                                    ");
    }
    function getDefaultItemImage($uuid)
    {
        $uuid = esc_sql($uuid);
        return $this->db->get_row("SELECT url
                                    FROM {$this->db->prefix}moo_images images
                                    WHERE images.item_uuid = '{$uuid}' order by images.is_default desc limit 1
                                    ");
    }
    function getOrderDetails($uuid)
    {
        $uuid = esc_sql($uuid);
        return $this->db->get_results("SELECT *
                                   FROM {$this->db->prefix}moo_item I
                                   INNER JOIN {$this->db->prefix}moo_item_order IO
                                   ON IO.`item_uuid` = I.uuid
                                   WHERE IO.order_uuid = '{$uuid}'
                                   ");
    }

    function saveItemWithImage($uuid,$description,$images) {
        $uuid = esc_sql($uuid);
        $compteur = 0;

        if($description != "")
            $this->db->update("{$this->db->prefix}moo_item", array('description' => $description), array( 'uuid' => $uuid ));

        $this->db->query('START TRANSACTION');
        $this->db->query("DELETE FROM {$this->db->prefix}moo_images  WHERE item_uuid = '{$uuid}'");
        foreach ($images as $image) {
            $image_url = $image['image_url'];
            $image_default = intval($image['image_default']);
            $image_enabled = intval($image['image_enabled']);
            $this->db->insert("{$this->db->prefix}moo_images", array('is_default' => $image_default, 'is_enabled'=> $image_enabled, 'item_uuid' => $uuid, 'url' => $image_url));
            if($this->db->insert_id) $compteur++;
        }

        if($compteur == count($images)) {
           $this->db->query('COMMIT');
           return true;
       } else {
           $this->db->query('ROLLBACK');
           return false;
       }
    }
    function updatePreSelectedModifiers($groupUuid, $preSelectedModifiers) {
        $this->db->query('START TRANSACTION');
        $this->db->query("UPDATE {$this->db->prefix}moo_modifier set is_pre_selected = 0 WHERE group_id = '{$groupUuid}'");

        if (count($preSelectedModifiers) > 0){
            //Prepare where field
            $WHERE = '`uuid` IN ("' . implode( '", "', $preSelectedModifiers ) . '")';
            $WHERE = sanitize_text_field($WHERE);
            //Run update Query
            $result = $this->db->query("UPDATE {$this->db->prefix}moo_modifier set is_pre_selected = 1 where {$WHERE}");
            if($result <= 0) {
                $this->db->query('ROLLBACK');
                return false;
            }
        }
        $this->db->query('COMMIT');
        return true;
    }
    function saveItemDescription($uuid,$description) {
        $uuid = esc_sql($uuid);
        $this->db->update("{$this->db->prefix}moo_item", array('description' => $description), array( 'uuid' => $uuid ));
        return true;
    }
    function updateItemName($uuid, $newName) {
        $uuid = esc_sql($uuid);
        $name = esc_sql($newName);
        return $this->db->update("{$this->db->prefix}moo_item", array('soo_name' => $name), array( 'uuid' => $uuid ));
    }
    function updateItem($item, $byUuid) {
        if ($byUuid){
            $fields = [];
            if (isset($item["description"]) && !empty($item["description"])){
                $fields["description"] = $item["description"];
            }

            if (isset($item["soo_name"]) && !empty($item["soo_name"])){
                $fields["soo_name"] = $item["soo_name"];
            }

            if ( isset($item["featured"]) ){
                $fields["featured"] = $item["featured"];
            }
            if ( isset($item["outofstock"]) ){
                $fields["outofstock"] = $item["outofstock"];
            }
            if ( isset($item["visible"]) ){
                $fields["visible"] = $item["visible"];
            }
            if (count($fields)>0){
                return $this->db->update("{$this->db->prefix}moo_item",
                    $fields,
                    array( 'uuid' => $item["uuid"] )
                );
            } else {
                return 0;
            }
        } else {
            $sql = "UPDATE `{$this->db->prefix}moo_item`";
            $sql .= " SET description = '".esc_sql($item["description"])."' ";

            if (isset($item["soo_name"]) && !empty($item["soo_name"])){
                $sql .= ", soo_name = '".esc_sql($item["soo_name"])."' ";
            }
            $sql .= "WHERE name like '%".esc_sql($item["name"])."%' ";
            $sql .= "AND uuid != '".esc_sql($item["uuid"])."' ;";
            return $this->db->query($sql);
        }
    }

    function updateCategory($cat) {
        $fields = [];
        if (isset($cat["name"]) && !empty($cat["name"])){
            $fields["name"] = $cat["name"];
        }
        if (isset($cat["sort_order"]) && !empty($cat["sort_order"])){
            $fields["sort_order"] = $cat["sort_order"];
        }

        if (isset($cat["show_by_default"])){
            $fields["show_by_default"] = $cat["show_by_default"];
        }

        if (isset($cat["image_url"])){
            if (!empty($cat["image_url"])){
                $fields["image_url"] = $cat["image_url"];
            } else {
                $fields["image_url"] = null;
            }
        }
        if (isset($cat["alternate_name"]) && !empty($cat["alternate_name"])){
            $fields["alternate_name"] = $cat["alternate_name"];
        }
        if (isset($cat["description"]) && !empty($cat["description"])){
            $fields["description"] = $cat["description"];
        }
        if (isset($cat["custom_hours"]) && !empty($cat["custom_hours"])){
            $fields["custom_hours"] = $cat["custom_hours"];
        }
        if (isset($cat["time_availability"]) && !empty($cat["time_availability"])){
            $fields["time_availability"] = $cat["time_availability"];
        }
        if (count($fields) > 0 && isset($cat["uuid"])){
            return $this->db->update("{$this->db->prefix}moo_category",
                $fields,
                array( 'uuid' => $cat["uuid"] )
            );
        } else {
            return 0;
        }

    }
    function updateModifierGroup($group) {
        $fields = [];
        if (isset($group["name"]) && !empty($group["name"])){
            $fields["name"] = $group["name"];
        }
        if (isset($group["sort_order"]) && !empty($group["sort_order"])){
            $fields["sort_order"] = $group["sort_order"];
        }
        if (isset($group["alternate_name"]) && !empty($group["alternate_name"])){
            $fields["alternate_name"] = $group["alternate_name"];
        }

        if (isset($group["show_by_default"])){
            $fields["show_by_default"] = $group["show_by_default"];
        } else {
            $fields["show_by_default"] = null;
        }

        if (count($fields) > 0 && isset($group["uuid"])){
            return $this->db->update("{$this->db->prefix}moo_modifier_group",
                $fields,
                array( 'uuid' => $group["uuid"] )
            );
        } else {
            return 0;
        }

    }
    function updateModifierGroupAlternateName($alternateName, $groupUuid) {
        return $this->db->update("{$this->db->prefix}moo_modifier_group",
            array(
                "alternate_name"=>$alternateName
            ),
            array( 'uuid' => $groupUuid )
        );

    }
    function updateModifier($modifier) {
        $fields = [];
        if (!empty($modifier["name"])){
            $fields["name"] = $modifier["name"];
        }
        if (!empty($modifier["sort_order"])){
            $fields["sort_order"] = $modifier["sort_order"];
        }
        if (!empty($modifier["alternate_name"])){
            $fields["alternate_name"] = $modifier["alternate_name"];
        }

        if (isset($modifier["show_by_default"])){
            $fields["show_by_default"] = $modifier["show_by_default"];
        } else {
            $fields["show_by_default"] = null;
        }

        if (isset($modifier["is_pre_selected"])){
            $fields["is_pre_selected"] = $modifier["is_pre_selected"];
        } else {
            $fields["is_pre_selected"] = 0;
        }

        if (count($fields) > 0 && isset($modifier["uuid"])){
            return $this->db->update("{$this->db->prefix}moo_modifier",
                $fields,
                array( 'uuid' => $modifier["uuid"] )
            );
        } else {
            return 0;
        }

    }
    function updateOrderTypeFromArray($ot) {
        $attributes = [
            "label",
            "taxable",
            "status",
            "show_sa",
            "minAmount",
            "sort_order",
            "type",
            "custom_hours",
            "time_availability",
            "use_coupons",
            "custom_message",
            "allow_sc_order",
            "maxAmount",
            "allow_service_fee"
        ];
        $fields = [];

        foreach ($attributes as $attribute){
            if (isset($ot[$attribute])){
                $fields[$attribute] = $ot[$attribute];
            } else {
                $fields[$attribute] = null;
            }
        }
        if (count($fields) > 0 && isset($ot["ot_uuid"])){
            return $this->db->update("{$this->db->prefix}moo_order_types",
                $fields,
                array( 'ot_uuid' => $ot["ot_uuid"] )
            );
        } else {
            return 0;
        }

    }

    function reOrderItems($tab) {
        $compteur = 0;
        foreach ($tab as $key => $value) {
            $this->db->update("{$this->db->prefix}moo_item",
                array(
                    'sort_order' => $key
                ),
                array( 'uuid' => $value ));
            $compteur++;
        }
        return $compteur;
    }
    function reOrderCategoryItems($categoryUuid,$tab) {
        $count = 0;
        foreach ($tab as $key => $value) {
           $result = $this->db->update("{$this->db->prefix}moo_items_categories",
                array(
                    'sort_order' => ($key+1)
                ),
                array(
                    'item_uuid' => $value,
                    'category_uuid' => $categoryUuid,
                ));
           if( $result > 0 ) {
               $count++;
           }
        }
        return $count;
    }

    function saveImageCategory($uuid,$image) {
        $uuid = esc_sql($uuid);
        $image = esc_sql($image);

        return $this->db->update("{$this->db->prefix}moo_category",
            array(
                'image_url' => $image
            ),
            array( 'uuid' => $uuid )
        );
    }
    function saveNewCategoriesorder($tab) {
        $compteur = 0;
        //Get the number of categories to compare it with the categories that are changed
        $cats_number = $this->NbCats();
        $cats_number = $cats_number[0]->nb;

        $this->db->query('START TRANSACTION');

        foreach ($tab as $key => $value) {
            $this->db->update("{$this->db->prefix}moo_category",
                array(
                    'sort_order' => $key
                ),
                array( 'uuid' => $value ));

            $compteur++;
        }
        if($compteur == $cats_number) {
            $this->db->query('COMMIT');
            return true;
        } else {
            $this->db->query('ROLLBACK');
            return false;
        }

    }

    function moo_DeleteImgCategorie($uuid) {
        $uuid = esc_sql($uuid);
        return $this->db->update("{$this->db->prefix}moo_category",
            array(
                'image_url' => null
            ),
            array( 'uuid' => $uuid )
        );

    }

    function moo_UpdateNameCategorie($uuid,$newName) {
        $uuid = esc_sql($uuid);
        return $this->db->update("{$this->db->prefix}moo_category",
            array(
                'alternate_name' => $newName
            ),
            array( 'uuid' => $uuid )
        );

    }
    function updateCategoryNameAndDescription($uuid,$newName,$newDescription) {
        $uuid           = esc_sql($uuid);
        $newName        = esc_sql($newName);
        $newDescription = esc_sql($newDescription);
        $data = array();

        $data['alternate_name'] = $newName;
        $data['description'] = $newDescription;

        return $this->db->update("{$this->db->prefix}moo_category",
            $data,
            array( 'uuid' => $uuid )
        );

    }
    function updateCategoryTime($uuid,$status,$hour) {
        $uuid           = esc_sql($uuid);
        $status        = esc_sql($status);
        $hour = esc_sql($hour);
        $data = array();
        if(!empty($status)){
            $data['time_availability'] = $status;
        }
        if(isset($hour) && !empty($hour)){
            $data['custom_hours'] = $hour;
        } else {
            $data['custom_hours'] = null;
        }

        return $this->db->update("{$this->db->prefix}moo_category",
            $data,
            array( 'uuid' => $uuid )
        );

    }
    function updateItemCustomHour($uuid,$hours) {
        $uuid           = esc_sql($uuid);
        $hours = esc_sql($hours);

        if(!isset($hours) || empty($hours)){
            $hours = null;
        }

        return $this->db->update("{$this->db->prefix}moo_item",
            array("custom_hours" => $hours),
            array( 'uuid' => $uuid )
        );

    }
    function getCategoriesWithCustomHours()
    {
        return $this->db->get_row("SELECT count(*) as nb FROM {$this->db->prefix}moo_category where  time_availability = 'custom' and custom_hours != '' ");
    }
    function getOrderTypesWithCustomHours()
    {
        return $this->db->get_row("SELECT count(*) as nb FROM {$this->db->prefix}moo_order_types where custom_hours != '' ");
    }

    function saveNewOrderGroupModifier($tab) {
        $compteur = 0;
        //Get the number of categories to compare it with the categories that are changed
        $group_number = $this->NbGroupModifier();
        $group_number = $group_number[0]->nb;
        $this->db->query('START TRANSACTION');
        foreach ($tab as $key => $value) {
            $this->db->update("{$this->db->prefix}moo_modifier_group",
                array(
                    'sort_order' => $key
                ),
                array( 'uuid' => $value ));

            $compteur++;
        }
        if($compteur == $group_number)
        {
            $this->db->query('COMMIT');
            return true;
        }
        else {
            $this->db->query('ROLLBACK');
            return false;
        }

    }

    public function saveNewOrderModifier($group,$tab){
        $compteur = 0;
        //Get the number of categories to compare it with the categories that are changed

        $cats_number = $this->NbModifier($group);
        $cats_number = $cats_number[0]->nb;

        $this->db->query('START TRANSACTION');

        foreach ($tab as $key => $value) {
            $this->db->update("{$this->db->prefix}moo_modifier",
                array(
                    'sort_order' => $key
                ),
                array( 'uuid' => $value ));

            $compteur++;
        }

        if($compteur == $cats_number)
        {
            $this->db->query('COMMIT');
            return true;
        }
        else {
            $this->db->query('ROLLBACK');
            return false;
        }

    }
    public function updateOneCategory($category) {
        $this->db->query('START TRANSACTION');
        try {
            //Get current items sort order
            $currentSorts = $this->db->get_results("SELECT item_uuid,sort_order from {$this->db->prefix}moo_items_categories where category_uuid = '{$category["id"]}'",'OBJECT_K');
            // remove old categories items associations
            $this->db->delete("{$this->db->prefix}moo_items_categories",array('category_uuid'=>$category["id"]));

            foreach ($category["items"]["elements"] as $item) {
                if (!empty($currentSorts[$item["id"]]->sort_order)){
                    $sortOrder = intval($currentSorts[$item["id"]]->sort_order);
                } else {
                    $sortOrder = null;
                }

                $this->db->insert("{$this->db->prefix}moo_items_categories", array(
                    'category_uuid' => $category["id"],
                    'item_uuid' => $item["id"],
                    'sort_order' => $sortOrder,
                ));
            }

            if ($this->db->get_var("SELECT COUNT(*) FROM {$this->db->prefix}moo_category where uuid='{$category["id"]}'") > 0) {
                $this->db->update("{$this->db->prefix}moo_category", array(
                    'name' => $category["name"],
                    'items' => null,
                    'items_imported' => 1,
                ), array('uuid' => $category["id"]));
            } else {
                $this->db->insert("{$this->db->prefix}moo_category", array(
                    'uuid' => $category["id"],
                    'name' => $category["name"],
                    'sort_order' => $category["sortOrder"],
                    'show_by_default' => 1,
                    'items' => null,
                    'items_imported' => 1,
                ));
            }

            $this->db->query('COMMIT');
            return true;
        } catch (Exception $e){
            $this->db->query('ROLLBACK');
            return false;
        }
    }
    public function updateOneOrderType($orderType) {
        if ($this->db->get_var("SELECT COUNT(*) FROM {$this->db->prefix}moo_order_types where ot_uuid='{$orderType["id"]}'") > 0)
            $res = $this->db->update("{$this->db->prefix}moo_order_types", array(
                'label' => $orderType["label"],
                'taxable' => $orderType["taxable"],
            ), array('ot_uuid' => $orderType["id"]));
        else
            $res = $this->db->insert("{$this->db->prefix}moo_order_types", array(
                'ot_uuid' => $orderType["id"],
                'label' => $orderType["label"],
                'taxable' => $orderType["taxable"],
                'status' => 0,
                'show_sa' => 0,
                'sort_order' => 0,
                'type' => 0,
                'minAmount' => '0',
            ));
        if ($res > 0)
            return true;
        return false;
    }
    public function updateOneTaxRate($tax) {
        if ($this->db->get_var("SELECT COUNT(*) FROM {$this->db->prefix}moo_tax_rate where uuid='{$tax["id"]}'") > 0)
            $res = $this->db->update("{$this->db->prefix}moo_tax_rate", array(
                'name' => $tax["name"],
                'rate' => $tax["rate"],
                'is_default' => $tax["isDefault"]
            ), array('uuid' => $tax["id"]));
        else
            $res = $this->db->insert("{$this->db->prefix}moo_tax_rate", array(
                'uuid' => $tax["id"],
                'name' => $tax["name"],
                'rate' => $tax["rate"],
                'is_default' => $tax["isDefault"]
            ));
        if ($res > 0)
            return true;
        return false;
    }
    public function updateOneModifier($modifier) {
        $this->db->hide_errors();
        $modifierUuid = esc_sql($modifier["id"]);
        $currentModifier = $this->db->get_row("SELECT * FROM {$this->db->prefix}moo_modifier where uuid='{$modifierUuid}'");

        if ( $currentModifier ) {
            if(isset($currentModifier->alternate_name) && !empty($currentModifier->alternate_name)){
                $res = $this->db->update("{$this->db->prefix}moo_modifier", array(
                    'name' => $modifier["name"],
                    'price' => $modifier["price"],
                    'group_id' => $modifier["modifierGroup"]["id"]
                ), array('uuid' => $modifier["id"]));
            } else {
                $res = $this->db->update("{$this->db->prefix}moo_modifier", array(
                    'name' => $modifier["name"],
                    'price' => $modifier["price"],
                    'alternate_name' => $modifier["alternateName"],
                    'group_id' => $modifier["modifierGroup"]["id"]
                ), array('uuid' => $modifier["id"]));
            }

        } else {
            $res = $this->db->insert("{$this->db->prefix}moo_modifier", array(
                'uuid' => $modifier["id"],
                'name' => $modifier["name"],
                'alternate_name' => $modifier["alternateName"],
                'price' => $modifier["price"],
                'show_by_default' => 1,
                'group_id' => $modifier["modifierGroup"]["id"]
            ));
        }
        return $res > 0;
    }

    /*
     * Update only one modifier group
     */
    public function updateOneModifierGroup($modifierGroup) {
        $modifierGroupUuid = esc_sql($modifierGroup["id"]);
        $currentModifierG = $this->db->get_row("SELECT * FROM {$this->db->prefix}moo_modifier_group where uuid='{$modifierGroupUuid}'");
        if ( $currentModifierG ) {
            if(isset($currentModifierG->alternate_name) && !empty($currentModifierG->alternate_name)){
                $res = $this->db->update("{$this->db->prefix}moo_modifier_group", array(
                    'name' => $modifierGroup["name"],
                    'min_required' => $modifierGroup["minRequired"],
                    'max_allowd' => $modifierGroup["maxAllowed"]
                ), array('uuid' => $modifierGroup["id"]));
            } else {
                $res = $this->db->update("{$this->db->prefix}moo_modifier_group", array(
                    'name' => $modifierGroup["name"],
                    'min_required' => $modifierGroup["minRequired"],
                    'max_allowd' => $modifierGroup["maxAllowed"],
                    'alternate_name' => $modifierGroup["alternateName"]
                ), array('uuid' => $modifierGroup["id"]));
            }
        } else {
            $res = $this->db->insert("{$this->db->prefix}moo_modifier_group", array(
                'uuid' => $modifierGroup["id"],
                'name' => $modifierGroup["minRequired"]->name,
                'alternate_name' => $modifierGroup["alternateName"],
                'show_by_default' => 1,
                'min_required' => $modifierGroup["minRequired"],
                'max_allowd' => $modifierGroup["maxAllowed"]
            ));
        }

        if ($res > 0)
            return true;

        return false;
    }

    /*
     * Update the modifiers of a group
     */
    public function updateModifiers($modifierGroup){

        $inserted = 0;

        $currentModifiers = array();
        // Get all current modifiers
        $modifiers = $this->db->get_results( $this->db->prepare(
            "SELECT * FROM `{$this->db->prefix}moo_modifier` m WHERE m.group_id = '%s'",
            array($modifierGroup["id"])
        ),'ARRAY_A');

        foreach ($modifiers as $item) {
            $currentModifiers[$item["uuid"]] = $item;
        }
        // Start Transaction
        $this->db->query('START TRANSACTION');

        // Delete current modifiers
        $this->db->delete("{$this->db->prefix}moo_modifier",array('group_id'=>$modifierGroup["id"]));

        // Add modifiers with custom names and sort order from previous data
        if (!empty($modifierGroup["modifiers"]["elements"])){
            foreach ($modifierGroup["modifiers"]["elements"] as $element) {

                //Restore the custom name
                if (!empty($currentModifiers[$element['id']]["alternate_name"])){
                    $alternateName = $currentModifiers[$element['id']]["alternate_name"];
                } else {
                    $alternateName = $element["alternateName"];
                }
                //Restore the default sort order
                if (!empty($currentModifiers[$element['id']]["sort_order"])){
                    $sortOrder = $currentModifiers[$element['id']]["sort_order"];
                } else {
                    $sortOrder = null;
                }
                //Restore the current stats of the modifier
                if (isset($currentModifiers[$element['id']]["show_by_default"])){
                    $showByDefault = $currentModifiers[$element['id']]["show_by_default"];
                } else {
                    $showByDefault = $element["available"];
                }
                //Restore the current value of is_pre_selected
                if (isset($currentModifiers[$element['id']]["is_pre_selected"])){
                    $isPreSelected = $currentModifiers[$element['id']]["is_pre_selected"];
                } else {
                    $isPreSelected = 0;
                }
                //Insert the modifier and count
                $res = $this->db->insert("{$this->db->prefix}moo_modifier", array(
                    'uuid' => $element["id"],
                    'name' => $element["name"],
                    'alternate_name' => $alternateName,
                    'price' => $element["price"],
                    'sort_order' => $sortOrder,
                    'show_by_default' => $showByDefault,
                    'is_pre_selected' => $isPreSelected,
                    'group_id' => $element["modifierGroup"]["id"]
                ));
                if ($res){
                    $inserted++;
                }
            }
        }
        // If all modifiers are added, commit the transaction
        if (isset($modifierGroup["modifiers"]["elements"]) && $inserted === count($modifierGroup["modifiers"]["elements"])){
            $this->db->query('COMMIT');
        } else {
            $this->db->query('ROLLBACK');
        }
        return $inserted;
    }
}