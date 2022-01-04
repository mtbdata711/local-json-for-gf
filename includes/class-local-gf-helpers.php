<?php

/**
 * Collection of helper functions used across the plugin.
 *
 * @link       https://github.com/mtbdata711/local-json-for-gf
 * @since      2.0.0
 *
 * @package    Local_JSON_for_Gravity_Forms
 * @subpackage Local_JSON_for_Gravity_Forms/includes
 */

class LocalGFHelpers
{

    /**
     * Boolean check if form is active
     * Called when JSON form is saved to match active / inactive states across environments
     *
     * @param int $form_id - GF Forms id
     * @return int - 1 if true, 0 if not
     */
    public static function is_form_active($form_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gf_form';
        return $wpdb->get_var(
            $wpdb->prepare(
                "
	                SELECT is_active
	                FROM $table_name
	                WHERE id = %d
	            ",
                $form_id
            )
        );
    }

    /**
     * Check if save point directory is writable or not
     * Called on the local_gf/filters/file-permissions-check filter
     * Does not run check if false or WP_Error instance has been passed in by previous filter
     *
     * @param bool|WP_Error - boolean value of a previous filter or instance of WP_Error
     * @param string $directory - directory where JSON files should be saved
     * @return bool|WP_Error - boolean value of a previous filter or instance of WP_Error
     */
    public static function is_writable($bool_or_error, $directory)
    {
        if (!$bool_or_error || $bool_or_error instanceof WP_Error) {
            return $bool_or_error;
        }

        if (!is_writable($directory)) {
            return new WP_Error(LocalGFConstants::PERMISSIONS, "Save point is not writable or does not exist: <b>$directory</b>");
        }

        return true;
    }

    /**
     * Check if save point directory exits or not
     * Called on the local_gf/filters/file-permissions-check filter
     * Does not run check if false or WP_Error instance has been passed in by previous filter
     *
     * @param bool|WP_Error - boolean value of a previous filter or instance of WP_Error
     * @param string $directory - directory where JSON files should be saved
     * @return bool|WP_Error - boolean value of a previous filter or instance of WP_Error
     */
    public static function is_directory($bool_or_error, $directory)
    {
        if (!$bool_or_error || $bool_or_error instanceof WP_Error) {
            return $bool_or_error;
        }

        if (!is_dir($directory)) {
            return new WP_Error(LocalGFConstants::PERMISSIONS, "Save point does not exist:<b> $directory</b>");
        }

        return true;
    }

    /**
     * Check if a single Local GF JSON form is readable or not
     * Called on the local_gf/filters/is-readable-check filter
     *
     * @param string $file_path - path to a JSON file
     * @return bool|WP_Error - true if file is readable or instance of WP_Error
     */
    public static function is_readable($file_path)
    {
        if (!is_readable($file_path)) {
            return new WP_Error(LocalGFConstants::PERMISSIONS, "File does not exist or is not readable: <b>$file_path</b>");
        }

        return true;
    }

    /**
     * Generate admin-post URL for a specific action
     * This function appends a WP nonce and referer link to returned URL
     *
     * @param string $action - WP action
     * @param string $nonce_name - nonce name for the WP nonce
     * @param string $form_key - Local GF form key
     * @param int|null $form_id - GF form id. Defaults to null
     * @return string - admin-post URL with params
     */
    public static function get_action_url($action, $nonce_name, $form_key, $form_id = null)
    {
        $admin_url = admin_url('admin-post.php');
        $nonce = wp_create_nonce($nonce_name);
        $referer = wp_unslash($_SERVER['REQUEST_URI']);
        $args = array(
            "action" => $action,
            "form_key" => $form_key,
            "form_id" => $form_id,
            $nonce_name => $nonce,
            "_wp_http_referer" => urlencode($referer)
        );

        return add_query_arg($args, $admin_url);
    }
}