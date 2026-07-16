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
            
            // Validate required fields
            if (!this.validateForm()) {
                return;
            }
            
            // Get signature data
            if (this.signaturePad && !this.signaturePad.isEmpty()) {
                $('#signature-data').val(this.signaturePad.toDataURL());
            }
            
            // Prepare form data
            const formData = new FormData(this.$form[0]);
            formData.append('action', 'submit_dealer_registration');
            
            // Show loading state
            this.$form.addClass('loading');
            this.$messages.removeClass('success error').hide();
            
            // Submit via AJAX
            $.ajax({
                url: decorB2B.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.$form.removeClass('loading');
                    
                    if (response.success) {
                        this.$messages
                            .removeClass('error')
                            .addClass('success')
                            .html(response.data.message)
                            .show();
                        
                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: this.$messages.offset().top - 100
                        }, 500);
                        
                        // Reset form
                        this.$form[0].reset();
                        if (this.signaturePad) {
                            this.signaturePad.clear();
                        }
                        
                        // Redirect after delay
                        if (response.data.redirect) {
                            setTimeout(() => {
                                // window.location.href = response.data.redirect;
                            }, 3000);
                        }
                    } else {
                        this.$messages
                            .removeClass('success')
                            .addClass('error')
                            .html(response.data.message)
                            .show();
                        
                        $('html, body').animate({
                            scrollTop: this.$messages.offset().top - 100
                        }, 500);
                    }
                },
                error: (xhr, status, error) => {
                    this.$form.removeClass('loading');
                    this.$messages
                        .removeClass('success')
                        .addClass('error')
                        .html('An error occurred. Please try again.')
                        .show();
                }
            });
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
