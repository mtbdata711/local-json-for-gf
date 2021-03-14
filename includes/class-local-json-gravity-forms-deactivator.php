<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/mtbdata711/
 * @since      1.0.0
 *
 * @package    Local_Json_Gravity_Forms
 * @subpackage Local_Json_Gravity_Forms/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Local_Json_Gravity_Forms
 * @subpackage Local_Json_Gravity_Forms/includes
 * @author     Matthew Thomas <n/a>
 */
class Local_Json_Gravity_Forms_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		delete_transient("local_gf_activated");
	}

}
