<?php
/**
 * Plugin Name:       Webwynk Booking
 * Plugin URI:        https://webwynk.com
 * Description:       Premium glassmorphism-style consultation booking system with 2-step flow, auto timezone detection, and admin dashboard.
 * Version:           1.0.0
 * Author:            Hasanur Jaman
 * Author URI:        https://webwynk.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       webwynk-booking
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WWGB_VERSION', '1.0.0');
define('WWGB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WWGB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WWGB_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class WebWynk_Booking {
    
    private static $instance = null;
    private $table_name;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'webwynk_bookings';
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    private function load_dependencies() {
        require_once WWGB_PLUGIN_DIR . 'includes/class-database.php';
        require_once WWGB_PLUGIN_DIR . 'includes/class-booking-handler.php';
        require_once WWGB_PLUGIN_DIR . 'includes/class-email-sender.php';
        require_once WWGB_PLUGIN_DIR . 'admin/class-admin-menu.php';
        require_once WWGB_PLUGIN_DIR . 'admin/class-bookings-list.php';
        require_once WWGB_PLUGIN_DIR . 'public/class-shortcode.php';
    }
    
    private function set_locale() {
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }
    
    public function load_plugin_textdomain() {
        load_plugin_textdomain('webwynk-booking', false, dirname(WWGB_PLUGIN_BASENAME) . '/languages/');
    }
    
    private function define_admin_hooks() {
        $admin = new WWGB_Admin_Menu();
        add_action('admin_menu', array($admin, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_admin_assets'));
        add_action('wp_ajax_wwgb_update_booking_status', array($admin, 'ajax_update_status'));
        add_action('wp_ajax_wwgb_delete_booking', array($admin, 'ajax_delete_booking'));
        add_action('wp_ajax_wwgb_get_booking_details', array($admin, 'ajax_get_booking_details'));
        add_action('wp_ajax_wwgb_save_meeting_link', array($admin, 'ajax_save_meeting_link'));
    }
    
    private function define_public_hooks() {
        $shortcode = new WWGB_Shortcode();
        add_shortcode('webwynk_booking', array($shortcode, 'render_booking_form'));
        
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('wp_ajax_wwgb_submit_booking', array($this, 'ajax_submit_booking'));
        add_action('wp_ajax_nopriv_wwgb_submit_booking', array($this, 'ajax_submit_booking'));
        add_action('wp_ajax_wwgb_get_available_slots', array($this, 'ajax_get_slots'));
        add_action('wp_ajax_nopriv_wwgb_get_available_slots', array($this, 'ajax_get_slots'));
        add_action('wp_ajax_wwgb_detect_timezone', array($this, 'ajax_detect_timezone'));
        add_action('wp_ajax_nopriv_wwgb_detect_timezone', array($this, 'ajax_detect_timezone'));
    }
    
    public function enqueue_public_assets() {
        wp_enqueue_style(
            'wwgb-inter-font',
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            array(),
            null
        );
        
        wp_enqueue_style(
            'webwynk-booking',
            WWGB_PLUGIN_URL . 'assets/css/public.css',
            array('wwgb-inter-font'),
            WWGB_VERSION
        );
        
        wp_enqueue_script(
            'webwynk-booking',
            WWGB_PLUGIN_URL . 'assets/js/public.js',
            array(),
            WWGB_VERSION,
            true
        );
        
        $working_days = get_option('wwgb_working_days', array('mon', 'tue', 'wed', 'thu', 'fri', 'sat'));
        wp_localize_script('webwynk-booking', 'wwgb_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wwgb_nonce'),
            'working_days' => $working_days
        ));
    }
    
    public function ajax_submit_booking() {
        check_ajax_referer('wwgb_nonce', 'nonce');
        
        $handler = new WWGB_Booking_Handler();
        $result = $handler->process_booking($_POST);
        
        wp_send_json($result);
    }
    
    public function ajax_get_slots() {
        check_ajax_referer('wwgb_nonce', 'nonce');
        
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $timezone = isset($_POST['timezone']) ? sanitize_text_field($_POST['timezone']) : 'Asia/Kolkata';
        
        $handler = new WWGB_Booking_Handler();
        $slots = $handler->get_available_slots($date, $timezone);
        
        wp_send_json_success(array('slots' => $slots));
    }
    
    public function ajax_detect_timezone() {
        wp_send_json_success(array(
            'timezone' => 'Asia/Kolkata',
            'label'    => 'Asia/Calcutta (Auto-detected)'
        ));
    }
}

// Activation hook
register_activation_hook(__FILE__, 'wwgb_activate');
function wwgb_activate() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
    $database = new WWGB_Database();
    $database->create_tables();
        $database->set_default_options();
        
        // Update default times if they haven't been customized yet
        if (get_option('wwgb_end_time') === '18:00') {
            update_option('wwgb_end_time', '23:50');
        }
    
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wwgb_deactivate');
function wwgb_deactivate() {
    flush_rewrite_rules();
}

// Uninstall hook
register_uninstall_hook(__FILE__, 'wwgb_uninstall');
function wwgb_uninstall() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
    $database = new WWGB_Database();
    $database->drop_tables();
    $database->delete_options();
}

// Initialize plugin
add_action('plugins_loaded', array('WebWynk_Booking', 'get_instance'));
