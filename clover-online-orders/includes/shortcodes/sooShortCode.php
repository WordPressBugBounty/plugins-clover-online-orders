<?php


class sooShortCode
{
    /**
     * the plugin settings
     * @var array()
     */
    protected $pluginSettings;

    /**
     * The model of this plugin (For all interaction with the DATABASE ).
     * @access   private
     * @var      Moo_OnlineOrders_Model    Object of functions that call the Database pr the API.
     */
    protected $model;

    /**
     * The model of this plugin (For all interaction with the DATABASE ).
     * @access   private
     * @var Moo_OnlineOrders_SooApi
     */
    protected $api;

    /**
     * use or not alternateNames
     * @var bool
     */
    protected $useAlternateNames;

    /**
     * checkoutPage constructor.
     */
    public function __construct()
    {
        $this->pluginSettings = (array)get_option('moo_settings');
        $this->pluginSettings = apply_filters("moo_filter_plugin_settings",$this->pluginSettings);

        $this->model = new moo_OnlineOrders_Model();
        $this->api   = new Moo_OnlineOrders_SooApi();

        if (isset($this->pluginSettings["useAlternateNames"])) {
            $this->useAlternateNames = ($this->pluginSettings["useAlternateNames"] !== "disabled");
        } else {
            $this->useAlternateNames = true;
        }

    }

    /**
     * Whether the modern cart beta is on for this merchant.
     *
     * Absent means off. A beta must never enable itself, and this ships to ~500 sites.
     *
     * @return bool
     */
    protected function isNewCartEnabled() {
        return self::resolveNewCartEnabled($this->pluginSettings);
    }

    /**
     * How long a browser-held cart survives, in hours.
     *
     * @return int
     */
    protected function newCartExpiryHours() {
        return self::resolveNewCartExpiryHours($this->pluginSettings);
    }

    /**
     * Static, settings-in/answer-out versions of isNewCartEnabled()/
     * newCartExpiryHours(), so a class that isn't a sooShortCode (e.g.
     * Moo_OnlineOrders_Public, which enqueues moo-OnlineOrders-public.js's
     * own SooCartBridge entry point) can resolve the same flag/expiry from
     * its own (filtered) settings array without duplicating the decision
     * logic -- only the "which filtered settings array" part is ever
     * duplicated, never what "on" or the default means.
     *
     * @param array $pluginSettings
     * @return bool
     */
    public static function resolveNewCartEnabled($pluginSettings) {
        return isset($pluginSettings['new_cart_enabled'])
            && $pluginSettings['new_cart_enabled'] === 'on';
    }

    /**
     * @param array $pluginSettings
     * @return int
     */
    public static function resolveNewCartExpiryHours($pluginSettings) {
        $hours = isset($pluginSettings['new_cart_expiry_hours'])
            ? (int) $pluginSettings['new_cart_expiry_hours']
            : 24;
        return $hours > 0 ? $hours : 24;
    }

    /**
     * The modern-cart entries of the `moo_params` JS object, resolved
     * from one (filtered) settings array. Every emitter of moo_params --
     * Moo_OnlineOrders_Public for the site-wide bundle and storePage for
     * the style2 theme's own copy, which replaces it on that page -- must
     * use this so the flags the cart UI reads (coupons, item notes,
     * combined taxes & fees) are present wherever the cart can render.
     *
     * @param array $pluginSettings already run through moo_filter_plugin_settings
     * @return array
     */
    public static function newCartParams($pluginSettings) {
        // Item notes: the per-theme 'allow special instructions for items'
        // switch (manifest default "on"; style1/style3 are the onePage
        // theme), AND the checkout-level use_special_instructions switch.
        $themeId = isset($pluginSettings['default_style']) ? $pluginSettings['default_style'] : 'onePage';
        if ($themeId === 'style3' || $themeId === 'style1') {
            $themeId = 'onePage';
        }
        $notesKey = $themeId . '_allowspecialinstructionforitems';
        $notesEnabled = !isset($pluginSettings[$notesKey]) || $pluginSettings[$notesKey] !== 'off';
        if (isset($pluginSettings['use_special_instructions']) && $pluginSettings['use_special_instructions'] !== 'enabled') {
            $notesEnabled = false;
        }
        $hintKey = $themeId . '_messageforspecialinstruction';
        return array(
            'newCartEnabled'   => self::resolveNewCartEnabled($pluginSettings),
            'newCartKey'       => 'soo_cart:' . get_current_blog_id(),
            'newCartExpiry'    => self::resolveNewCartExpiryHours($pluginSettings),
            'combineTaxesFees' => isset($pluginSettings['combine_taxes_fees']) && $pluginSettings['combine_taxes_fees'] === 'enabled',
            'couponsEnabled'   => isset($pluginSettings['use_coupons']) && $pluginSettings['use_coupons'] === 'enabled',
            'itemNotesEnabled' => $notesEnabled,
            'itemNotesHint'    => isset($pluginSettings[$hintKey]) ? (string) $pluginSettings[$hintKey] : '',
        );
    }

