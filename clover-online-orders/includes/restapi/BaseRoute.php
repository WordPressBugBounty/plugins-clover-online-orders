<?php
/**
 * Created by Mohammed EL BANYAOUI.
 * User: Smart MerchantApps
 * Date: 3/5/2019
 * Time: 12:44 PM
 */

class BaseRoute
{
    /*
     * isProduction: it's a flag to hide all php notices in production mode
     */
    protected $isProduction;

    /*
     * version : the plugin version
     */
    protected $version;

    /**
     * @var array
     */
    protected $pluginSettings;

    /**
     * @var bool
     */
    protected $useAlternateNames;

    /**
     * The namespace and the version of the api
     * @var string
     */
    protected $namespace = 'moo-clover/v2';

    protected $v3Namespace = 'moo-clover/v3';

    /**
     * BaseRoute constructor.
     */
    public function __construct() {
        $this->isProduction = ! (defined('SOO_ENV') && (SOO_ENV === "DEV"));
        if(defined('SOO_VERSION')){
            $this->version = SOO_VERSION;
        }
        //Get the plugin settings
        $this->pluginSettings = (array) get_option('moo_settings');

        $this->pluginSettings = apply_filters("moo_filter_plugin_settings",$this->pluginSettings);

        if(isset($this->pluginSettings["useAlternateNames"])){
            $this->useAlternateNames = ($this->pluginSettings["useAlternateNames"] !== "disabled");
        } else {
            $this->useAlternateNames = true;
        }
    }


    public function permissionCheck( $request ) {
        // Extract the nonce from the request
        $nonce = isset($request['_wpnonce']) ? $request['_wpnonce'] : $request->get_header('X-WP-Nonce');

        // Verify nonce for security
        if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('invalid_nonce', 'Security check failed. Please refresh the page and try again.', ['status' => 403]);
        }
        // Check if user has the appropriate capability
        if (!current_user_can('manage_options')) {
            return new WP_Error('forbidden', 'You do not have permission to perform this action.', ['status' => 403]);
        }

        // All checks passed
        return true;
    }
    public static function sortBySortOrder($a,$b)
    {
        if ($a["sort_order"] == $b["sort_order"]) {
            return 0;
        }
        return ($a["sort_order"] < $b["sort_order"]) ? -1 : 1;
    }

    /**
     * @throws Exception
     */
    protected function uploadFileByUrl($image_url ) {

        // If the function it's not available, require it.
        if ( ! function_exists( 'download_url' ) ) {
            // it allows us to use download_url() and wp_handle_sideload() functions
            require_once ABSPATH . '/wp-admin/includes/file.php';
        }

        // download to temp dir
        $temp_file = download_url( $image_url );

        if( is_wp_error( $temp_file ) ) {
            return false;
        }
        $filetype = wp_check_filetype( $temp_file );


        // move the temp file into the uploads directory
        $file = array(
            'name'     => basename( $image_url ),
            'type'     => $filetype['type'],
            'tmp_name' => $temp_file,
            'size'     => filesize( $temp_file ),
        );

        $sideload = wp_handle_sideload(
            $file,
            array(
                'test_form'   => false // no needs to check 'action' parameter
            )
        );

        if( ! empty( $sideload[ 'error' ] ) ) {
            // you may return an error message if you want
            throw new Exception($sideload[ 'error' ]);
        }

        // it is time to add our uploaded image into WordPress media library
        $attachment_id = wp_insert_attachment(
            array(
                'guid'           => $sideload[ 'url' ],
                'post_mime_type' => $sideload[ 'type' ],
                'post_title'     => basename( $sideload[ 'file' ] ),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ),
            $sideload[ 'file' ]
        );

        if( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            return false;
        }

        // update metadata, regenerate image sizes
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        wp_update_attachment_metadata(
            $attachment_id,
            wp_generate_attachment_metadata( $attachment_id, $sideload[ 'file' ] )
        );

        @unlink( $temp_file );

        return $sideload[ 'url' ];

    }

}