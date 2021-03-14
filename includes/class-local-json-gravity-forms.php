<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/mtbdata711/
 * @since      1.0.0
 *
 * @package    Local_Json_Gravity_Forms
 * @subpackage Local_Json_Gravity_Forms/includes
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
 * @package    Local_Json_Gravity_Forms
 * @subpackage Local_Json_Gravity_Forms/includes
 * @author     Matthew Thomas <n/a>
 */
class Local_Json_Gravity_Forms
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Local_Json_Gravity_Forms_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('LOCAL_JSON_GRAVITY_FORMS_VERSION')) {
			$this->version = LOCAL_JSON_GRAVITY_FORMS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'local-json-gravity-forms';

		$this->load_dependencies();
		$this->define_admin_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Local_Json_Gravity_Forms_Loader. Orchestrates the hooks of the plugin.
	 * - Local_Json_Gravity_Forms_i18n. Defines internationalization functionality.
	 * - Local_Json_Gravity_Forms_Admin. Defines all hooks for the admin area.
	 * - Local_Json_Gravity_Forms_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-local-json-gravity-forms-loader.php';

		/**
		 * The class responsible for the core API of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-local-json-gravity-forms-admin.php';

		/**
		 * The class responsible for defining all admin actions for the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/includes/class-local-json-gravity-forms-admin-actions.php';
		/**
		 * The class responsible for handling all views in the admin area for the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/includes/class-local-json-gravity-forms-views.php';
		/**
		 * The class responsible for the table view in the admin area for the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/includes/class-local-json-gravity-forms-table.php';

		$this->loader = new Local_Json_Gravity_Forms_Loader();
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
		$plugin_admin = new Local_Json_Gravity_Forms_Admin($this->get_plugin_name(), $this->get_version());
		$plugin_actions = new Local_Json_Gravity_Forms_Admin_Actions($plugin_admin);
		$plugin_views = new Local_Json_Gravity_Forms_Views($plugin_admin);
		$this->loader->add_action("init", $plugin_admin, "init", 10, 1);
		$this->loader->add_action("gform_after_save_form", $plugin_admin, "save", 10, 1);
		$this->loader->add_action("admin_post_local_gf_admin_update_form", $plugin_actions, "local_gf_admin_update_form");
		$this->loader->add_action("admin_post_local_gf_admin_import_form", $plugin_actions, "local_gf_admin_import_form");
		$this->loader->add_action("admin_menu", $plugin_views, "add_menu_local_gf_list_table_page");
		$this->loader->add_action("admin_notices", $plugin_views, "maybe_display_admin_notice");
		$this->loader->add_action("admin_notices", $plugin_views, "maybe_display_activation_error_message");
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
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Local_Json_Gravity_Forms_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
