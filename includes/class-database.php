<?php
/**
 * Database handler
 */

if (!defined('ABSPATH')) exit;

class WWGB_Database {
    
    private $bookings_table;
    private $charset_collate;
    
    public function __construct() {
        global $wpdb;
        $this->bookings_table = $wpdb->prefix . 'webwynk_bookings';
        $this->charset_collate = $wpdb->get_charset_collate();
    }
    
    public function create_tables() {
        global $wpdb;
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->bookings_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(255) NOT NULL,
            phone varchar(50) NOT NULL,
            message text,
            booking_date date NOT NULL,
            booking_time time NOT NULL,
            ist_date date DEFAULT NULL,
            ist_time time DEFAULT NULL,
            timezone varchar(100) DEFAULT 'Asia/Kolkata',
            meeting_link varchar(255) DEFAULT NULL,
            status varchar(20) DEFAULT 'confirmed',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_date (booking_date),
            KEY status (status),
            KEY email (email)
        ) {$this->charset_collate};";
        
        dbDelta($sql);
        
        add_option('wwgb_db_version', WWGB_VERSION);
    }
    
    public function set_default_options() {
        $defaults = array(
            'wwgb_working_days' => array('mon', 'tue', 'wed', 'thu', 'fri', 'sat'),
            'wwgb_start_time' => '09:00',
            'wwgb_end_time' => '18:00',
            'wwgb_slot_duration' => 30,
            'wwgb_admin_email' => get_option('admin_email'),
            'wwgb_message_required' => false,
            'wwgb_default_timezone' => 'Asia/Kolkata',
            'wwgb_user_email_subject' => 'Your Consultation is Confirmed - WebWynk',
            'wwgb_user_email_template' => $this->get_default_user_email(),
            'wwgb_admin_email_subject' => 'New Booking Received - WebWynk',
            'wwgb_admin_email_template' => $this->get_default_admin_email(),
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    private function get_default_user_email() {
        return "Hello {first_name},

Your consultation has been confirmed!

📅 Date: {date}
⏰ Time: {time}
🌍 Timezone: {timezone}

If you need to reschedule, please contact us as soon as possible.

Best regards,
WebWynk Team";
    }
    
    private function get_default_admin_email() {
        return "New Booking Alert!

👤 Name: {first_name} {last_name}
📧 Email: {email}
📞 Phone: {phone}
📅 Date: {date}
⏰ Time: {time}
🌍 Timezone: {timezone}
📝 Message: {message}

Login to view all bookings.";
    }
    
    public function drop_tables() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$this->bookings_table}");
    }
    
    public function delete_options() {
        $options = array(
            'wwgb_db_version', 'wwgb_working_days', 'wwgb_start_time', 'wwgb_end_time',
            'wwgb_slot_duration', 'wwgb_admin_email', 'wwgb_message_required',
            'wwgb_default_timezone', 'wwgb_user_email_subject', 'wwgb_user_email_template',
            'wwgb_admin_email_subject', 'wwgb_admin_email_template'
        );
        foreach ($options as $option) {
            delete_option($option);
        }
    }
}
