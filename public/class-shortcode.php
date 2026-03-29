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
            <div class="wwgb-container">
                <!-- Left Panel -->
                <div class="wwgb-glass-panel wwgb-info-panel">
                    <h2>Book a Consultation</h2>
                    <p>Schedule a 30-minute strategy session with our expert team. We'll discuss your project requirements and provide a tailored roadmap.</p>
                    
                    <div class="wwgb-info-item">
                        <div class="wwgb-info-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div class="wwgb-info-text">
                            <strong>30 Minutes</strong>
                            <span>Duration</span>
                        </div>
                    </div>
                    
                    <div class="wwgb-info-item">
                        <div class="wwgb-info-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        </div>
                        <div class="wwgb-info-text">
                            <strong id="user-timezone">Asia/Calcutta</strong>
                            <span>Your Timezone (Auto-detected)</span>
                        </div>
                    </div>
                </div>
                
                <!-- Right Panel (Wizard) -->
                <div class="wwgb-glass-panel wwgb-main-panel">
                    
                    <!-- NEW PROGRESS BAR -->
                    <div class="wwgb-progress-wrapper">
                        <div class="wwgb-progress-text" id="progress-text">STEP 1 OF 3</div>
                        <div class="wwgb-progress-bar-bg">
                            <div class="wwgb-progress-bar-fill" id="progress-fill"></div>
                        </div>
                    </div>

                    <!-- Step 1: Date & Time -->
                    <div class="wwgb-step-content active" data-step="1">
                        <div class="wwgb-datetime-grid">
                            
                            <div class="wwgb-calendar">
                                <div class="wwgb-calendar-header">
                                    <button class="wwgb-nav-btn" id="prev-month">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg>
                                    </button>
                                    <h3 id="current-month" style="margin:0; font-size:1rem; font-weight:600; color:white;">March 2026</h3>
                                    <button class="wwgb-nav-btn" id="next-month">
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                                    </button>
                                </div>
                                
                                <div class="wwgb-day-names">
                                    <span>SU</span><span>MO</span><span>TU</span><span>WE</span><span>TH</span><span>FR</span><span>SA</span>
                                </div>
                                <div class="wwgb-days" id="calendar-days"></div>
                            </div>
                            
                            <div class="wwgb-times">
                                <h3 class="wwgb-time-col-header">Select Time</h3>
                                <div class="wwgb-time-list" id="time-slots">
                                    <div class="wwgb-time-slot disabled">09:00 AM</div>
                                    <div class="wwgb-time-slot disabled">09:30 AM</div>
                                    <div class="wwgb-time-slot disabled">10:00 AM</div>
                                    <div class="wwgb-time-slot disabled">10:30 AM</div>
                                    <div class="wwgb-time-slot disabled">11:00 AM</div>
                                    <div class="wwgb-time-slot disabled">11:30 AM</div>
                                    <div class="wwgb-time-slot disabled">12:00 PM</div>
                                    <div class="wwgb-time-slot disabled">12:30 PM</div>
                                    <div class="wwgb-time-slot disabled">01:00 PM</div>
                                    <div class="wwgb-time-slot disabled">01:30 PM</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="wwgb-btn-wrapper">
                            <button class="wwgb-btn-gradient" id="btn-step-2" disabled>
                                Next Step
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 2: Details -->
                    <div class="wwgb-step-content" data-step="2">
                        
                        <button class="wwgb-back-btn" id="back-to-step-1" type="button">
                            &larr; Your Details
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
                                    <div class="wwgb-phone-input" id="wwgb-phone-container">
                                        <input type="hidden" name="country_code" value="+1">
                                        <div class="wwgb-custom-select-wrapper">
                                            <div class="wwgb-custom-select-trigger" id="wwgb-country-trigger">
                                                <img src="https://flagcdn.com/w20/us.png" alt="US"> <span>US +1</span>
                                            </div>
                                            <div class="wwgb-custom-select-options hidden" id="wwgb-country-options">
                                                <div class="wwgb-custom-option" data-value="+1" data-country="US"><img src="https://flagcdn.com/w20/us.png" alt="US"> US +1</div>
                                                <div class="wwgb-custom-option" data-value="+1" data-country="CA"><img src="https://flagcdn.com/w20/ca.png" alt="CA"> CA +1</div>
                                                <div class="wwgb-custom-option" data-value="+44" data-country="UK"><img src="https://flagcdn.com/w20/gb.png" alt="UK"> UK +44</div>
                                                <div class="wwgb-custom-option" data-value="+61" data-country="AU"><img src="https://flagcdn.com/w20/au.png" alt="AU"> AU +61</div>
                                                <div class="wwgb-custom-option" data-value="+91" data-country="IN"><img src="https://flagcdn.com/w20/in.png" alt="IN"> IN +91</div>
                                                <div class="wwgb-custom-option" data-value="+971" data-country="AE"><img src="https://flagcdn.com/w20/ae.png" alt="AE"> AE +971</div>
                                                <div class="wwgb-custom-option" data-value="+92" data-country="PK"><img src="https://flagcdn.com/w20/pk.png" alt="PK"> PK +92</div>
                                                <div class="wwgb-custom-option" data-value="+880" data-country="BD"><img src="https://flagcdn.com/w20/bd.png" alt="BD"> BD +880</div>
                                                <div class="wwgb-custom-option" data-value="+972" data-country="IL"><img src="https://flagcdn.com/w20/il.png" alt="IL"> IL +972</div>
                                                <div class="wwgb-custom-option" data-value="+49" data-country="DE"><img src="https://flagcdn.com/w20/de.png" alt="DE"> DE +49</div>
                                                <div class="wwgb-custom-option" data-value="+33" data-country="FR"><img src="https://flagcdn.com/w20/fr.png" alt="FR"> FR +33</div>
                                                <div class="wwgb-custom-option" data-value="+81" data-country="JP"><img src="https://flagcdn.com/w20/jp.png" alt="JP"> JP +81</div>
                                                <div class="wwgb-custom-option" data-value="+65" data-country="SG"><img src="https://flagcdn.com/w20/sg.png" alt="SG"> SG +65</div>
                                                <div class="wwgb-custom-option" data-value="+64" data-country="NZ"><img src="https://flagcdn.com/w20/nz.png" alt="NZ"> NZ +64</div>
                                                <div class="wwgb-custom-option" data-value="+27" data-country="ZA"><img src="https://flagcdn.com/w20/za.png" alt="ZA"> ZA +27</div>
                                            </div>
                                        </div>
                                        <input type="tel" name="phone" required placeholder="(555) 000-0000">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="wwgb-form-group wwgb-full-width">
                                <label>Message (<?php echo get_option('wwgb_message_required') ? 'Required' : 'Optional'; ?>)</label>
                                <textarea name="message" rows="3" placeholder="Tell us about your project..." <?php echo get_option('wwgb_message_required') ? 'required' : ''; ?>></textarea>
                            </div>
                            
                            <div class="wwgb-booking-summary">
                                <div class="wwgb-summary-content">
                                    <h4 id="summary-date">Thursday, March 26th, 2026</h4>
                                    <p id="summary-time">at 15:00 (Asia/Calcutta)</p>
                                </div>
                                <div class="wwgb-summary-right">
                                    <h4>Free</h4>
                                    <p>30 Minutes</p>
                                </div>
                            </div>
                            
                            <div class="wwgb-btn-wrapper">
                                <button type="submit" class="wwgb-btn-gradient">
                                    Confirm Booking
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Step 3: Success -->
                    <div class="wwgb-step-content" data-step="3">
                        <div class="wwgb-success-card">
                            <div class="wwgb-success-icon-wrapper">
                                <div class="wwgb-success-icon-inner">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                </div>
                            </div>
                            <h2>Booking Confirmed</h2>
                            <p class="wwgb-success-message">Your consultation has been scheduled successfully. You'll receive a confirmation email with the meeting link shortly.</p>
                            
                            <div class="wwgb-success-details" id="success-details">
                                <!-- Details injected by JS -->
                            </div>
                            
                            <div class="wwgb-btn-group">
                                <a href="/" class="wwgb-btn-outline">
                                    Back to Home
                                </a>
                                <div class="wwgb-calendar-dropdown">
                                    <button type="button" class="wwgb-btn-gradient" id="btn-add-calendar">
                                        Add to Calendar
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 8px; vertical-align: middle;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    </button>
                                    <div class="wwgb-dropdown-menu hidden" id="calendar-menu">
                                        <a href="#" id="cal-google" target="_blank">Google Calendar</a>
                                        <a href="#" id="cal-ics">iCal / Outlook</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
