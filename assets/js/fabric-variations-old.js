/**
 * Fabric Variations JavaScript
 * West Elm style variation selector functionality
 */

(function($) {
    'use strict';

    class FabricVariations {
        constructor() {
            this.$selector = $('.decor-fabric-selector');
            this.$summary = $('.decor-selection-summary');
            this.$form = $('form.variations_form');
            this.productId = this.$selector.data('product-id');
            this.variations = this.$form.data('product_variations') || [];
            this.selectedVariation = null;
            this.filters = {
                color: '',
                fabric: ''
            };

            if (this.$selector.length) {
                this.init();
            }
        }

        init() {
            this.bindEvents();
            this.showSummary();
            this.initFilters();
        }
        
        initFilters() {
            // Count variations per filter option
            const colorCounts = {};
            const fabricCounts = {};
            
            this.$selector.find('.fabric-swatch').each(function() {
                const colorGroup = $(this).data('color-group');
                const fabricType = $(this).data('fabric-type');
                
                if (colorGroup) {
                    colorCounts[colorGroup] = (colorCounts[colorGroup] || 0) + 1;
                }
                if (fabricType) {
                    fabricCounts[fabricType] = (fabricCounts[fabricType] || 0) + 1;
                }
            });
            
            // Update filter option counts
            this.$selector.find('.filter-dropdown-content[data-filter-type="color"] .filter-option').each(function() {
                const value = $(this).data('value');
                if (value && colorCounts[value]) {
                    $(this).append(' <span class="filter-count">(' + colorCounts[value] + ')</span>');
                }
            });
            
            this.$selector.find('.filter-dropdown-content[data-filter-type="fabric"] .filter-option').each(function() {
                const value = $(this).data('value');
                if (value && fabricCounts[value]) {
                    $(this).append(' <span class="filter-count">(' + fabricCounts[value] + ')</span>');
                }
            });
        }

        bindEvents() {
            const self = this;

            // Toggle selector
            this.$selector.on('click', '.fabric-selector-header', function() {
                self.toggleSelector();
            });

            // Filter dropdowns
            this.$selector.on('click', '.filter-btn', function(e) {
                e.stopPropagation();
                self.toggleFilterDropdown($(this).closest('.filter-dropdown'));
            });

            // Filter options
            this.$selector.on('click', '.filter-option', function(e) {
                e.stopPropagation();
                const $option = $(this);
                const filterType = $option.closest('.filter-dropdown-content').data('filter-type');
                const value = $option.data('value');
                
                self.setFilter(filterType, value);
                $option.siblings().removeClass('active');
                $option.addClass('active');
                $option.closest('.filter-dropdown').removeClass('open');
            });

            // Swatch hover - update left panel
            this.$selector.on('mouseenter', '.fabric-swatch', function() {
                self.updateDetailPanel($(this));
            });

            // Swatch selection
            this.$selector.on('click', '.fabric-swatch', function() {
                self.selectSwatch($(this));
            });

            // Close dropdowns on outside click
            $(document).on('click', function() {
                self.$selector.find('.filter-dropdown').removeClass('open');
            });

            // Listen for WooCommerce variation changes
            this.$form.on('found_variation', function(e, variation) {
                self.onVariationFound(variation);
            });

            this.$form.on('reset_data', function() {
                self.onVariationReset();
            });

            // Summary links
            this.$summary.on('click', '.make-selection-link', function(e) {
                e.preventDefault();
                self.scrollToSelector();
            });
        }
        
        updateDetailPanel($swatch) {
            const image = $swatch.data('image');
            const name = $swatch.data('name');
            const description = $swatch.data('description');
            const content = $swatch.data('content');
            const rubCount = $swatch.data('rub-count');
            const priceTier = $swatch.data('price-tier');
            const care = $swatch.data('care');
            
            // Update image
            if (image) {
                this.$selector.find('#fabric-detail-image').attr('src', image).show();
            }
            
            // Update name
            this.$selector.find('#fabric-detail-name').text(name || 'Select a fabric');
            
            // Update description
            const $desc = this.$selector.find('#fabric-detail-description');
            if (description) {
                $desc.text(description).show();
            } else {
                $desc.hide();
            }
            
            // Update specs
            this.updateDetailSpec('content', content);
            this.updateDetailSpec('rub-count', rubCount);
            this.updateDetailSpec('price-tier', priceTier);
            this.updateDetailSpec('care', care);
        }
        
        updateDetailSpec(specId, value) {
            const $spec = this.$selector.find('#spec-' + specId);
            if (value) {
                $spec.find('.spec-value').text(value);
                $spec.show();
            } else {
                $spec.hide();
            }
        }

        toggleSelector() {
            const $toggle = this.$selector.find('.fabric-selector-toggle');
            const isExpanded = $toggle.attr('aria-expanded') === 'true';
            
            $toggle.attr('aria-expanded', !isExpanded);
            this.$selector.find('.fabric-filters, .fabric-info-panel, .fabric-categories-container, .order-swatches-section')
                .slideToggle(300);
        }

        toggleFilterDropdown($dropdown) {
            const isOpen = $dropdown.hasClass('open');
            this.$selector.find('.filter-dropdown').removeClass('open');
            
            if (!isOpen) {
                $dropdown.addClass('open');
            }
        }

        setFilter(type, value) {
            this.filters[type] = value;
            this.applyFilters();
            
            // Update filter button state
            const $btn = this.$selector.find(`.filter-btn[data-filter="${type}"]`);
            if (value) {
                $btn.addClass('active');
            } else {
                $btn.removeClass('active');
            }
        }

        applyFilters() {
            const self = this;
            let visibleCount = 0;
            
            this.$selector.find('.fabric-swatch').each(function() {
                const $swatch = $(this);
                const colorGroup = String($swatch.data('color-group') || '').toLowerCase();
                const fabricType = String($swatch.data('fabric-type') || '').toLowerCase();
                
                let show = true;
                
                // Filter by color
                if (self.filters.color) {
                    const filterColor = String(self.filters.color).toLowerCase();
                    if (colorGroup !== filterColor) {
                        show = false;
                    }
                }
                
                // Filter by fabric type
                if (self.filters.fabric) {
                    const filterFabric = String(self.filters.fabric).toLowerCase();
                    if (fabricType !== filterFabric) {
                        show = false;
                    }
                }
                
                if (show) {
                    $swatch.removeClass('hidden').show();
                    visibleCount++;
                } else {
                    $swatch.addClass('hidden').hide();
                }
            });

            // Hide empty categories
            this.$selector.find('.fabric-category').each(function() {
                const $category = $(this);
                const visibleSwatches = $category.find('.fabric-swatch:not(.hidden)').length;
                if (visibleSwatches > 0) {
                    $category.show();
                } else {
                    $category.hide();
                }
            });

            // Update count
            this.$selector.find('.fabric-choices-count').text(
                visibleCount + ' ' + decorFabric.i18n.choices
            );
            
            console.log('Filters applied:', this.filters, 'Visible:', visibleCount);
        }

        selectSwatch($swatch) {
            const variationId = $swatch.data('variation-id');
            const attributes = $swatch.data('attributes');
            
            // Update visual selection
            this.$selector.find('.fabric-swatch').removeClass('selected');
            $swatch.addClass('selected');
            
            // Find the variation data
            const variation = this.variations.find(v => v.variation_id === variationId);
            
            if (variation) {
                this.selectedVariation = variation;
                this.showFabricInfo(variation);
                this.updateWooCommerceForm(attributes);
                this.updateSummary(variation);
            }
        }

        showFabricInfo(variation) {
            const $panel = this.$selector.find('.fabric-info-panel');
            
            // Get variation name from attributes
            let name = '';
            for (const [key, value] of Object.entries(variation.attributes)) {
                name += value + ' ';
            }
            name = name.trim();
            
            // Update panel content
            $panel.find('.fabric-preview-image').attr('src', variation.image?.src || variation.swatch_image || '');
            $panel.find('.fabric-info-name').text(name);
            $panel.find('.fabric-info-description').text(variation.fabric_description || '');
            
            // Update specs
            this.updateSpec($panel, 'content', variation.fabric_content);
            this.updateSpec($panel, 'rub-count', variation.rub_count);
            this.updateSpec($panel, 'price-tier', variation.price_tier);
            this.updateSpec($panel, 'care', variation.care_instructions);
            
            $panel.slideDown(300);
        }

        updateSpec($panel, specClass, value) {
            const $spec = $panel.find(`.spec-${specClass}`);
            if (value) {
                $spec.find('.spec-value').text(value);
                $spec.show();
            } else {
                $spec.hide();
            }
        }

        updateWooCommerceForm(attributes) {
            const self = this;
            
            // Set each attribute in the WooCommerce form
            for (const [key, value] of Object.entries(attributes)) {
                // Try multiple selector patterns
                const $select = this.$form.find(`select[name="${key}"], select[data-attribute_name="${key}"], [name="${key}"]`);
                
                if ($select.length) {
                    $select.val(value).trigger('change');
                } else {
                    // If no select found, try to create hidden input
                    let $hidden = this.$form.find(`input[name="${key}"]`);
                    if (!$hidden.length) {
                        $hidden = $('<input type="hidden">').attr('name', key);
                        this.$form.append($hidden);
                    }
                    $hidden.val(value);
                }
            }
            
            // Trigger WooCommerce to find the variation
            this.$form.trigger('check_variations');
            this.$form.trigger('woocommerce_variation_select_change');
            
            // Also trigger found_variation with the variation data
            const variationId = this.selectedVariation?.variation_id;
            if (variationId) {
                const variation = this.variations.find(v => v.variation_id === variationId);
                if (variation) {
                    this.$form.trigger('found_variation', [variation]);
                }
            }
        }

        onVariationFound(variation) {
            this.selectedVariation = variation;
            this.updateSummary(variation);
        }

        onVariationReset() {
            this.selectedVariation = null;
            this.$selector.find('.fabric-swatch').removeClass('selected');
            this.$selector.find('.fabric-info-panel').slideUp(300);
            this.resetSummary();
        }

        showSummary() {
            // Summary is now visible by default, no need to slide down
            // Just ensure it's visible
            this.$summary.show();
        }

        updateSummary(variation) {
            const $fabricItem = this.$summary.find('.selection-fabric');
            
            // Get variation name
            let name = '';
            for (const [key, value] of Object.entries(variation.attributes)) {
                name += value + ' ';
            }
            name = name.trim();
            
            // Update fabric selection
            $fabricItem.find('.selection-value').html(`<strong>${name}</strong>`);
            $fabricItem.find('.selection-check').show();
            
            // Update preview image
            if (variation.image?.src) {
                this.$summary.find('.selection-preview-image')
                    .attr('src', variation.image.src)
                    .show();
            }
            
            // Update fabric details
            const $details = this.$summary.find('.selection-fabric-details');
            
            if (variation.fabric_content) {
                $details.find('.fabric-content-value').text(variation.fabric_content);
            }
            if (variation.rub_count) {
                $details.find('.rub-count-value').text(variation.rub_count);
            }
            if (variation.price_tier) {
                $details.find('.price-tier-value').text(variation.price_tier);
            }
            if (variation.care_instructions) {
                $details.find('.care-value').text(variation.care_instructions);
            }
            
            if (variation.fabric_content || variation.rub_count || variation.price_tier || variation.care_instructions) {
                $details.slideDown(300);
            }
            
            // Update price
            if (variation.price_html) {
                this.$summary.find('.total-price-value').html(variation.price_html);
            }
        }

        resetSummary() {
            const $fabricItem = this.$summary.find('.selection-fabric');
            
            $fabricItem.find('.selection-value').html(
                `<a href="#" class="make-selection-link">${decorFabric.i18n.makeSelection}</a>`
            );
            $fabricItem.find('.selection-check').hide();
            
            this.$summary.find('.selection-fabric-details').slideUp(300);
            this.$summary.find('.selection-preview-image').hide();
        }

        scrollToSelector() {
            $('html, body').animate({
                scrollTop: this.$selector.offset().top - 100
            }, 500);
        }
    }

    // Initialize on document ready
    $(document).ready(function() {
        new FabricVariations();
    });

})(jQuery);
