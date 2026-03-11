<?php
/**
 * Bookings list table
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WWGB_Bookings_List extends WP_List_Table {
    
    public function __construct() {
        parent::__construct(array(
            'singular' => 'booking',
            'plural'   => 'bookings',
            'ajax'     => false
        ));
    }
    
    public function get_columns() {
        return array(
            'cb'       => '<input type="checkbox" />',
            'name'     => 'Name',
            'email'    => 'Email',
            'phone'    => 'Phone',
            'datetime' => 'Date & Time',
            'status'   => 'Status',
            'actions'  => 'Actions'
        );
    }
    
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="booking[]" value="%s" />', $item['id']);
    }
    
    public function column_name($item) {
        return esc_html($item['first_name'] . ' ' . $item['last_name']);
    }
    
    public function column_email($item) {
        return esc_html($item['email']);
    }
    
    public function column_phone($item) {
        return esc_html($item['phone']);
    }
    
    public function column_datetime($item) {
        $date = date('M j, Y', strtotime($item['booking_date']));
        $time = date('H:i', strtotime($item['booking_time']));
        return esc_html("$date at $time");
    }
    
    public function column_status($item) {
        $statuses = array(
            'confirmed' => '<span class="wwgb-status confirmed">Confirmed</span>',
            'cancelled' => '<span class="wwgb-status cancelled">Cancelled</span>',
            'pending'   => '<span class="wwgb-status pending">Pending</span>',
        );
        return isset($statuses[$item['status']]) ? $statuses[$item['status']] : $item['status'];
    }
    
    public function column_actions($item) {
        $actions = sprintf(
            '<button class="button view-booking" data-id="%d">View Details</button>',
            $item['id']
        );
        
        if ($item['status'] === 'confirmed') {
            $actions .= sprintf(
                ' <button class="button cancel-booking" data-id="%d">Cancel</button>',
                $item['id']
            );
        } else {
            $actions .= sprintf(
                ' <button class="button confirm-booking" data-id="%d">Confirm</button>',
                $item['id']
            );
        }
        
        $actions .= sprintf(
            ' <button class="button delete-booking" data-id="%d" style="color:#dc3232;">Delete</button>',
            $item['id']
        );
        
        return $actions;
    }
    
    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'webwynk_bookings';
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;
        
        $this->_column_headers = array($this->get_columns(), array(), array());
        
        // Search
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $where = '';
        if ($search) {
            $where = $wpdb->prepare(
                "WHERE first_name LIKE %s OR last_name LIKE %s OR email LIKE %s",
                '%' . $search . '%', '%' . $search . '%', '%' . $search . '%'
            );
        }
        
        // Total items
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} {$where}");
        
        // Get items
        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page, $offset
            ),
            ARRAY_A
        );
        
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
}
