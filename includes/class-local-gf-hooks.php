<?php
/**
 * Core hooks called by this plugin.
 *
 * @link       https://github.com/mtbdata711/local-json-for-gf
 * @since      2.0.0
 *
 * @package    Local_JSON_for_Gravity_Forms
 * @subpackage Local_JSON_for_Gravity_Forms/includes
 */

class LocalGFHooks
{


    /**
     * Register plugin settings with Gravity Forms
     * Some settings are not saved settings and only display status updates
     *
     * @param array $form - GF form array
     * @return array - array of plugin settings
     */
    public static function register_settings($form)
    {
        $form_key = LocalGFAPI::get_key_by_form($form);

        return array(
            array(
                'title' => esc_html__('Local JSON For Gravity Forms', 'local-gf'),
                'fields' => array(
                    array(
                        'label' => esc_html__('Enable form', 'local-gf'),
                        'type' => 'checkbox',
                        'name' => 'enabled',
                        'tooltip' => esc_html__('Save JSON copy of this form on save?', 'local-gf'),
                        'choices' => array(
                            array(
                                'label' => esc_html__('Enabled', 'local-gf'),
                                'name' => LocalGFConstants::ENABLED,
                            ),
                        ),
                    ),
                    array(
                        "label" => esc_html__('Form key', 'local-gf'),
                        "type" => "text",
                        "class" => "gf_readonly",
                        'tooltip' => esc_html__('This key is used internally to keep track of your form', 'local-gf'),
                        "readonly" => "readonly",
                        "name" => LocalGFConstants::FORM_KEY,
                        "value" => $form_key ? $form_key : LocalGFAPI::generate_key()
                    ),
                    array(
                        "label" => esc_html__('Last modified', 'local-gf'),
                        "type" => "hidden",
                        "name" => LocalGFConstants::MODIFIED,
                        "value" => time()
                    ),
                    array(
                        "label" => esc_html__('Save point', 'local-gf'),
                        "type" => "savepoint",
                        "name" => "savepoint",
                    ),
                    array(
                        "label" => esc_html__('Last sync', 'local-gf'),
                        "type" => "last_sync",
                        "name" => "last_sync",
                    ),
                    array(
                        "label" => esc_html__('Actions', 'local-gf'),
                        "type" => "actions",
                        "name" => "actions",
                    )
                ),
            ),
        );
    }

    /**
     * Format and save JSON representation of form on update
     * Runs on gform_after_save_form && local_gf/actions/save hook.
     *
     * @param array $form - GF form array
     * @return     void
     */
    public static function save($form)
    {
        if (!LocalGFAPI::is_enabled($form)) {
            return false;
        }

        /**
         * Apply file-permissions-check filter before JSON save
         * By default the local_gf/filters/file-permissions-check filter checks if savepoint directory exists and is writable
         *
         * @param string - Local GF save point
         * @return mixed - true or WP_Error if directory does not exist or is not writable
         */
        $can_save_form = apply_filters("local_gf/filters/file-permissions-check", true, LocalGFAPI::get_save_point());

        if (!$can_save_form || is_wp_error($can_save_form)) {
            return LocalGFViews::add_permissions_error_notice($can_save_form);
        }

        $form_id = $form["id"];
        $form_key = $form[LocalGFConstants::SETTINGS_KEY][LocalGFConstants::FORM_KEY];
        $path = LocalGFAPI::get_file_path_by_key($form_key);

        $forms = GFFormsModel::get_form_meta_by_id($form_id);
        $forms = GFExport::prepare_forms_for_export($forms);

        $forms = array_map(function ($form) {
            if (!is_array($form)) {
                return $form;
            }

            if (!array_key_exists(LocalGFConstants::MODIFIED, $form[LocalGFConstants::SETTINGS_KEY])) {
                $form[LocalGFConstants::SETTINGS_KEY][LocalGFConstants::MODIFIED] = time();
            }

            $form["is_active"] = LocalGFHelpers::is_form_active($form["id"]);

            return $form;
        }, $forms);

        $forms['version'] = GFForms::$version;

        /**
         * Apply before-save filter on Gravity Forms data before JSON save.
         * This filter provides an opportunity to sanitize // amend any data or update settings
         * before JSON is saved.
         *
         * Defaults to the current value of $forms
         *
         * @param array $forms - GF form array
         *
         * @return     array                          filtered GF form array
         * @since      1.0.0
         */
        $forms = apply_filters("local_gf/filters/before-save", $forms);
        $forms = json_encode($forms);

        $saved_form = file_put_contents($path, $forms);

        if ($saved_form === false) {
            $saved_form = new WP_Error("local_gf_save", "Form JSON could not be saved");
        }

        /**
         * Dispatch action after JSON representation has been saved
         * This action allows an opportunity for any clean-up // functionality after
         * a JSON form has been saved
         *
         *
         * @param array $form - GF form array that has been saved.
         * @param int $form_id - GF form id.
         * @param int|WP_Error $saved_form - Number of bytes that were written to the file or WP_Error instance
         *
         * @since      1.0.0
         */
        do_action("local_gf/actions/after-save", $form, $form_id, $saved_form);

        if (is_wp_error($saved_form)) {
            return LocalGFViews::add_save_error_notice($saved_form);
        }

        LocalGFViews::add_save_success_notice();

        return $saved_form;
    }

