<?php
/**
 * Sanity-check a reCAPTCHA v3 key pair when the merchant saves it.
 *
 * A wrong pair is silent until it is too late: the checkout page still loads, the customer
 * still fills the form, and only the order is rejected — every order, until someone
 * notices. The two mistakes seen in the wild both look identical to a customer:
 *
 *   - the same key pasted into both fields
 *   - a secret from a different Google project, or a typo
 *
 * Neither is detectable from the site key alone, but siteverify tells them apart: probe it
 * with the secret and a token that cannot be valid, and Google answers 'invalid-input-secret'
 * when the secret itself is wrong, or 'invalid-input-response' when the secret is fine and
 * only the token was bad.
 *
 * Advisory by design. The verdict never blocks a save: a firewalled host or a Google outage
 * must not leave a merchant unable to store their own keys.
 */
class SooRecaptchaKeyCheck {

    /** Option holding the last verdict, for the admin notice to render. */
    const NOTICE_OPTION = 'soo_recaptcha_key_notice';

    /**
     * Inspect a key pair.
     *
     * @param string $siteKey
     * @param string $secret
     * @return array {
     *     @type string $status  'ok', 'identical', 'invalid_secret', or 'unknown'
     *     @type string $message Human-readable problem, empty when status is 'ok'.
     * }
     */
    public static function inspect($siteKey, $secret) {
        $siteKey = is_string($siteKey) ? trim($siteKey) : '';
        $secret  = is_string($secret)  ? trim($secret)  : '';

        // Nothing to check: an empty pair simply turns reCAPTCHA off.
        if ($siteKey === '' || $secret === '') {
            return self::verdict('ok');
        }

        if (hash_equals($siteKey, $secret)) {
            return self::verdict(
                'identical',
                __('The Site Key and Secret Key are the same. They are two different values from the Google reCAPTCHA console — with this pair every order is rejected at checkout.', 'moo_OnlineOrders')
            );
        }

        return self::probeSecret($secret);
    }

    /**
     * Ask siteverify whether the secret itself is accepted.
     *
     * The response token is deliberately junk: we are testing the secret, and a bad token
     * with a good secret produces a different error code than a bad secret does.
     *
     * @param string $secret
     * @return array See inspect().
     */
    private static function probeSecret($secret) {
        $response = wp_remote_post(SOO_G_RECAPTCHA_URL, array(
            'method'  => 'POST',
            'timeout' => 5,
            'body'    => array(
                'secret'   => $secret,
                'response' => 'soo-key-check',
            ),
        ));

        if (is_wp_error($response)) {
            return self::verdict('unknown');
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($result) || !isset($result['error-codes']) || !is_array($result['error-codes'])) {
            return self::verdict('unknown');
        }

        if (in_array('invalid-input-secret', $result['error-codes'], true)) {
            return self::verdict(
                'invalid_secret',
                __('Google rejected the Secret Key. Check that it is the Secret Key (not the Site Key) from the same reCAPTCHA v3 site in your Google console — until it is corrected, every order is rejected at checkout.', 'moo_OnlineOrders')
            );
        }

        // 'invalid-input-response' alone means the secret was accepted and only our
        // deliberately-bogus token was refused, which is the result we want.
        return self::verdict('ok');
    }

    /**
     * @param string $status
     * @param string $message
     * @return array
     */
    private static function verdict($status, $message = '') {
        return array('status' => $status, 'message' => $message);
    }

    /**
     * Record a verdict for the next admin page load, or clear it when the pair is fine.
     *
     * @param array $verdict See inspect().
     * @return void
     */
    public static function storeNotice($verdict) {
        if (!is_array($verdict) || empty($verdict['message'])) {
            delete_option(self::NOTICE_OPTION);
            return;
        }

        update_option(self::NOTICE_OPTION, $verdict['message'], false);
    }

    /**
     * Render and clear the stored verdict. Shown once, like a save confirmation.
     *
     * @return void
     */
    public static function renderNotice() {
        $message = get_option(self::NOTICE_OPTION);
        if (empty($message) || !is_string($message)) {
            return;
        }

        delete_option(self::NOTICE_OPTION);

        printf(
            '<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p></div>',
            esc_html__('Google reCAPTCHA:', 'moo_OnlineOrders'),
            esc_html($message)
        );
    }
}
