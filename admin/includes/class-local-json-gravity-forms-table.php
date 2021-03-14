<?php
/**
 * This class is responsible for displaying all available JSON representations of forms on the Local JSON for Gravity Forms screen in WP admin.
 * Uses core Local_Json_Gravity_Forms_Admin API class as a dependency.
 * Extends - and requires if needed - the WP_List_Table class from WP core.
 * 
 * This class was built off an example gist found here:
 * https://gist.github.com/paulund/7659452
 *
 * @link       https://github.com/mtbdata711/
 * @since      1.0.0
 *
 * @package    Local_Json_Gravity_Forms
 * @subpackage Local_Json_Gravity_Forms/admin/includes
 */


// WP_List_Table is not loaded automatically so we need to load it in our application
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Local_Json_Gravity_Forms_Table extends WP_List_Table
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
     * Array of meta information from JSON representations of Gravity Forms as PHP arrays from save point directory.
     * The data is retrieved from get_forms_data() defined in core Local_Json_Gravity_Forms_Admin class.
     *
     * @since    1.0.0
     * @access   private
     * @var      array  $json_data Array of meta information from JSON representations of Gravity Forms as PHP arrays from save point directory.
     */
    private $json_data;


    /**
     * Initialize the class and set its properties.
     * 
     * @since      1.0.0
     * @param     Local_Json_Gravity_Forms_Admin   $api  instance of Local_Json_Gravity_Forms_Admin class.
     */
    public function __construct($api)
    {
        $this->api = $api;
        $this->json_data = $api->get_forms_data();
        parent::__construct(array(
            'singular'  => 'wp_local_gf_form',
            'plural'    => 'wp_local_gf_forms',
            'ajax'      => false
        ));
    }
    /**
     * Prepare the items for the table to process
     *
     * @return void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        usort($data, array(&$this, 'sort_data'));


        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in table.
     *
     * @return array
     */
    public function get_columns()
    {
        $columns = array(
            'title'       => 'Title',
            'last_updated' => 'Last Updated',
            'sync_available' => 'Sync Available',
            'form_exists' => "Form Exists"
        );

        return $columns;
    }

    /**
     * Define which columns are hidden.
     *
     * @return array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns.
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        return array('title' => array('title', false));
    }

    /**
     * Get the table data.
     * Returns empty array if no data found.
     *
     * @return array
     */
    private function table_data()
    {
        $form_data = $this->json_data;

        if( ! $form_data ){
            return array();
        }

        return array_map(function ($form) {
            if ($form["sync_available"]) {
                $admin_url = admin_url('admin-post.php');

                $path = basename($form["path"]);
                $nonce = wp_nonce_field("local_gf_admin_update_form", "local_gf_admin_update_form_nonce");
                $form["sync_available"] = "<form action='$admin_url' method='POST' ><input type='hidden' name='action' value='local_gf_admin_update_form' /><input type='hidden' name='path' value='$path' />$nonce<input type='submit' name='submit' class='button button-primary' value='Sync Form' /></form>";
            }

            if (!$form["form_exists"]) {
                $admin_url = admin_url('admin-post.php');

                $path = basename($form["path"]);
                $nonce = wp_nonce_field("local_gf_admin_import_form", "local_gf_admin_import_form_nonce");
                $form["form_exists"] = "<form action='$admin_url' method='POST' ><input type='hidden' name='action' value='local_gf_admin_import_form' /><input type='hidden' name='path' value='$path' />$nonce<input type='submit' name='submit' class='button button-primary' value='Import Form' /></form>";
            } else {
                $form_id = $form['form_exists'];
                $form["form_exists"] = "<a href='/wp-admin/admin.php?page=gf_edit_forms&id=$form_id'>Edit Form</a>";
            }

            return $form;
        }, $form_data);
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  array $item           Form data
     * @param  string $column_name   Current column name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'title':
            case 'last_updated':
            case 'sync_available':
            case 'form_exists':
                return $item[$column_name];

            default:
                return print_r($item, true);
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return mixed
     */
    private function sort_data($a, $b)
    {
        // Set defaults
        $orderby = 'title';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if (!empty($_GET['orderby'])) {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if (!empty($_GET['order'])) {
            $order = $_GET['order'];
        }


        $result = strcmp($a[$orderby], $b[$orderby]);

        if ($order === 'asc') {
            return $result;
        }

        return -$result;
    }
}
