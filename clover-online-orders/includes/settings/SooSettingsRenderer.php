<?php
/**
 * Renders a managed setting in the admin UI.
 *
 * Customized mode -> editable input matching the field's type.
 * Global mode     -> disabled input populated with the dashboard value,
 *                    plus a "Managed from Dashboard" badge.
 */
class SooSettingsRenderer {
    /**
     * Renders a managed input element. The caller is responsible for
     * providing a matching <label for="{admin_field_id}"> element nearby
     * for accessibility — this helper only emits the <input> and badge.
     *
     * @param string $key  registry key (e.g. 'order_later_minutes')
     * @param array  $opts optional: 'name' (form field name override),
     *                     'extra_class'
     */
    public static function renderField($key, array $opts = array()) {
        $def = SooSettingsRegistry::get($key);
        if ($def === null) {
            return;
        }
        $source = SooSettingsSource::instance();
        $value = $source->get($key);
        $readOnly = $source->isReadOnly($key);
        $name = isset($opts['name']) ? $opts['name'] : 'moo_settings[' . $def['local_key'] . ']';
        $id = $def['admin_field_id'];
        $extraClass = isset($opts['extra_class']) ? $opts['extra_class'] : '';

        switch ($def['type']) {
            case 'bool_toggle':
                self::renderToggle($id, $name, $value, $readOnly, $extraClass);
                break;
            case 'integer':
                self::renderInteger($id, $name, $value, $readOnly, $extraClass);
                break;
            case 'text':
                self::renderText($id, $name, $value, $readOnly, $extraClass);
                break;
            default:
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    trigger_error(
                        "SooSettingsRenderer: unknown type '" . $def['type'] . "' for key '" . $key . "'",
                        E_USER_WARNING
                    );
                }
                self::renderText($id, $name, $value, $readOnly, $extraClass);
                break;
        }

        if ($readOnly) {
            self::renderBadge($def['label']);
        }
    }

    private static function renderToggle($id, $name, $value, $readOnly, $extraClass) {
        $checked = in_array($value, array('on', 'enabled', 'yes', true, 1, '1', 'true'), true) ? 'checked' : '';
        $disabled = $readOnly ? 'disabled' : '';
        echo '<input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" '
            . 'class="' . esc_attr($extraClass) . '" '
            . $checked . ' ' . $disabled . ' value="on">';
    }

    private static function renderInteger($id, $name, $value, $readOnly, $extraClass) {
        $disabled = $readOnly ? 'disabled' : '';
        $val = is_numeric($value) ? intval($value) : '';
        echo '<input type="number" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" '
            . 'class="' . esc_attr($extraClass) . '" '
            . 'value="' . esc_attr((string) $val) . '" ' . $disabled . '>';
    }

    private static function renderText($id, $name, $value, $readOnly, $extraClass) {
        $disabled = $readOnly ? 'disabled' : '';
        echo '<input type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" '
            . 'class="' . esc_attr($extraClass) . '" '
            . 'value="' . esc_attr((string) $value) . '" ' . $disabled . '>';
    }

    private static function renderBadge($label) {
        $title = sprintf(
            /* translators: %s is the human label of the setting */
            esc_attr__('%s is managed from the Smart Online Order Dashboard', 'moo_OnlineOrders'),
            $label
        );
        echo '<span class="soo-managed-badge" title="' . esc_attr($title) . '">'
            . esc_html__('Managed from Dashboard', 'moo_OnlineOrders')
            . '</span>';
    }
}
