/**
 * Admin JavaScript
 */
jQuery(document).ready(function($) {
    // View booking details
    $(document).on('click', '.view-booking', function() {
        var bookingId = $(this).data('id');
        
        $.ajax({
            url: wwgb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wwgb_get_booking_details',
                nonce: wwgb_admin.nonce,
                booking_id: bookingId
            },
            success: function(response) {
                if (response.success) {
                    var booking = response.data;
                    var html = '<p><strong>Name:</strong> ' + booking.first_name + ' ' + booking.last_name + '</p>' +
                              '<p><strong>Email:</strong> ' + booking.email + '</p>' +
                              '<p><strong>Phone:</strong> ' + booking.phone + '</p>' +
                              '<p><strong>Date:</strong> ' + booking.booking_date + '</p>' +
                              '<p><strong>Time:</strong> ' + booking.booking_time + '</p>' +
                              '<p><strong>Timezone:</strong> ' + booking.timezone + '</p>' +
                              '<p><strong>Message:</strong> ' + (booking.message || 'N/A') + '</p>' +
                              '<hr>' +
                              '<h3>Meeting Link</h3>' +
                              '<p>Insert a Google Meet or Zoom link below and click Send.</p>' +
                              '<input type="url" id="meeting-link-input" style="width:100%; margin-bottom:10px;" value="' + (booking.meeting_link || '') + '" placeholder="https://meet.google.com/...">' +
                              '<button type="button" class="button button-primary send-meeting-link" data-id="' + booking.id + '">Save & Send Email</button>' +
                              '<span class="meeting-link-status" style="margin-left:10px; color:green; font-weight:bold;"></span>';
                    
                    $('#booking-details').html(html);
                    $('#booking-modal').show();
                }
            }
        });
    });
    
    // Close modal
    $('.wwgb-close').click(function() {
        $('#booking-modal').hide();
    });
    
    // Update status
    $(document).on('click', '.cancel-booking, .confirm-booking', function() {
        var bookingId = $(this).data('id');
        var newStatus = $(this).hasClass('cancel-booking') ? 'cancelled' : 'confirmed';
        var $btn = $(this);
        
        $.ajax({
            url: wwgb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wwgb_update_booking_status',
                nonce: wwgb_admin.nonce,
                booking_id: bookingId,
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });
    
    // Delete booking
    $(document).on('click', '.delete-booking', function() {
        if (!confirm('Are you sure you want to delete this booking?')) return;
        
        var bookingId = $(this).data('id');
        
        $.ajax({
            url: wwgb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wwgb_delete_booking',
                nonce: wwgb_admin.nonce,
                booking_id: bookingId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    // Save Meeting Link and Send Email
    $(document).on('click', '.send-meeting-link', function() {
        var bookingId = $(this).data('id');
        var meetingLink = $('#meeting-link-input').val();
        var $btn = $(this);
        var $status = $('.meeting-link-status');
        
        if (!meetingLink) {
            alert('Please enter a meeting link first.');
            return;
        }
        
        $btn.prop('disabled', true).text('Sending...');
        $status.text('');
        
        $.ajax({
            url: wwgb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wwgb_save_meeting_link',
                nonce: wwgb_admin.nonce,
                booking_id: bookingId,
                meeting_link: meetingLink
            },
            success: function(response) {
                if (response.success) {
                    $status.text('Link Saved & Email Sent!');
                    $btn.prop('disabled', false).text('Save & Send Email');
                } else {
                    $status.css('color', 'red').text(response.data || 'Failed to send.');
                    $btn.prop('disabled', false).text('Try Again');
                }
            },
            error: function() {
                $status.css('color', 'red').text('Network Error.');
                $btn.prop('disabled', false).text('Try Again');
            }
        });
    });
});
