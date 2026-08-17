/**
 * Fabric Variations - West Elm Style
 * Professional B2B functionality
 */

console.log('Fabric Variations JS loaded');

(function($) {
    'use strict';
    
    console.log('Fabric Variations initializing...');

    class FabricVariations {
        constructor() {
            this.$selector = $('.decor-fabric-selector');
            this.$form = $('form.variations_form');
            this.variations = this.$form.data('product_variations') || [];
            this.selectedVariation = null;
            this.filters = { color: '', fabric: '' };

            console.log('FabricVariations constructor', this.$selector.length, this.$form.length);

            if (this.$selector.length) {
                this.init();
                console.log('FabricVariations initialized');
            }
        }

        init() {
            this.bindEvents();
            this.selectFirstSwatch();
        }

        bindEvents() {
            const self = this;

            // Header toggle
            this.$selector.on('click', '.fabric-selector-header', function() {
                self.toggleBody();
            });

            // Filter dropdowns
            this.$selector.on('click', '.filter-btn', function(e) {
                e.stopPropagation();
                const $dropdown = $(this).closest('.filter-dropdown');
                self.$selector.find('.filter-dropdown').not($dropdown).removeClass('open');
                $dropdown.toggleClass('open');
            });

            // Filter options
            this.$selector.on('click', '.filter-option', function(e) {
                e.stopPropagation();
                const $option = $(this);
                const filterType = $option.closest('.filter-dropdown-menu').data('filter-type');
                const value = $option.data('value');
                
                self.setFilter(filterType, value);
                $option.siblings().removeClass('active');
                $option.addClass('active');
                $option.closest('.filter-dropdown').removeClass('open');
            });

            // Close dropdowns on outside click
            $(document).on('click', function() {
                self.$selector.find('.filter-dropdown').removeClass('open');
            });

            // Swatch hover - update left panel
            this.$selector.on('mouseenter', '.swatch-item', function() {
                self.showSwatchDetails($(this));
            });

            // Swatch click - select variation or attribute value
            this.$selector.on('click', '.swatch-item', function() {
                const $swatch = $(this);
                
                // Check if this is an attribute swatch (has data-attribute) or a variation swatch (has data-variation-id)
                if ($swatch.data('attribute')) {
                    // This is an attribute value swatch
                    self.selectAttributeSwatch($swatch);
                } else {
                    // This is a full variation swatch
                    self.selectSwatch($swatch);
                }
            });
            
            // Accordion header click
            this.$selector.on('click', '.accordion-header', function() {
                const $panel = $(this).closest('.attribute-panel');
                $panel.toggleClass('open');
            });
            
            // Attribute option click
            this.$selector.on('click', '.attribute-option', function() {
                const $option = $(this);
                const attrName = $option.data('attribute');
                const attrValue = $option.data('value');
                
                // Update visual selection
                self.$selector.find(`.attribute-option[data-attribute="${attrName}"]`).removeClass('selected');
                $option.addClass('selected');
                
                // Update WooCommerce form
                self.setAttributeValue(attrName, attrValue);
            });

            // WooCommerce events
            this.$form.on('found_variation', function(e, variation) {
                self.onVariationFound(variation);
            });
        }

        toggleBody() {
            const $body = this.$selector.find('.fabric-selector-body');
            const $toggle = this.$selector.find('.header-toggle');
            const isExpanded = $toggle.attr('aria-expanded') === 'true';
            
            $toggle.attr('aria-expanded', !isExpanded);
            $body.toggleClass('collapsed');
        }

        setFilter(type, value) {
            this.filters[type] = value;
            this.applyFilters();
            
            const $btn = this.$selector.find(`.filter-btn[data-filter="${type}"]`);
            $btn.toggleClass('active', !!value);
        }

        applyFilters() {
            const self = this;
            let visibleCount = 0;
            
            this.$selector.find('.swatch-item').each(function() {
                const $swatch = $(this);
                const colorGroup = String($swatch.data('color-group') || '').toLowerCase();
                const fabricType = String($swatch.data('fabric-type') || '').toLowerCase();
                
                let show = true;
                
                if (self.filters.color) {
                    if (colorGroup !== String(self.filters.color).toLowerCase()) {
                        show = false;
                    }
                }
                
                if (self.filters.fabric) {
                    if (fabricType !== String(self.filters.fabric).toLowerCase()) {
                        show = false;
                    }
                }
                
                $swatch.toggleClass('hidden', !show);
                if (show) visibleCount++;
            });

            // Hide empty categories
            this.$selector.find('.fabric-category-section').each(function() {
                const $section = $(this);
                const visible = $section.find('.swatch-item:not(.hidden)').length;
                $section.toggle(visible > 0);
            });

            // Update count
            this.$selector.find('.swatches-count').text(visibleCount + ' Choices');
        }

        showSwatchDetails($swatch) {
            const image = $swatch.data('image');
            const name = $swatch.data('name');
            const description = $swatch.data('description');
            const content = $swatch.data('content');
            const rubCount = $swatch.data('rub-count');
            const priceTier = $swatch.data('price-tier');
            const care = $swatch.data('care');
            
            // Update image
            const $img = this.$selector.find('#hover-detail-image');
            if (image) {
                $img.attr('src', image).show();
            }
            
            // Update name
            this.$selector.find('#hover-detail-name').text(name || '');
            
            // Update description
            const $desc = this.$selector.find('#hover-detail-description');
            if (description) {
                $desc.text(description).show();
            } else {
                $desc.hide();
            }
            
            // Update specs
            this.updateSpec('content', content);
            this.updateSpec('rub-count', rubCount);
            this.updateSpec('price-tier', priceTier);
            this.updateSpec('care', care);
        }

        updateSpec(specName, value) {
            const $spec = this.$selector.find(`.spec-line[data-spec="${specName}"]`);
            if (value) {
                $spec.find('span').text(value);
                $spec.addClass('visible');
            } else {
                $spec.removeClass('visible');
            }
        }

        selectFirstSwatch() {
            const $first = this.$selector.find('.swatch-item').first();
            if ($first.length) {
                this.showSwatchDetails($first);
            }
        }

        selectSwatch($swatch) {
            const variationId = parseInt($swatch.data('variation-id'));
            const attributes = $swatch.data('attributes');
            const name = $swatch.data('name') || '';
            const image = $swatch.data('image') || '';
            
            console.log('Selecting swatch:', variationId, attributes);
            
            // Update visual selection
            this.$selector.find('.swatch-item').removeClass('selected');
            $swatch.addClass('selected');
            
            // Update header
            this.$selector.find('#selected-fabric-name').text(name);
            if (image) {
                this.$selector.find('#selected-fabric-swatch').attr('src', image);
            }
            
            // Find variation and update WooCommerce form
            const variation = this.variations.find(v => v.variation_id === variationId);
            console.log('Found variation:', variation);
            
            if (variation) {
                this.selectedVariation = variation;
                this.updateWooCommerceForm(attributes, variation);
            } else if (attributes) {
                // If variation not found in cache, still try to set attributes
                this.updateWooCommerceForm(attributes, null);
            }
            
            // Update header and summary
            this.updateHeaderSelection();
        }

        updateWooCommerceForm(attributes, variation) {
            console.log('Updating WooCommerce form with:', attributes);
            
            // Set each attribute
            if (attributes && typeof attributes === 'object') {
                for (const [key, value] of Object.entries(attributes)) {
                    console.log('Setting attribute:', key, '=', value);
                    
                    // Try to find and set the select
                    const $select = this.$form.find(`select[name="${key}"]`);
                    if ($select.length) {
                        $select.val(value).trigger('change');
                        console.log('Set select value');
                    } else {
                        // Create or update hidden input
                        let $hidden = this.$form.find(`input[name="${key}"]`);
                        if (!$hidden.length) {
                            $hidden = $('<input type="hidden">').attr('name', key);
                            this.$form.append($hidden);
                        }
                        $hidden.val(value);
                        console.log('Set hidden input value');
                    }
                }
            }
            
            // Set variation_id hidden field
            if (variation && variation.variation_id) {
                let $variationId = this.$form.find('input[name="variation_id"]');
                if (!$variationId.length) {
                    $variationId = $('<input type="hidden" name="variation_id">');
                    this.$form.append($variationId);
                }
                $variationId.val(variation.variation_id);
                console.log('Set variation_id:', variation.variation_id);
            }
            
            // Trigger WooCommerce events
            this.$form.trigger('check_variations');
            this.$form.trigger('woocommerce_variation_select_change');
            
            if (variation) {
                this.$form.trigger('found_variation', [variation]);
                
                // Update price display
                if (variation.price_html) {
                    $('.woocommerce-variation-price, .single_variation .price').html(variation.price_html);
                }
                
                // Show add to cart
                this.$form.find('.single_variation_wrap').show();
                this.$form.find('.woocommerce-variation-add-to-cart').show();
            }
        }

        onVariationFound(variation) {
            this.selectedVariation = variation;
            
            // Update price display if needed
            if (variation.price_html) {
                $('.woocommerce-variation-price').html(variation.price_html);
            }
        }
        
        selectAttributeSwatch($swatch) {
            const attrName = $swatch.data('attribute');
            const attrValue = $swatch.data('value');
            const name = $swatch.data('name') || '';
            const image = $swatch.data('image') || '';
            
            console.log('Selecting attribute swatch:', attrName, '=', attrValue);
            
            // Update visual selection for this attribute's swatches
            this.$selector.find(`.swatch-item[data-attribute="${attrName}"]`).removeClass('selected');
            $swatch.addClass('selected');
            
            // Update header swatch image
            if (image) {
                this.$selector.find('#selected-fabric-swatch').attr('src', image);
            }
            
            // Set the attribute value in WooCommerce form
            this.setAttributeValue(attrName, attrValue);
            
            // Update header and summary
            this.updateHeaderSelection();
        }
        
        setAttributeValue(attrName, attrValue) {
            console.log('Setting attribute:', attrName, '=', attrValue);
            
            // Find the select for this attribute
            const $select = this.$form.find(`select[name="attribute_${attrName}"]`);
            if ($select.length) {
                $select.val(attrValue).trigger('change');
            } else {
                // Try hidden input
                let $hidden = this.$form.find(`input[name="attribute_${attrName}"]`);
                if (!$hidden.length) {
                    $hidden = $('<input type="hidden">').attr('name', 'attribute_' + attrName);
                    this.$form.append($hidden);
                }
                $hidden.val(attrValue);
            }
            
            // Trigger WooCommerce to check variations
            this.$form.trigger('check_variations');
            this.$form.trigger('woocommerce_variation_select_change');
            
            // Update header with current selections
            this.updateHeaderSelection();
        }
        
        updateHeaderSelection() {
            const self = this;
            const selections = [];
            
            // Get selections from attribute options
            this.$selector.find('.attribute-option.selected').each(function() {
                const attrName = $(this).data('attribute');
                const name = $(this).find('.option-name').text();
                selections.push(name);
                
                // Update accordion header selection
                self.updateAccordionSelection(attrName, name, null);
            });
            
            // Get selections from swatches
            this.$selector.find('.swatch-item.selected').each(function() {
                const attrName = $(this).data('attribute');
                const name = $(this).data('name');
                const image = $(this).data('image');
                selections.push(name);
                
                // Update accordion header selection
                self.updateAccordionSelection(attrName, name, image);
            });
            
            if (selections.length > 0) {
                this.$selector.find('#selected-fabric-name').text(selections.join(', '));
            }
            
            // Update inline summary
            this.updateInlineSummary();
        }
        
        updateAccordionSelection(attrName, name, image) {
            const $panel = this.$selector.find(`.attribute-panel[data-attribute="${attrName}"]`);
            const $selection = $panel.find('.accordion-selection');
            
            let html = '';
            if (image) {
                html += `<img src="${image}" alt="${name}">`;
            }
            html += `<span class="selection-text">${name}</span>`;
            
            $selection.html(html);
        }
        
        updateInlineSummary() {
            const self = this;
            
            // Update attribute selections in summary
            this.$selector.find('.attribute-option.selected').each(function() {
                const attrName = $(this).data('attribute');
                const value = $(this).find('.option-name').text();
                self.$selector.find(`.summary-row[data-attribute="${attrName}"] .summary-value`).text(value);
            });
            
            // Update from swatch selections
            const $selectedSwatch = this.$selector.find('.swatch-item.selected');
            if ($selectedSwatch.length) {
                // Show fabric details section
                const $fabricDetails = this.$selector.find('#summary-fabric-details');
                $fabricDetails.show();
                
                // Get fabric details from swatch data or from popup
                const $popup = $selectedSwatch.find('.swatch-popup');
                if ($popup.length) {
                    // Extract details from popup specs
                    $popup.find('.popup-specs div').each(function() {
                        const text = $(this).text();
                        if (text.includes('Content:')) {
                            const value = text.replace('Content:', '').trim();
                            self.$selector.find('.summary-row[data-detail="content"] .summary-value').text(value || '-');
                        } else if (text.includes('Rub Count:')) {
                            const value = text.replace('Rub Count:', '').trim();
                            self.$selector.find('.summary-row[data-detail="rub-count"] .summary-value').text(value || '-');
                        } else if (text.includes('Price Tier:')) {
                            const value = text.replace('Price Tier:', '').trim();
                            self.$selector.find('.summary-row[data-detail="price-tier"] .summary-value').text(value || '-');
                        } else if (text.includes('Care:')) {
                            const value = text.replace('Care:', '').trim();
                            self.$selector.find('.summary-row[data-detail="care"] .summary-value').text(value || '-');
                        }
                    });
                }
            }
            
            if (this.selectedVariation) {
                // Update price
                if (this.selectedVariation.display_price) {
                    const price = this.selectedVariation.display_price;
                    const formattedPrice = '$' + parseFloat(price).toFixed(2);
                    this.$selector.find('#summary-total-price').text(formattedPrice);
                } else if (this.selectedVariation.price_html) {
                    this.$selector.find('#summary-total-price').html(this.selectedVariation.price_html);
                }
                
                // Update attribute values from variation
                if (this.selectedVariation.attributes) {
                    for (const [key, value] of Object.entries(this.selectedVariation.attributes)) {
                        const attrName = key.replace('attribute_', '');
                        const $row = self.$selector.find(`.summary-row[data-attribute="${attrName}"]`);
                        if ($row.length) {
                            // Try to get term name
                            const $option = self.$selector.find(`.attribute-option[data-attribute="${attrName}"][data-value="${value}"]`);
                            const displayValue = $option.length ? $option.find('.option-name').text() : value;
                            $row.find('.summary-value').text(displayValue);
                        }
                    }
                }
            }
        }
        
        /**
         * Check if all required attributes are selected
         * Disables Add to Cart button if not all selected
         */
        checkRequiredAttributes() {
            const self = this;
            const $addToCartBtn = this.$form.find('.single_add_to_cart_button');
            const $attributePanels = this.$selector.find('.attribute-panel');
            
            if ($attributePanels.length === 0) {
                return true;
            }
            
            let allSelected = true;
            this.missingAttrs = [];
            
            $attributePanels.each(function() {
                const $panel = $(this);
                const attrName = $panel.data('attribute');
                const attrLabel = $panel.find('.accordion-title').text().trim();
                
                // Check if any option is selected in this panel
                const hasSelection = $panel.find('.swatch-item.selected, .attribute-option.selected').length > 0;
                
                if (!hasSelection) {
                    allSelected = false;
                    self.missingAttrs.push(attrLabel);
                    $panel.addClass('missing-selection');
                } else {
                    $panel.removeClass('missing-selection');
                }
            });
            
            // Also check swatches outside panels
            const $swatchesGrid = this.$selector.find('.swatches-grid');
            if ($swatchesGrid.length && $swatchesGrid.find('.swatch-item').length > 0) {
                const hasSwatchSelection = $swatchesGrid.find('.swatch-item.selected').length > 0;
                if (!hasSwatchSelection) {
                    allSelected = false;
                    this.missingAttrs.push('Fabric');
                }
            }
            
            // Update button state
            if (!allSelected) {
                $addToCartBtn.addClass('disabled');
                
                // Update button text
                if (!$addToCartBtn.data('original-text')) {
                    $addToCartBtn.data('original-text', $addToCartBtn.text());
                }
                $addToCartBtn.text('Select Options');
            } else {
                $addToCartBtn.removeClass('disabled');
                
                // Restore original text
                const originalText = $addToCartBtn.data('original-text');
                if (originalText) {
                    $addToCartBtn.text(originalText);
                }
            }
            
            this.allAttributesSelected = allSelected;
            return allSelected;
        }
        
        /**
         * Show modal when trying to add to cart without all selections
         */
        showRequiredAttributesModal() {
            // Remove existing modal
            $('.decor-required-modal-overlay').remove();
            
            const missingList = this.missingAttrs.map(attr => `<li>${attr}</li>`).join('');
            
            const modalHtml = `
                <div class="decor-required-modal-overlay">
                    <div class="decor-required-modal">
                        <button class="modal-close">&times;</button>
                        <div class="modal-icon">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#c4a47c" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                        <h3>Please Complete Your Selection</h3>
                        <p>To add this item to your cart, please select the following options:</p>
                        <ul class="missing-list">${missingList}</ul>
                        <button class="modal-btn">Continue Shopping</button>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            // Bind close events
            $('.decor-required-modal-overlay').on('click', function(e) {
                if ($(e.target).hasClass('decor-required-modal-overlay') || 
                    $(e.target).hasClass('modal-close') || 
                    $(e.target).hasClass('modal-btn')) {
                    $(this).fadeOut(200, function() {
                        $(this).remove();
                    });
                }
            });
            
            // Show with animation
            $('.decor-required-modal-overlay').hide().fadeIn(200);
        }
    }
    
    /**
     * Sticky Product Info (Name & Price) when scrolling fabrics
     */
    class StickyProductInfo {
        constructor() {
            this.$fabricSelector = $('.decor-fabric-selector, .decor-attribute-selector');
            
            if (this.$fabricSelector.length) {
                this.init();
            }
        }
        
        init() {
            this.createStickyWrapper();
            this.bindScroll();
            // Initial check
            this.updateSticky();
        }
        
        createStickyWrapper() {
            // Find product title and price - try multiple selectors
            const $title = $('.product_title, .entry-title, h1.title').first();
            const $price = $('.summary .price, .product-summary .price, .woocommerce-Price-amount').first().closest('.price');
            
            if (!$title.length) {
                console.log('Sticky: No title found');
                return;
            }
            
            const priceHtml = $price.length ? $price.html() : '';
            
            // Create sticky header
            const $stickyHeader = $(`
                <div class="product-sticky-header">
                    <div class="sticky-content">
                        <div class="sticky-product-name">${$title.text()}</div>
                        <div class="sticky-product-price">${priceHtml}</div>
                    </div>
                </div>
            `);
            
            // Insert at top of body
            $('body').append($stickyHeader);
            this.$stickyHeader = $stickyHeader;
        }
        
        bindScroll() {
            const self = this;
            const $window = $(window);
            
            $window.on('scroll', function() {
                self.updateSticky();
            });
        }
        
        updateSticky() {
            if (!this.$stickyHeader) return;
            
            const selectorTop = this.$fabricSelector.offset().top;
            const scrollTop = $(window).scrollTop();
            const headerHeight = 80; // Approximate header height
            
            if (scrollTop > selectorTop - headerHeight - 60) {
                this.$stickyHeader.addClass('is-sticky');
            } else {
                this.$stickyHeader.removeClass('is-sticky');
            }
        }
        
        // Update price when variation changes
        updatePrice(priceHtml) {
            if (this.$stickyHeader) {
                this.$stickyHeader.find('.sticky-product-price').html(priceHtml);
            }
        }
    }

    // Initialize
    $(document).ready(function() {
        const fabricVariations = new FabricVariations();
        const stickyInfo = new StickyProductInfo();
        
        // Check required attributes on init and after any selection
        setTimeout(function() {
            fabricVariations.checkRequiredAttributes();
        }, 500);
        
        // Re-check when attributes change
        $(document).on('click', '.swatch-item, .attribute-option', function() {
            setTimeout(function() {
                fabricVariations.checkRequiredAttributes();
            }, 100);
        });
        
        // Intercept Add to Cart click when not all attributes selected
        $(document).on('click', '.single_add_to_cart_button', function(e) {
            if (!fabricVariations.allAttributesSelected && fabricVariations.missingAttrs && fabricVariations.missingAttrs.length > 0) {
                e.preventDefault();
                e.stopPropagation();
                fabricVariations.showRequiredAttributesModal();
                return false;
            }
        });
        
        // Update sticky price when variation found
        $('form.variations_form').on('found_variation', function(e, variation) {
            if (variation.price_html) {
                stickyInfo.updatePrice(variation.price_html);
            }
        });
    });

})(jQuery);
