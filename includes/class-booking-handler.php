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
        // Explode the hidden value string "19:30|2026-03-24"
        $time_payload = sanitize_text_field($data['time']);
        $time_parts = explode('|', $time_payload);
        
        // $date was the client's display date during submission. We only care about the IST source-of-truth date now.
        $client_display_date = sanitize_text_field($data['date']);
        
        if (count($time_parts) === 2) {
            $db_time = $time_parts[0];
            $db_date = $time_parts[1];
        } else {
            return array('success' => false, 'message' => 'Invalid slot selection.');
        }

        $timezone = isset($data['timezone']) ? sanitize_text_field($data['timezone']) : 'Asia/Kolkata';
        
        if (!is_email($email)) {
            return array('success' => false, 'message' => 'Please enter a valid email address.');
        }
        
        // Check if slot is already booked
        if ($this->is_slot_booked($db_date, $db_time)) {
            return array('success' => false, 'message' => 'This time slot is no longer available. Please select another.');
        }
        
        // Insert booking
        global $wpdb;
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'booking_date' => $db_date,
                'booking_time' => $db_time,
                'timezone' => $timezone,
                'status' => 'confirmed',
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
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
    
    public function is_slot_booked($date, $time) {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE booking_date = %s AND booking_time = %s AND status != 'cancelled'",
            $date, $time
        ));
        return $count > 0;
    }
    
    public function get_available_slots($date, $user_timezone = 'Asia/Kolkata') {
        $working_days = get_option('wwgb_working_days', array('mon', 'tue', 'wed', 'thu', 'fri', 'sat'));
        $start_time = get_option('wwgb_start_time', '09:00');
        // If end time is theoretically "next day 2am", we handle it by checking if end < start
        $end_time = get_option('wwgb_end_time', '02:00'); 
        $slot_duration = get_option('wwgb_slot_duration', 30);
        
        $agency_timezone = new DateTimeZone('Asia/Kolkata');
        
        try {
            $client_tz = new DateTimeZone($user_timezone);
        } catch (Exception $e) {
            $client_tz = $agency_timezone;
        }

        $slots = array();
        
        // Because of dramatic timezone differences and overnight shifts, an agency shift 
        // starting on Wednesday in IST might translate to Tuesday in EST.
        // We evaluate a 3-day window of the *Agency's* shifts to find slots 
        // that fall on the specific $date requested by the *Client*.
        $dates_to_check = array(
            date('Y-m-d', strtotime($date . ' -1 day')),
            $date,
            date('Y-m-d', strtotime($date . ' +1 day'))
        );
        
        $now = new DateTime('now', $client_tz);

        foreach ($dates_to_check as $check_date) {
            $day_name = strtolower(date('D', strtotime($check_date)));
            
            // Is the agency open on this day?
            if (!in_array(substr($day_name, 0, 3), $working_days)) {
                continue; 
            }

            $current_slot_agency = new DateTime($check_date . ' ' . $start_time, $agency_timezone);
            $end_target_agency = new DateTime($check_date . ' ' . $end_time, $agency_timezone);
            
            // If the end time is numerically smaller than start time (e.g. 02:00 < 09:00), 
            // the shift spans past midnight into the *next calendar day*.
            if ($end_target_agency <= $current_slot_agency) {
                $end_target_agency->modify('+1 day');
            }

            while ($current_slot_agency <= $end_target_agency) {
                // Convert the exact Agency moment into the Client's timezone moment
                $client_slot = clone $current_slot_agency;
                $client_slot->setTimezone($client_tz);

                // Check 1: Does this moment land on the specific date the client clicked?
                if ($client_slot->format('Y-m-d') === $date) {
                    
                    // Check 2: Is this slot physically in the future? Do not show past times.
                    if ($client_slot > $now) {
                        
                        $time_str_client = $client_slot->format('g:i A');
                        
                        // We must check the database based on the actual Agency date/time 
                        // because that is our "Source of Truth"
                        $agency_db_date = $current_slot_agency->format('Y-m-d');
                        $agency_db_time = $current_slot_agency->format('H:i');
                        
                        if (!$this->is_slot_booked($agency_db_date, $agency_db_time)) {
                            // We return both. Display = local UI. Value = hidden payload for the DB.
                            $slots[] = array(
                                'display' => $time_str_client,
                                'value' => $agency_db_time . '|' . $agency_db_date
                            );
                        }
                    }
                }
                
                // Advance 30 minutes
                $current_slot_agency->modify("+{$slot_duration} minutes");
            }
        }
        
        // Sort slots chronologically for the client UI
        usort($slots, function($a, $b) {
            return strtotime($a['display']) - strtotime($b['display']);
        });

        // Filter out duplicates that might occur exactly on shift overlaps
        $unique_slots = array();
        $seen = array();
        foreach ($slots as $slot) {
            if (!isset($seen[$slot['display']])) {
                $seen[$slot['display']] = true;
                $unique_slots[] = $slot;
            }
        }

        return $unique_slots;
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
