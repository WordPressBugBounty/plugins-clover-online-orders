<?php
/**
 * Created by Mohammed EL BANYAOUI.
 * Sync route to handle all requests to sync the inventory with Clover
 * User: Smart MerchantApps
 * Date: 3/5/2019
 * Time: 12:23 PM
 */
require_once "BaseRoute.php";

class SyncRoutes extends BaseRoute
{
    /**
     * The model of this plugin (For all interaction with the DATABASE ).
     * @access   private
     * @var      Moo_OnlineOrders_Model    Object of functions that call the Database pr the API.
     */
    private $model;

    /**
     * The model of this plugin (For all interaction with the DATABASE ).
     * @access   private
     * @var Moo_OnlineOrders_SooApi
     */
    private $api;

    /**
     * SyncRoutes constructor.
     *
     */
    public function __construct($model, $api){

        parent::__construct();

        $this->model    =     $model;
        $this->api      =     $api;
    }


    // Register our routes.
    public function register_routes(){
        // Update category route
        register_rest_route($this->namespace, '/sync/update_category/(?P<cat_id>[a-zA-Z0-9-]+)', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'syncUpdateCategory'),
                'permission_callback' => '__return_true'
            )
        ));
        // Update item route
        register_rest_route($this->namespace, '/sync/update_item/(?P<item_id>[a-zA-Z0-9-]+)', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'syncUpdateItem'),
                'permission_callback' => '__return_true'
            )
        ));
        // Update all modifiers
        register_rest_route($this->namespace, '/sync/update_modifiers', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'syncUpdateAllModifiers'),
                'permission_callback' => '__return_true'
            )
        ));
        // Update all modifier groups
        register_rest_route($this->namespace, '/sync/update_modifier_groups', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'syncUpdateAllModifierGroups'),
                'permission_callback' => '__return_true'
            )
        ));

        // Update one modifier
        register_rest_route($this->namespace, '/sync/update_modifier/(?P<group_uuid>[a-zA-Z0-9-]+)/(?P<uuid>[a-zA-Z0-9-]+)', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'syncUpdateOneModifier'),
                'permission_callback' => '__return_true'
            )
        ));
        // Update one modifier group
        register_rest_route($this->namespace, '/sync/update_modifier_group/(?P<group_uuid>[a-zA-Z0-9-]+)', array(
            // Here we register the readable endpoint for collections.
            array(
                'methods' => 'GET',
                'callback' => array($this, 'syncUpdateOneModifierGroup'),
                'permission_callback' => '__return_true'
            )
        ));

    }

    /**
     * Auto Sync functions
     * for:
     * Categories, items, Modifiers, Modifier Groups
     */
    function syncUpdateCategory($request) {
        if (empty( $request["cat_id"] )) {
            return new WP_Error( 'category_id_required', 'Category id not found', array( 'status' => 404 ) );
        } else {
            $category_id = sanitize_text_field($request["cat_id"]);
            $category = $this->api->getCategoryWithoutSaving($category_id);
            if (isset($category['id'])){
                if ($this->model->update_category($category)){
                    $this->api->sendEvent([
                        "event"=>"updated-category",
                        "uuid"=>$category_id
                    ]);
                    return 'Category Has Been Updated';
                } else {
                    return 'Category Not Updated';
                }
            } else {
                if (isset($category['message']) && $category['message'] === "Not Found"){
                    $this->model->deleteCategory($request["cat_id"]);
                    $this->api->sendEvent([
                        "event"=>"updated-category",
                        "uuid"=>$category_id
                    ]);
                    return 'Category Has Been Deleted';
                } else {
                    return 'Category not exist';
                }
            }
        }
    }
    function syncUpdateItem($request) {
        if (empty( $request["item_id"] )) {
            return new WP_Error( 'item_id_required', 'Item id not found', array( 'status' => 404 ) );
        } else {
            $categories = [];
            $countUpdatedCategories = 0;
            $item_id = sanitize_text_field($request["item_id"]);
            $cloverItem = $this->api->getItemWithoutSaving($item_id);
            $currentItem = $this->model->getItem($item_id);

            //Update Categories
            $currentItemCategories = $this->model->getItemCategories($item_id);
            if(count($currentItemCategories)>0) {
                foreach ($currentItemCategories as $cate) {
                    $categories[] = $cate["id"];
                }
            }
            if(isset($cloverItem['categories']['elements']) && count($cloverItem['categories']['elements'])>0) {
                foreach ($cloverItem['categories']['elements'] as $category) {
                    $categories[] = $category["id"];
                }
            }
            $categories = array_unique($categories);
            foreach ($categories as $category) {
                $cat = $this->api->getCategoryWithoutSaving($category);
                if (isset($cat['id'])){
                   if ($this->model->update_category($cat)){
                       $countUpdatedCategories++;
                   }
                }
            }
            if(isset($cloverItem['id'])){
                if(isset($currentItem->modified_time) && intval($currentItem->modified_time) === $cloverItem['modifiedTime']){
                    if ($countUpdatedCategories>0){
                        $this->api->sendEvent([
                            "event"=>"updated-item",
                            "uuid"=>$cloverItem['id']
                        ]);
                        return 'The item '.$cloverItem['name'].' categories have been updated.';
                    } else {
                        return 'The item '.$cloverItem['name'].' already up-to-date';
                    }
                } else {
                    $this->api->update_item($cloverItem);
                    $this->api->sendEvent([
                        "event"=>"updated-item",
                        "uuid"=>$cloverItem['id']
                    ]);
                    return 'The item '.$cloverItem['name'].' was updated successfully';
                }
            } else {
                if (isset($cloverItem['message']) && $cloverItem['message'] === "Not Found"){
                    $this->model->hideItem($request["item_id"]);
                    $this->api->sendEvent([
                        "event"=>"updated-item",
                        "uuid"=>$request["item_id"]
                    ]);
                    return 'Item Has Been Hidden';
                } else {
                    return "Item not found on Clover";
                }

            }
        }
    }
    function syncUpdateOneModifier($request) {
        if (empty( $request["uuid"] )) {
            return new WP_Error( 'uuid_required', 'Modifier uuid not found', array( 'status' => 400 ) );
        }
        if (empty( $request["group_uuid"] )) {
            return new WP_Error( 'group_uuid_required', 'Modifier Group uuid not found', array( 'status' => 400 ) );
        }
        $uuid = sanitize_text_field($request["uuid"]);
        $group_uuid = sanitize_text_field($request["group_uuid"]);
        $modifier = $this->api->getOneModifierWithoutSaving($group_uuid,$uuid);
        if(isset($modifier["id"])){
            if($this->model->updateOneModifier($modifier)){
                $response = array(
                    'received_modifier'	 => $modifier,
                    'is_updated'=>true
                );
            } else {
                $response = array(
                    'received_modifier'	 => $modifier,
                    'is_updated'=>false
                );
            }
        } else {
            if (isset($modifier['message']) && $modifier['message'] === "Not Found"){
                $isDeleted = $this->model->deleteModifierInGroup($request["uuid"],$request["group_uuid"]);
                $response = array(
                    'received_modifier'	 => $modifier,
                    'is_updated'=>true,
                    'is_deleted'=>$isDeleted,
                );
            } else {
                $response = array(
                    'received_modifier'	 => null,
                    'is_updated'=>false
                );
            }

        }
        $this->api->sendEvent([
            "event"=>"updated-modifier",
            "uuid"=>$request["uuid"]
        ]);
        return $response;
    }
    function syncUpdateOneModifierGroup($request) {
        if (empty( $request["group_uuid"] )) {
            return new WP_Error( 'group_uuid_required', 'Modifier Group uuid not found', array( 'status' => 400 ) );
        }
        $group_uuid = sanitize_text_field($request["group_uuid"]);
        $modifierGroup = $this->api->getOneModifierGroupWithoutSaving($group_uuid, true);
        if(isset($modifierGroup["id"])){
            $count = 0;
            //Update the Group
            $groupUpdated = $this->model->updateOneModifierGroup($modifierGroup);
            //Update the modifiers
            $count = $this->model->updateModifiers($modifierGroup);
            if($groupUpdated){
                $response = array(
                    'received_modifier_group'	 => $modifierGroup,
                    'updated_modifiers'	 => $count,
                    'is_updated'=>true
                );
            } else {
                $response = array(
                    'received_modifier_group'	 => $modifierGroup,
                    'updated_modifiers'	 => $count,
                    'is_updated'=>false
                );
            }
        } else {
            if (isset($modifierGroup['message']) && $modifierGroup['message'] === "Not Found"){
                $deletedModifiers = 0;
                $modifiers = $this->model->getModifiers($request["group_uuid"]);
                foreach ($modifiers as $item) {
                    if ($this->model->deleteModifier($item->uuid)){
                        $deletedModifiers++;
                    }
                }
                $isGroupDeleted = $this->model->deleteModifierGroup($request["group_uuid"]);
                $response = array(
                    'received_modifier_group'	 => $modifierGroup,
                    'updated_modifiers'	 => 0,
                    'deleted_modifiers'	 => $deletedModifiers,
                    'is_updated'=>false,
                    'is_deleted'=>$isGroupDeleted
                );
            } else {
                $response = array(
                    'received_modifier_group'	 => null,
                    'updated_modifiers'	 => 0,
                    'is_updated'=>false
                );
            }
        }
        $this->api->sendEvent([
            "event"=>"updated-modifier-group",
            "uuid"=>$request["group_uuid"]
        ]);
        return $response;
    }
    function syncUpdateAllModifiers($request) {
        $compteur = 0;
        $res = $this->api->getModifiersWithoutSaving();
        if($res){
            foreach ($res as $modifier) {
                if($this->model->updateOneModifier($modifier))
                    $compteur++;
            }
            $response = array(
                'modifers_received'	 => @count($res),
                'modifier_updated'=>$compteur
            );
        } else {
            $response = array(
                'modifer_received'	 => 0,
                'modifier_updated'=>$compteur
            );
        }
        $this->api->sendEvent([
            "event"=>"updated-modifiers"
        ]);
        return $response;
    }
    function syncUpdateAllModifierGroups($request) {
        $compteur = 0;
        $res  = $this->api->getModifiersGroupsWithoutSaving();
        if($res){
            foreach ($res as $modifierG) {
                if($this->model->updateOneModifierGroup($modifierG)) {
                    $compteur++;
                }
            }
            $response = array(
                'modifer_groups_received'	 => @count($res),
                'modifer_groups_updated'=>$compteur
            );
        } else {
            $response = array(
                'modifer_groupsreceived'	 => 0,
                'modifer_groups_updated'=>$compteur
            );
        }
        $this->api->sendEvent([
            "event"=>"updated-modifier-groups"
        ]);
        return $response;
    }

}