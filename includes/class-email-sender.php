<?php
/**
 * Email sender
 */

if (!defined('ABSPATH'))
  exit;

class WWGB_Email_Sender
{

  public function send_user_confirmation($booking_id)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'webwynk_bookings';

    $booking = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$table_name} WHERE id = %d",
      $booking_id
    ));

    if (!$booking)
      return false;

    $subject = get_option('wwgb_user_email_subject', 'Your Consultation is Confirmed - WebWynk');
    $template = get_option('wwgb_user_email_template', $this->get_default_user_template());

    // Read precisely the explicitly-vetted local time that was booked directly by the customer's calendar
    $agency_tz = new DateTimeZone('Asia/Kolkata');
    $client_tz_string = !empty($booking->timezone) ? $booking->timezone : 'Asia/Kolkata';

    try {
      $client_tz = new DateTimeZone($client_tz_string);
    }
    catch (Exception $e) {
      $client_tz = $agency_tz;
    }

    // Treat as pure local time
    $booking_datetime = new DateTime($booking->booking_date . ' ' . $booking->booking_time, $client_tz);

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

    // Ensure legacy plain-text templates render line breaks correctly in HTML render
    if (strip_tags($message) == $message) {
      $message = nl2br($message);
    }

    // Note: Do NOT set a custom From: header here.
    // WP Mail SMTP (Gmail) requires From to match the authenticated account.
    // Let WP Mail SMTP control the From address automatically.
    $headers = array('Content-Type: text/html; charset=UTF-8');

    return wp_mail($booking->email, $subject, $message, $headers);
  }

  public function send_meeting_link($booking_id)
  {
    $handler = new WWGB_Booking_Handler();
    $booking = $handler->get_booking($booking_id);
    
    // get_booking() returns ARRAY_A — use array access throughout
    if (!$booking || empty($booking['meeting_link'])) return false;
    
    $subject = get_option('wwgb_meeting_email_subject', 'Your Meeting Link is Ready - WebWynk');
    $template = get_option('wwgb_meeting_email_template', $this->get_default_meeting_email_template());
    
    $client_tz_string = !empty($booking['timezone']) ? $booking['timezone'] : 'Asia/Kolkata';
    try {
      $client_tz = new DateTimeZone($client_tz_string);
    } catch (Exception $e) {
      $client_tz = new DateTimeZone('Asia/Kolkata');
    }
    $booking_datetime = new DateTime($booking['booking_date'] . ' ' . $booking['booking_time'], $client_tz);
    
    $placeholders = array(
        '{first_name}'   => $booking['first_name'],
        '{last_name}'    => $booking['last_name'],
        '{date}'         => $booking_datetime->format('l, F j, Y'),
        '{time}'         => $booking_datetime->format('g:i A'),
        '{timezone}'     => $client_tz_string,
        '{phone}'        => $booking['phone'],
        '{message}'      => $booking['message'],
        '{email}'        => $booking['email'],
        '{meeting_link}' => '<a href="' . esc_url($booking['meeting_link']) . '" style="color:#8169F1;">' . esc_html($booking['meeting_link']) . '</a>'
    );
    
    $message = str_replace(array_keys($placeholders), array_values($placeholders), $template);
    if (strip_tags($message) == $message) {
        $message = nl2br($message);
    }
    
    $from_name  = get_bloginfo('name');
    $from_email = get_option('wwgb_admin_email', get_option('admin_email'));
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
    );
    
    return wp_mail($booking['email'], $subject, $message, $headers);
  }

  public function send_admin_notification($booking_id)
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'webwynk_bookings';

    $booking = $wpdb->get_row($wpdb->prepare(
      "SELECT * FROM {$table_name} WHERE id = %d",
      $booking_id
    ));

    if (!$booking)
      return false;

    $admin_email = get_option('wwgb_admin_email', get_option('admin_email'));
    $subject = get_option('wwgb_admin_email_subject', 'New Booking Received - WebWynk');
    $template = get_option('wwgb_admin_email_template', $this->get_default_admin_template());

    // Admin always receives time in IST because Agency is in India
    $agency_tz = new DateTimeZone('Asia/Kolkata');

    $ist_date = (!empty($booking->ist_date) && $booking->ist_date !== '0000-00-00') ? $booking->ist_date : $booking->booking_date;
    $ist_time = !empty($booking->ist_time) ? $booking->ist_time : $booking->booking_time;

    $booking_datetime = new DateTime($ist_date . ' ' . $ist_time, $agency_tz);

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

    if (strip_tags($message) == $message) {
      $message = nl2br($message);
    }

    // Note: Do NOT set a custom From: header here.
    // WP Mail SMTP (Gmail) requires From to match the authenticated account.
    $headers = array('Content-Type: text/html; charset=UTF-8');

    return wp_mail($admin_email, $subject, $message, $headers);
  }

  private function get_default_user_template()
  {
    return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin:0; padding:0; background-color:#f5f6fb; font-family:Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f6fb;">
<tr>
<td align="center">

<table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff;">

  <!-- Header -->
  <tr>
    <td align="center" style="background-color:#efebfc; padding:25px;">
      <img src="https://webwynk.webwynk.com/wp-content/uploads/2026/03/webwynk-logo-email.jpg"
           alt="WebWynk"
           width="140"
           style="display:block; border:0;">
    </td>
  </tr>

  <!-- Body -->
  <tr>
    <td style="padding:25px; color:#333333; font-size:15px; line-height:1.6;">

      <p style="margin:0 0 10px 0;">Hi {first_name},</p>

      <p style="margin:0 0 10px 0;">
        Thank you for scheduling your consultation.
      </p>

      <p style="margin:0 0 10px 0;">
        We’ll send your Google Meet / Zoom link one day before your meeting.
      </p>

      <p style="margin:0 0 10px 0;">
        📅 Date: {date}<br>
        ⏰ Time: {time}<br>
        🌍 Timezone: {timezone}
      </p>

      <p style="margin:0 0 15px 0;">
        If you need to reschedule, please contact us as soon as possible.
      </p>

      <p style="margin:15px 0 0 0;">
        Regards,<br>
        <strong style="color:#8169F1;">WebWynk Team</strong>
      </p>

    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td align="center" style="background-color:#110645; padding:15px; font-size:13px; color:#ffffff;">

      <p style="margin:5px 0; color:#ffffff;">Connect With Us</p>

      <p style="margin:5px 0;">
        <a href="https://wa.me/919083895364" style="color:#8169F1; text-decoration:none;">WhatsApp</a> |
        <a href="mailto:contact@webwynk.com" style="color:#8169F1; text-decoration:none;">Email</a> |
        <a href="https://webwynk.com" style="color:#8169F1; text-decoration:none;">Website</a>
      </p>

      <p style="margin-top:10px; font-size:12px; color:#ffffff;">
        © 2026 WebWynk. All rights reserved.
      </p>

    </td>
  </tr>

</table>

</td>
</tr>
</table>

</body>
</html>';
  }

  private function get_default_admin_template()
  {
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

  private function get_default_meeting_email_template()
  {
    return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin:0; padding:0; background-color:#f5f6fb; font-family:Arial, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f5f6fb;">
<tr>
<td align="center">

<table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff;">

  <!-- Header -->
  <tr>
    <td align="center" style="background-color:#efebfc; padding:25px;">
      <img src="https://webwynk.webwynk.com/wp-content/uploads/2026/03/webwynk-logo-email.jpg"
           alt="WebWynk"
           width="140"
           style="display:block; border:0;">
    </td>
  </tr>

  <!-- Body -->
  <tr>
    <td style="padding:25px; color:#333333; font-size:15px; line-height:1.6;">

      <p style="margin:0 0 10px 0;">Hi {first_name},</p>

      <p style="margin:0 0 10px 0;">
        We’re excited to connect with you.
      </p>

      <p style="margin:0 0 10px 0;">
        Please find your consultation details below:
      </p>

      <p style="margin:0 0 10px 0;">
        🔗 Meeting Link: {meeting_link}<br>
        📅 Date: {date}<br>
        ⏰ Time: {time}<br>
        🌍 Timezone: {timezone}
      </p>

      <p style="margin:0 0 10px 0;">
        Kindly ensure you join on time so we can make the most of your session.
      </p>

      <p style="margin:0 0 15px 0;">
        If you need any assistance beforehand, feel free to reply to this email.
      </p>

      <p style="margin:15px 0 0 0;">
        Warm regards,<br>
        <strong style="color:#8169F1;">WebWynk Team</strong>
      </p>

    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td align="center" style="background-color:#110645; padding:15px; font-size:13px; color:#ffffff;">

      <p style="margin:5px 0; color:#ffffff;">Connect With Us</p>

      <p style="margin:5px 0;">
        <a href="https://wa.me/919083895364" style="color:#8169F1; text-decoration:none;">WhatsApp</a> |
        <a href="mailto:contact@webwynk.com" style="color:#8169F1; text-decoration:none;">Email</a> |
        <a href="https://webwynk.com" style="color:#8169F1; text-decoration:none;">Website</a>
      </p>

      <p style="margin-top:10px; font-size:12px; color:#ffffff;">
        © 2026 WebWynk. All rights reserved.
      </p>

    </td>
  </tr>

</table>

</td>
</tr>
</table>

</body>
</html>';
  }
}
