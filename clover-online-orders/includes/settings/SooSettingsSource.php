<?php
/**
 * Resolves a setting value to either its local (plugin admin) value
 * or its dashboard-managed value, depending on the active mode.
 *
 * Mode is stored in the moo_settings_source WP option:
 *   - 'customized' (default): always read from local plugin settings
 *   - 'global': for any setting with a global_path, read from the
 *               dashboard response; otherwise fall back to local
 */
class SooSettingsSource {
    private static $instance = null;

    /** @var SooDashboardClient */
    private $client;

    /** @var array */
    private $localSettings;

    /**
     * Returns the singleton resolver. On first call, accepts an optional
     * $api object — callers that already have one (REST routes, public.php,
     * shortcodes) should pass it to avoid instantiating a duplicate
     * Moo_OnlineOrders_SooApi per request.
     *
     * On subsequent calls the $api parameter is ignored — the singleton
     * is already bound to whatever api was passed first.
     */
    public static function instance($api = null) {
        if (self::$instance === null) {
            if ($api === null) {
                $api = new Moo_OnlineOrders_SooApi();
            }
            $client = new SooDashboardClient($api);
            $local = get_option('moo_settings', array());
            self::$instance = new self($client, is_array($local) ? $local : array());
        }
        return self::$instance;
    }

    public function __construct(SooDashboardClient $client, array $localSettings) {
        $this->client = $client;
        $this->localSettings = $localSettings;
    }

    public static function current() {
        $value = get_option('moo_settings_source', 'customized');
        if ($value !== 'global' && $value !== 'customized') {
            // One-time per request: log so admins notice typos in wp-cli sets.
            static $warned = false;
            if (!$warned) {
                error_log("[moo_OnlineOrders] Unexpected moo_settings_source value: " . var_export($value, true) . " — falling back to 'customized'");
                $warned = true;
            }
            return 'customized';
        }
        return $value;
    }

    /**
     * Returns the resolved value for a registered setting key,
     * or null if the key is unknown or unresolvable.
     */
    public function get($key) {
        $def = SooSettingsRegistry::get($key);
        if ($def === null) {
            return null;
        }
        if (self::current() === 'global' && !empty($def['global_path'])) {
            $value = $this->client->valueAt($def['global_path']);
            if ($value !== null) {
                return $value;
            }
            // Dashboard fetch failed or path missing — fall through to local for safety
        }
        return isset($this->localSettings[$def['local_key']])
            ? $this->localSettings[$def['local_key']]
            : null;
    }

    /**
     * Like get(), but throws SooDashboardUnavailableException when in
     * Global mode and the value cannot be resolved (fetch failed OR the
     * mapped path is missing from the response). Use this from any
     * Global-mode consumer that must fail loud rather than silently fall
     * back to local moo_settings.
     *
     * In Customized mode this is identical to get().
     *
     * @throws SooDashboardUnavailableException
     */
    public function getOrThrow($key) {
        $def = SooSettingsRegistry::get($key);
        if ($def === null) {
            return null;
        }
        if (self::current() === 'global' && !empty($def['global_path'])) {
            if ($this->globalFetchFailed()) {
                throw new SooDashboardUnavailableException("Dashboard unreachable for setting '{$key}'");
            }
            $value = $this->client->valueAt($def['global_path']);
            if ($value === null) {
                throw new SooDashboardUnavailableException("Dashboard response missing path '{$def['global_path']}' for setting '{$key}'");
            }
            return $value;
        }
        return isset($this->localSettings[$def['local_key']])
            ? $this->localSettings[$def['local_key']]
            : null;
    }

    /**
     * True when the value is being managed by the dashboard (read-only in admin).
     *
     * In Global mode + dashboard fetch failure: returns true to keep the
     * field disabled (with a "Dashboard unreachable" badge). The admin
     * save handler also short-circuits writes to managed keys when this
     * returns true — so even a curl-bypassing-the-disabled-input attempt
     * cannot overwrite local values during a Global-mode outage.
     */
    public function isReadOnly($key) {
        $def = SooSettingsRegistry::get($key);
        if ($def === null || empty($def['global_path'])) {
            return false;
        }
        return self::current() === 'global';
    }

    /**
     * Direct accessor for the dashboard client (admin renderer needs raw response).
     */
    public function dashboardClient() {
        return $this->client;
    }

    /**
     * True when in Global mode AND the dashboard endpoint failed to respond.
     * Callers use this to render fallback UI / error responses.
     */
    public function globalFetchFailed() {
        if (self::current() !== 'global') {
            return false;
        }
        return $this->client->fetch() === null;
    }
}
