<?php
require_once 'class-wp-list-table-moo.php';
class Coupons_List_Moo extends WP_List_Table_MOO {
    /**
     * @var mixed
     */
    private $couponPageUrl;

    /** Class constructor */
    public function __construct() {
        $this->couponPageUrl = admin_url('admin.php?page=moo_coupons');
        parent::__construct( array(
            'singular' => __( 'Coupon',"moo_OnlineOrders"), //singular name of the listed records
            'plural'   => __( 'Coupons',"moo_OnlineOrders"), //plural name of the listed records
            'ajax'     => false //should this table support ajax?

        ) );
        /** Process bulk action */
        $this->process_bulk_action();

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
        require_once plugin_dir_path( dirname(__FILE__) )."../includes/moo-OnlineOrders-sooapi.php";
        $api = new Moo_OnlineOrders_SooApi();
        $res = $api->getCoupons($per_page,$page_number-1);
        $res = json_decode($res,true);
        if (isset($res['elements'])) {
            return $res['elements'];
        } else {
            return array();
        }
    }
    /** Text displayed when no customer data is available */
    public function no_items() {
        _e( 'No Coupon available.',"moo_OnlineOrders");
    }

    /** Delete Order */
    public function delete_coupon($code) {
        require_once plugin_dir_path( dirname(__FILE__) )."../includes/moo-OnlineOrders-sooapi.php";
        $api = new Moo_OnlineOrders_SooApi();
        $api->deleteCoupon($code);
        return true;
    }
    public function enable_coupon($code,$status) {
        require_once plugin_dir_path( dirname(__FILE__) )."../includes/moo-OnlineOrders-sooapi.php";
        $api = new Moo_OnlineOrders_SooApi();
        $res = $api->enableCoupon($code,$status);
        $res = json_decode($res);
        if($res->status=="success")
            return true;
        return false;
    }
    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public function record_count() {
        require_once plugin_dir_path( dirname(__FILE__) )."../includes/moo-OnlineOrders-sooapi.php";
        $api = new Moo_OnlineOrders_SooApi();
        $res = json_decode($api->getNbCoupons());
        return $res->elements;
    }
    /*
     * Get the first Item for an Order
     */
    function column_name( $coupon ) {
        // create a nonce
        $delete_nonce = wp_create_nonce( 'moo_delete_coupon' );
        $title = '<strong>' . stripslashes($coupon['name']) . '</strong>';

        if($coupon['isEnabled']=="1")
            $actions['Disable'] = sprintf( '<a href="?page=%s&paged=%s&action=%s&coupon=%s">Disable</a>', ((isset($_REQUEST['page']))?sanitize_text_field($_REQUEST['page']):''),((isset($_REQUEST['paged']))?$_REQUEST['paged']:''), 'disable',$coupon['code']);
        else
            $actions['Enable']  = sprintf( '<a href="?page=%s&paged=%s&action=%s&coupon=%s">Enable</a>',((isset($_REQUEST['page']))?$_REQUEST['page']:''),((isset($_REQUEST['paged']))?sanitize_text_field($_REQUEST['paged']):''), 'enable',$coupon['code']);

        $actions['Edit']   = sprintf( '<a href="?page=%s&paged=%s&action=%s&coupon=%s">Edit</a>', ((isset($_REQUEST['page']))?$_REQUEST['page']:''), ((isset($_REQUEST['paged']))?sanitize_text_field($_REQUEST['paged']):''), 'edit_coupon',urlencode( $coupon['code']) );
        $actions['Delete'] = sprintf( '<a onclick="mooDeleteCoupon(event)" href="?page=%s&paged=%s&action=%s&coupon=%s&_wpnonce=%s">Delete</a>',((isset($_REQUEST['page']))?sanitize_text_field($_REQUEST['page']):''),((isset($_REQUEST['paged']))?$_REQUEST['paged']:''), 'delete',urlencode($coupon['code']), $delete_nonce );

        return
            sprintf( '%s',$title) . $this->row_actions( $actions );
    }

