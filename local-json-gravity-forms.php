<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/mtbdata711/
 * @since             1.0.0
 * @package           Local_Json_Gravity_Forms
 *
 * @wordpress-plugin
 * Plugin Name:       Local JSON for Gravity Forms
 * Plugin URI:        https://github.com/mtbdata711/
 * Description:       Store forms from Gravity Forms as JSON to easily share forms across environments!
 * Version:           1.0.0
 * Author:            Matthew Thomas
 * Author URI:        https://github.com/mtbdata711/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       local-json-gravity-forms
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LOCAL_JSON_GRAVITY_FORMS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-local-json-gravity-forms-activator.php
 */
function activate_local_json_gravity_forms() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-local-json-gravity-forms-activator.php';
	Local_Json_Gravity_Forms_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-local-json-gravity-forms-deactivator.php
 */
function deactivate_local_json_gravity_forms() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-local-json-gravity-forms-deactivator.php';
	Local_Json_Gravity_Forms_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_local_json_gravity_forms' );
register_deactivation_hook( __FILE__, 'deactivate_local_json_gravity_forms' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-local-json-gravity-forms.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_local_json_gravity_forms() {
	$plugin = new Local_Json_Gravity_Forms();
	$plugin->run();

}
run_local_json_gravity_forms();
