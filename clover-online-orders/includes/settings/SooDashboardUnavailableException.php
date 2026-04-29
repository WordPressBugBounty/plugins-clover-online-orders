<?php
/**
 * Thrown by SooSettingsSource and DashboardCheckoutMapper when in Global
 * mode but the unified dashboard endpoint is unreachable or returns an
 * unusable payload.
 *
 * Per the 2026-04-22 mode-handling-fixes spec: every Global-mode consumer
 * fails loud on outage rather than silently falling back to local
 * moo_settings. Catchers render an error banner (PHP pages) or return a
 * 503 (REST endpoints).
 */
class SooDashboardUnavailableException extends RuntimeException {
}
