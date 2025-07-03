<?php
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class GMP_Cycle_List_Table extends WP_List_Table {

    private $items_data;

    public function __construct($items) {
        parent::__construct(['singular' => 'cycle', 'plural' => 'cycles', 'ajax' => false]);
        $this->items_data = $items;
    }

    public function get_columns() {
        return [
            'product'   => 'Product',
            'start_date' => 'Start Date',
            'end_date'   => 'End Date',
            'status'     => 'Status',
            'actions'    => 'Actions'
        ];
    }

    public function prepare_items() {
        $this->items = $this->items_data;
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'product':
            case 'start_date':
            case 'end_date':
            case 'status':
                return esc_html($item[$column_name]);
            case 'actions':
                return '<a href="' . admin_url('admin.php?page=gmp-cycle-detail&id=' . $item['id']) . '">View</a>';
        }
    }
}
