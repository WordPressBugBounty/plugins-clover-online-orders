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

        // The plugin's reCAPTCHA verifier is one provider on the request-verification hook.
        // Registered once for the whole request: every route extends this class, so an
        // unguarded add_filter here would stack a duplicate per route instance.
        if (!self::$verificationProviderRegistered) {
            self::$verificationProviderRegistered = true;
            add_filter('soo_request_verification', array(__CLASS__, 'recaptchaVerificationProvider'), 20, 2);
        }
    }

    /** @var bool Guards one-time registration of the reCAPTCHA provider. */
    private static $verificationProviderRegistered = false;


    /**
     * Verify a request through the reCAPTCHA providers and return the verdict.
     *
     * One entry point for every surface that needs bot verification — checkout, login,
     * sign-up, password reset. Providers hook soo_request_verification and each returns a
     * verdict { outcome, score, action, hostname, reason }, outcome being 'pass', 'fail',
     * 'unavailable', or 'skipped'. A provider that has already settled the verdict is left
     * untouched by later ones, so the chain is ordered by filter priority.
     *
     * @param array $args {
     *     @type string|null $token         reCAPTCHA token from the client.
     *     @type string      $action        Action the token was minted for.
     *     @type array       $body          Request body, for providers that inspect it.
     *     @type bool        $authenticated Whether a signed-in customer sent the request.
     *     @type string      $surface       'checkout', 'login', 'signup', 'password', ...
     * }
     * @return array Verdict; always carries an 'outcome' key.
     */
    protected function verifyRequest($args) {
        $seed = array(
            'outcome'  => 'skipped',
            'score'    => null,
            'action'   => null,
            'hostname' => null,
            'reason'   => 'no_provider',
        );

        $args = array_merge(array(
            'token'         => null,
            'action'        => null,
            'body'          => array(),
            'authenticated' => false,
            'surface'       => '',
        ), is_array($args) ? $args : array());

        $verdict = apply_filters('soo_request_verification', $seed, $args);

        return (is_array($verdict) && isset($verdict['outcome'])) ? $verdict : $seed;
    }

    /**
     * Convenience wrapper for the two-outcome auth surfaces (login, sign-up, password).
     *
     * Fails closed: a failed check and an unreachable verifier both reject, because a
     * blocked sign-in costs one retry while letting a bot through costs an account. A site
     * with no keys configured ('skipped') is allowed, so those sites keep working unchanged.
     *
     * @param array $args See verifyRequest().
     * @return true|WP_Error
     */
    protected function verifyAuthRequest($args) {
        $verdict = $this->verifyRequest($args);

        if ($verdict['outcome'] === 'pass' || $verdict['outcome'] === 'skipped') {
            return true;
        }

        return new WP_Error(
            'recaptcha_failed',
            __("Oops! It seems there was an issue with the reCAPTCHA. Please try submitting the form again.", "moo_OnlineOrders"),
            array( 'status' => 400 )
        );
    }

    /**
     * reCAPTCHA provider for the soo_request_verification hook.
     *
     * Static so it is registered once for the whole request rather than once per route
     * instance; it resolves its own settings for the same reason. Yields to any provider
     * that has already settled the verdict, and to a site with no key pair configured. The
     * options surface carries no token, so there it only defers.
     *
     * @param array $verdict Verdict so far.
     * @param array $args    See verifyRequest().
     * @return array
     */
    public static function recaptchaVerificationProvider($verdict, $args) {
        if (is_array($verdict) && isset($verdict['outcome'])
            && in_array($verdict['outcome'], array('pass', 'fail', 'unavailable'), true)) {
            return $verdict;
        }

        $settings = apply_filters('moo_filter_plugin_settings', (array) get_option('moo_settings'));
        $siteKey  = !empty($settings['reCAPTCHA_site_key'])   ? $settings['reCAPTCHA_site_key']   : '';
        $secret   = !empty($settings['reCAPTCHA_secret_key']) ? $settings['reCAPTCHA_secret_key'] : '';

        if ($siteKey === '' || $secret === '') {
            return array(
                'outcome'  => 'skipped',
                'score'    => null,
                'action'   => null,
                'hostname' => null,
                'reason'   => 'unconfigured',
            );
        }

        // The options surface asks a merchant-level question and sends no token to verify.
        if (isset($args['surface']) && $args['surface'] === 'options') {
            return is_array($verdict) ? $verdict : array('outcome' => 'skipped', 'reason' => 'options');
        }

        $action   = isset($args['action']) ? $args['action'] : null;
        $token    = isset($args['token'])  ? $args['token']  : null;
        $minScore = self::recaptchaMinScore($args);

        return self::scoreRecaptchaToken($secret, $token, $action, $minScore);
    }

    /**
     * Verify a token with Google and score the result.
     *
     * v3 does not reject anything on its own: siteverify answers success=true for any
     * well-formed, unexpired token minted with the site key — and the site key is public by
     * design, in the page's own JavaScript. The score and the action are the actual verdict.
     *
     * @param string      $secret         reCAPTCHA secret key.
     * @param mixed       $token          Token supplied by the client.
     * @param string|null $expectedAction Action the token must have been minted for.
     * @param float       $minScore       Minimum score to pass.
     * @return array Verdict; see verifyRequest().
     */
    protected static function scoreRecaptchaToken($secret, $token, $expectedAction, $minScore) {
        $verdict = array(
            'outcome'  => 'fail',
            'score'    => null,
            'action'   => null,
            'hostname' => null,
            'reason'   => '',
        );

        if (!is_string($token) || $token === '') {
            $verdict['reason'] = 'missing_token';
            return $verdict;
        }

        // No 'remoteip': it would come from a client-supplied forwarding header.
        $response = wp_remote_post(SOO_G_RECAPTCHA_URL, array(
            'method'  => 'POST',
            'timeout' => 5,
            'body'    => array(
                'secret'   => $secret,
                'response' => $token,
            ),
        ));

        if (is_wp_error($response)) {
            $verdict['outcome'] = 'unavailable';
            $verdict['reason']  = 'transport_error';
            return $verdict;
        }

        $result = json_decode(wp_remote_retrieve_body($response));
        if (!is_object($result) || !isset($result->success)) {
            $verdict['outcome'] = 'unavailable';
            $verdict['reason']  = 'unreadable_response';
            return $verdict;
        }

        return self::judgeRecaptchaResult($result, $expectedAction, $minScore);
    }

    /**
     * Turn a decoded siteverify response into a verdict. Split out so the decision is
     * testable without a network call.
     *
     * @param object      $result         Decoded siteverify response.
     * @param string|null $expectedAction Action the token must have been minted for.
     * @param float       $minScore       Minimum score to pass.
     * @return array See verifyRequest().
     */
    protected static function judgeRecaptchaResult($result, $expectedAction, $minScore) {
        $verdict = array(
            'outcome'  => 'fail',
            'score'    => isset($result->score) ? (float) $result->score : null,
            'action'   => isset($result->action) ? (string) $result->action : null,
            'hostname' => isset($result->hostname) ? (string) $result->hostname : null,
            'reason'   => '',
        );

        if (empty($result->success)) {
            $verdict['reason'] = 'rejected_by_google';
            return $verdict;
        }

        // A token minted on another form — or another site sharing the key pair — says
        // nothing about this request.
        if ($expectedAction !== null && $verdict['action'] !== null && $verdict['action'] !== $expectedAction) {
            $verdict['reason'] = 'action_mismatch';
            return $verdict;
        }

        // v2 keys carry no score; there success is the whole verdict.
        if ($verdict['score'] === null) {
            $verdict['outcome'] = 'pass';
            $verdict['reason']  = 'no_score';
            return $verdict;
        }

        if ($verdict['score'] < $minScore) {
            $verdict['reason'] = 'low_score';
            return $verdict;
        }

        $verdict['outcome'] = 'pass';
        return $verdict;
    }

    /**
     * Minimum score a request must clear, chosen by what it is doing.
     *
     * Placing an order is the abused path, so a guest is held to a higher bar than a
     * signed-in customer, who is far less likely to be automated and is already exempt from
     * the duplicate-cart guard. Other actions sit at Google's default. Filterable, with the
     * request context, so any of these can be tuned without a release.
     *
     * @param array $args See verifyRequest().
     * @return float 0.0 – 1.0
     */
    protected static function recaptchaMinScore($args) {
        $action        = isset($args['action']) ? $args['action'] : '';
        $authenticated = !empty($args['authenticated']);

        if ($action === 'createOrder') {
            $score = $authenticated ? 0.4 : 0.6;
        } else {
            $score = 0.5;
        }

        $score = (float) apply_filters('moo_filter_recaptcha_min_score', $score, $args);

        if ($score < 0) {
            $score = 0.0;
        }
        if ($score > 1) {
            $score = 1.0;
        }

        return $score;
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
}