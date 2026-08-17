<?php
/**
 * Simple Product Attribute Selector Template
 * For products without variations - uses attribute pricing
 * 
 * @var array $attributes_data - Array of attributes with their options and prices
 * @var WC_Product $product - The product object
 */

if (!defined('ABSPATH')) {
    exit;
}

$base_price = $product->get_price();
?>

<div class="decor-attribute-selector" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
    
    <!-- Header -->
    <div class="attribute-selector-header">
        <div class="header-left">
            <span class="header-title">Customize Your Selection</span>
        </div>
        <div class="header-right">
            <span class="base-price-label">Base Price:</span>
            <span class="base-price"><?php echo wc_price($base_price); ?></span>
        </div>
    </div>
    
    <!-- Attribute Panels (Accordion Style) -->
    <?php foreach ($attributes_data as $attr_name => $attr_data) : 
        $has_images = false;
        foreach ($attr_data['options'] as $option) {
            if (!empty($option['image'])) {
                $has_images = true;
                break;
            }
        }
    ?>
        <div class="attribute-panel" 
             data-attribute="<?php echo esc_attr($attr_name); ?>"
             data-label="<?php echo esc_attr($attr_data['label']); ?>"
             data-group="<?php echo esc_attr($attr_data['group'] ?? ''); ?>">
            
            <!-- Accordion Header -->
            <div class="accordion-header">
                <span class="accordion-title"><?php echo esc_html($attr_data['label']); ?></span>
                <div class="accordion-right">
                    <span class="accordion-selection" data-attribute="<?php echo esc_attr($attr_name); ?>"></span>
                    <button type="button" class="clear-selection-btn" data-attribute="<?php echo esc_attr($attr_name); ?>" style="display: none;">Clear Selection</button>
                    <span class="accordion-icon">▼</span>
                </div>
            </div>
            
            <!-- Accordion Content -->
            <div class="accordion-content">
                <?php if ($has_images) : ?>
                <!-- Swatches with images -->
                <div class="swatches-grid">
                    <?php foreach ($attr_data['options'] as $option_slug => $option_data) : 
                        $has_popup_content = !empty($option_data['content']) || !empty($option_data['rub_count']) || !empty($option_data['care']) || !empty($option_data['description']);
                        // Get price for this specific option (per-variation pricing)
                        // Use $attr_data['name'] for custom attributes since taxonomy might be empty
                        $pricing_key = !empty($attr_data['taxonomy']) ? $attr_data['taxonomy'] : $attr_data['name'];
                        $option_price_info = Decor_Attribute_Pricing::get_product_attribute_price($product->get_id(), $pricing_key, $option_slug);
                        $display_price = floatval($option_price_info['price']);
                        $display_price_type = $option_price_info['type'];
                    ?>
                        <div class="swatch-item <?php echo $has_popup_content ? 'has-popup' : ''; ?>" 
                             data-attribute="<?php echo esc_attr($attr_name); ?>"
                             data-value="<?php echo esc_attr($option_slug); ?>"
                             data-name="<?php echo esc_attr($option_data['name']); ?>"
                             data-price="<?php echo esc_attr($display_price); ?>"
                             data-price-type="<?php echo esc_attr($display_price_type); ?>"
                             data-image="<?php echo esc_attr($option_data['image']); ?>">
                            
                            <?php if ($has_popup_content) : ?>
                            <!-- Hover Popup - Only show if has content -->
                            <div class="swatch-popup">
                                <?php if ($option_data['image']) : ?>
                                <div class="popup-image">
                                    <img src="<?php echo esc_url($option_data['image']); ?>" alt="<?php echo esc_attr($option_data['name']); ?>">
                                </div>
                                <?php endif; ?>
                                <div class="popup-content">
                                    <h5 class="popup-name"><?php echo esc_html($option_data['name']); ?></h5>
                                    <?php if (!empty($option_data['description'])) : ?>
                                        <p class="popup-desc"><?php echo esc_html($option_data['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="popup-specs">
                                        <?php if (!empty($option_data['content'])) : ?>
                                            <div><strong>Content:</strong> <?php echo esc_html($option_data['content']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($option_data['rub_count'])) : ?>
                                            <div><strong>Rub Count:</strong> <?php echo esc_html($option_data['rub_count']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($option_data['price_tier_label'])) : ?>
                                            <div><strong>Price Tier:</strong> <?php echo esc_html($option_data['price_tier_label']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($option_data['care'])) : ?>
                                            <div><strong>Care:</strong> <?php echo esc_html($option_data['care']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="swatch-image">
                                <?php if ($option_data['image']) : ?>
                                    <img src="<?php echo esc_url($option_data['image']); ?>" alt="<?php echo esc_attr($option_data['name']); ?>" loading="lazy">
                                <?php else : ?>
                                    <div class="swatch-placeholder"></div>
                                <?php endif; ?>
                            </div>
                            <div class="swatch-label">
                                <span class="swatch-name"><?php echo esc_html($option_data['name']); ?></span>
                                <?php if ($display_price > 0) : ?>
                                    <span class="swatch-price">
                                        +<?php echo $display_price_type === 'percentage' ? $display_price . '%' : '$' . number_format($display_price, 2); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php else : ?>
                <!-- Simple buttons without images -->
                <div class="attribute-options-grid">
                    <?php foreach ($attr_data['options'] as $option_slug => $option_data) : 
                        // Get price for this specific option (per-variation pricing)
                        // Use $attr_data['name'] for custom attributes since taxonomy might be empty
                        $pricing_key = !empty($attr_data['taxonomy']) ? $attr_data['taxonomy'] : $attr_data['name'];
                        $option_price_info = Decor_Attribute_Pricing::get_product_attribute_price($product->get_id(), $pricing_key, $option_slug);
                        $display_price = floatval($option_price_info['price']);
                        $display_price_type = $option_price_info['type'];
                    ?>
                        <div class="attribute-option-wrapper">
                            <button type="button" class="attribute-option" 
                                    data-attribute="<?php echo esc_attr($attr_name); ?>"
                                    data-value="<?php echo esc_attr($option_slug); ?>"
                                    data-price="<?php echo esc_attr($display_price); ?>"
                                    data-price-type="<?php echo esc_attr($display_price_type); ?>">
                                <span class="option-name"><?php echo esc_html($option_data['name']); ?></span>
                            </button>
                            <?php if ($display_price > 0) : ?>
                                <span class="option-price">
                                    +<?php echo $display_price_type === 'percentage' ? $display_price . '%' : '$' . number_format($display_price, 2); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Hidden input for form submission -->
                <input type="hidden" name="attribute_<?php echo esc_attr(sanitize_title($attr_name)); ?>" data-original-name="<?php echo esc_attr($attr_name); ?>" value="">
                
            </div><!-- .accordion-content -->
        </div>
    <?php endforeach; ?>
    
    <!-- Selection Summary -->
    <div class="selection-summary">
        <h4 class="summary-title">Your Selection Summary</h4>
        
        <!-- Price Breakdown (populated by JS) -->
        <div class="price-breakdown-container"></div>
        
        <!-- Final Total Price -->
        <div class="final-price-row">
            <span class="final-price-label">Total Price:</span>
            <span class="attribute-final-price" data-base-price="<?php echo esc_attr($base_price); ?>"><?php echo wc_price($base_price); ?></span>
        </div>
    </div>
    
</div>

<!-- Styles are in fabric-variations.css -->

<script>
jQuery(document).ready(function($) {
    var $selector = $('.decor-attribute-selector');
    var basePrice = parseFloat($selector.find('.attribute-final-price').data('base-price')) || 0;
    var currencySymbol = '$';
    
    // Accordion toggle
    $(document).on('click', '.decor-attribute-selector .accordion-header', function() {
        $(this).closest('.attribute-panel').toggleClass('open');
    });
    
    // Handle exclusive groups - clears other selections in same group
    function handleExclusiveGroup($panel) {
        var group = $panel.data('group');
        if (!group || group === '') return;
        
        var currentAttr = $panel.data('attribute');
        var clearedSomething = false;
        
        $('.decor-attribute-selector .attribute-panel[data-group="' + group + '"]').each(function() {
            var $otherPanel = $(this);
            var otherAttr = $otherPanel.data('attribute');
            
            if (otherAttr !== currentAttr) {
                // Check if this panel has a selection
                var $selected = $otherPanel.find('.swatch-item.selected, .attribute-option.selected');
                if ($selected.length > 0) {
                    clearedSomething = true;
                    
                    $selected.removeClass('selected');
                    $otherPanel.find('input[type="hidden"]').val('');
                    $otherPanel.find('.accordion-selection').empty();
                    $otherPanel.find('.clear-selection-btn').hide();
                    
                    // Hide summary row and reset price
                    $('.selection-summary .summary-row').each(function() {
                        var rowAttr = $(this).data('attribute');
                        if (rowAttr === otherAttr || rowAttr === otherAttr.replace('pa_', '')) {
                            $(this).hide().find('.summary-price').text('');
                        }
                    });
                }
            }
        });
        
        if (clearedSomething) {
            updateTotalPrice();
        }
    }
    
    // Format price - simple dollar format
    function formatPrice(price) {
        return currencySymbol + parseFloat(price).toFixed(2);
    }
    
    // Calculate and update total price
    function updateTotalPrice() {
        var totalPrice = basePrice;
        var hasSelections = false;
        
        // Go through all selected items
        $selector.find('.swatch-item.selected, .attribute-option.selected').each(function() {
            hasSelections = true;
            var price = parseFloat($(this).data('price')) || 0;
            var priceType = $(this).data('price-type') || 'fixed';
            
            if (price > 0) {
                if (priceType === 'percentage') {
                    totalPrice += basePrice * (price / 100);
                } else {
                    totalPrice += price;
                }
            }
        });
        
        // Update the displayed total price in summary
        $selector.find('.attribute-final-price').text(formatPrice(totalPrice));
        
        // Also update the main product price display
        var $mainPrice = $('.product .price .woocommerce-Price-amount bdi, .summary .price .woocommerce-Price-amount bdi').first();
        if ($mainPrice.length) {
            $mainPrice.text(formatPrice(totalPrice));
        }
    }
    
    // Clear selection for a panel
    function clearPanelSelection($panel) {
        $panel.find('.swatch-item.selected, .attribute-option.selected').removeClass('selected');
        $panel.find('input[type="hidden"]').val('');
        $panel.find('.accordion-selection').empty();
        $panel.find('.clear-selection-btn').hide();
    }
    
    // Clear selection button click
    $(document).on('click', '.decor-attribute-selector .clear-selection-btn', function(e) {
        e.stopPropagation(); // Prevent accordion toggle
        var $panel = $(this).closest('.attribute-panel');
        clearPanelSelection($panel);
    });
    
    // Swatch selection
    $(document).on('click', '.decor-attribute-selector .swatch-item', function() {
        var $swatch = $(this);
        var $panel = $swatch.closest('.attribute-panel');
        var attribute = $swatch.data('attribute');
        var value = $swatch.data('value');
        var name = $swatch.data('name');
        var image = $swatch.data('image');
        var price = parseFloat($swatch.data('price')) || 0;
        var priceType = $swatch.data('price-type') || 'fixed';
        
        // If already selected, deselect it
        if ($swatch.hasClass('selected')) {
            clearPanelSelection($panel);
            return;
        }
        
        // Handle exclusive group
        handleExclusiveGroup($panel);
        
        // Update visual selection
        $swatch.closest('.accordion-content').find('.swatch-item').removeClass('selected');
        $swatch.addClass('selected');
        
        // Update hidden input
        $swatch.closest('.accordion-content').find('input[type="hidden"]').val(value);
        
        // Update accordion header with price
        var $selection = $panel.find('.accordion-selection');
        var html = '';
        if (image) {
            html += '<img src="' + image + '" alt="' + name + '">';
        }
        html += '<span>' + name + '</span>';
        if (price > 0) {
            html += '<span class="selection-price"> (+' + (priceType === 'percentage' ? price + '%' : formatPrice(price)) + ')</span>';
        }
        $selection.html(html);
        
        // Show clear button
        $panel.find('.clear-selection-btn').show();
    });
    
    // Option button selection
    $(document).on('click', '.decor-attribute-selector .attribute-option', function() {
        var $option = $(this);
        var $panel = $option.closest('.attribute-panel');
        var attribute = $option.data('attribute');
        var value = $option.data('value');
        var name = $option.find('.option-name').text();
        var price = parseFloat($option.data('price')) || 0;
        var priceType = $option.data('price-type') || 'fixed';
        
        // If already selected, deselect it
        if ($option.hasClass('selected')) {
            clearPanelSelection($panel);
            return;
        }
        
        // Handle exclusive group
        handleExclusiveGroup($panel);
        
        // Update visual selection
        $option.closest('.accordion-content').find('.attribute-option').removeClass('selected');
        $option.addClass('selected');
        
        // Update hidden input
        $option.closest('.accordion-content').find('input[type="hidden"]').val(value);
        
        // Update accordion header with price
        var $selection = $panel.find('.accordion-selection');
        var html = '<span>' + name + '</span>';
        if (price > 0) {
            html += '<span class="selection-price"> (+' + (priceType === 'percentage' ? price + '%' : formatPrice(price)) + ')</span>';
        }
        $selection.html(html);
        
        // Show clear button
        $panel.find('.clear-selection-btn').show();
    });
    
    // ===== Required Attributes Validation =====
    
    // Get all unique groups that need selection
    function getRequiredGroups() {
        var groups = {};
        $selector.find('.attribute-panel[data-group]').each(function() {
            var group = $(this).data('group');
            if (group && group !== '') {
                groups[group] = $(this).find('.accordion-title').first().text().trim().split(' ')[0]; // Get first word like "GRADE"
            }
        });
        return groups;
    }
    
    // Check if all required groups have a selection
    function checkRequiredSelections() {
        var groups = getRequiredGroups();
        var missingGroups = [];
        
        for (var group in groups) {
            var hasSelection = false;
            $selector.find('.attribute-panel[data-group="' + group + '"]').each(function() {
                if ($(this).find('.swatch-item.selected, .attribute-option.selected').length > 0) {
                    hasSelection = true;
                    return false; // break
                }
            });
            
            if (!hasSelection) {
                missingGroups.push(groups[group] || group);
            }
        }
        
        // Also check panels without groups (independent attributes like SIZE)
        $selector.find('.attribute-panel:not([data-group]), .attribute-panel[data-group=""]').each(function() {
            var $panel = $(this);
            var label = $panel.find('.accordion-title').text().trim();
            var hasSelection = $panel.find('.swatch-item.selected, .attribute-option.selected').length > 0;
            
            if (!hasSelection && label) {
                missingGroups.push(label);
            }
        });
        
        return missingGroups;
    }
    
    // Show modal for missing selections
    function showRequiredModal(missingGroups) {
        // Remove existing modal
        $('.decor-required-modal-overlay').remove();
        
        var missingList = missingGroups.map(function(g) { return '<li>' + g + '</li>'; }).join('');
        
        var modalHtml = '<div class="decor-required-modal-overlay">' +
            '<div class="decor-required-modal">' +
                '<button class="modal-close">&times;</button>' +
                '<div class="modal-icon">' +
                    '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#c4a47c" stroke-width="2">' +
                        '<circle cx="12" cy="12" r="10"></circle>' +
                        '<line x1="12" y1="8" x2="12" y2="12"></line>' +
                        '<line x1="12" y1="16" x2="12.01" y2="16"></line>' +
                    '</svg>' +
                '</div>' +
                '<h3>Please Complete Your Selection</h3>' +
                '<p>To add this item to your cart, please select one option from each of the following:</p>' +
                '<ul class="missing-list">' + missingList + '</ul>' +
                '<button class="modal-btn">Continue Shopping</button>' +
            '</div>' +
        '</div>';
        
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
    
    // Intercept Add to Cart button click
    $(document).on('click', '.single_add_to_cart_button', function(e) {
        var missingGroups = checkRequiredSelections();
        
        if (missingGroups.length > 0) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            showRequiredModal(missingGroups);
            return false;
        }
    });
});
</script>