    /**
     * Update Gravity Forms form using JSON representation of a single form
     * Setup success / error messages and redirects
     * Called on local_gf_admin_update_form hook
     *
     * @return void
     */
    public static function update()
    {
        $data = $_REQUEST;

        $nonce_field = LocalGFConstants::UPDATE_NONCE;
        self::validate_request($data, $nonce_field, $nonce_field);

        $redirect_url = $data["_wp_http_referer"];
        $form_id = $data["form_id"];
        $form_key = $data["form_key"];

        $form = LocalGFAPI::get_form_by_key($form_key);

        if (is_wp_error($form)) {
            $redirect_url = LocalGFViews::get_notice_url($redirect_url, "update", "failure", LocalGFConstants::FORM_KEY, $form_key);
            wp_redirect($redirect_url, 302);
            die();
        }

        $updated_form = self::update_one($form, $form_id);

        if (is_wp_error($updated_form)) {
            $redirect_url = LocalGFViews::get_notice_url($redirect_url, "update", "failure", LocalGFConstants::FORM_KEY, $form_key);
        } else {
            $redirect_url = LocalGFViews::get_notice_url($redirect_url, "update", "success", LocalGFConstants::FORM_ID, $form_id);
        }

        wp_redirect($redirect_url, 302);
        die();
    }

    /**
     * Check if request plugin actions - sync / import - are valid requests
     * Request is terminated if invalid request
     *
     * @param array $data - $_REQUEST global array
     * @param string $nonce_name - name of WP nonce to check. This should be a key in $_REQUEST global
     * @param string $action - name of WP nonce action to check
     *
     * @return void
     */
    public static function validate_request($data, $nonce_name, $action)
    {
        if (!array_key_exists($nonce_name, $data)) {
            wp_die("Unauthorized request: no nonce field", "Local GF", 401);
        }

        if (!wp_verify_nonce($data[$nonce_name], $action)) {
            wp_die("Unauthorized request: invalid nonce supplied", "Local GF", 401);
        }
    }

    /**
     * Update Gravity Forms form using JSON representation of a single form
     * Calls local_gf/filters/before-sync before form is updated
     * Dispatches local_gf/actions/after-sync action after form has been updated
     *
     * @param $form - Local GF form to update
     * @param $form_id - Gravity Forms form id
     * @return int|WP_Error - Gravity Forms ID on success. WP_Error on failure.
     */
    public static function update_one($form, $form_id)
    {
        /**
         * Apply before-sync filter on Gravity Forms data before form update
         * This filter provides an opportunity to sanitize // amend any data or update settings
         * before form is saved
         *
         * Defaults to the current value of $form
         *
         * @param array $form - GF form array
         *
         * @return     array                          filtered GF form array
         * @since      2.0.0
         */
        $form = apply_filters("local_gf/filters/before-sync", $form);
        $updated_form = GFAPI::update_form($form, $form_id);

        if( ! $updated_form instanceof WP_Error ){
            $updated_form = $form_id;
        }

        /**
         * Dispatch action after form has been synced
         * This action allows an opportunity for any clean-up // functionality post sync
         *
         * @param array $form - GF form array that has been synced
		 * @param int|WP_Error $updated_form - Gravity Forms ID on success. WP_Error on failure.
         * @since      2.0.0
         */
        do_action("local_gf/actions/after-sync", $form, $updated_form);

        return $updated_form;
    }