    /**
     * Modern cart (beta): the password-reset flow rendered inside the
     * sign-in card, swapping with the login form in place
     * (sooAuth.showInlineReset()). Three steps, all in the card:
     * email -> code (from the email) -> new password; on success the
     * customer is signed in. Same ids sooAuth.js already binds
     * (#moo-resetPassword-form, #sooInputEmail4Reset); the code and
     * password steps are driven by sooAuth.inlineResetStep().
     */
    protected function renderModernResetForm()
    {
        ob_start();
        ?>
        <form id="moo-resetPassword-form" class="soo-auth-reset" action="#" method="post" style="display:none" data-reset-step="email" aria-label="<?php esc_attr_e('Reset your password', 'moo_OnlineOrders'); ?>">
            <div class="soo-auth-reset__step" data-step="email">
                <p class="soo-auth-reset__hint"><?php esc_html_e("Enter your email and we'll send you a code to reset your password.", 'moo_OnlineOrders'); ?></p>
                <div class="moo-form-group">
                    <label for="sooInputEmail4Reset"><?php _e('Email', 'moo_OnlineOrders'); ?></label>
                    <div class="soo-auth-field soo-auth-field--email">
                        <input type="email" class="moo-form-control sooCheckoutTextInput" id="sooInputEmail4Reset" autocomplete="email" placeholder="<?php esc_attr_e('you@example.com', 'moo_OnlineOrders'); ?>" onkeyup="sooAuth.onInputChange(this)">
                    </div>
                    <span class="moo-error-section"><?php _e('Please enter your email', 'moo_OnlineOrders'); ?></span>
                </div>
                <div class="moo-form-group">
                    <button name="submit" class="sooResetPasswordButton soo-auth-btn soo-auth-btn--primary" type="submit"><?php _e('Reset', 'moo_OnlineOrders'); ?></button>
                </div>
            </div>

            <div class="soo-auth-reset__step" data-step="code" style="display:none">
                <p class="soo-auth-reset__hint" data-soo-reset-message><?php esc_html_e('We sent a verification code to your email. It is valid for 10 minutes.', 'moo_OnlineOrders'); ?></p>
                <div class="moo-form-group">
                    <label for="sooInputResetCode"><?php esc_html_e('Verification code', 'moo_OnlineOrders'); ?></label>
                    <div class="soo-auth-field soo-auth-field--code">
                        <input type="text" class="moo-form-control sooCheckoutTextInput" id="sooInputResetCode" inputmode="numeric" autocomplete="one-time-code" maxlength="8" placeholder="<?php esc_attr_e('Enter your verification code', 'moo_OnlineOrders'); ?>" onkeyup="sooAuth.onInputChange(this)">
                    </div>
                    <span class="moo-error-section"><?php esc_html_e('OTP code not valid', 'moo_OnlineOrders'); ?></span>
                </div>
                <div class="moo-form-group">
                    <button type="button" class="soo-auth-btn soo-auth-btn--primary" onclick="sooAuth.inlineCheckOtp()"><?php esc_html_e('Verify code', 'moo_OnlineOrders'); ?></button>
                </div>
            </div>

            <div class="soo-auth-reset__step" data-step="password" style="display:none">
                <p class="soo-auth-reset__hint"><?php esc_html_e('Choose a new password for your account.', 'moo_OnlineOrders'); ?></p>
                <div class="moo-form-group">
                    <label for="sooInputResetPassword"><?php esc_html_e('New password', 'moo_OnlineOrders'); ?></label>
                    <div class="soo-auth-field soo-auth-field--password">
                        <input type="password" class="moo-form-control sooCheckoutTextInput" id="sooInputResetPassword" autocomplete="new-password" placeholder="<?php esc_attr_e('At least 8 characters', 'moo_OnlineOrders'); ?>" onkeyup="sooAuth.onInputChange(this)">
                        <button type="button" class="soo-auth-field__eye" aria-label="<?php esc_attr_e('Show password', 'moo_OnlineOrders'); ?>" aria-pressed="false" onclick="var i=document.getElementById('sooInputResetPassword');var show=i.type==='password';i.type=show?'text':'password';this.setAttribute('aria-pressed',show?'true':'false');this.classList.toggle('soo-auth-field__eye--on',show);"></button>
                    </div>
                    <span class="moo-error-section"><?php esc_html_e('The password must be at least 8 characters', 'moo_OnlineOrders'); ?></span>
                </div>
                <div class="moo-form-group">
                    <button type="button" class="soo-auth-btn soo-auth-btn--primary" onclick="sooAuth.inlineNewPassword()"><?php esc_html_e('Save new password', 'moo_OnlineOrders'); ?></button>
                </div>
            </div>

            <p class="soo-auth-card__foot"><a href="#" onclick="sooAuth.showInlineReset(false); return false;"><?php esc_html_e('Back to sign in', 'moo_OnlineOrders'); ?></a></p>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * One-time hand-off of a pre-existing PHP-session cart to the
     * browser-held cart (Phase 3, Task 6). Call this only when
     * isNewCartEnabled() is true, once per page render, from
     * checkoutPage.php / cartPage.php.
     *
     * Reads the session cart and, if it holds anything, clears it --
     * items, itemsQte and coupon -- in this same call, unconditionally,
     * before returning. That read-and-clear is the entire once-only
     * guarantee: nothing under the beta ever writes back into the
     * session (every add-to-cart entry point routes through
     * SooCartBridge, which writes to localStorage instead once the flag
     * is on), so a session cart can only ever be a leftover from before
     * the flag was switched on, and this can only ever find one once.
     * Whether the browser actually imports the returned lines (only
     * when its own cart is currently empty -- see
     * SooCartBridge.importSessionCartOnce() in sooCartStore.js) is a
     * client-side decision that this clear does not wait for and must
     * not depend on: leaving the session behind "just in case" would let
     * the same stale cart be handed off again on every later page load,
     * including one where the customer had deliberately emptied their
     * browser cart in between.
     *
     * @param MOO_SESSION $session
     * @return array|null {lines:[{item_uuid,quantity,note,modifiers:[{uuid,qty}]}], coupon:string|null}
     *                     in SooCartStore.add()'s input shape, or null
     *                     when there was nothing to hand off.
     */
    protected function captureSessionCartForImport($session) {
        if ($session->isEmpty("items")) {
            return null;
        }

        $items = $session->get("items");
        $lines = array();
        if (is_array($items)) {
            foreach ($items as $line) {
                if (!$line || !isset($line['item']) || !isset($line['item']->uuid)) {
                    continue;
                }
                $modifiers = array();
                if (isset($line['modifiers']) && is_array($line['modifiers'])) {
                    foreach ($line['modifiers'] as $modUuid => $mod) {
                        $qty = (isset($mod['qty']) && intval($mod['qty']) > 0) ? intval($mod['qty']) : 1;
                        $uuid = (is_array($mod) && isset($mod['uuid'])) ? $mod['uuid'] : $modUuid;
                        $modifiers[] = array('uuid' => $uuid, 'qty' => $qty);
                    }
                }
                $lines[] = array(
                    'item_uuid' => $line['item']->uuid,
                    'quantity'  => isset($line['quantity']) ? intval($line['quantity']) : 1,
                    'note'      => isset($line['special_ins']) ? $line['special_ins'] : '',
                    'modifiers' => $modifiers,
                );
            }
        }

        $coupon = null;
        if (!$session->isEmpty("coupon")) {
            $storedCoupon = $session->get("coupon");
            if (is_array($storedCoupon) && isset($storedCoupon['code'])) {
                $coupon = $storedCoupon['code'];
            }
        }

        // The hand-off, atomic with the read above: whatever happens
        // client-side from here, this session cart is spent.
        $session->delete("items");
        $session->delete("itemsQte");
        $session->delete("coupon");

        if (empty($lines)) {
            return null;
        }

        return array('lines' => $lines, 'coupon' => $coupon);
    }

    /**
     * Holds captureSessionCartForImport()'s result between
     * prepareSessionCartImport() (called early, before $totals/etc. are
     * computed) and localizeSessionCartImport() (called later, once
     * 'sooCartUI' is registered -- see that method's doc comment for
     * why the localize step must happen there and not here). false
     * means "not prepared this request"; null means "prepared, nothing
     * to hand off"; an array is a real payload.
     *
     * @var array|null|false
     */
    protected $sessionCartImportPayload = false;

    /**
     * Step 1 of the session-cart hand-off: read-and-clear the session
     * (via captureSessionCartForImport(), which is where the actual
     * once-only guarantee lives -- see its own doc comment) and stash
     * the result for localizeSessionCartImport() to attach later.
     *
     * Call this EARLY, before any code that reads session totals (e.g.
     * checkoutPage.php calls this before $session->getTotalsV2()), so a
     * stale session cart never renders a moment of session-based totals
     * just before this clears it out from under itself. No-op when the
     * beta flag is off -- callers may call this unconditionally.
     *
     * @return void
     */
    protected function prepareSessionCartImport() {
        if (!$this->isNewCartEnabled()) {
            return;
        }
        $this->sessionCartImportPayload = $this->captureSessionCartForImport(MOO_SESSION::instance());
    }

    /**
     * Step 2: localises prepareSessionCartImport()'s already-captured
     * payload as window.soo_cart_import on the 'sooCartUI' handle.
     *
     * Deliberately NOT 'sooCartStore': that handle is registered and
     * enqueued unconditionally by Moo_OnlineOrders_Public::enqueue_scripts(),
     * which hooks 'wp_enqueue_scripts' and therefore runs -- and gets
     * PRINTED via wp_head() -- before a shortcode's render() (invoked
     * later, from the theme's the_content()) ever executes.
     * wp_localize_script() on an already-printed handle is silently a
     * no-op: verified live (blog 2, 2026-09-03) -- a payload localised
     * on 'sooCartStore' from inside render() never reached
     * window.soo_cart_import at all, while captureSessionCartForImport()
     * had already deleted the session's items in the very same request,
     * permanently losing that cart with nothing importing it.
     *
     * 'sooCartUI' is registered from inside this same render() call
     * chain (cartPage.php's enqueueCartUiAssets() / checkoutPage.php's
     * enqueueCheckoutCartUiAssets()) -- but only IF this method is
     * called AFTER that registration runs; call it too early in
     * checkoutPage.php (where 'sooCartUI' is not registered until deep
     * inside the cart-section HTML block) and wp_localize_script() finds
     * an unregistered handle and silently no-ops exactly the same way.
     * This is why the hand-off is split into two calls: prepare early
     * (session timing), localize late (handle-registration timing).
     * sooCartUI.js's own render() calls
     * SooCartBridge.importSessionCartOnce() before its first
     * SooCartStore.read(), which is what actually consumes this.
     *
     * Safe to call whether or not prepareSessionCartImport() ran, found
     * anything, or the flag is off -- a no-op in every case but "there
     * really is a payload waiting."
     */
    protected function localizeSessionCartImport() {
        if ($this->sessionCartImportPayload !== null && $this->sessionCartImportPayload !== false) {
            wp_localize_script('sooCartUI', 'soo_cart_import', $this->sessionCartImportPayload);
        }
    }

    protected function enqueueFontAwesome() {
        //Font Awesome Styles
        wp_register_style( 'sooFontAwesome', '//cdnjs.cloudflare.com/ajax/libs/font-awesome/5.5.0/css/all.min.css', array(), SOO_VERSION);
        wp_enqueue_style ( 'sooFontAwesome' );
    }
    protected function enqueueSweetAlerts() {
        //SweetAlerts Styles
        wp_register_style( 'moo-sweetalert-css-2',SOO_PLUGIN_URL . '/public/css/dist/sweetalert2.min.css', array(), SOO_VERSION);
        wp_enqueue_style(  'moo-sweetalert-css-2' );

        //SweetAlerts Scripts
        wp_register_script('moo-sweetalert-js-2', SOO_PLUGIN_URL .'/public/js/dist/sweetalert2.min.js',array('jquery','moo-bluebird'), SOO_VERSION, false);
        wp_enqueue_script('moo-sweetalert-js-2');
    }

    protected function enqueueSweetAlerts11Css() {
        wp_register_style('SooSweetalerts',  '//cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), SOO_VERSION);
        wp_enqueue_style('SooSweetalerts');
    }

    protected function enqueueSweetAlerts11Js() {
        //use the new version of Sweetalerts
        wp_register_script('SooSweetalerts', SOO_PLUGIN_URL . '/public/js/dist/sweetalert2-v11-7-10.all.min.js',array(),SOO_VERSION, false);
        wp_enqueue_script('SooSweetalerts');
    }
    protected function enqueueRecaptchaJs($reCAPTCHA_site_key) {
        wp_register_script('SooGoogleRecaptcha',  '//www.google.com/recaptcha/api.js?render='.$reCAPTCHA_site_key,array(),SOO_VERSION, false);
        wp_enqueue_script('SooGoogleRecaptcha');
    }

    protected function enqueueApplePaySDK() {
        wp_register_script('SooApplePAySdk',  '//applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js',array(),SOO_VERSION, false);
        wp_enqueue_script('SooApplePAySdk');
    }

    protected function enqueueModifiersPopUp() {
        //Modifiers Styles
        wp_register_style( 'sooModifiersPopUp',SOO_PLUGIN_URL . '/public/css/dist/sooModifiersSelector.min.css', array(), SOO_VERSION);
        wp_enqueue_style( 'sooModifiersPopUp' );

        // The modifiers panel picks up the same self-hosted Archivo/
        // Spline Sans Mono faces as the cart drawer (see
        // sooModifiersSelector.less's CSS ISOLATION block and
        // public/less/sooFonts.less) -- unconditionally, same as the
        // panel's own CSS reset: this ships to every merchant, not just
        // the new-cart beta.
        $this->enqueueFontsCss();

        // The cart store bundle defines SooCartBridge, which
        // sooModifiersSelector.js's add-to-cart calls go through
        // regardless of the beta flag (the bridge itself decides whether
        // to POST or write to the store) -- so it has to be registered
        // as a dependency here unconditionally, not only when the flag
        // is on.
        $this->enqueueCartStoreJs();

        //Modifiers Scripts
        wp_register_script('sooModifiersPopUp', SOO_PLUGIN_URL .  '/public/js/dist/sooModifiersSelector.min.js', array('jquery','sooCartStore'), SOO_VERSION, false);
        wp_enqueue_script('sooModifiersPopUp');
    }

    /**
     * Registers/enqueues the cart store bundle (SooCartStore + its
     * SooCartBridge adapter). Unconditional -- every add-to-cart call
     * site now goes through SooCartBridge.add(), which itself is what
     * decides (via moo_params.newCartEnabled) whether to POST to the
     * legacy endpoint or write to the browser store, so the bundle must
     * exist on the page whether or not the beta flag is on.
     */
    protected function enqueueCartStoreJs() {
        wp_register_script('sooCartStore', SOO_PLUGIN_URL . '/public/js/dist/sooCartStore.min.js', array(), SOO_VERSION, false);
        wp_enqueue_script('sooCartStore');
    }

    /**
     * Registers/enqueues the self-hosted Archivo/Spline Sans Mono
     * @font-face declarations (public/less/sooFonts.less). Shared by
     * both surfaces that use these faces -- the modifiers panel
     * (enqueueModifiersPopUp(), unconditional) and the cart drawer
     * (each enqueueCartUiAssets(), cartPage.php's and storePage.php's
     * own copy) -- because neither of those two loads reliably
     * whenever the other's UI can appear, so declaring the faces in
     * either one alone would leave the other with no faces to resolve.
     * A single stylesheet enqueued from both call sites still loads
     * only once per page (WordPress dedupes a handle enqueued more
     * than once), so the declaration itself is never duplicated.
     */
    protected function enqueueFontsCss() {
        wp_register_style('sooFonts', SOO_PLUGIN_URL . '/public/css/dist/sooFonts.min.css', array(), SOO_VERSION);
        wp_enqueue_style('sooFonts');
    }
    protected function enqueueCssGrid() {
        //Soo Css Grid
        wp_register_style( 'SooCssGrid',SOO_PLUGIN_URL .  '/public/css/dist/grid12.min.css',array(), SOO_VERSION);
        wp_enqueue_style( 'SooCssGrid' );
    }
    protected function enqueuePublicCss() {
        //Public Soo Css
        wp_enqueue_style( 'sooPublic', SOO_PLUGIN_URL . '/public/css/dist/moo-OnlineOrders-public.min.css', array(), SOO_VERSION );
        wp_enqueue_style( 'sooPublic' );
    }
    protected function enqueueCartJs() {
        //Cart JS
        wp_register_script('moo-script-cart-v3', SOO_PLUGIN_URL . '/public/js/dist/sooCartPage.min.js',array('jquery'), SOO_VERSION, false);
        wp_enqueue_script('moo-script-cart-v3');
    }
    protected function enqueueGiftCardsJs() {
        //GiftCards JS
        wp_register_script('sooGiftCards', SOO_PLUGIN_URL . '/public/js/dist/sooGiftCards.min.js',array('jquery'), SOO_VERSION, false);
        wp_enqueue_script('sooGiftCards');
    }
    protected function enqueueCloverSDK()
    {
        $cloverSkd = (defined('SOO_ENV') && (SOO_ENV === "DEV"))? 'https://checkout.sandbox.dev.clover.com/sdk.js' : 'https://checkout.clover.com/sdk.js';

        //Clover iframe SDK
        wp_register_script('sooCloverSdk', $cloverSkd, array('jquery'), SOO_VERSION, false);
        wp_enqueue_script('sooCloverSdk');
    }
}