<?php
/**
 * Hooks and functions responsible for displaying notices or generating views for this plugin
 *
 * @link       https://github.com/mtbdata711/local-json-for-gf
 * @since      2.0.0
 *
 * @package    Local_JSON_for_Gravity_Forms
 * @subpackage Local_JSON_for_Gravity_Forms/includes
 */

class LocalGFViews {

	/**
	 * Show admin notices on Local GF admin dashboard if Local GF $_GET param exists
	 * 
	 * @return void
	 */
	public static function maybe_add_admin_notice() {
		if(! isset($_GET["page"]) || $_GET["page"] !== LocalGFConstants::MENU_SLUG ){
			return false;
		}

		if ( ! isset( $_GET[ LocalGFConstants::TYPE ] ) ) {
			return false;
		}

		$type   = $_GET[ LocalGFConstants::TYPE ];
		$status = $_GET[ LocalGFConstants::STATUS ];
		$form_key = array_key_exists( LocalGFConstants::FORM_KEY, $_GET ) ? $_GET[ LocalGFConstants::FORM_KEY ] : null;
		$form_id = array_key_exists( LocalGFConstants::FORM_ID, $_GET ) ? $_GET[ LocalGFConstants::FORM_ID ] : null;

		if ( $type === "update" ) {
			return ( $status === "failure" ? self::add_update_error_notice( $form_key ) : self::add_update_success_notice( $form_id ) );
		}

		if ( $type === "import" ) {
			return ( $status === "failure" ? self::add_import_error_notice( $form_key ) : self::add_import_success_notice( $form_id ) );
		}
	}

	/**
	 * Generate redirect URL to show admin notice for a specific action
	 * 
	 * @param string $redirect_url - the URL to append params to
	 * @param string $type - what type of action is taking place? update / import
	 * @param string $status - success / failure
	 * @param string $data_key - key for any additional data. E.g. form_key or form_id. Defaults to null
	 * @param string $data_value - value for additional data key. Defaults to null.
	 * @return string redirect URL with params
	 */
	public static function get_notice_url( $redirect_url, $type, $status, $data_key = null, $data_value = null ) {
		$type_key   = LocalGFConstants::TYPE;
		$status_key = LocalGFConstants::STATUS;

		$args = array(
			$type_key   => $type,
			$status_key => $status,
		);

		if ( $data_key ) {
			$args[ $data_key ] = $data_value;
		}

		$result = add_query_arg( $args, $redirect_url );

		return $result;
	}

	/**
	 * Format Local GF JSON form data for use in WP_List_Table
	 * This function is called on the local_gf/filters/list-table filter
	 * 
	 * @param array $forms - Local GF JSON forms
	 * @param array $gravity_forms - array of GF forms
	 * @return array array of formatted Local GF JSON forms
	 */
	public static function prepare_table_data( $forms, $gravity_forms ) {
		$forms = array_filter($forms, function($form){
			return LocalGFAPI::is_enabled($form);
		});
		
		return array_map( function ( $form ) use ( $gravity_forms ) {
			$form_key                = LocalGFAPI::get_key_by_form( $form );
			$array                   = array();
			$array["title"]          = $form["title"];
			$array["last_updated"]   = date( "r", $form[ LocalGFConstants::SETTINGS_KEY ][ LocalGFConstants::MODIFIED ] );
			$array["sync_available"] = null;
			$gravity_form_id         = array_key_exists( $form_key, $gravity_forms ) ? $gravity_forms[ $form_key ]["id"] : false;
			$can_import_form         = LocalGFAPI::can_import_form( $gravity_forms, $form );

			if ( $can_import_form ) {
				$action                = LocalGFHelpers::get_action_url( LocalGFConstants::IMPORT_HOOK,  LocalGFConstants::IMPORT_NONCE, $form_key);
				$array["form_exists"] = "<a href='$action'>Import form</a>";
			} else {
				$array["form_exists"] = "<a href='/wp-admin/admin.php?page=gf_edit_forms&id=$gravity_form_id'>Edit Form</a>";
			}

			if ( ! $can_import_form ) {
				if ( LocalGFAPI::can_sync_form( $gravity_forms, $form ) ) {
					$action                   = LocalGFHelpers::get_action_url(LocalGFConstants::UPDATE_HOOK, LocalGFConstants::UPDATE_NONCE, $form_key, $gravity_form_id);
					$array["sync_available"] = "<a href='$action'>Sync form</a>";
				} else {
					$array["sync_available"] = "<b>Synced</b>";
				}
			}

			return $array;
		}, $forms );
	}

	/**
	 * Render list table on Local JSON admin page
	 * 
	 * @return void
	 */
	public static function render_list_table() {
		$form_data               = LocalGFAPI::get_valid_forms();
		$json_data               = LocalGFAPI::get_json_forms();
		$local_gf_table_instance = ( new LocalGFTable() )->set_json_data( $json_data )->set_form_data( $form_data );
		$local_gf_table_instance->prepare_items();
		?>
        <div class="wrap">
            <h2>Local JSON for Gravity Forms</h2>
			<?php $local_gf_table_instance->display(); ?>
        </div>
		<?
	}

