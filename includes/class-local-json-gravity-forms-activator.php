<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/mtbdata711/
 * @since      1.0.0
 *
 * @package    Local_Json_Gravity_Forms
 * @subpackage Local_Json_Gravity_Forms/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Local_Json_Gravity_Forms
 * @subpackage Local_Json_Gravity_Forms/includes
 * @author     Matthew Thomas <n/a>
 */
class Local_Json_Gravity_Forms_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		set_transient( 'local_gf_activated', true, 5 );
	}

}
