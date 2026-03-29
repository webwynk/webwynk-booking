<?php
/**
 * Admin menu
 */

if (!defined('ABSPATH')) exit;

class WWGB_Admin_Menu {
    
    public function add_admin_menu() {
        add_menu_page(
            __('WebWynk Booking', 'webwynk-booking'),
            __('WebWynk Booking', 'webwynk-booking'),
            'manage_options',
            'webwynk-booking',
            array($this, 'render_bookings_page'),
            'dashicons-calendar-alt',
            30
        );
        
        add_submenu_page(
            'webwynk-booking',
            __('All Bookings', 'webwynk-booking'),
            __('All Bookings', 'webwynk-booking'),
            'manage_options',
            'webwynk-booking',
            array($this, 'render_bookings_page')
        );
        
        add_submenu_page(
            'webwynk-booking',
            __('Settings', 'webwynk-booking'),
            __('Settings', 'webwynk-booking'),
            'manage_options',
            'webwynk-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'webwynk-booking',
            __('Email Templates', 'webwynk-booking'),
            __('Email Templates', 'webwynk-booking'),
            'manage_options',
            'webwynk-emails',
            array($this, 'render_emails_page')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'webwynk') === false) return;
        
        wp_enqueue_style(
            'webwynk-admin',
            WWGB_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WWGB_VERSION
        );
        
        wp_enqueue_script(
            'webwynk-admin',
            WWGB_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WWGB_VERSION,
            true
        );
        