    public function column_default( $item, $column_name ) {

        switch ( $column_name ) {
            case 'name':
            case 'code':
            case 'type':
            case 'minAmount':
            case 'startdate':
            case 'expirationdate':
                if (isset($item[$column_name]))
                    return stripslashes((string)$item[$column_name]);
                return '';
            case 'uses':
                return $item['maxuses'] > 0 ? stripslashes((string)$item['uses']).' / '.$item['maxuses'] : $item['uses'];
            case 'value':
                return ($item['type']=="amount")?"$".$item['value']:$item['value']."%";
            case 'isEnabled':
                return ($item['isEnabled']=="1")?"<span style='color: green'>Yes</span>":"<span style='color: red'>No</span>";
            default:
                return print_r( $item, true ); //Show the whole array for troubleshooting purposes
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
            '<input type="checkbox" name="bulk-coupon[]" value="%s" />', $item['code']
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
            'name'    => __( 'Coupon Name',"moo_OnlineOrders"),
            'code' => __( 'Code',"moo_OnlineOrders"),
            'value'    => __( 'Value',"moo_OnlineOrders"),
            'type'    => __( 'Type',"moo_OnlineOrders"),
            'minAmount'    => __( 'Min Amount',"moo_OnlineOrders"),
            'uses' => __( 'Number of uses',"moo_OnlineOrders"),
            'isEnabled' => __( 'Is enabled ?',"moo_OnlineOrders"),
            'expirationdate'    => __( 'Expiry date',"moo_OnlineOrders")
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
            '_id' => array( '_id', false )
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
            'bulk-delete' => 'Delete Coupons',
            'bulk-enable' => 'Enable Coupons',
            'bulk-disable' => 'Disable Coupons'
        );

        return $actions;
    }
    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {

        //$this->_column_headers = $this->get_column_info();
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        /** Process bulk action */
        //$this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'moo_items_per_page', 10 );
        $current_page = $this->get_pagenum();
        $total_items  = $this->record_count();

        $this->set_pagination_args( array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ) );


        $this->items = $this::get_items( $per_page, $current_page );
    }
    public function process_bulk_action() {
        //Detect when a bulk action is being triggered...
        if ( 'delete' === $this->current_action() ) {
            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr( $_REQUEST['_wpnonce'] );
            if ( ! wp_verify_nonce( $nonce, 'moo_delete_coupon' ) ) {
                die( 'You are not permitted to perform this action' );
            } else {
                $res = $this->delete_coupon(sanitize_text_field($_GET['coupon']));
                $redirect_url = add_query_arg(array("deleted"=>$res), $this->couponPageUrl);
                wp_safe_redirect( $redirect_url );
                exit;
            }

        }
        if ( 'enable' === $this->current_action() ) {
            $res = $this->enable_coupon(sanitize_text_field($_GET['coupon']),"1");
            $redirect_url = add_query_arg(array("enabled"=>$res), $this->couponPageUrl);
            wp_safe_redirect( $redirect_url );
            exit;
        }

        if ( 'disable' === $this->current_action() ) {
            $res = $this->enable_coupon(sanitize_text_field($_GET['coupon']),"0");
            $redirect_url = add_query_arg(array("disabled"=>$res), $this->couponPageUrl);
            wp_safe_redirect( $redirect_url );
            exit;
        }

        // If the delete bulk action is triggered
        if ( 'bulk-delete' === $this->current_action()) {
            $codes = esc_sql( $_POST['bulk-coupon'] );
            // loop over the array of record IDs and delete them
            foreach ( $codes as $code ) {
                $res = $this->delete_coupon( $code );
            }
            $redirect_url = add_query_arg(array("deleted"=>$res), $this->couponPageUrl);
            wp_safe_redirect( $redirect_url );
            exit;
        }

        if ( 'bulk-enable' === $this->current_action()) {
            $codes = esc_sql( $_POST['bulk-coupon'] );
            // loop over the array of record IDs and delete them
            foreach ( $codes as $code ) {
                $res = $this->enable_coupon( $code, "1" );
            }
            $redirect_url = add_query_arg(array("enabled"=>$res), $this->couponPageUrl);
            wp_safe_redirect( $redirect_url );
            exit;
        }

        if ( 'bulk-disable' === $this->current_action()) {

            $codes = esc_sql( $_POST['bulk-coupon'] );
            // loop over the array of record IDs and delete them
            foreach ( $codes as $code ) {
                $res = $this->enable_coupon( $code, "0" );
            }
            $redirect_url = add_query_arg(array("disabled"=>$res), $this->couponPageUrl);
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
}
