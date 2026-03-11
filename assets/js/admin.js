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
                              '<p><strong>Message:</strong> ' + (booking.message || 'N/A') + '</p>';
                    
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
});