        wp_localize_script('webwynk-admin', 'wwgb_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wwgb_admin_nonce'),
        ));
    }
    
    public function render_bookings_page() {
        $list_table = new WWGB_Bookings_List();
        $list_table->prepare_items();
        ?>
        <div class="wrap webwynk-admin">
            <h1><?php _e('All Bookings', 'webwynk-booking'); ?></h1>
            <form method="post">
                <?php $list_table->display(); ?>
            </form>
        </div>
        
        <!-- Booking Details Modal -->
        <div id="booking-modal" class="webwynk-modal" style="display:none;">
            <div class="webwynk-modal-content">
                <span class="webwynk-close">&times;</span>
                <h2><?php _e('Booking Details', 'webwynk-booking'); ?></h2>
                <div id="booking-details"></div>
            </div>
        </div>
        <?php
    }
    
    public function render_settings_page() {
        if (isset($_POST['wwgb_save_settings']) && check_admin_referer('wwgb_settings')) {
            $this->save_settings();
        }
        
        $working_days = get_option('wwgb_working_days', array('mon', 'tue', 'wed', 'thu', 'fri', 'sat'));
        ?>
        <div class="wrap webwynk-admin">
            <h1><?php _e('Booking Settings', 'webwynk-booking'); ?></h1>
            
            <form method="post">
                <?php wp_nonce_field('wwgb_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Working Days', 'webwynk-booking'); ?></th>
                        <td>
                            <?php
                            $days = array(
                                'mon' => 'Monday',
                                'tue' => 'Tuesday',
                                'wed' => 'Wednesday',
                                'thu' => 'Thursday',
                                'fri' => 'Friday',
                                'sat' => 'Saturday',
                                'sun' => 'Sunday'
                            );
                            foreach ($days as $key => $label) {
                                $checked = in_array($key, $working_days) ? 'checked' : '';
                                echo '<label style="margin-right:15px;">';
                                echo '<input type="checkbox" name="working_days[]" value="' . $key . '" ' . $checked . '> ' . $label;
                                echo '</label>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Start Time', 'webwynk-booking'); ?></th>
                        <td>
                            <input type="time" name="start_time" value="<?php echo esc_attr(get_option('wwgb_start_time', '09:00')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('End Time', 'webwynk-booking'); ?></th>
                        <td>
                            <input type="time" name="end_time" value="<?php echo esc_attr(get_option('wwgb_end_time', '18:00')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Slot Duration (minutes)', 'webwynk-booking'); ?></th>
                        <td>
                            <select name="slot_duration">
                                <option value="15" <?php selected(get_option('wwgb_slot_duration'), 15); ?>>15 minutes</option>
                                <option value="30" <?php selected(get_option('wwgb_slot_duration'), 30); ?>>30 minutes</option>
                                <option value="45" <?php selected(get_option('wwgb_slot_duration'), 45); ?>>45 minutes</option>
                                <option value="60" <?php selected(get_option('wwgb_slot_duration'), 60); ?>>60 minutes</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Admin Email', 'webwynk-booking'); ?></th>
                        <td>
                            <input type="email" name="admin_email" value="<?php echo esc_attr(get_option('wwgb_admin_email', get_option('admin_email'))); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Message Required', 'webwynk-booking'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="message_required" value="1" <?php checked(get_option('wwgb_message_required'), true); ?>>
                                <?php _e('Make message field required', 'webwynk-booking'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Default Timezone', 'webwynk-booking'); ?></th>
                        <td>
                            <select name="default_timezone">
                                <?php
                                $zones = timezone_identifiers_list();
                                $current = get_option('wwgb_default_timezone', 'Asia/Kolkata');
                                foreach ($zones as $zone) {
                                    printf('<option value="%s" %s>%s</option>', esc_attr($zone), selected($current, $zone, false), esc_html($zone));
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings', 'primary', 'wwgb_save_settings'); ?>
            </form>
        </div>
        <?php
    }
    
    public function render_emails_page() {
        if (isset($_POST['wwgb_save_emails']) && check_admin_referer('wwgb_emails')) {
            update_option('wwgb_user_email_subject', sanitize_text_field($_POST['user_email_subject']));
            update_option('wwgb_user_email_template', wp_unslash($_POST['user_email_template']));
            update_option('wwgb_admin_email_subject', sanitize_text_field($_POST['admin_email_subject']));
            update_option('wwgb_admin_email_template', wp_unslash($_POST['admin_email_template']));
            update_option('wwgb_meeting_email_subject', sanitize_text_field($_POST['meeting_email_subject']));
            update_option('wwgb_meeting_email_template', wp_unslash($_POST['meeting_email_template']));
            echo '<div class="notice notice-success"><p>Email templates saved!</p></div>';
        }
        ?>
        <div class="wrap webwynk-admin">
            <h1><?php _e('Email Templates', 'webwynk-booking'); ?></h1>
            
            <form method="post">
                <?php wp_nonce_field('wwgb_emails'); ?>
                
                <h2><?php _e('User Confirmation Email', 'webwynk-booking'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Subject', 'webwynk-booking'); ?></th>
                        <td>
                            <input type="text" name="user_email_subject" value="<?php echo esc_attr(get_option('wwgb_user_email_subject')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Template', 'webwynk-booking'); ?></th>
                        <td>
                            <textarea name="user_email_template" rows="10" class="large-text"><?php echo esc_textarea(get_option('wwgb_user_email_template')); ?></textarea>
                            <p class="description">
                                Available placeholders: {first_name}, {last_name}, {date}, {time}, {timezone}, {phone}, {message}, {email}
                            </p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Admin Notification Email', 'webwynk-booking'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Subject', 'webwynk-booking'); ?></th>
                        <td>
                            <input type="text" name="admin_email_subject" value="<?php echo esc_attr(get_option('wwgb_admin_email_subject')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Template', 'webwynk-booking'); ?></th>
                        <td>
                            <textarea name="admin_email_template" rows="10" class="large-text"><?php echo esc_textarea(get_option('wwgb_admin_email_template')); ?></textarea>
                            <p class="description">
                                Available placeholders: {first_name}, {last_name}, {date}, {time}, {timezone}, {phone}, {message}, {email}
                            </p>
                        </td>
                    </tr>
                </table>
                
                <hr>
                
                <h2><?php _e('Meeting Link Email', 'webwynk-booking'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Subject', 'webwynk-booking'); ?></th>
                        <td>
                            <input type="text" name="meeting_email_subject" value="<?php echo esc_attr(get_option('wwgb_meeting_email_subject', 'Your Meeting Link is Ready - WebWynk')); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Template', 'webwynk-booking'); ?></th>
                        <td>
                            <textarea name="meeting_email_template" rows="10" class="large-text"><?php echo esc_textarea(get_option('wwgb_meeting_email_template')); ?></textarea>
                            <p class="description">
                                Available placeholders: {first_name}, {last_name}, {date}, {time}, {timezone}, {phone}, {message}, {email}, <strong>{meeting_link}</strong>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Templates', 'primary', 'wwgb_save_emails'); ?>
            </form>
        </div>
        <?php
    }
    
    private function save_settings() {
        update_option('wwgb_working_days', isset($_POST['working_days']) ? array_map('sanitize_text_field', $_POST['working_days']) : array());
        update_option('wwgb_start_time', sanitize_text_field($_POST['start_time']));
        update_option('wwgb_end_time', sanitize_text_field($_POST['end_time']));
        update_option('wwgb_slot_duration', intval($_POST['slot_duration']));
        update_option('wwgb_admin_email', sanitize_email($_POST['admin_email']));
        update_option('wwgb_message_required', isset($_POST['message_required']) ? true : false);
        update_option('wwgb_default_timezone', sanitize_text_field($_POST['default_timezone']));
        
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    
    public function ajax_update_status() {
        check_ajax_referer('wwgb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $id = intval($_POST['booking_id']);
        $status = sanitize_text_field($_POST['status']);
        
        $handler = new WWGB_Booking_Handler();
        $result = $handler->update_status($id, $status);
        
        if ($result !== false) {
            wp_send_json_success('Status updated');
        } else {
            wp_send_json_error('Failed to update status');
        }
    }
    
    public function ajax_delete_booking() {
        check_ajax_referer('wwgb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $id = intval($_POST['booking_id']);
        
        $handler = new WWGB_Booking_Handler();
        $result = $handler->delete_booking($id);
        
        if ($result !== false) {
            wp_send_json_success('Booking deleted');
        } else {
            wp_send_json_error('Failed to delete booking');
        }
    }
    
    public function ajax_get_booking_details() {
        check_ajax_referer('wwgb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $id = intval($_POST['booking_id']);
        
        $handler = new WWGB_Booking_Handler();
        $booking = $handler->get_booking($id);
        
        if ($booking) {
            wp_send_json_success($booking);
        } else {
            wp_send_json_error('Booking not found');
        }
    }

    public function ajax_save_meeting_link() {
        check_ajax_referer('wwgb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $id = intval($_POST['booking_id']);
        $meeting_link = esc_url_raw($_POST['meeting_link']);
        
        if (empty($meeting_link)) {
            wp_send_json_error('Link cannot be empty');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'webwynk_bookings';
        
        $result = $wpdb->update(
            $table_name,
            array('meeting_link' => $meeting_link),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Trigger the meeting link email
            $email_sender = new WWGB_Email_Sender();
            $email_sender->send_meeting_link($id);
            wp_send_json_success('Meeting link saved and email sent');
        } else {
            wp_send_json_error('Failed to save meeting link');
        }
    }
}