	public static function add_permissions_error_notice( $bool_or_error ) {
		$class   = 'notice notice-error';
		$message = "<b>Local GF: Error!</b> Form could not be saved";
		if ( $bool_or_error instanceof WP_Error ) {
			$error   = $bool_or_error->errors[ LocalGFConstants::PERMISSIONS ][0];
			$message = "<b>Local GF Error!:</b> $error";
		}

		return self::print_notice( $class, $message );
	}

	public static function add_save_error_notice( $error ) {
		$class   = 'notice notice-error';
		$message = "<b>Local GF: Error!</b> Form could not be saved";
		if ( $error instanceof WP_Error ) {
			$error   = $error->errors[ LocalGFConstants::SAVE ][0];
			$message = "<b>Local GF Error!:</b> $error";
		}

		return self::print_notice( $class, $message );
	}

	public static function add_save_success_notice() {
		$class   = 'notice notice-success';
		$message = "<b>Local GF: Success!</b> Form JSON synced";

		return self::print_notice( $class, $message );
	}


	public static function add_update_error_notice( $form_key ) {
		$class   = 'notice notice-error';
		$message = "<b>Local GF: Error!</b> $form_key.json could not be synced. Check JSON file.";

		return self::print_notice( $class, $message );
	}

	public static function add_update_success_notice( $form_id ) {
		$class   = 'notice notice-success';
		$message = "<b>Local GF: Success!</b> Form updated <a href='/wp-admin/admin.php?page=gf_edit_forms&id=$form_id'>View Form</a>";

		return self::print_notice( $class, $message );
	}

	public static function add_import_success_notice( $form_id ) {
		$class   = 'notice notice-success';
		$message = "<b>Local GF: Success!</b> Form imported <a href='/wp-admin/admin.php?page=gf_edit_forms&id=$form_id'>View Form</a>";

		return self::print_notice( $class, $message );
	}

	public static function add_import_error_notice( $form_key ) {
		$class   = 'notice notice-error';
		$message = "<b>Local GF: Error!</b> $form_key.json could not be imported. Check JSON file.";

		return self::print_notice( $class, $message );
	}

	public function maybe_add_status_notice(){
		if(! isset($_GET["page"]) || $_GET["page"] !== LocalGFConstants::MENU_SLUG ){
			return false;
		}

		return self::add_status_notice();
	}

	public static function add_status_notice(){

		$status = LocalGFAPI::get_file_permissions_status();

		if( $status instanceof WP_Error ){
			return self::add_permissions_error_notice($status);
		}
		
		$class   = 'notice notice-success';
		$message = "<b>Local GF: Success!</b> Save point is active!";
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	}

	public static function add_savepoint_setting(){
		$status = LocalGFAPI::get_file_permissions_status();
		$save_point = realpath(LocalGFAPI::get_save_point());

		if( $save_point ){
			echo "<code>$save_point</code>";
			echo "<br class='clear'>";
		}

		if( ! $status || $status instanceof WP_Error ){
			echo "<span class='dashicons dashicons-no'></span>Save point is not writable or does not exist";
		}else{
			echo "<span class='dashicons dashicons-yes'></span> Savepoint is active!";
		}
	}

	public static function add_last_sync_setting( $form ){
		$settings = LocalGFAPI::get_form_settings( $form );
		$modified = $settings && array_key_exists( LocalGFConstants::MODIFIED, $settings ) ? $settings[LocalGFConstants::MODIFIED] : false;

		if( $modified ){
			echo date("r", $modified);
		}else{
			echo "Enable form to sync Local JSON";
		}
	}

	public static function add_actions_setting( $form ){
		$form_key = LocalGFAPI::get_key_by_form( $form );
		$json_form = LocalGFAPI::get_form_by_key( $form_key );

		if( ! $json_form || $json_form instanceof WP_Error  ){
			echo "<span class='dashicons dashicons-no'></span>Local JSON is not enabled for this form or form JSON does not exist";
			return;
		}

		$is_syncable = LocalGFAPI::can_sync_form(array($form_key => $form), $json_form);

		if( $is_syncable ){
			$sync_link = LocalGFHelpers::get_action_url(LocalGFConstants::UPDATE_HOOK, LocalGFConstants::UPDATE_NONCE, $form_key, $form["id"]);
			echo "<a href='$sync_link'>Sync form</a>";
		}else{
			echo "<span class='dashicons dashicons-yes'></span> Synced!";
		}
	}

	/**
	 * Utility function to render a single admin notice
	 * 
	 * @param string $class - CSS class to use
	 * @param string $message - $message to render
	 * @return void
	 */
	public static function print_notice( $class, $message ) {
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	}

}