<?php

/**
 * This class is responsible for the API for saving, importing and updating JSON & forms
 *
 * @link       https://github.com/mtbdata711/
 * @since      1.0.0
 *
 * @package    Local_Json_Gravity_Forms
 * @subpackage Local_Json_Gravity_Forms/admin
 */
class Local_Json_Gravity_Forms_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Directory to store JSON files. 
     * Defaults to /gravity-forms-json within the current forms directory.
     * This can be overwritten using the local_gf/filters/save-point filter.
     *
     * @since    1.0.0
     * @access   public
     * @var      string    $directory   Local JSON save point.
     */
    public $directory;

    /**
     * Array of Gravity Forms ids to exclude from local JSON.
     * Defaults to an empty array.
     * This can be overwritten using the local_gf/filters/exclude-forms filter.
     *
     * @since    1.0.0
     * @access   public
     * @var      array    $excluded_forms  Forms to exclude.
     */
    public $excluded_forms;

    /**
     * Initialize the class and set its properties.
     * 
     * @since      1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Apply filters and set class properties on init hook.
     * 
     * Sets directory property using local_gf/filters/save-point filter.
     * Defaults to /gravity-forms-json in current theme directory.
     * 
     * Sets excluded forms NOT to save by Gravity Form id.
     * Defaults to an empty array.
     * 
     * @since      1.0.0.
     */
    public function init()
    {
        $this->directory = apply_filters("local_gf/filters/save-point", get_stylesheet_directory() . "/" . "gravity-forms-json" . "/");
        $this->excluded_forms = apply_filters("local_gf/filters/exclude-forms", array());
    }

    /**
     * Get JSON representation of single form.
     * 
     * @since                      1.0.0
     * @param      string          $file_name  File name to retrieve.
     * @return     object|false    JSON representation of single form. Returns false on failure.
     */
    public function get_form($file_name)
    {
        $file_path = $this->get_path($file_name);

        if (!file_exists($file_path)) {
            return false;
        }

        return file_get_contents($file_path);
    }


    /**
     * Get JSON representation of all forms within save point directory
     * 
     * @since      1.0.0
     * @param      string    $file_name  File name to retrieve.
     * @return     array     array of JSON representations of forms.
     */
    public function get_forms()
    {
        $files = glob("$this->directory*.json");
        return array_map(function ($file) {
            return file_get_contents($file);
        }, $files);
    }

    /**
     * Get meta information of forms within save point directory, title, last updated time, etc.
     * Used to create table view.
     * 
     * @since      1.0.0
     * @return     array     array of form meta.
     */
    public function get_forms_data()
    {
        if (!is_dir($this->directory)) {
            return false;
        }
        $form_paths = glob($this->directory . "*.json");
        return array_map(function ($path) {
            $form_data = json_decode(file_get_contents($path), true)[0];
            return array(
                "title" => $form_data["title"],
                "last_updated" => date("F d Y H:i:s.", filemtime($path)),
                "sync_available" => $this->is_update_available($form_data["title"], strtotime($form_data["date_updated"])),
                "form_exists" => $this->get_form_id_by_title($form_data["title"]),
                "path" => $path
            );
        }, $form_paths);
    }

    /**
     * Format and save JSON representation of form on update.
     * Runs on gform_after_save_form hook.
     * 
     * @since      1.0.0
     * @return     void
     */
    public function save(array $form)
    {
        $form_id = $form["id"];
        if (in_array($form_id, $this->excluded_forms)) {
            return false;
        }
        $form_object = GFExport::prepare_forms_for_export(GFFormsModel::get_form_meta_by_id($form_id));
        /**
         * Apply before-save filter on Gravity Forms data before JSON save.
         * This filter provides an opportunity to sanitize // amend any data or update settings
         * before JSON is saved.
         *
         * Defaults to the current value of $form_object
         *
         * @since      1.0.0
         * @param      array   $form_object           Gravity Forms form object array.
         * @param      Local_Json_Gravity_Forms_Admin The current instance of this class.
         * @return     array                          Filtered Gravity Forms object array.
         */
        $form_object = apply_filters("local_gf/filters/before-save", $form_object, $this);
        $form_json = json_encode($form_object);
        $save_point = strtolower($this->directory . urlencode($form["title"]) . ".json");
        file_put_contents($save_point, $form_json);

        /**
         * Dispatch action after JSON representation has been saved.
         * This action allows an opportunity for any clean-up // functionality after
         * a JSON form has been saved.
         *
         *
         * @since      1.0.0
         * @param      int   $form_id                 Gravity Forms form id.
         * @param      array $form_object             Gravity Forms object array that has been saved.
         */
        do_action("local_gf/actions/after-save", $form_id, $form_object);
    }


    /**
     * Check if a single JSON representation of a form is available to be synced.
     * Returns false if form does not exist or JSON representation and Gravity Forms object last updated value are the same.
     * 
     * @since      1.0.0
     * @param      string  $title  title of the form to search for.
     * @param      int     $last_modified_json_timestamp date_updated value of JSON representation of single form as unix timestamp.
     * @return     boolean returns true if JSON form is not synced with gravity form. 
     */
    public function is_update_available($title, $last_modified_json_timestamp)
    {
        $form_id = $this->get_form_id_by_title($title);

        if (!$form_id) {
            return false;
        }

        $gravity_forms_object = GFAPI::get_form($form_id);
        return array_key_exists("date_updated", $gravity_forms_object) && strtotime($gravity_forms_object["date_updated"]) < $last_modified_json_timestamp;
    }

    /**
     * Unwrap // convert JSON representation of form into Gravity Forms array.
     * 
     * @since      1.0.0
     * @param      string    $file_name  File name to retrieve.
     * @return     array     returns JSON representation as Gravity Forms array.
     */
    public function prepare_form($file_name)
    {
        $form_json = $this->get_form($file_name);
        $form_data = json_decode($form_json, true)[0];
        return GFFormsModel::sanitize_settings(GFFormsModel::convert_field_objects($form_data));
    }

    /**
     * Import JSON representation of a single form into Gravity Forms.
     * Updates form if form exists. 
     * Returns false if wp_error.
     * 
     * @since      1.0.0
     * @param      string    $file_name  File name to retrieve.
     * @return     int       returns form id for imported / updated form.
     */
    public function import_form($file_name)
    {
        $form_data = $this->prepare_form($file_name);
        $form_exists = $this->get_form_id_by_title($form_data["title"]);

        if ($form_exists) {
            return $this->update_form($file_name);
        }

        $form_data["date_updated"] = self::get_form_updated_date_string();
        $form_id = GFAPI::add_form($form_data);

        if (is_wp_error($form_id)) {
            return false;
        }

        return $form_id;
    }

    /**
     * Update Gravity Forms form using JSON representation of a single form.
     * Returns false if wp_error or if form does not exist.
     * 
     * @since      1.0.0
     * @param      string    $file_name  File name to retrieve.
     * @return     int       returns form id for updated form.
     */
    public function update_form($file_name)
    {
        $form_data = $this->prepare_form($file_name);
        $gravity_forms_id = $this->get_form_id_by_title($form_data["title"]);

        if (!$gravity_forms_id) {
            return false;
        }

        $form_data["date_updated"] = self::get_form_updated_date_string();
        $updated_form = GFAPI::update_form($form_data, $gravity_forms_id);

        if (is_wp_error($updated_form)) {
            return false;
        }

        return $gravity_forms_id;
    }

    /**
     * Get pull path to single JSON file by file name
     * Uses directory value defined in this class to build full path.
     * Defaults to /gravity-forms-json within the current forms directory.
     * This can be overwritten using the local_gf/filters/save-point filter.
     * 
     * @since      1.0.0
     * @param      string    $file_name  File name to retrieve.
     * @return     string    returns full path to file.
     */
    public function get_path($file_name)
    {
        return $this->directory . $file_name;
    }

    /**
     * Get single Gravity Forms form id by form title. 
     * 
     * @since      1.0.0
     * @param      string    $title  Title of form to retrieve.
     * @return     int       Gravity Forms form id. Returns 0 on failure.
     */
    public static function get_form_id_by_title(string $title)
    {
        global $wpdb;
        $forms_table = GFFormsModel::get_form_table_name();
        $form_id = $wpdb->get_var($wpdb->prepare("select id from {$forms_table} where title = '%s'", $title));
        return (int) $form_id;
    }

    /**
     * Get formatted date string
     * 
     * @since      1.0.0
     * @return     string  formatted date string.
     */
    public static function get_form_updated_date_string()
    {
        $now = new DateTime();
        return $now->format('Y-m-d H:i:s');
    }
}
