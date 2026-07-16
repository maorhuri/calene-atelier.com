/**
 * Fabric Variations - West Elm Style
 * Professional B2B functionality
 */

(function($) {
    'use strict';

    class FabricVariations {
        constructor() {
            this.$selector = $('.decor-fabric-selector');
            this.$form = $('form.variations_form');
            this.variations = this.$form.data('product_variations') || [];
            this.selectedVariation = null;
            this.filters = { color: '', fabric: '' };

            if (this.$selector.length) {
                this.init();
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

            // Swatch click - select variation
            this.$selector.on('click', '.swatch-item', function() {
                self.selectSwatch($(this));
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
            const variationId = $swatch.data('variation-id');
            const attributes = $swatch.data('attributes');
            const name = $swatch.data('name');
            const image = $swatch.data('image');
            
            // Update visual selection
            this.$selector.find('.swatch-item').removeClass('selected');
            $swatch.addClass('selected');
            
            // Update header
            this.$selector.find('#selected-fabric-name').text(name);
            this.$selector.find('#selected-fabric-swatch').attr('src', image);
            this.$selector.find('#current-selection-text').text(name);
            
            // Show details in left panel
            this.showSwatchDetails($swatch);
            
            // Find variation and update WooCommerce form
            const variation = this.variations.find(v => v.variation_id === variationId);
            if (variation) {
                this.selectedVariation = variation;
                this.updateWooCommerceForm(attributes, variation);
            }
        }

        updateWooCommerceForm(attributes, variation) {
            // Set each attribute
            for (const [key, value] of Object.entries(attributes)) {
                const $select = this.$form.find(`select[name="${key}"], [name="${key}"]`);
                if ($select.length) {
                    $select.val(value).trigger('change');
                } else {
                    let $hidden = this.$form.find(`input[name="${key}"]`);
                    if (!$hidden.length) {
                        $hidden = $('<input type="hidden">').attr('name', key);
                        this.$form.append($hidden);
                    }
                    $hidden.val(value);
                }
            }
            
            // Trigger WooCommerce
            this.$form.trigger('check_variations');
            this.$form.trigger('woocommerce_variation_select_change');
            
            if (variation) {
                this.$form.trigger('found_variation', [variation]);
            }
        }

        onVariationFound(variation) {
            this.selectedVariation = variation;
            
            // Update price display if needed
            if (variation.price_html) {
                $('.woocommerce-variation-price').html(variation.price_html);
            }
        }
    }

    // Initialize
    $(document).ready(function() {
        new FabricVariations();
    });

})(jQuery);
