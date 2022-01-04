<?php
GFForms::include_addon_framework();

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/mtbdata711/local-json-for-gf
 * @since      1.0.0
 *
 * @package    Local_JSON_for_Gravity_Forms
 * @subpackage Local_JSON_for_Gravity_Forms/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Local_JSON_for_Gravity_Forms
 * @subpackage Local_JSON_for_Gravity_Forms/includes
 * @author     Matthew Thomas <n/a>
 */
class LocalGF extends GFAddOn
{


    /**
     * Instance of this class
     *
     * @var LocalGF $_instance
     */
    private static $_instance = null;

    /**
     * Instance of core API class
     *
     * @var LocalGFAPI|null
     */
    public $api = null;
    /**
     * Instance of hooks class
     *
     * @var LocalGFHooks|null
     */
    public $hooks = null;
    /**
     * Instance of helpers class
     *
     * @var LocalGFHelpers|null
     */
    public $helpers = null;

    /**
     * The loader that"s responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      LocalGF_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;
    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    // Gravity forms AddOn props
    protected $_version = LOCAL_JSON_FOR_GRAVITY_FORMS_VERSION;
    protected $_min_gravityforms_version = "2.4";
    protected $_slug = "local_json_for_gravity_forms";
    protected $_full_path = __FILE__;
    protected $_title = "Local JSON for Gravity Forms";
    protected $_short_title = "Local JSON";

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    private function __construct()
    {
        parent::__construct();
        $this->plugin_name = "local-json-for-gravity-forms";

        $this->load_dependencies();
        $this->set_class_props();
        $this->define_admin_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - LocalGF_Loader. Orchestrates the hooks of the plugin.
     * - LocalGF_i18n. Defines internationalization functionality.
     * - LocalGF_Admin. Defines all hooks for the admin area.
     * - LocalGF_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . "includes/class-local-gf-loader.php";

        /**
         * The class responsible for core API of this plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . "includes/class-local-gf-api.php";

        /**
         * The class responsible for all admin views in this plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . "includes/class-local-gf-views.php";

        /**
         * The class responsible for all hooks in this plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . "includes/class-local-gf-hooks.php";

        /**
         * The class which contains all constants used across this plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . "includes/class-local-gf-constants.php";

        /**
         * Class of helper functions used across this plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . "includes/class-local-gf-helpers.php";

        /**
         * The class responsible for displaying Local JSON WP table
         */
        require_once plugin_dir_path(dirname(__FILE__)) . "includes/class-local-gf-table.php";

        $this->loader = new LocalGFLoader();
    }

    /**
     * Register instances of core classes used by this plugin.
     *
     * @since 2.0.0
     * @access private
     */
    private function set_class_props()
    {
        $this->api = new LocalGFAPI();
        $this->hooks = new LocalGFHooks();
        $this->views = new LocalGFViews();
        $this->helpers = new LocalGFHelpers();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $update_hook = LocalGFConstants::UPDATE_HOOK;
        $import_hook = LocalGFConstants::IMPORT_HOOK;
        $this->loader->add_action("admin_post_$update_hook", $this->hooks, "update");
        $this->loader->add_action("admin_post_$import_hook", $this->hooks, "import");
        $this->loader->add_filter("gform_admin_pre_render", $this->hooks, "inject_modified_setting", 10, 1);
        $this->loader->add_action("gform_after_save_form", $this->hooks, "save", 10, 1);
        $this->loader->add_action("local_gf/actions/save", $this->hooks, "save", 10, 1);
        $this->loader->add_filter("gform_addon_navigation", $this->hooks, "add_menu_page", 99999, 1);
        $this->loader->add_action("admin_notices", $this->views, "maybe_add_admin_notice", 10);
        $this->loader->add_action("admin_notices", $this->views, "maybe_add_status_notice", 10);

        //local_gf default filters
        $this->loader->add_filter("local_gf/filters/file-permissions-check", $this->helpers, "is_writable", 10, 2);
        $this->loader->add_filter("local_gf/filters/file-permissions-check", $this->helpers, "is_directory", 10, 2);
        $this->loader->add_filter("local_gf/filters/is-readable-check", $this->helpers, "is_readable", 10, 2);
        $this->loader->add_filter("local_gf/filters/list-table", $this->views, "prepare_table_data", 10, 2);
    }

    /**
     * Getter for singleton instance of this class.
     *
     * @return LocalGF - instance of this class.
     * @since 2.0.0
     * @access public
     */
    public static function get_instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new LocalGF();
        }

        return self::$_instance;
    }


    /**
     * Register plugin settings with Gravity Forms.
     *
     * @return array - array of settings
     * @since 2.0.0
     * @access public
     */
    public function form_settings_fields($form)
    {
        return LocalGFHooks::register_settings($form);
    }

    /**
     * Overwrite maybe_save_form_settings from GFAddOn to dispatch action on form settings save
	 * Calls parent if this is a valid request
	 * 
     * @since 2.0.0
     * @access public
     * @param array $form - GF form arry
     * @return null|true|false - true on success, null on no action
     */
    public function maybe_save_form_settings($form)
    {
		if(! $this->is_save_postback()){
			return false;
		}

        $result = parent::maybe_save_form_settings($form);
        do_action("local_gf/actions/save", $form);
        return $result;
    }

    /**
     * Custom Gravity Forms setting field callback.
     *
     * @return void
     * @since 2.0.0
     * @access public
     */
    public function settings_savepoint()
    {
        LocalGFViews::add_savepoint_setting();
    }

    /**
     * Custom Gravity Forms setting field callback.
     *
     * @return void
     * @since 2.0.0
     * @access public
     */
    public function settings_last_sync()
    {
        $form = $this->get_current_form();
        LocalGFViews::add_last_sync_setting($form);
    }

    /**
     * Custom Gravity Forms setting field callback.
     *
     * @return void
     * @since 2.0.0
     * @access public
     */
    public function settings_actions()
    {
        $form = $this->get_current_form();
        LocalGFViews::add_actions_setting($form);
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    LocalGF_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version()
    {
        return $this->_version;
    }
}
