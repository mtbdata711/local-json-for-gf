<?php

/**
 * The plugin bootstrap file
 *
 * @link              https://github.com/mtbdata711/local-json-for-gf
 * @since             1.0.0
 * @package           Local_JSON_for_Gravity_Forms
 *
 * @wordpress-plugin
 * Plugin Name:       Local JSON for Gravity Forms
 * Plugin URI:        https://github.com/mtbdata711/local-json-for-gf
 * Description:       Store forms from Gravity Forms as JSON to easily share forms across environments!
 * Version:           2.0.0
 * Author:            Matthew Thomas
 * Author URI:        https://github.com/mtbdata711/local-json-for-gf
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       local-gf
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('LOCAL_JSON_FOR_GRAVITY_FORMS_VERSION', '2.0.0');


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-local-gf.php';


add_action('gform_loaded', array('Local_JSON_for_Gravity_Forms_Bootstrap', 'load'), 5);

class Local_JSON_for_Gravity_Forms_Bootstrap
{

    public static function load()
    {

        if (!method_exists('GFForms', 'include_addon_framework')) {
            return;
        }

        GFAddOn::register('LocalGF');
    }

}

function local_gf()
{
    return LocalGF::get_instance();
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_Local_JSON_for_Gravity_Forms()
{
    $plugin = LocalGF::get_instance();
    $plugin->run();

}

run_Local_JSON_for_Gravity_Forms();
