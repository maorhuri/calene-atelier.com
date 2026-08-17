/**
 * WooCommerce B2B JavaScript
 * Handles dealer registration form and signature pad
 */

(function($) {
    'use strict';

    // Signature Pad Class
    class SignaturePad {
        constructor(canvas) {
            this.canvas = canvas;
            this.ctx = canvas.getContext('2d');
            this.isDrawing = false;
            this.lastX = 0;
            this.lastY = 0;
            
            this.init();
        }
        
        init() {
            // Set canvas size
            this.resize();
            
            // Event listeners
            this.canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
            this.canvas.addEventListener('mousemove', (e) => this.draw(e));
            this.canvas.addEventListener('mouseup', () => this.stopDrawing());
            this.canvas.addEventListener('mouseout', () => this.stopDrawing());
            
            // Touch events
            this.canvas.addEventListener('touchstart', (e) => this.startDrawing(e));
            this.canvas.addEventListener('touchmove', (e) => this.draw(e));
            this.canvas.addEventListener('touchend', () => this.stopDrawing());
            
            // Resize handler
            window.addEventListener('resize', () => this.resize());
        }
        
        resize() {
            const rect = this.canvas.getBoundingClientRect();
            this.canvas.width = rect.width;
            this.canvas.height = rect.height;
            
            // Set drawing style
            this.ctx.strokeStyle = '#000';
            this.ctx.lineWidth = 2;
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
        }
        
        getPosition(e) {
            const rect = this.canvas.getBoundingClientRect();
            
            if (e.touches) {
                return {
                    x: e.touches[0].clientX - rect.left,
                    y: e.touches[0].clientY - rect.top
                };
            }
            
            return {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top
            };
        }
        
        startDrawing(e) {
            e.preventDefault();
            this.isDrawing = true;
            const pos = this.getPosition(e);
            this.lastX = pos.x;
            this.lastY = pos.y;
        }
        
        draw(e) {
            if (!this.isDrawing) return;
            e.preventDefault();
            
            const pos = this.getPosition(e);
            
            this.ctx.beginPath();
            this.ctx.moveTo(this.lastX, this.lastY);
            this.ctx.lineTo(pos.x, pos.y);
            this.ctx.stroke();
            
            this.lastX = pos.x;
            this.lastY = pos.y;
        }
        
        stopDrawing() {
            this.isDrawing = false;
        }
        
        clear() {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        }
        
        isEmpty() {
            const pixelData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height).data;
            return !pixelData.some(channel => channel !== 0);
        }
        
        toDataURL() {
            return this.canvas.toDataURL('image/png');
        }
    }

    // Dealer Registration Form Handler
    class DealerRegistrationForm {
        constructor() {
            // Support both old and new form IDs
            this.$form = $('#dealer-registration-form, #trade-application-form').first();
            this.$messages = this.$form.find('.form-messages');
            this.signaturePad = null;
            
            // Always try to init signature pad even without form
            this.initSignaturePad();
            
            if (this.$form.length) {
                this.init();
            }
        }
        
        initSignaturePad() {
            // Initialize signature pad
            const canvas = document.getElementById('signature-pad');
            if (canvas) {
                // Make sure canvas has proper dimensions
                const container = canvas.parentElement;
                if (container) {
                    canvas.width = container.offsetWidth || 400;
                    canvas.height = 150;
                }
                
                this.signaturePad = new SignaturePad(canvas);
                
                // Clear signature button
                $('.clear-signature-btn').on('click', () => {
                    if (this.signaturePad) {
                        this.signaturePad.clear();
                    }
                });
                
                console.log('Signature pad initialized');
            }
        }
        
        init() {
            
            // Form submission
            this.$form.on('submit', (e) => this.handleSubmit(e));
            
            // Auto-fill print name from first/last name
            $('#first_name, #last_name').on('change', () => {
                const firstName = $('#first_name').val();
                const lastName = $('#last_name').val();
                if (firstName && lastName) {
                    $('#print_name').val(firstName + ' ' + lastName);
                }
            });
        }
        
        handleSubmit(e) {
            e.preventDefault();
            
            console.log('Form submit triggered');
            
            // Validate required fields
            if (!this.validateForm()) {
                console.log('Form validation failed');
                return;
            }
            
            console.log('Form validation passed');
            
            // Get signature data (optional - don't block if no signature)
            if (this.signaturePad && !this.signaturePad.isEmpty()) {
                $('#signature-data').val(this.signaturePad.toDataURL());
            }
            
            // Prepare form data
            const formData = new FormData(this.$form[0]);
            formData.append('action', 'submit_dealer_registration');
            
            // Show loading state
            this.$form.addClass('loading');
            this.$form.find('.trade-submit-btn').prop('disabled', true).text('Submitting...');
            this.$messages.removeClass('success error').hide();
            
            console.log('Submitting to:', decorB2B.ajaxUrl);
            
            // Submit via AJAX
            $.ajax({
                url: decorB2B.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    console.log('AJAX response:', response);
                    this.$form.removeClass('loading');
                    this.$form.find('.trade-submit-btn').prop('disabled', false).text('Submit Trade Application');
                    
                    if (response.success) {
                        // Show beautiful Thank You modal
                        this.showThankYouModal();
                        
                        // Reset form
                        this.$form[0].reset();
                        if (this.signaturePad) {
                            this.signaturePad.clear();
                        }
                    } else {
                        this.$messages
                            .removeClass('success')
                            .addClass('error')
                            .html(response.data.message || 'An error occurred.')
                            .show();
                        
                        $('html, body').animate({
                            scrollTop: this.$messages.offset().top - 100
                        }, 500);
                    }
                },
                error: (xhr, status, error) => {
                    console.log('AJAX error:', status, error);
                    this.$form.removeClass('loading');
                    this.$form.find('.trade-submit-btn').prop('disabled', false).text('Submit Trade Application');
                    this.$messages
                        .removeClass('success')
                        .addClass('error')
                        .html('An error occurred. Please try again.')
                        .show();
                }
            });
        }
        
        showThankYouModal() {
            // Remove existing modal
            $('.trade-thank-you-modal').remove();
            
            const modalHtml = `
                <div class="trade-thank-you-modal">
                    <div class="thank-you-content">
                        <button class="modal-close">&times;</button>
                        <div class="thank-you-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#c4a47c" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                        <h2>THANK YOU FOR APPLYING</h2>
                        <p>Your Trade Access application has been received.</p>
                        <p>We'll review your details and be in touch once your account is approved.</p>
                        <a href="/shop/" class="explore-btn">EXPLORE THE COLLECTION</a>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            // Add styles if not already added
            if (!$('#trade-thank-you-styles').length) {
                const styles = `
                    <style id="trade-thank-you-styles">
                        .trade-thank-you-modal {
                            position: fixed;
                            top: 0;
                            left: 0;
                            right: 0;
                            bottom: 0;
                            background: rgba(0, 0, 0, 0.7);
                            z-index: 999999;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            padding: 20px;
                            animation: fadeIn 0.3s ease;
                        }
                        
                        @keyframes fadeIn {
                            from { opacity: 0; }
                            to { opacity: 1; }
                        }
                        
                        .trade-thank-you-modal .thank-you-content {
                            background: #fff;
                            padding: 60px 50px;
                            max-width: 500px;
                            width: 100%;
                            text-align: center;
                            position: relative;
                            border-radius: 4px;
                            animation: slideUp 0.4s ease;
                        }
                        
                        @keyframes slideUp {
                            from { 
                                opacity: 0;
                                transform: translateY(30px);
                            }
                            to { 
                                opacity: 1;
                                transform: translateY(0);
                            }
                        }
                        
                        .trade-thank-you-modal .modal-close {
                            position: absolute;
                            top: 15px;
                            right: 20px;
                            background: none;
                            border: none;
                            font-size: 30px;
                            color: #999;
                            cursor: pointer;
                            line-height: 1;
                        }
                        
                        .trade-thank-you-modal .modal-close:hover {
                            color: #333;
                        }
                        
                        .trade-thank-you-modal .thank-you-icon {
                            margin-bottom: 25px;
                        }
                        
                        .trade-thank-you-modal h2 {
                            font-size: 24px;
                            font-weight: 600;
                            letter-spacing: 3px;
                            color: #1a1a1a;
                            margin: 0 0 20px 0;
                        }
                        
                        .trade-thank-you-modal p {
                            font-size: 15px;
                            color: #666;
                            line-height: 1.7;
                            margin: 0 0 10px 0;
                        }
                        
                        .trade-thank-you-modal .explore-btn {
                            display: inline-block;
                            margin-top: 30px;
                            padding: 15px 40px;
                            background: #c4a47c;
                            color: #fff;
                            text-decoration: none;
                            font-size: 13px;
                            font-weight: 600;
                            letter-spacing: 2px;
                            transition: background 0.2s;
                        }
                        
                        .trade-thank-you-modal .explore-btn:hover {
                            background: #b39369;
                            color: #fff;
                        }
                    </style>
                `;
                $('head').append(styles);
            }
            
            // Close modal on click
            $('.trade-thank-you-modal').on('click', function(e) {
                if ($(e.target).hasClass('trade-thank-you-modal') || 
                    $(e.target).hasClass('modal-close')) {
                    $(this).fadeOut(200, function() {
                        $(this).remove();
                    });
                }
            });
            
            // Show modal
            $('.trade-thank-you-modal').hide().fadeIn(300);
        }
        
        validateForm() {
            let isValid = true;
            const requiredFields = [
                'first_name', 'last_name', 'company', 'address_1', 
                'city', 'state', 'zip', 'country', 'phone', 'email', 'business_type'
            ];
            
            // Remove previous error states
            this.$form.find('.form-group').removeClass('has-error');
            
            requiredFields.forEach(field => {
                const $field = this.$form.find(`[name="${field}"]`);
                if (!$field.val()) {
                    $field.closest('.form-group').addClass('has-error');
                    isValid = false;
                }
            });
            
            // Validate email format
            const email = this.$form.find('[name="email"]').val();
            if (email && !this.isValidEmail(email)) {
                this.$form.find('[name="email"]').closest('.form-group').addClass('has-error');
                isValid = false;
            }
            
            // Check terms agreement
            if (!this.$form.find('[name="agree_terms"]').is(':checked')) {
                this.$messages
                    .removeClass('success')
                    .addClass('error')
                    .html('Please agree to the Terms & Conditions.')
                    .show();
                isValid = false;
            }
            
            if (!isValid) {
                // Scroll to first error
                const $firstError = this.$form.find('.has-error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 500);
                }
            }
            
            return isValid;
        }
        
        isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    }

    // Initialize on document ready
    $(document).ready(function() {
        new DealerRegistrationForm();
    });

})(jQuery);
