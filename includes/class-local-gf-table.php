<?php
// WP_List_Table is not loaded automatically, so we need to load it in our application
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class LocalGFTable extends WP_List_Table
{

    /**
     * Gravity forms form data
     *
     * @var array $form_data
     */
    public $form_data;

    /**
     * Local GF JSON form data
     *
     * @var arry $json_data
     */
    public $json_data;

    /**
     * Initialize the class and set its properties.
     *
     * @since      1.0.0
     */
    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'wp_local_gf_form',
            'plural' => 'wp_local_gf_forms',
            'ajax' => false
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
        $per_page = 10;
        $current_Page = $this->get_pagenum();
        $total_items = count($data);

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page
        ));

        $offset = (($current_Page - 1) * $per_page);

        $data = array_slice($data, $offset, $per_page);

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
        return array(
            'title' => 'Title',
            'last_updated' => 'Last updated',
            'sync_available' => 'Sync available',
            'form_exists' => "Form exists"
        );
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
        return array();
    }

    /**
     * Get the table data.
     * Returns empty array if no data found.
     *
     * @return array
     */
    private function table_data()
    {
        $json_forms = $this->json_data;
        $gravity_forms = $this->form_data;

        if (!$json_forms) {
            return [];
        }

        return apply_filters("local_gf/filters/list-table", $json_forms, $gravity_forms);
    }

    public function set_form_data($data)
    {
        $this->form_data = $data;
        return $this;
    }

    public function set_json_data($json)
    {
        $this->json_data = $json;
        return $this;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param array $item Form data
     * @param string $column_name Current column name
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
        }
    }
}
