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
        $this->handleWpdbErrors();
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

    function handleWpdbErrors()
    {
        global $wpdb;

        $wpdb->hide_errors(); // always hide from frontend

        if (defined('SOO_DEBUG') && SOO_DEBUG) {
            $wpdb->show_errors(); // optional for local/dev
        }
    }

    /**
     * Get Cart On Simple Format without Qty
     * @return array
     */
    public function getCart() {
        return SooCartPricer::lineItems($this->get("items"));
    }

    /**
     * Get the totals
     * @param int $deliveryCharges
     * @param int $servicesFees
     * @param string $servicesFeesType
     * @return array|bool
     */
    public function getTotals($deliveryCharges = 0,$servicesFees = 0, $servicesFeesType = 'amount'){
        $coupon = $this->isEmpty("coupon") ? null : $this->get("coupon");
        $totals = SooCartPricer::totals($this->get("items"), $coupon, $deliveryCharges, $servicesFees, $servicesFeesType);
        if ($totals === false) {
            return false;
        }
        // The pricer is session-free, so the capped-coupon write the original did inline
        // happens here instead.
        if (!empty($totals['_coupon_writeback'])) {
            $this->set($totals['_coupon_writeback'], "coupon");
        }
        unset($totals['_coupon_writeback']);
        return $totals;
    }

    public function getTotalsV2($notTaxableCharges, $discountedAmount){
        return SooCartPricer::totalsV2($this->get("items"), $notTaxableCharges, $discountedAmount);
    }

}