    /**
     * Import Gravity Forms form using JSON representation of a single form
     * Setup success / error messages and redirects
     * Called on local_gf_admin_import_form hook
     *
     * @return void
     */
    public static function import()
    {
        $data = $_REQUEST;
        $nonce_field = LocalGFConstants::IMPORT_NONCE;

        self::validate_request($data, $nonce_field, $nonce_field);

        $redirect_url = $data["_wp_http_referer"];
        $form_key = $data["form_key"];

        $form = LocalGFAPI::get_form_by_key($form_key);

        if (is_wp_error($form)) {
            $redirect_url = LocalGFViews::get_notice_url($redirect_url, "import", "failure", LocalGFConstants::FORM_KEY, $form_key);
            wp_redirect($redirect_url, 302);
            die();
        }

        $form_id = self::import_one($form);

        if (is_wp_error($form_id)) {
            $redirect_url = LocalGFViews::get_notice_url($redirect_url, "import", "failure", LocalGFConstants::FORM_KEY, $form_key);
        } else {
            $redirect_url = LocalGFViews::get_notice_url($redirect_url, "import", "success", LocalGFConstants::FORM_ID, $form_id);
        }

        wp_redirect($redirect_url, 302);
        die();
    }

    /**
     * Import Gravity Forms form using JSON representation of a single form
     * Calls local_gf/filters/before-import before form is imported
     * Dispatches local_gf/actions/after-import action after form has been imported
     *
     * @param $form - Local GF form to imported
     * @return int|WP_Error - Gravity Forms ID on success. WP_Error on failure.
     */
    public static function import_one($form)
    {
        /**
         * Apply before-import filter on Gravity Forms data before form import
         * This filter provides an opportunity to sanitize // amend any data or update settings
         * before form is imported
         *
         * Defaults to the current value of $form
         *
         * @param array $form - GF form array
         *
         * @return     array                          filtered GF form array
         * @since      2.0.0
         */

        $form = apply_filters("local_gf/filters/before-import", $form);
        $form_id = GFAPI::add_form($form);

        /**
         * Dispatch action after form has been imported
         * This action allows an opportunity for any clean-up // functionality post import
         *
         * @param array $form - GF form array that has been imported
		 * @param int|WP_Error $form_id - form_id on success or WP_Error instance
         * @since      2.0.0
         */
        do_action("local_gf/actions/after-import", $form, $form_id);

        return $form_id;
    }

    /**
     * Inject modified setting to local-json-for-gravity-forms form settings on admin render
     * Called on gform_admin_pre_render filter
     * Returns passed in form if form is not enabled
     *
     * @param array $form - GF form array
     * @return array - filter GF form array
     */
    public static function inject_modified_setting($form)
    {
        if (!LocalGFAPI::is_enabled($form)) {
            return $form;
        }

        $form[LocalGFConstants::SETTINGS_KEY][LocalGFConstants::MODIFIED] = time();

        return $form;
    }

    /**
     * Add plugin dashboard to Gravity Forms subnavigation
     * Called on the gform_addon_navigation filter
     *
     * @param array $menus - array of menu items
     * @return array - filtered array of menu items
     */
    public static function add_menu_page($menus)
    {
        $menus[] = array(
            'name' => LocalGFConstants::MENU_SLUG,
            'label' => __('Local JSON'),
            'callback' => array("LocalGFViews", "render_list_table")
        );

        return $menus;
    }


}