<?php
/**
 * Thin wrapper around the unified backend endpoint that returns
 * store_status, pickup_slots, and checkout_settings in one response.
 *
 * No caching: the response includes live runtime data (open/close,
 * ASAP availability, throttled slots) and must be fresh each call.
 *
 * Memoization is per-request only: callers that fetch multiple settings
 * pay for one HTTP call total per page load, not one per setting.
 */
class SooDashboardClient {
    /** @var Moo_OnlineOrders_SooApi */
    private $api;

    /** @var array|false|null per-request memo (false = fetch failed) */
    private $memo = null;

    public function __construct(Moo_OnlineOrders_SooApi $api) {
        $this->api = $api;
    }

    /**
     * Returns the unified endpoint response, or null on failure.
     * Memoized for the duration of the request.
     */
    public function fetch() {
        if ($this->memo !== null) {
            return $this->memo === false ? null : $this->memo;
        }

        $url = $this->getEndpointUrl();
        $headers = array(
            "Accept" => "application/json",
        );

        $response = $this->api->getRequest($url, true, $headers);

        if (!is_array($response)) {
            $this->memo = false;
            return null;
        }

        // Reject error-shaped payloads. A valid response MUST carry at least
        // one of the expected top-level keys. Plain error bodies like
        //   ["message" => "Not Found!"]
        // or ["code" => "invalid_pubkey", ...] are arrays but not usable data.
        $hasExpectedShape = isset($response['checkout_settings'])
            || isset($response['store_status'])
            || isset($response['pickup_slots'])
            || isset($response['merchant']);
        if (!$hasExpectedShape) {
            $this->memo = false;
            return null;
        }

        $this->memo = $response;
        return $response;
    }

    /**
     * Resolve a dot-notation path against the response.
     * Returns null if any segment is missing.
     */
    public function valueAt($path) {
        $data = $this->fetch();
        if ($data === null) {
            return null;
        }
        $segments = explode('.', $path);
        $cursor = $data;
        foreach ($segments as $seg) {
            if (!is_array($cursor) || !array_key_exists($seg, $cursor)) {
                return null;
            }
            $cursor = $cursor[$seg];
        }
        return $cursor;
    }

    private function getEndpointUrl() {
        // Uses the url_api_v2 property already set on the API object
        // per environment (sandbox vs production) — see setApiLinks() in
        // includes/moo-OnlineOrders-sooapi.php.
        return $this->api->url_api_v2 . 'merchants/checkout-settings-v2';
    }
}
