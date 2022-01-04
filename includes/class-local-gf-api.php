<?php
/**
 * Core API for this plugin.
 *
 * @link       https://github.com/mtbdata711/local-json-for-gf
 * @since      2.0.0
 *
 * @package    Local_JSON_for_Gravity_Forms
 * @subpackage Local_JSON_for_Gravity_Forms/includes
 */


class LocalGFAPI
{

    /**
     * Get form key from Gravity / LocalGF JSON form
     *
     * @param array $form - GF form array
     * @return string|bool - form key if exists. Otherwise, false.
     */
    public static function get_key_by_form($form)
    {
        $settings = self::get_form_settings($form);

        if (!$settings) {
            return false;
        }

        return $settings[LocalGFConstants::FORM_KEY];
    }

    /**
     * Get plugin settings from Gravity / LocalGF JSON form
     *
     * @param array $form - GF form array
     * @return array|bool - array of plugin settings if exists. Otherwise, false.
     */
    public static function get_form_settings($form)
    {
        if (!self::is_enabled($form)) {
            return false;
        }

        return $form[LocalGFConstants::SETTINGS_KEY];
    }


    /**
     * Boolean check for if a form is enabled for Local JSON
     *
     * @param array $form - GF form array
     * @return bool - true if enabled
     */
    public static function is_enabled($form)
    {
        return array_key_exists(LocalGFConstants::SETTINGS_KEY, $form) && array_key_exists(LocalGFConstants::ENABLED, $form[LocalGFConstants::SETTINGS_KEY]) && $form[LocalGFConstants::SETTINGS_KEY][LocalGFConstants::ENABLED];
    }

    public static function get_form_by_key($key)
    {
        $file_path = self::get_file_path_by_key($key);

        $can_get_file = apply_filters("local_gf/filters/is-readable-check", $file_path);

        if (!$can_get_file || $can_get_file instanceof WP_Error) {
            return $can_get_file;
        }

        $json = file_get_contents($file_path);

        return self::prepare_form($json);
    }

    /**
     * Get path to JSON form by form key
     *
     * @param string $form_key - Local GF form key
     * @return string - path to form
     */
    public static function get_file_path_by_key($form_key)
    {
        $save_point = realpath(self::get_save_point());

        return $save_point . "/" . $form_key . ".json";
    }

    /**
     * Get savepoint where JSON forms should be saved
     * Defaults to /local-gf-json in your active theme directory
     * This can be updated using the local_gf/filters/save-point filter
     *
     * @return string - path to save JSON files
     */
    public static function get_save_point()
    {
        $default_savepoint = get_stylesheet_directory() . "/" . LocalGFConstants::DEFAULT_SAVEPOINT . "/";
        return apply_filters("local_gf/filters/save-point", $default_savepoint);
    }

    /**
     * Decode and sanitize JSON form for use with Gravity Forms
     *
     * @param string $json - JSON form
     * @return array - GF form array
     */
    public static function prepare_form($json)
    {
        $json = json_decode($json, true);

        return GFFormsModel::sanitize_settings(GFFormsModel::convert_field_objects($json[0]));
    }

    /**
     * Get all Local GF forms within the savepoint directory
     *
     * @return array - array of JSON forms. Returns empty array if none exist.
     */
    public static function get_json_forms()
    {
        $prefix = self::get_prefix();
        $save_point = realpath(self::get_save_point()) . "/" . "$prefix*" . ".json";
        $forms = glob($save_point);

        if (!$forms) {
            return array();
        }

        return array_map(function ($path) {
            return self::prepare_form(file_get_contents($path));
        }, $forms);
    }

    /**
     * Get prefix for generating Local GF form keys
     * Defaults to local_gf_
     * This can be overwritten using the local_gf/filters/prefix" filter
     *
     * @return string - Form key prefix
     */
    public static function get_prefix()
    {
        return apply_filters("local_gf/filters/prefix", LocalGFConstants::PREFIX);
    }

    /**
     * Get all Gravity Forms with Local JSON enabled
     * Returned array is keyed by Local GF form key
     *
     * @return array - array of enabled Gravity Forms
     */
    public static function get_valid_forms()
    {
        $forms = GFAPI::get_forms();

        return array_reduce($forms, function ($array, $form) {
            if (!self::is_enabled($form)) {
                return $array;
            }

            $form_key = $form[LocalGFConstants::SETTINGS_KEY][LocalGFConstants::FORM_KEY];
            $array[$form_key] = $form;

            return $array;
        }, array());
    }

    /**
     * Boolean check if a single JSON form can be synced with an existing Gravity Form
     * Returns false if no Gravity Form is found with valid form key
     *
     * @param array $gravity_forms - array of Gravity Forms
     * @param array $form - JSON form to test against
     * @return bool - true if form synced
     */
    public static function can_sync_form($gravity_forms, $form)
    {
        $form_key = $form[LocalGFConstants::SETTINGS_KEY][LocalGFConstants::FORM_KEY];

        if (!array_key_exists($form_key, $gravity_forms)) {
            return false;
        }

        $gravity_form = $gravity_forms[$form_key];
        $form_settings = self::get_form_settings($gravity_form);
        $gravity_form_last_modified = (int)$form_settings[LocalGFConstants::MODIFIED];
        $form_last_modified = (int)$form[LocalGFConstants::SETTINGS_KEY][LocalGFConstants::MODIFIED];

        return ($gravity_form_last_modified !== $form_last_modified);
    }

    /**
     * Boolean check if a single JSON form can be imported
     * Returns false if JSON form currently exists as a Gravity Form or is not enabled
     *
     * @return bool - true if form can be imported
     */
    public static function can_import_form($gravity_forms, $form)
    {
        $form_key = $form[LocalGFConstants::SETTINGS_KEY][LocalGFConstants::FORM_KEY];

        return !array_key_exists($form_key, $gravity_forms) && self::is_enabled($form);
    }

    /**
     * Check if Local GF savepoint exists and is writable
     * By default the local_gf/filters/file-permissions-check filter checks if savepoint directory exists and is writable
     * This filter can be used to add further checks if needed
     *
     * @return mixed - true or WP_Error if directory does not exist or is not writable
     */
    public static function get_file_permissions_status()
    {
        return apply_filters("local_gf/filters/file-permissions-check", true, LocalGFAPI::get_save_point());
    }

    /**
     * Generate key to use for json file names && Local GF form key
     * Keys are generated using the built-in uniqid function: https://www.php.net/manual/en/function.uniqid.php
     * Generated keys are prefixed with local_gf_ by default
     * This can be overwritten using the local_gf/filters/prefix filter
     *
     * @return string - Local GF form key
     */
    public static function generate_key()
    {
        return uniqid(self::get_prefix());
    }

}
