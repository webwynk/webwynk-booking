<?php
/**
 * Booking handler
 */

if (!defined('ABSPATH')) exit;

class WWGB_Booking_Handler {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'webwynk_bookings';
    }
    
    public function process_booking($data) {
        // Validate required fields
        $required = array('first_name', 'last_name', 'email', 'phone', 'date', 'time');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return array('success' => false, 'message' => 'Please fill in all required fields.');
            }
        }
        
        $first_name = sanitize_text_field($data['first_name']);
        $last_name = sanitize_text_field($data['last_name']);
        $email = sanitize_email($data['email']);
        $phone = sanitize_text_field($data['phone']);
        $message = isset($data['message']) ? sanitize_textarea_field($data['message']) : '';
        
        // Explode the hidden value string "Local_Time|Local_Date|IST_Time|IST_Date"
        $time_payload = sanitize_text_field($data['time']);
        $time_parts = explode('|', $time_payload);
        
        $client_display_date = sanitize_text_field($data['date']);
        
        if (count($time_parts) === 4) {
            $local_time = $time_parts[0];
            $local_date = $time_parts[1];
            $ist_time   = $time_parts[2];
            $ist_date   = $time_parts[3];
        } else {
            return array('success' => false, 'message' => 'Invalid slot selection.');
        }

        $timezone = isset($data['timezone']) ? sanitize_text_field($data['timezone']) : 'Asia/Kolkata';
        
        if (!is_email($email)) {
            return array('success' => false, 'message' => 'Please enter a valid email address.');
        }
        
        // Ensure the submitted local time is technically still in the future for safety
        try {
            $client_tz = new DateTimeZone($timezone);
            $now = new DateTime('now', $client_tz);
            $submitted_datetime = new DateTime($local_date . ' ' . $local_time, $client_tz);
            if ($submitted_datetime <= $now) {
                return array('success' => false, 'message' => 'This time slot is in the past. Please select a future time.');
            }
        } catch (Exception $e) {}

        // Insert booking (No conflict checking per requirement)
        global $wpdb;
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'booking_date' => $local_date,
                'booking_time' => $local_time,
                'ist_date' => $ist_date,
                'ist_time' => $ist_time,
                'timezone' => $timezone,
                'status' => 'confirmed',
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return array('success' => false, 'message' => 'An error occurred. Please try again.');
        }
        
        $booking_id = $wpdb->insert_id;
        
        // Send emails
        $email_sender = new WWGB_Email_Sender();
        $email_sender->send_user_confirmation($booking_id);
        $email_sender->send_admin_notification($booking_id);
        
        return array(
            'success' => true,
            'message' => 'Booking confirmed!',
            'booking_id' => $booking_id,
            'data' => array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'date' => $client_display_date,
                'timezone' => $timezone,
            )
        );
    }
    
    public function get_available_slots($date, $user_timezone = 'Asia/Kolkata') {
        $slot_duration = get_option('wwgb_slot_duration', 30);
        $agency_timezone = new DateTimeZone('Asia/Kolkata');
        
        $slots = array();

        $working_days = get_option('wwgb_working_days', array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'));
        $day_name = strtolower(date('D', strtotime($date)));
        
        if (!in_array($day_name, $working_days)) {
            return $slots;
        }
        
        try {
            $client_tz = new DateTimeZone($user_timezone);
        } catch (Exception $e) {
            $client_tz = $agency_timezone;
        }

        $slots = array();
        $now = new DateTime('now', $client_tz);

        // Define 8:00 AM to 11:50 PM in the user's local timezone
        try {
            $start_slot = new DateTime($date . ' 08:00:00', $client_tz);
            $end_slot = new DateTime($date . ' 23:50:00', $client_tz);
        } catch (Exception $e) {
            return $slots;
        }

        $current_slot_local = clone $start_slot;
        
        while ($current_slot_local <= $end_slot) {
            // Check: Is this slot physically in the future? Do not show past times for today.
            if ($current_slot_local > $now) {
                $time_str_client = $current_slot_local->format('g:i A');
                
                // Convert the user's local moment into the Agency's IST timezone
                $ist_slot = clone $current_slot_local;
                $ist_slot->setTimezone($agency_timezone);
                
                $ist_db_date = $ist_slot->format('Y-m-d');
                $ist_db_time = $ist_slot->format('H:i');
                
                // Save the local strings
                $local_db_date = $current_slot_local->format('Y-m-d');
                $local_db_time = $current_slot_local->format('H:i');
                
                // Return explicitly concatenated data value string containing all metrics
                $slots[] = array(
                    'display' => $time_str_client,
                    'value' => $local_db_time . '|' . $local_db_date . '|' . $ist_db_time . '|' . $ist_db_date
                );
            }
            // Advance 30 minutes
            $current_slot_local->modify("+{$slot_duration} minutes");
        }
        
        return $slots;
    }
    
    public function get_booking($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ), ARRAY_A);
    }
    
    public function update_status($id, $status) {
        global $wpdb;
        return $wpdb->update(
            $this->table_name,
            array('status' => $status),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }
    
    public function delete_booking($id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, array('id' => $id), array('%d'));
    }
}
