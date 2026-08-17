/**
 * Attribute Pricing - Frontend JavaScript
 * Calculates dynamic pricing based on selected attributes
 */

(function($) {
    'use strict';

    class AttributePricingCalculator {
        constructor() {
            this.basePrice = parseFloat(attributePricing.basePrice) || 0;
            this.selections = {};
            this.priceCache = {};
            
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.updatePriceDisplay();
        }
        
        bindEvents() {
            const self = this;
            
            // Listen for attribute selection changes (from fabric selector)
            $(document).on('attribute-selected', function(e, data) {
                if (data && data.attribute && data.value) {
                    self.selections[data.attribute] = data.value;
                    self.calculatePrice();
                }
            });
            
            // Listen for swatch and option clicks - use setTimeout to let template JS run first
            $(document).on('click', '.swatch-item[data-attribute], .attribute-option[data-attribute]', function() {
                const $el = $(this);
                const attribute = $el.data('attribute');
                
                if (attribute) {
                    // Use setTimeout to let the template JS update the hidden input first
                    setTimeout(function() {
                        self.syncSelectionsFromInputs();
                        self.calculatePrice();
                    }, 10);
                }
            });
            
            // Listen for clear selection button clicks
            $(document).on('click', '.clear-selection-btn[data-attribute]', function() {
                const attribute = $(this).data('attribute');
                if (attribute) {
                    // Use setTimeout to let the template JS clear the input first
                    setTimeout(function() {
                        self.syncSelectionsFromInputs();
                        self.calculatePrice();
                    }, 10);
                }
            });
            
            // Listen for select changes (standard WooCommerce)
            $(document).on('change', 'select[name^="attribute_"]', function() {
                const name = $(this).attr('name').replace('attribute_', '');
                const value = $(this).val();
                
                if (value) {
                    self.selections[name] = value;
                } else {
                    delete self.selections[name];
                }
                self.calculatePrice();
            });
        }
        
        // Sync selections from hidden inputs (after template JS updates them)
        syncSelectionsFromInputs() {
            const self = this;
            this.selections = {};
            
            // Read all hidden attribute inputs
            $('input[name^="attribute_"]').each(function() {
                // Use original name if available (for custom attributes like SIZE)
                const originalName = $(this).data('original-name');
                const name = originalName || $(this).attr('name').replace('attribute_', '');
                const value = $(this).val();
                
                if (value) {
                    self.selections[name] = value;
                }
            });
        }
        
        calculatePrice() {
            const self = this;
            
            // Create cache key
            const cacheKey = JSON.stringify(this.selections);
            
            // Check cache
            if (this.priceCache[cacheKey]) {
                this.updatePriceDisplay(this.priceCache[cacheKey]);
                return;
            }
            
            // Get product ID
            const productId = $('input[name="product_id"]').val() || 
                              $('button[name="add-to-cart"]').val() ||
                              $('.product').data('product-id') ||
                              $('form.cart').find('input[name="add-to-cart"]').val();
            
            if (!productId) {
                return;
            }
            
            $.ajax({
                url: attributePricing.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'calculate_attribute_price',
                    nonce: attributePricing.nonce,
                    product_id: productId,
                    selections: this.selections
                },
                success: function(response) {
                    if (response.success) {
                        self.priceCache[cacheKey] = response.data;
                        self.updatePriceDisplay(response.data);
                    }
                }
            });
        }
        
        updatePriceDisplay(data) {
            if (!data) {
                return;
            }
            
            // Update any custom price displays (Total Price in summary)
            // Use simple dollar format to avoid HTML entity issues
            $('.attribute-final-price').text('$' + parseFloat(data.final_price).toFixed(2));
            
            // Show breakdown if element exists
            if (data.breakdown && data.breakdown.length > 0) {
                const self = this;
                let breakdownHtml = '<div class="price-breakdown">';
                breakdownHtml += '<div class="breakdown-row base"><span>Base Price:</span><span>' + this.formatPrice(data.base_price) + '</span></div>';
                
                data.breakdown.forEach(function(item) {
                    breakdownHtml += '<div class="breakdown-row modifier">';
                    breakdownHtml += '<span>' + item.attribute + ': ' + item.option + '</span>';
                    if (item.modifier > 0) {
                        breakdownHtml += '<span>+' + self.formatPrice(item.modifier) + '</span>';
                    } else {
                        breakdownHtml += '<span style="color: #999;">—</span>';
                    }
                    breakdownHtml += '</div>';
                });
                
                breakdownHtml += '<div class="breakdown-row total"><span>Total:</span><span>' + this.formatPrice(data.final_price) + '</span></div>';
                breakdownHtml += '</div>';
                
                $('.price-breakdown-container').html(breakdownHtml);
            } else {
                // No selections - show only base price
                let breakdownHtml = '<div class="price-breakdown">';
                breakdownHtml += '<div class="breakdown-row base"><span>Base Price:</span><span>' + this.formatPrice(data.base_price) + '</span></div>';
                breakdownHtml += '</div>';
                $('.price-breakdown-container').html(breakdownHtml);
            }
            
            // Trigger event for other scripts
            $(document).trigger('attribute-price-updated', [data]);
        }
        
        formatPrice(price) {
            const formatted = parseFloat(price).toFixed(attributePricing.decimals);
            const parts = formatted.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, attributePricing.thousandSeparator);
            
            let priceString = parts.join(attributePricing.decimalSeparator);
            
            return attributePricing.priceFormat
                .replace('%1$s', attributePricing.currencySymbol)
                .replace('%2$s', priceString);
        }
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize on product pages
        if ($('.single-product').length || $('form.cart').length) {
            new AttributePricingCalculator();
        }
        
        // Cart icon handler is in functions.php - no duplicate needed here
        
        // Update cart count after add to cart
        $(document.body).on('added_to_cart wc_fragments_refreshed', function() {
            // Get updated count from Woodmart's cart
            var count = $('.wd-cart-number').first().text() || '0';
            $('.decor-cart-count').text(count);
            
            // Show/hide count badge
            if (parseInt(count) > 0) {
                $('.decor-cart-count').show();
            } else {
                $('.decor-cart-count').hide();
            }
        });
    });

})(jQuery);
