/**
 * WebWynk Booking - Public JavaScript
 * Handles 2-step booking flow, calendar, timezone detection
 */

(function() {
    'use strict';

    // State management
    const state = {
        currentStep: 1,
        selectedDate: null,
        selectedTime: null,
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        timezone: 'Asia/Kolkata',
        bookingData: {}
    };

    // DOM Elements
    const elements = {
        calendarDays: document.getElementById('calendar-days'),
        currentMonth: document.getElementById('current-month'),
        prevMonth: document.getElementById('prev-month'),
        nextMonth: document.getElementById('next-month'),
        timeSlots: document.getElementById('time-slots'),
        btnStep2: document.getElementById('btn-step-2'),
        backToStep1: document.getElementById('back-to-step-1'),
        bookingForm: document.getElementById('booking-form-submit'),
        timezoneDisplay: document.getElementById('user-timezone'),
        summaryDate: document.getElementById('summary-date'),
        summaryTime: document.getElementById('summary-time'),
        successDetails: document.getElementById('success-details')
    };

    // Initialize
    function init() {
        setupCustomSelect();
        detectTimezone();
        detectCountryCode();
        renderCalendar();
        bindEvents();
    }

    // Detect country code based on IP
    function detectCountryCode() {
        fetch('https://ipapi.co/json/')
            .then(response => response.json())
            .then(data => {
                if (data) {
                    // Force timezone to match IP geolocation
                    if (data.timezone) {
                        state.timezone = data.timezone;
                        const tzDisplay = document.getElementById('user-timezone');
                        if (tzDisplay) {
                            tzDisplay.textContent = data.timezone.replace('_', ' ');
                        }
                    }

                    if (data.country) {
                        const countryIso = data.country;
                        const code = data.country_calling_code;
                        const optionsPanel = document.getElementById('wwgb-country-options');
                        if (optionsPanel) {
                            const option = optionsPanel.querySelector(`.wwgb-custom-option[data-country="${countryIso}"]`);
                            if (option) {
                                option.click();
                            } else if (code) {
                                const fallback = optionsPanel.querySelector(`.wwgb-custom-option[data-value="${code}"]`) || 
                                              optionsPanel.querySelector(`.wwgb-custom-option[data-value="+${code.replace('+','')}"]`);
                                if (fallback) fallback.click();
                            }
                        }
                    }
                }
            })
            .catch(e => console.warn('Could not detect country code:', e));
    }

    // Detect timezone
    function detectTimezone() {
        try {
            // Try to get from Intl API
            const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
            state.timezone = userTimezone;
            if (elements.timezoneDisplay) {
                elements.timezoneDisplay.textContent = userTimezone.replace('_', ' ');
            }
        } catch (e) {
            // Fallback to IP-based detection via AJAX
            fetch(wwgb_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=wwgb_detect_timezone&nonce=' + wwgb_ajax.nonce
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    state.timezone = data.data.timezone;
                    if (elements.timezoneDisplay) {
                        elements.timezoneDisplay.textContent = data.data.label;
                    }
                }
            });
        }
    }

    // Bind events
    function bindEvents() {
        if (elements.prevMonth) {
            elements.prevMonth.addEventListener('click', () => changeMonth(-1));
        }
        if (elements.nextMonth) {
            elements.nextMonth.addEventListener('click', () => changeMonth(1));
        }
        if (elements.btnStep2) {
            elements.btnStep2.addEventListener('click', goToStep2);
        }
        if (elements.backToStep1) {
            elements.backToStep1.addEventListener('click', goToStep1);
        }
        if (elements.bookingForm) {
            elements.bookingForm.addEventListener('submit', submitBooking);
        }
    }

    // Setup Custom Country Select
    function setupCustomSelect() {
        const trigger = document.getElementById('wwgb-country-trigger');
        const optionsPanel = document.getElementById('wwgb-country-options');
        const hiddenInput = document.querySelector('input[name="country_code"]');
        if (!trigger || !optionsPanel || !hiddenInput) return;

        // Toggle dropdown
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            optionsPanel.classList.toggle('hidden');
            trigger.classList.toggle('active');
        });

        // Handle selection
        const options = optionsPanel.querySelectorAll('.wwgb-custom-option');
        options.forEach(option => {
            option.addEventListener('click', function(e) {
                e.stopPropagation();
                const value = this.getAttribute('data-value');
                const countryCode = this.getAttribute('data-country');
                
                // Update trigger
                trigger.innerHTML = this.innerHTML;
                
                // Update hidden input
                hiddenInput.value = value;
                
                // Close dropdown
                optionsPanel.classList.add('hidden');
                trigger.classList.remove('active');
                
                updatePhonePlaceholderCustom(countryCode);
            });
        });

        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!trigger.contains(e.target) && !optionsPanel.contains(e.target)) {
                optionsPanel.classList.add('hidden');
                trigger.classList.remove('active');
            }
        });

        // Initial placeholder setup
        updatePhonePlaceholderCustom('US');
    }

    function updatePhonePlaceholderCustom(countryCode) {
        const input = document.querySelector('input[name="phone"]');
        if (!input) return;

        const placeholders = {
            'US': '(555) 000-0000',
            'CA': '(555) 000-0000',
            'UK': '07000 000000',
            'AU': '0400 000 000',
            'IN': '98000 00000',
            'AE': '50 000 0000',
            'PK': '300 0000000',
            'BD': '01700-000000',
            'IL': '050-000-0000',
            'DE': '0151 2345678',
            'FR': '06 12 34 56 78',
            'JP': '090-1234-5678',
            'SG': '8123 4567',
            'NZ': '021 123 4567',
            'ZA': '082 123 4567'
        };

        input.placeholder = placeholders[countryCode] || 'Enter phone number';
    }

    // Calendar functions
    function changeMonth(direction) {
        state.currentMonth += direction;
        if (state.currentMonth > 11) {
            state.currentMonth = 0;
            state.currentYear++;
        } else if (state.currentMonth < 0) {
            state.currentMonth = 11;
            state.currentYear--;
        }
        renderCalendar();
    }

    function renderCalendar() {
        if (!elements.calendarDays || !elements.currentMonth) return;

        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        
        elements.currentMonth.textContent = `${monthNames[state.currentMonth]} ${state.currentYear}`;
        
        const firstDay = new Date(state.currentYear, state.currentMonth, 1).getDay();
        const daysInMonth = new Date(state.currentYear, state.currentMonth + 1, 0).getDate();
        const today = new Date();
        
        let html = '';
        
        // Empty cells for days before the 1st
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="wwgb-day empty"></div>';
        }
        
        // Days
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(state.currentYear, state.currentMonth, day);
            const dateStr = formatDate(date);
            const isPast = date < new Date(today.setHours(0, 0, 0, 0));
            const isSelected = state.selectedDate === dateStr;
            let className = 'wwgb-day';
            if (isPast) {
                className += ' disabled';
            }
            if (isSelected) {
                className += ' selected';
            }
            
            if (!isPast) {
                html += `<div class="${className}" data-date="${dateStr}">${day}</div>`;
            } else {
                html += `<div class="${className}">${day}</div>`;
            }
        }
        
        elements.calendarDays.innerHTML = html;
        
        // Add click handlers
        document.querySelectorAll('.wwgb-day[data-date]').forEach(day => {
            day.addEventListener('click', () => selectDate(day.dataset.date));
        });
    }

    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function selectDate(dateStr) {
        state.selectedDate = dateStr;
        state.selectedTime = null;
        renderCalendar();
        loadTimeSlots();
        updateNextButton();
    }

    // Load time slots via AJAX
    function loadTimeSlots() {
        if (!elements.timeSlots) return;
        
        elements.timeSlots.innerHTML = '<div class="wwgb-loading"></div>';
        
        const formData = new FormData();
        formData.append('action', 'wwgb_get_available_slots');
        formData.append('nonce', wwgb_ajax.nonce);
        formData.append('date', state.selectedDate);
        formData.append('timezone', state.timezone);
        
        fetch(wwgb_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.slots.length > 0) {
                renderTimeSlots(data.data.slots);
            } else {
                elements.timeSlots.innerHTML = '<p class="wwgb-select-date-first">No available slots for this date</p>';
            }
        })
        .catch(() => {
            elements.timeSlots.innerHTML = '<p class="wwgb-select-date-first">Error loading slots</p>';
        });
    }

    function renderTimeSlots(slots) {
        if (!elements.timeSlots) return;
        
        // slots is now an array of objects: { display: '10:00 AM', value: '19:30|2026-03-24' }
        let html = '';
        slots.forEach(slot => {
            const isSelected = state.selectedTime === slot.value;
            const className = isSelected ? 'wwgb-time-slot selected' : 'wwgb-time-slot';
            // Store the true IST value in data-time, show display text inside div
            html += `<div class="${className}" data-time="${slot.value}" data-display="${slot.display}">${slot.display}</div>`;
        });
        
        elements.timeSlots.innerHTML = html;
        
        document.querySelectorAll('.wwgb-time-slot').forEach(slot => {
            slot.addEventListener('click', () => selectTime(slot.dataset.time, slot.dataset.display));
        });
    }

    function selectTime(timeValue, timeDisplay) {
        state.selectedTime = timeValue;  // This holds "19:30|2026-03-24" (IST schedule)
        state.selectedTimeDisplay = timeDisplay; // This holds what the user sees "10:00 AM"

        document.querySelectorAll('.wwgb-time-slot').forEach(slot => {
            slot.classList.toggle('selected', slot.dataset.time === timeValue);
        });
        updateNextButton();
    }

    function updateNextButton() {
        if (elements.btnStep2) {
            elements.btnStep2.disabled = !(state.selectedDate && state.selectedTime);
        }
    }

    // Step navigation
    function goToStep2() {
        state.currentStep = 2;
        updateSteps();
        updateSummary();
    }

    function goToStep1() {
        state.currentStep = 1;
        updateSteps();
    }

    function updateSteps() {
        document.querySelectorAll('.wwgb-step-content').forEach(content => {
            const stepNum = parseInt(content.dataset.step);
            content.classList.toggle('active', stepNum === state.currentStep);
        });

        // Continuous line progress updates
        const progressText = document.getElementById('progress-text');
        const progressFill = document.getElementById('progress-fill');
        if (progressText && progressFill) {
            progressText.innerText = `STEP ${state.currentStep} OF 3`;
            if (state.currentStep === 1) progressFill.style.width = '33.33%';
            if (state.currentStep === 2) progressFill.style.width = '66.66%';
            if (state.currentStep === 3) progressFill.style.width = '100%';
        }
    }

    function updateSummary() {
        if (elements.summaryDate && state.selectedDate) {
            const date = new Date(state.selectedDate);
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            elements.summaryDate.textContent = date.toLocaleDateString('en-US', options);
        }
        if (elements.summaryTime && state.selectedTime) {
            elements.summaryTime.textContent = `at ${state.selectedTimeDisplay} (${state.timezone})`;
        }
    }

    // Form submission
    function submitBooking(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="wwgb-loading"></span> Processing...';
        
        // Remove any existing error messages
        const existingError = form.querySelector('.wwgb-error');
        if (existingError) existingError.remove();
        
        const formData = new FormData(form);
        formData.append('action', 'wwgb_submit_booking');
        formData.append('nonce', wwgb_ajax.nonce);
        formData.append('date', state.selectedDate);
        formData.append('time', state.selectedTime);
        formData.append('timezone', state.timezone);
        
        // Combine country code with phone
        const countryCode = form.querySelector('[name="country_code"]').value;
        const phone = form.querySelector('[name="phone"]').value;
        formData.set('phone', countryCode + ' ' + phone);
        
        fetch(wwgb_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // We pass in the display metrics so the success screen looks perfect for the user
                data.data.display_time = state.selectedTimeDisplay;
                showSuccess(data.data);
            } else {
                showError(form, data.data.message || 'An error occurred. Please try again.');
            }
        })
        .catch(() => {
            showError(form, 'Network error. Please try again.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    }

    function showError(form, message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'wwgb-error';
        errorDiv.textContent = message;
        form.insertBefore(errorDiv, form.firstChild);
    }

    function showSuccess(data) {
        state.currentStep = 3;
        updateSteps();
        
        if (elements.successDetails) {
            const date = new Date(state.selectedDate);
            const dateStr = date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            
            elements.successDetails.innerHTML = `
                <div class="detail-row">
                    <span class="label">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        Name
                    </span>
                    <span class="value">${data.first_name} ${data.last_name}</span>
                </div>
                <div class="detail-row">
                    <span class="label">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                        Email
                    </span>
                    <span class="value">${data.email}</span>
                </div>
                <div class="detail-row">
                    <span class="label">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        Date
                    </span>
                    <span class="value">${dateStr}</span>
                </div>
                <div class="detail-row">
                    <span class="label">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        Time
                    </span>
                    <span class="value">${data.display_time}</span>
                </div>
                <div class="detail-row">
                    <span class="label">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        Timezone
                    </span>
                    <span class="value" style="color: var(--wwgb-highlight);">${data.timezone}</span>
                </div>
            `;
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
