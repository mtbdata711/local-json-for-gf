<?php

/**
 * This class is responsible for all admin_post actions for this plugin.
 * Uses core Local_Json_Gravity_Forms_Admin API class as a dependency.
 * 
 *
 * @link       https://github.com/mtbdata711/
 * @since      1.0.0
 *
 * @package    Local_Json_Gravity_Forms
 * @subpackage Local_Json_Gravity_Forms/admin/includes
 */

class Local_Json_Gravity_Forms_Admin_Actions
{
    /**
     * Instance of Local_Json_Gravity_Forms_Admin class.
     *
     * @since    1.0.0
     * @access   private
     * @var      Local_Json_Gravity_Forms_Admin   $api  instance of Local_Json_Gravity_Forms_Admin class.
     */
    private $api;

    /**
     * Initialize the class and set its properties.
     * 
     * @since      1.0.0
     * @param      Local_Json_Gravity_Forms_Admin   $api  instance of Local_Json_Gravity_Forms_Admin class.
     */
    public function __construct($api)
    {
        $this->api = $api;
    }

    /**
     * Handle form imports on Local JSON for Gravity Forms screen in WP admin.
     * Redirects back to Local JSON for Gravity Forms screen.
     * 
     * @since      1.0.0
     * @return     void
     */
    public function local_gf_admin_import_form()
    {
        if (!isset($_POST["local_gf_admin_import_form_nonce"]) || !wp_verify_nonce($_POST["local_gf_admin_import_form_nonce"], "local_gf_admin_import_form")) {
            die();
        }
        $redirect_url = $_POST["_wp_http_referer"];
        $imported_form = $this->api->import_form($_POST["path"]);

        if (!$imported_form) {
            $redirect_url = Local_Json_Gravity_Forms_Views::build_redirect_url_string($redirect_url, "form_import", "error", $imported_form, "local_gf_admin_notice_nonce", "local_gf_admin_notice");
        } else {
            $redirect_url = Local_Json_Gravity_Forms_Views::build_redirect_url_string($redirect_url, "form_import", "success", $imported_form, "local_gf_admin_notice_nonce", "local_gf_admin_notice");
        }

        wp_redirect($redirect_url, 302);
        die();
    }

    /**
     * Handle form updates on Local JSON for Gravity Forms screen in WP admin.
     * Redirects back to Local JSON for Gravity Forms screen.
     * 
     * @since      1.0.0
     * @return     void
     */
    public function local_gf_admin_update_form()
    {
        if (!isset($_POST["local_gf_admin_update_form_nonce"]) || !wp_verify_nonce($_POST["local_gf_admin_update_form_nonce"], "local_gf_admin_update_form")) {
            die();
        }
        $redirect_url = $_POST["_wp_http_referer"];

        $updated_form = $this->api->update_form($_POST["path"]);

        if (!$updated_form) {
            $redirect_url = Local_Json_Gravity_Forms_Views::build_redirect_url_string($redirect_url, "form_updated", "error", $updated_form, "local_gf_admin_notice_nonce", "local_gf_admin_notice");
        } else {
            $redirect_url = Local_Json_Gravity_Forms_Views::build_redirect_url_string($redirect_url, "form_updated", "success", $updated_form, "local_gf_admin_notice_nonce", "local_gf_admin_notice");
        }


        wp_redirect($redirect_url, 302);
        die();
    }
}
