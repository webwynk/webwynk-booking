<?php
/**
 * Shortcode handler
 */

if (!defined('ABSPATH')) exit;

class WWGB_Shortcode {
    
    public function render_booking_form($atts) {
        ob_start();
        ?>
        <div id="booking-form" class="webwynk-booking-container">
            <div class="wwgb-booking-layout">
                <!-- Left Panel -->
                <div class="wwgb-left-panel">
                    <div class="wwgb-info-card">
                        <h2>Book a<br>Consultation</h2>
                        <p>Schedule a 30-minute strategy session with our expert team. We'll discuss your project requirements and provide a tailored roadmap.</p>
                        
                        <div class="wwgb-info-items">
                            <div class="wwgb-info-item">
                                <div class="wwgb-info-icon">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                                    </svg>
                                </div>
                                <div class="wwgb-info-text">
                                    <strong>30 Minutes</strong>
                                    <span>Duration</span>
                                </div>
                            </div>
                            <div class="wwgb-info-item">
                                <div class="wwgb-info-icon">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/>
                                    </svg>
                                </div>
                                <div class="wwgb-info-text">
                                    <strong id="user-timezone">Asia/Calcutta</strong>
                                    <span>Your Timezone (Auto-detected)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Panel -->
                <div class="wwgb-right-panel">
                    <!-- Step Indicator Moved Here -->
                    <div class="wwgb-progress-wrapper" style="display: flex; justify-content: center; margin-bottom: 30px;">
                        <div class="wwgb-step-indicator" style="width: 100%; max-width: 400px; margin-bottom: 0;">
                            <div class="wwgb-step active" data-step="1">
                                <div class="wwgb-step-number">1</div>
                                <div class="wwgb-step-line"></div>
                            </div>
                            <div class="wwgb-step" data-step="2">
                                <div class="wwgb-step-number">2</div>
                            </div>
                        </div>
                    </div>
                    <!-- Step 1: Date & Time -->
                    <div class="wwgb-step-content active" data-step="1">
                        <div class="wwgb-datetime-layout">
                            <div class="wwgb-calendar-section">
                                <div class="wwgb-calendar-header">
                                    <button class="wwgb-nav-btn" id="prev-month">
                                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                                            <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
                                        </svg>
                                    </button>
                                    <h3 id="current-month">March 2026</h3>
                                    <button class="wwgb-nav-btn" id="next-month">
                                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                                            <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
                                        </svg>
                                    </button>
                                </div>
                                
                                <div class="wwgb-calendar-grid">
                                    <div class="wwgb-day-names">
                                        <span>SU</span><span>MO</span><span>TU</span><span>WE</span>
                                        <span>TH</span><span>FR</span><span>SA</span>
                                    </div>
                                    <div class="wwgb-days" id="calendar-days"></div>
                                </div>
                            </div>
                            
                            <div class="wwgb-time-section">
                                <h3>Select Time</h3>
                                <div class="wwgb-time-slots" id="time-slots">
                                    <p class="wwgb-select-date-first">Please select a date first</p>
                                </div>
                            </div>
                        </div>
                        
                        <button class="wwgb-btn-gradient" id="btn-step-2" disabled>
                            Next Step →
                        </button>
                    </div>
                    
                    <!-- Step 2: Your Details -->
                    <div class="wwgb-step-content" data-step="2">
                        <button class="wwgb-back-btn" id="back-to-step-1">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                            </svg>
                            Your Details
                        </button>
                        
                        <form id="booking-form-submit">
                            <div class="wwgb-form-row">
                                <div class="wwgb-form-group">
                                    <label>First Name</label>
                                    <input type="text" name="first_name" required placeholder="John">
                                </div>
                                <div class="wwgb-form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" required placeholder="Doe">
                                </div>
                            </div>
                            
                            <div class="wwgb-form-row">
                                <div class="wwgb-form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" required placeholder="john@example.com">
                                </div>
                                <div class="wwgb-form-group">
                                    <label>Phone</label>
                                    <div class="wwgb-phone-input">
                                        <select name="country_code" class="wwgb-country-select">
                                            <option value="+91">IN +91</option>
                                            <option value="+1">US +1</option>
                                            <option value="+44">UK +44</option>
                                            <option value="+61">AU +61</option>
                                            <option value="+971">AE +971</option>
                                        </select>
                                        <input type="tel" name="phone" required placeholder="(555) 000-0000">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="wwgb-form-group">
                                <label>Message (<?php echo get_option('wwgb_message_required') ? 'Required' : 'Optional'; ?>)</label>
                                <textarea name="message" rows="4" placeholder="Tell us about your project..." <?php echo get_option('wwgb_message_required') ? 'required' : ''; ?>></textarea>
                            </div>
                            
                            <div class="wwgb-booking-summary">
                                <div class="wwgb-summary-content">
                                    <strong id="summary-date">Thursday, March 26th, 2026</strong>
                                    <span id="summary-time">15:00 • Asia/Calcutta</span>
                                </div>
                                <div class="wwgb-summary-check">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                    </svg>
                                </div>
                            </div>
                            
                            <button type="submit" class="wwgb-btn-gradient">
                                Confirm Booking
                            </button>
                        </form>
                    </div>
                    
                    <!-- Success Screen -->
                    <div class="wwgb-step-content" data-step="3">
                        <div class="wwgb-success-card">
                            <div class="wwgb-success-icon">
                                <svg viewBox="0 0 24 24" width="48" height="48" fill="currentColor">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                </svg>
                            </div>
                            <h2>Booking Confirmed</h2>
                            <p class="wwgb-success-message">Your consultation has been scheduled successfully. You'll receive a confirmation email with the meeting link shortly.</p>
                            <div class="wwgb-success-details" id="success-details"></div>
                            <a href="https://webwynk.com" class="wwgb-btn-gradient">
                                Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
