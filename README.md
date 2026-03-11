# WebWynk Consultation Booking Plugin

A premium WordPress consultation booking plugin with glassmorphism UI design, 2-step booking flow, auto timezone detection, and comprehensive admin dashboard.

## ✨ Features

### Frontend
- **2-Step Booking Flow**: Date/Time selection → User details → Confirmation
- **Glassmorphism Design**: Modern frosted glass UI with backdrop blur
- **Auto Timezone Detection**: Detects user timezone via JavaScript Intl API
- **Dynamic Time Slots**: 30-minute intervals (customizable)
- **Real-time Availability**: Automatically disables booked slots
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Smooth Animations**: Framer Motion-style transitions

### Admin Features
- **Booking Dashboard**: View all bookings with status management
- **Booking Details Modal**: View complete booking information
- **Settings Panel**:
  - Working days (Mon-Sat default)
  - Start/End time configuration
  - Slot duration (15/30/45/60 min)
  - Message required toggle
  - Default timezone
- **Email Templates**: Customizable user and admin email templates
- **Status Management**: Confirm/Cancel/Delete bookings

### Email System
- User confirmation email
- Admin notification email
- Customizable templates with placeholders
- Placeholders: `{first_name}`, `{last_name}`, `{date}`, `{time}`, `{timezone}`, `{phone}`, `{message}`, `{email}`

## 🎨 Brand Colors
- **Primary**: `#8169f1` (Purple)
- **Accent**: `#ff512a` (Orange)

## 🚀 Installation

1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the zip file
4. Activate the plugin

## 📝 Usage

### Shortcode
Add the booking form to any page or post using:

```
[webwynk_booking]
```

The form will automatically render inside a container with id `#booking-form`.

### Elementor
Use the shortcode widget and paste `[webwynk_booking]`.

## ⚙️ Configuration

1. Go to **Dashboard → WebWynk Booking → Settings**
2. Configure working days and time slots
3. Set admin email for notifications
4. Customize email templates

## 📁 Database Schema

### Table: `wp_webwynk_bookings`

| Field | Type | Description |
|-------|------|-------------|
| id | bigint(20) | Primary key |
| first_name | varchar(100) | User first name |
| last_name | varchar(100) | User last name |
| email | varchar(255) | User email |
| phone | varchar(50) | User phone |
| message | text | Optional message |
| booking_date | date | Selected date |
| booking_time | time | Selected time |
| timezone | varchar(100) | User timezone |
| status | varchar(20) | confirmed/cancelled/pending |
| created_at | datetime | Booking timestamp |

## 📞 AJAX Endpoints

### Public
- `wwgb_get_available_slots` - Get available time slots for a date
- `wwgb_submit_booking` - Submit booking form
- `wwgb_detect_timezone` - Get default timezone

### Admin
- `wwgb_update_booking_status` - Update booking status
- `wwgb_delete_booking` - Delete booking
- `wwgb_get_booking_details` - Get single booking details

## 🖼️ File Structure

```
webwynk-booking/
├── webwynk-booking.php          # Main plugin file
├── includes/
│   ├── class-database.php          # Database operations
│   ├── class-booking-handler.php   # Booking logic
│   └── class-email-sender.php      # Email functionality
├── admin/
│   ├── class-admin-menu.php        # Admin menu/pages
│   └── class-bookings-list.php     # WP_List_Table for bookings
├── public/
│   └── class-shortcode.php         # Shortcode handler
├── assets/
│   ├── css/
│   │   ├── public.css                 # Frontend styles
│   │   └── admin.css                  # Admin styles
│   └── js/
│       ├── public.js                 # Frontend JavaScript
│       └── admin.js                  # Admin JavaScript
├── demo-preview.html             # Standalone UI preview
└── README.md                     # This file
```

## 🎯 Bonus Feature Suggestions

For future enhancements:

1. **Google Calendar Integration** - Auto-add events to Google Calendar
2. **SMS Notifications** - Twilio integration for SMS alerts
3. **Payment Integration** - Stripe/PayPal for paid consultations
4. **Buffer Time** - Set buffer between bookings
5. **Blackout Dates** - Block specific dates
6. **Admin Calendar View** - Visual calendar for admin
7. **Reschedule Link** - Allow users to reschedule
8. **Google Meet Auto-link** - Generate Meet links automatically
9. **Webhook Integration** - Zapier/Make.com support
10. **CSV Export** - Export bookings to CSV

## 📧 Support

For support or customizations, visit: [https://webwynk.com](https://webwynk.com)

## 📖 License

GPL v2 or later

---

**Author:** Hasanur Jaman  
**Company:** WebWynk  
**Version:** 1.0.0
