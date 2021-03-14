<?php

/**
 * This class is responsible for all admin views for this plugin.
 * Uses core Local_Json_Gravity_Forms_Admin API class as a dependency.
 * Creates instance of Local_Json_Gravity_Forms_Table to handle table view.
 *
 * @link       https://github.com/mtbdata711/
 * @since      1.0.0
 *
 * @package    Local_Json_Gravity_Forms
 * @subpackage Local_Json_Gravity_Forms/admin/includes
 */


class Local_Json_Gravity_Forms_Views
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
     * @param     Local_Json_Gravity_Forms_Admin   $api  instance of Local_Json_Gravity_Forms_Admin class.
     */
    public function __construct($api)
    {
        $this->api = $api;
    }


    /**
     * Create menu page for plugin within WP Admin.
     * 
     * @since      1.0.0
     * @return     void
     */
    public function add_menu_local_gf_list_table_page()
    {
        add_menu_page('Local JSON for Gravity Forms', 'Local JSON for Gravity Forms', 'manage_options', 'local-json-for-gf.php', array($this, 'list_table_page'));
    }

    /**
     * Display plugin related admin notices on import / update success or failure.
     * 
     * @since      1.0.0
     * @return     void
     */
    public function maybe_display_admin_notice()
    {
        if (!isset($_GET["local_gf_admin_notice_nonce"]) || !wp_verify_nonce($_GET["local_gf_admin_notice_nonce"], "local_gf_admin_notice")) {
            return false;
        }

        if (isset($_GET["action"]) && $_GET["action"] === "form_update") {
            if (isset($_GET["status"]) && $_GET["status"] === "success") {
                $class = 'notice notice-success';
                $form_id = $_GET["form_id"];
                $message = "<b>Success!</b> Form updated <a href='/wp-admin/admin.php?page=gf_edit_forms&id=$form_id'>View Form</a>";

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class),  $message);
            }

            if (isset($_GET["status"]) && $_GET["status"] === "error") {
                $class = 'notice notice-error';
                $message = "<b>Error!</b> Form could not be updated. Please check JSON file.";

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class),  $message);
            }
        }

        if (isset($_GET["action"]) && $_GET["action"] === "form_import") {
            if (isset($_GET["status"]) && $_GET["status"] === "success") {
                $class = 'notice notice-success';
                $form_id = $_GET["form_id"];
                $message = "<b>Success!</b> Form imported <a href='/wp-admin/admin.php?page=gf_edit_forms&id=$form_id'>View Form</a>";

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class),  $message);
            }

            if (isset($_GET["status"]) && $_GET["status"] === "error") {
                $class = 'notice notice-error';
                $message = "<b>Error!</b> Form could not be imported. Please check JSON file.";

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class),  $message);
            }
        }
    }

    /**
     * Display error message on plugin activation if Gravity Forms is not detected.
     * 
     * @since      1.0.0
     * @return     void
     */
    public function maybe_display_activation_error_message(){
        if( ! get_transient( "local_gf_activated" ) || ! is_admin() ){
            return false;
        }

        if (! is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
            $class = 'notice notice-error';
            $message = "<b>Error!</b> Gravity Forms is not activated!";
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class),  $message);
            delete_transient("local_gf_activated");
        }
    }


    /**
     * Display table on Local JSON for Gravity Forms admin screen.
     * 
     * @since      1.0.0
     * @return     void
     */
    public function list_table_page()
    {
        $local_gf_table_instance = new Local_Json_Gravity_Forms_Table($this->api);
        $local_gf_table_instance->prepare_items();
?>
        <div class="wrap">
            <h2>Local JSON for Gravity Forms</h2>
            <?php $local_gf_table_instance->display(); ?>
        </div>
<?php
    }

    /**
     * Utility function to redirct URLs for Local JSON for Gravity Forms admin screen based on user action / state.
     * This function also creates a nonce to be verified to ensure that a valid request is being made.
     * 
     * @since      1.0.0
     * @param      string  $redirect_url    The base URL to redirect to.
     * @param      string  $action          The admin_post action the user is attempting to perform.
     * @param      string  $status          Whether the attempted action has been successful or if an error has occurred.
     * @param      int     $form_id         Gravity Forms form id.
     * @param      string  $nonce_name      Title of WP Nonce to add to query string.
     * @param      string  $nonce_value     Action of WP nonce to create.
     * @return     string                   URL to redirect to.
     */
    public static function build_redirect_url_string($redirect_url, $action, $status, $form_id, $nonce_name, $nonce_value)
    {
        return add_query_arg(array(
            "action" => $action,
            "status" => $status,
            "form_id" => $form_id,
            $nonce_name => wp_create_nonce( $nonce_value )
        ), $redirect_url);
    }
}
