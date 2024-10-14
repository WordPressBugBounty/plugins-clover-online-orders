<?php
require_once 'class-wp-list-table-moo.php';
class Products_List_Moo extends WP_List_Table_MOO {

    protected $allImages = array();
    protected $allHours = array();
    protected $placeholderImg;
    protected $editIcon;
    protected $api;
    private $itemsPageUrl;

    /** Class constructor */
    public function __construct() {
        require_once plugin_dir_path( dirname(__FILE__) )."../includes/moo-OnlineOrders-sooapi.php";
        $this->api = new Moo_OnlineOrders_SooApi();
        $this->itemsPageUrl = admin_url('admin.php?page=moo_items');

        /** Process bulk action */
        $this->process_bulk_action();
        $this->getAllCustomHours();
        $this->getAllImages();
        $this->placeholderImg = plugin_dir_url(dirname(__FILE__))."img/placeholder-150x150.png";
        $this->editIcon = plugin_dir_url(dirname(__FILE__))."img/edit-icon.png";

        parent::__construct( array(
            'singular' => __( 'Item',"moo_OnlineOrders"), //singular name of the listed records
            'plural'   => __( 'Items',"moo_OnlineOrders"), //plural name of the listed records
            'ajax'     => false //should this table support ajax?

        ) );
    }
    /**
     * Retrieve item’s data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public function get_items( $per_page = 20, $page_number = 1 ) {
        global $wpdb;
        $sql = "SELECT items.* FROM {$wpdb->prefix}moo_item as items";
        $sql .= $this->getQueryWhereConditions();
        if ( ! empty( $_REQUEST['orderby'] ) ) {
            $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
            $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
        }
        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
        return $wpdb->get_results( $sql, 'ARRAY_A' );
    }
    private function getAllImages( ) {
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}moo_images";
        $result = $wpdb->get_results( $sql, 'ARRAY_A' );
        foreach ($result as $image){
            if(isset($this->allImages[$image["item_uuid"]])){
                array_push($this->allImages[$image["item_uuid"]],$image);
            } else {
                $this->allImages[$image["item_uuid"]] = array($image);
            }
        }
    }
    private function getAllCustomHours() {
        $this->allHours = $this->api->getMerchantCustomHours('categories');
    }
    private function getOneImage($itemUuid) {
        $link =  $this->placeholderImg;
        if(isset($this->allImages[$itemUuid])){
            foreach ($this->allImages[$itemUuid] as $item_uuid => $image){
                if($image["is_enabled"]  === "1") {
                    if($image["is_default"] === "1"){
                        return $image["url"];
                    } else {
                        $link = $image["url"];
                    }
                }
            }
        }
        return $link;
    }
    private function getCustomHours($hours) {
        if(isset($this->allHours[$hours])){
            return $this->allHours[$hours];
        }
        return '-';
    }

    /**
     * Hide an item.
     *
     * @param $id
     */
    public function hide_item( $id ) {
        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}moo_item",
            array(
                'visible' => '0'
            ),
            array( 'uuid' => $id )
        );
        $this->api->sendEvent([
            "event"=>'updated-item',
            "uuid"=>$id,
        ]);
    }

    /**
     * Show an item.
     *
     * @param $id
     */
    public function show_item( $id ) {
        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}moo_item",
            array(
            'visible' => '1'
            ),
            array( 'uuid' => $id )
        );
        $this->api->sendEvent([
            "event"=>'updated-item',
            "uuid"=>$id,
        ]);
    }

    /**
     * Go out of stock.
     *
     * @param $id
     * @param $status
     */
    public function out_of_stock($id, $status) {
        global $wpdb;
        $res = ($status)?'1':'0';
        $wpdb->update(
            "{$wpdb->prefix}moo_item",
            array(
            'outofstock' => $res
            ),
            array( 'uuid' => $id )
        );
        $this->api->sendEvent([
            "event"=>'updated-item',
            "uuid"=>$id,
        ]);
    }

    public function markItemAsFeatured($id, $status) {
        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}moo_item",
            array(
            'featured' => $status
            ),
            array( 'uuid' => $id )
        );
        $this->api->sendEvent([
            "event"=>'updated-featured-items',
            "uuid"=>$id,
        ]);
    }
    /** Text displayed when no customer data is available */
    public function no_items() {
        _e( 'No items available.',"moo_OnlineOrders");
    }
    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public function record_count() {
        global $wpdb;
        $sql = "SELECT count(*) FROM {$wpdb->prefix}moo_item as items";
        $sql .= $this->getQueryWhereConditions();
        return $wpdb->get_var( $sql );
    }
    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    function column_soo_name( $item ) {

        // create a nonce
        $hide_nonce       = wp_create_nonce( 'moo_hide_item' );
        $show_nonce       = wp_create_nonce( 'moo_show_item' );

        $enable_ot_nonce       = wp_create_nonce( 'moo_enable_ot' );
        $disable_ot_nonce       = wp_create_nonce( 'moo_disable_ot' );

        if (isset($item['description'])){
            $itemDescription = stripslashes((string)$item['description']);
        } else {
            $itemDescription = null;
        }


        if(!empty($item[ "soo_name" ])){
            $itemName = $item['soo_name'];
        } else {
            if(!empty($item[ "alternate_name" ])){
                $itemName = $item['name'] . " (alternate name : ".$item[ "alternate_name" ].")";
            } else {
                $itemName = $item['name'];
            }
        }

        $title = '<div class="mooItemNameSection" id="item-name-section-for-'.$item['uuid'].'">';
        $title .= '<div class="moo-item-name"><strong>' . stripslashes((string)$itemName) . '</strong></div><img onclick="moo_editItemName(event,\''.$item['uuid'].'\')" style="margin-left: 10px;cursor: pointer" src="'.$this->editIcon.'" alt="placeholder" sizes="(max-width: 150px) 100vw, 150px" id="moo-edit-item-name-'.esc_attr($item['uuid']).'">';
        $title .= '</div>';

        if(!empty($itemDescription) && strlen($itemDescription)>255){
            $shortItemDesc  = substr($itemDescription,0,255);
            $shortItemDesc  = $shortItemDesc . "...";
            $title .= "<p style='font-size:11px' class='moo-itemTitle-desc' id='moo-itemTitleDesc-ItemUuid-".esc_attr($item['uuid'])."'>".$shortItemDesc."</p>";
        } else {
            $title .= "<p style='font-size:11px' class='moo-itemTitle-desc' id='moo-itemTitleDesc-ItemUuid-".esc_attr($item['uuid'])."'>".$itemDescription."</p>";
        }

        $actions = array(
            'id' =>"ID: ".esc_attr($item['uuid']),
        );
        if( ! empty( $_REQUEST['filter'] )){
            $filter = esc_attr($_REQUEST['filter']);
        } else {
            $filter = 'all';
        }

        if( ! empty( $_REQUEST['category'] )) {
            $actions['edit'] = sprintf( '<a href="?page=%s&action=%s&item_uuid=%s&category=%s&paged=%s&filter=%s">Add / Edit Images</a>',
                'moo_items', 'update_item',esc_attr($item['uuid']),esc_attr($_REQUEST["category"]),$this->get_pagenum(),$filter);
        } else {
            $actions['edit'] = sprintf( '<a href="?page=%s&action=%s&item_uuid=%s&paged=%s&filter=%s">Add / Edit Images</a>',
                'moo_items', 'update_item',esc_attr($item['uuid']),$this->get_pagenum(),$filter);
        }

        $actions['visibility']  = sprintf( '<a style="%s" id="sooHideAnItem-%s"  data-is-visible="true" href="#" onclick="moo_changeItemVisibility(event, \'%s\')">Hide from the Website</a><a style="%s" id="sooShowAnItem-%s"  data-is-visible="false" href="#" onclick="moo_changeItemVisibility(event, \'%s\')">Show in the Website</a>',
            (($item['visible'] == '1') ? '':'display: none;'), esc_attr($item['uuid']), esc_attr($item['uuid']),  (($item['visible'] == "1") ? 'display: none;':''), esc_attr($item['uuid']), esc_attr($item['uuid']));

        $actions['outofstock']  = sprintf( '<a style="%s" id="sooOutOfStockItem-%s"  data-is-out-of-stock="true" href="#" onclick="moo_markItemAsOutOfStock(event, \'%s\')">Enable Out Of Stock</a><a style="%s" id="sooInStockItem-%s"  data-is-out-of-stock="false" href="#" onclick="moo_markItemAsOutOfStock(event, \'%s\')">Disable Out Of Stock</a>',
            (empty($item['outofstock']) ? '':'display: none;'), esc_attr($item['uuid']), esc_attr($item['uuid']),  (empty($item['outofstock']) ? 'display: none;':''), esc_attr($item['uuid']), esc_attr($item['uuid']));

        $actions['edit_description'] = sprintf( '<a class="moo-edit-description-button" href="#" onclick="moo_editItemDescription(event,\'%s\',\'%s\')">Add / Edit description</a>',
            esc_attr($item['uuid']), esc_js($itemName));

        $actions['featured_item']  = sprintf( '<a style="%s" id="sooMarkAsFeaturedLink-%s"  data-is-featured="true" href="#" onclick="moo_markItemAsFeatured(event, \'%s\')">Mark as Featured</a><a style="%s" id="sooMarkAsUnFeaturedLink-%s"  data-is-featured="false" href="#" onclick="moo_markItemAsFeatured(event, \'%s\')">Unfeature</a>',
            (empty($item['featured']) ? '':'display: none;'), esc_attr($item['uuid']), esc_attr($item['uuid']),  (empty($item['featured']) ? 'display: none;':''), esc_attr($item['uuid']), esc_attr($item['uuid']));

        return $title . $this->row_actions( $actions );
    }
    /**
     * Render a column when no column specific method exists.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'name':
            case 'sku':
            case 'code':
            case 'unit_name':
                return $item[ $column_name ];
            case 'visible':
                if($item[ $column_name ]) {
                    return "<span id='visibility-item-{$item["uuid"]}'>No</span>";
                } else {
                    return "<span id='visibility-item-{$item["uuid"]}'>Yes</span>";
                }
            case 'available':
                if($item[ $column_name ]) {
                    return "<span>Yes</span>";
                } else {
                    return "<span>No</span>";
                }
            case 'outofstock':
                if($item[ $column_name ]) {
                    return "<span id='outofstock-item-{$item["uuid"]}'>Yes</span>";
                } else {
                    return "<span id='outofstock-item-{$item["uuid"]}'>No</span>";
                }
            case 'custom_hours':
                return $this->getCustomHours($item[ $column_name ]);
            case 'price_type':
                return $item[ $column_name ]=="PER_UNIT"?"Per Unit<br>Unit Name:".$item[ "unit_name" ]:$item[ $column_name ];
            case 'price':
                return '$'.round(($item['price']/100),2);
            case 'image':
                return $this->getOneImage($item['uuid']);
            default:
                return $column_name; //Show the whole array for troubleshooting purposes
        }
    }
    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="bulk-hideOrShow[]" value="%s" />', $item['uuid']
        );
    }
    /**
     * Render the image column
     *
     * @param array $item
     *
     * @return string
     */
    function column_image( $item ) {
        $link = $this->getOneImage($item["uuid"]);

        if(!empty($item[ "soo_name" ])){
            $itemName = $item['soo_name'];
        } else {
            if(!empty($item[ "alternate_name" ])){
                $itemName = $item['name'] . " (alternate name : ".$item[ "alternate_name" ].")";
            } else {
                $itemName = $item['name'];
            }
        }

        return sprintf(
            '<a class="mooItemsList-placeholderImg" href="#" onclick="mooEditImageOnItemsPage(event,\'%s\',\'%s\')"><img width="40" height="40" src="%s" alt="placeholder" sizes="(max-width: 150px) 100vw, 150px" id="moo-item-img-%s"></a>',
            $item['uuid'],esc_js($itemName),$link,$item['uuid']
        );
    }
    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns() {
        $columns = array(
            'cb'      => '<input type="checkbox" />',
            'image'      => "",
            'soo_name'    => __( 'Online Name',"moo_OnlineOrders"),
            'name'    => __( 'Clover Name',"moo_OnlineOrders"),
            'price' => __( 'Price',"moo_OnlineOrders"),
            'price_type' => __( 'Price Type',"moo_OnlineOrders"),
            'outofstock' => __( 'Out Of Stock',"moo_OnlineOrders"),
            'visible' => __( 'is Hidden',"moo_OnlineOrders"),
            'available' => __( 'Available',"moo_OnlineOrders"),
            'custom_hours' => __( 'Custom Hours',"moo_OnlineOrders"),

        );

        return $columns;
    }
    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'soo_name' => array( 'soo_name', true ),
            'price' => array( 'price', false ),
            'outofstock' => array( 'outofstock', false ),
            'available' => array( 'available', false ),
        );

        return $sortable_columns;
    }
    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = array(
            'bulk-show' => 'Show Items',
            'bulk-hide' => 'Hide Items',
            'bulk-enable-ot' => 'Enable Out of stock',
            'bulk-disable-ot' => 'Disable Out of stock',
            'bulk-feature' => 'Mark as Featured items',
            'bulk-unfeature' => 'Mark as Regular items',
        );

        return $actions;
    }
    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {

       // $this->_column_headers = $this->get_column_info();
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        /** Process bulk action */
        //$this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'moo_items_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items  = $this->record_count();

        $this->set_pagination_args( array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ) );


        $this->items = $this->get_items( $per_page, $current_page );
    }
    public function process_bulk_action() {
        $queryArgs =  array('paged'=>$this->get_pagenum());

        if( ! empty( $_REQUEST['filter'] )){
            $queryArgs[] = array(
                "filter" => esc_attr($_REQUEST['filter'])
            );
        }

        if( ! empty( $_REQUEST['category'] )){
            $queryArgs[] = array(
                "category" => esc_attr($_REQUEST['category'])
            );
        }
        $redirect_url  = add_query_arg($queryArgs, $this->itemsPageUrl );
        //Detect when a single action is being triggered...
        if ( 'hide' === $this->current_action() ) {

            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'moo_hide_item' ) ) {
                die( 'You are not permitted to perform this action' );
            } else {
                $this->hide_item(sanitize_text_field($_GET['item']));
                wp_safe_redirect($redirect_url);
                exit;
            }

        }
        if ( 'show' === $this->current_action() ){
            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );
            if ( ! wp_verify_nonce( $nonce, 'moo_show_item' ) ) {
                die( 'You are not permitted to perform this action' );
            }
            else {
                $this->show_item(sanitize_text_field($_GET['item']));
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
        if ( 'enable_ot' === $this->current_action() ) {
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'moo_enable_ot' ) ) {
                die( 'You are not permitted to perform this action' );
            } else {
                $this->out_of_stock(sanitize_text_field($_GET['item']),true);
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
        if ( 'disable_ot' === $this->current_action() ) {
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );
            if ( ! wp_verify_nonce( $nonce, 'moo_disable_ot' ) ) {
                die( 'You are not permitted to perform this action' );
            } else {
                $this->out_of_stock(sanitize_text_field($_GET['item']),false);
                wp_safe_redirect($redirect_url);
                exit;
            }
        }

        // If the delete bulk action is being triggered...
        if ( 'bulk-hide' === $this->current_action() ) {

            $hide_ids = esc_sql( $_POST['bulk-hideOrShow'] );
            // loop over the array of record IDs and delete them
            foreach ( $hide_ids as $id ) {
               $this->hide_item( esc_sql($id) );
            }
            wp_safe_redirect($redirect_url);
            exit;
         }
        if ( 'bulk-show' === $this->current_action() ) {

            $show_ids = esc_sql( $_POST['bulk-hideOrShow'] );
            // loop over the array of record IDs and delete them
            foreach ( $show_ids as $id ) {
                $this->show_item( esc_sql($id) );
            }

            wp_safe_redirect($redirect_url);
            exit;
        }
        if ( 'bulk-enable-ot' === $this->current_action() ) {

            $enable_ids = esc_sql( $_POST['bulk-hideOrShow'] );
            // loop over the array of record IDs and delete them
            foreach ( $enable_ids as $id ) {
                $this->out_of_stock( esc_sql($id),true);
            }
            wp_safe_redirect($redirect_url);
            exit;
        }
        if ( 'bulk-disable-ot' === $this->current_action() ) {

            $disable_ids = esc_sql( $_POST['bulk-hideOrShow'] );
            // loop over the array of record IDs and delete them
            foreach ( $disable_ids as $id ) {
                $this->out_of_stock( esc_sql($id),false );
            }
            wp_safe_redirect($redirect_url);
            exit;
        }

        if ( 'bulk-feature' === $this->current_action() ) {
            $ids = esc_sql( $_POST['bulk-hideOrShow'] );
            // loop over the array of record IDs and delete them
            foreach ( $ids as $id ) {
                $this->markItemAsFeatured( $id, true );
            }

            wp_safe_redirect($redirect_url);
            exit;
        }
        if ( 'bulk-unfeature' === $this->current_action() ) {

            $ids = esc_sql( $_POST['bulk-hideOrShow'] );
            // loop over the array of record IDs and delete them
            foreach ( $ids as $id ) {
                $this->markItemAsFeatured( esc_sql($id), false );
            }
            wp_safe_redirect($redirect_url);
            exit;
        }


    }
    public function single_row( $item ) {
        if(! $item['visible']) {
            echo "<tr class='item-hidden' id='soo-item-row-{$item["uuid"]}'>";
        } else {
            echo "<tr id='soo-item-row-{$item["uuid"]}'>";
        }
        $this->single_row_columns( $item );
        echo '</tr>';
    }
    function extra_tablenav( $which ) {
        global $wpdb;
        $move_on_url = '&category=';
        if ( $which == "top" || $which == "bottom" ){
            ?>
            <div class="alignleft actions bulkactions">
                <?php
                $cats = $wpdb->get_results("select * from {$wpdb->prefix}moo_category order by sort_order asc", 'ARRAY_A');

                if( $cats ){

                    ?>
                    <select id="moo_cat_filter" class="ewc-filter-cat">
                        <option value="">All categories</option>
                        <?php
                        foreach( $cats as $cat ){
                            $selected = '';
                            if(isset($_GET['category']) && $_GET['category'] == $cat['uuid'] ){
                                $selected = ' selected = "selected"';
                            }
                            ?>
                                <option value="<?php echo esc_attr($cat['uuid']); ?>" <?php echo $selected; ?>><?php
                                    if($cat["alternate_name"]=="" || $cat["alternate_name"]== null ){
                                        echo stripslashes((string)$cat['name']);
                                    } else {
                                        echo stripslashes((string)$cat['alternate_name']);
                                    }
                                    ?></option>
                                <?php
                        }
                        ?>
                    </select>
                    <input type="button" name="filter_action" onclick="moo_filtrer_by_category(event)" class="button" value="Filter">
                    <?php
                }
                ?>
            </div>
            <?php
        }
        if ( $which == "bottom" ){
            //The code that goes after the table is there

        }
    }

    function get_views() {
        $views   = array();
        $current = ( ! empty( $_REQUEST['filter'] ) ? $_REQUEST['filter'] : 'all' );

        //All Actions
        $class        = ( $current == 'all' ? ' class="current"' : '' );
        //$all_url      = remove_query_arg( array( 'filter', 's', 'paged', 'alert', 'user' ) );
        $views['all'] = "<a href='{$this->itemsPageUrl}' {$class} >All Items</a>";
        $views_item   = array(
            'featured'   => array( "name" => "Featured Items", "featured" => 1 )
        );
        foreach ( $views_item as $k => $v ) {
            $custom_url  = add_query_arg( array('filter'=>$k), $this->itemsPageUrl );
            $class       = ( $current == $k ? ' class="current"' : '' );
            $views[ $k ] = "<a href='{$custom_url}' {$class} >" . $v['name'] . "</a>";
        }

        return $views;
    }

    private function getCategoryItemsAsString($category_id) {
        global $wpdb;
        $category_id = esc_sql($category_id);
        $items_uuids_in_query = "";
        $category = $wpdb->get_row("SELECT * from {$wpdb->prefix}moo_category WHERE uuid='{$category_id}'");
        if(empty($category->items) || (isset($category->items_imported) && $category->items_imported) ) {
            $result = $wpdb->get_results("SELECT *
                                    FROM {$wpdb->prefix}moo_items_categories
                                    WHERE category_uuid = '{$category->uuid}'
                                    ",'ARRAY_A');
            foreach ($result as $catItem) {
                $items_uuids_in_query .= "'".$catItem["item_uuid"] . "',";
            }
        } else {
            $category_items =  explode(",",$category->items);
            foreach ($category_items as $category_item) {
                if (strlen($category_item) > 0){
                    $items_uuids_in_query .= "'".$category_item . "',";
                }
            }
        }
        return substr($items_uuids_in_query,0,strlen($items_uuids_in_query)-1);
    }

    private function getQueryWhereConditions() {
        $where = "";
        $featured = !empty($_REQUEST['filter']) && $_REQUEST['filter'] === 'featured';
        $items_uuids_in_query = "";
        if(!empty($_GET['category'])) {
            $items_uuids_in_query = $this->getCategoryItemsAsString($_GET['category']);
        }
        if(!empty($_GET['category'])) {
            if(strlen($items_uuids_in_query)>0) {
                $where = " where hidden = 0 and items.uuid in ({$items_uuids_in_query}) ";
            } else {
                $where = " where 1=-1 ";
            }
        } else {
            $where = " where hidden = 0 ";
        }

        if ( $featured ) {
            $where .= ' and featured=1 ';
        }
        if( !empty($_POST['s']) ) {
            $search_term = esc_sql($_POST['s']);
            $where .= " and ( items.name like '%{$search_term}%' or items.uuid like '{$search_term}' or items.soo_name like '%{$search_term}%')";
        }
        return $where;
    }
}
