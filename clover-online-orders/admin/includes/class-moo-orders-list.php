<?php
require_once 'class-wp-list-table-moo.php';

class Orders_List_Moo extends WP_List_Table_MOO {

    static protected $total;

    /** Class constructor */
    public function __construct() {

        parent::__construct( array(
            'singular' => __( 'Order', "moo_OnlineOrders"), //singular name of the listed records
            'plural'   => __( 'Orders', "moo_OnlineOrders"), //plural name of the listed records
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
    public static function get_items( $per_page = 20, $page_number = 1 ) {
        if (! class_exists( 'Moo_OnlineOrders_SooApi' ) ){
            require_once plugin_dir_path( dirname(__FILE__) )."../includes/moo-OnlineOrders-sooapi.php";
        }
        $api = new Moo_OnlineOrders_SooApi();
        $orders = $api->getOrdersByPage($per_page,$page_number);
        self::$total = $orders["total"];
        return $orders["data"];
    }
    /** Text displayed when no customer data is available */
    public function no_items() {
        _e( 'No orders available.',"moo_OnlineOrders");
    }

    function column_order_number( $order ) {
        if (isset($order["title"]) && !empty($order["title"]) ){
            if (substr( $order["title"], 0, 3 ) === "SOO"){
                $t = explode(" ",$order["title"]);
                $title = $t[0];
            } else {
                $title = $order["uuid"];
            }
        } else {
            $title = $order["uuid"];
        }
        return
            sprintf( '<a href="?page=%s&action=%s&order_uuid=%s">%s</a>',
                ((isset($_REQUEST['page']))?sanitize_text_field($_REQUEST['page']):''),
                'show_order_detail',
                $order['uuid'],
                $title
            );
    }
    function column_status( $order ) {
        if ( $order["payment_status"] === "PAID"){
            return
                sprintf( '<span style="color: green">%s</span><br/>%s',
                    $order["payment_status"],
                    $order["payment_method"]
                );
        }
        return
            sprintf( '%s<br/>%s',
                $order["payment_status"],
                $order["payment_method"]
            );
    }
    function column_order_type_label( $order ) {
        if (!isset( $order["order_type_label"]) || empty( $order["order_type_label"])){
            if ($order["ordertype"] !== "onDemandDelivery"){
                $order["order_type_label"] = ucfirst($order["ordertype"]);
            }
        }
        return
            sprintf( '%s',
                $order["order_type_label"]
            );
    }
    function column_amount( $order ) {
        return
            sprintf( '$%s',
                number_format((($order["amount"]+$order["tip_amount"])/100),2)
            );
    }
    function column_source( $order ) {
        $img_url =  plugin_dir_url(dirname(__FILE__))."img/";

       //IOS Order
        $iosPos     = strpos(strtolower($order["channel"]), 'ios');
        if ($iosPos !== false ){
            return
                sprintf( '<img src="%s" title="IOS" alt="IOS"/>',
                    $img_url . "ios-icon.svg"
                );
        }
        //Android Order
        $androidPos = strpos(strtolower($order["channel"]), 'android');
        if ($androidPos !== false){
            return
                sprintf( '<img src="%s" title="Android" alt="Android"/>',
                    $img_url . "android-icon.svg"
                );
        }
        //Website Order
        $websitePos = strpos(strtolower($order["channel"]), 'website');

        if ($websitePos !== false){
            return
                sprintf( '<img src="%s" title="Website" alt="Website"/>',
                    $img_url . "website-icon.svg"
                );
        }

        return
            sprintf( '%s',
                $order["channel"]
            );
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
            case 'order_number':
            case 'customer_name':
            case 'order_type_label':
            case 'status':
            case 'amount':
            case 'created_at_hf':
            case 'source':
                if (isset($item[$column_name]))
                    return stripslashes((string)$item[$column_name]);
                return '';
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }
    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns() {
        $columns = array(
            'order_number'    => __( 'Order Number', "moo_OnlineOrders"),
            'customer_name' => __( 'Customer Name',"moo_OnlineOrders"),
            'order_type_label'    => __( 'Order Type',"moo_OnlineOrders"),
            'status' => __( 'Status',"moo_OnlineOrders"),
            'amount' => __( 'Amount',"moo_OnlineOrders"),
            'created_at_hf' => __( 'Order Date',"moo_OnlineOrders"),
            'source' => __('Source',"moo_OnlineOrders")
        );

        return $columns;
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {

        $columns = $this->get_columns();
        $hidden = array();
        $this->_column_headers = array($columns, $hidden, []);

        $per_page     = $this->get_items_per_page( 'moo_items_per_page', 20 );
        $current_page = $this->get_pagenum();
        $this->items  = self::get_items( $per_page, $current_page );
        $total_items  = self::$total;
        $this->set_pagination_args( array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ) );

    }
}
