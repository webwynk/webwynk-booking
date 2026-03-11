<?php
/**
 * Email sender
 */

if (!defined('ABSPATH')) exit;

class WWGB_Email_Sender {
    
    public function send_user_confirmation($booking_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'webwynk_bookings';
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $booking_id
        ));
        
        if (!$booking) return false;
        
        $subject = get_option('wwgb_user_email_subject', 'Your Consultation is Confirmed - WebWynk');
        $template = get_option('wwgb_user_email_template', $this->get_default_user_template());
        
        // Convert IST source-of-truth back to Client's local time for their confirmation email
        $agency_tz = new DateTimeZone('Asia/Kolkata');
        $client_tz_string = !empty($booking->timezone) ? $booking->timezone : 'Asia/Kolkata';
        
        try {
            $client_tz = new DateTimeZone($client_tz_string);
        } catch (Exception $e) {
            $client_tz = $agency_tz;
        }
        
        $booking_datetime = new DateTime($booking->booking_date . ' ' . $booking->booking_time, $agency_tz);
        $booking_datetime->setTimezone($client_tz);

        $placeholders = array(
            '{first_name}' => $booking->first_name,
            '{last_name}' => $booking->last_name,
            '{date}' => $booking_datetime->format('l, F j, Y'),
            '{time}' => $booking_datetime->format('g:i A'),
            '{timezone}' => $client_tz_string,
            '{phone}' => $booking->phone,
            '{message}' => $booking->message,
            '{email}' => $booking->email,
        );
        
        $message = str_replace(array_keys($placeholders), array_values($placeholders), $template);
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        return wp_mail($booking->email, $subject, $message, $headers);
    }
    
    public function send_admin_notification($booking_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'webwynk_bookings';
        
        $booking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $booking_id
        ));
        
        if (!$booking) return false;
        
        $admin_email = get_option('wwgb_admin_email', get_option('admin_email'));
        $subject = get_option('wwgb_admin_email_subject', 'New Booking Received - WebWynk');
        $template = get_option('wwgb_admin_email_template', $this->get_default_admin_template());
        
        // Admin always receives time in IST because Agency is in India
        $agency_tz = new DateTimeZone('Asia/Kolkata');
        $booking_datetime = new DateTime($booking->booking_date . ' ' . $booking->booking_time, $agency_tz);

        $placeholders = array(
            '{first_name}' => $booking->first_name,
            '{last_name}' => $booking->last_name,
            '{date}' => $booking_datetime->format('l, F j, Y'),
            '{time}' => $booking_datetime->format('g:i A'),
            '{timezone}' => 'Asia/Kolkata (Your Agency Time)',
            '{phone}' => $booking->phone,
            '{message}' => $booking->message,
            '{email}' => $booking->email,
        );
        
        $message = str_replace(array_keys($placeholders), array_values($placeholders), $template);
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        return wp_mail($admin_email, $subject, $message, $headers);
    }
    
    private function get_default_user_template() {
        return "Hello {first_name},

Your consultation has been confirmed!

📅 Date: {date}
⏰ Time: {time}
🌍 Timezone: {timezone}

If you need to reschedule, please contact us as soon as possible.

Best regards,
WebWynk Team";
    }
    
    private function get_default_admin_template() {
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
}
