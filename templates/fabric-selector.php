<?php
/**
 * Fabric Selector Template - West Elm Style
 * Tabs for each attribute type
 */

if (!defined('ABSPATH')) {
    exit;
}

// Build labels from WooCommerce attribute terms
$fabric_labels = array('other' => 'Other');
if (!empty($fabric_types)) {
    foreach ($fabric_types as $term) {
        $fabric_labels[$term->slug] = $term->name;
    }
}

$color_labels = array();
if (!empty($color_groups)) {
    foreach ($color_groups as $term) {
        $color_labels[$term->slug] = $term->name;
    }
}

// Get first variation for initial display
$first_variation = null;
$first_swatch = '';
$first_name = '';
foreach ($grouped_variations as $cat => $vars) {
    if (!empty($vars)) {
        $first_variation = $vars[0];
        $first_swatch = $first_variation['image']['thumb_src'] ?? '';
        // Build name
        foreach ($first_variation['attributes'] as $attr_name => $attr_value) {
            $taxonomy = str_replace('attribute_', '', $attr_name);
            $term = get_term_by('slug', $attr_value, $taxonomy);
            $first_name .= ($term ? $term->name : $attr_value) . ', ';
        }
        $first_name = rtrim($first_name, ', ');
        break;
    }
}

// Check if we have multiple attributes (need tabs)
$has_multiple_attributes = !empty($attributes_data) && count($attributes_data) > 1;
?>

<div class="decor-fabric-selector" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
    
    <!-- Collapsible Header with Current Selection -->
    <div class="fabric-selector-header" data-expanded="true">
        <div class="header-content">
            <span class="header-label">Fabric and Color:</span>
            <span class="header-selection" id="selected-fabric-name"><?php echo esc_html($first_name ?: 'Select an option'); ?></span>
        </div>
        <div class="header-swatch">
            <img src="<?php echo esc_url($first_swatch); ?>" alt="" id="selected-fabric-swatch">
        </div>
        <button type="button" class="header-toggle" aria-expanded="true">
            <svg class="toggle-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        </button>
    </div>
    
    <!-- Expandable Content -->
    <div class="fabric-selector-body">
        
        <?php 
        // Determine which attribute is the "fabric" type (shows images with popup)
        $fabric_type_attr = Decor_Fabric_Variations::get_instance()->get_fabric_type_attribute();
        $fabric_attr_name = str_replace('pa_', '', $fabric_type_attr);
        
        if (!empty($attributes_data)) : ?>
        <!-- Attribute Tabs -->
        <div class="attribute-tabs">
            <?php $tab_index = 0; foreach ($attributes_data as $attr_name => $attr_data) : ?>
                <button type="button" class="attribute-tab <?php echo $tab_index === 0 ? 'active' : ''; ?>" 
                        data-attribute="<?php echo esc_attr($attr_name); ?>">
                    <?php echo esc_html($attr_data['label']); ?>
                    <span class="tab-count"><?php echo count($attr_data['options']); ?> Choices</span>
                </button>
            <?php $tab_index++; endforeach; ?>
        </div>
        
        <!-- Attribute Panels (Accordion Style) -->
        <?php $panel_index = 0; foreach ($attributes_data as $attr_name => $attr_data) : 
            // Check if this is the fabric type attribute (shows images)
            $is_fabric_attr = (strpos($attr_name, 'fabric') !== false || strpos($attr_name, 'color') !== false || $attr_name === $fabric_attr_name);
        ?>
            <div class="attribute-panel" 
                 data-attribute="<?php echo esc_attr($attr_name); ?>"
                 data-label="<?php echo esc_attr($attr_data['label']); ?>">
                
                <!-- Accordion Header -->
                <div class="accordion-header">
                    <span class="accordion-title"><?php echo esc_html($attr_data['label']); ?></span>
                    <div class="accordion-right">
                        <span class="accordion-selection" data-attribute="<?php echo esc_attr($attr_name); ?>"></span>
                        <span class="accordion-icon">▼</span>
                    </div>
                </div>
                
                <!-- Accordion Content -->
                <div class="accordion-content">
                <?php if ($is_fabric_attr) : ?>
                <!-- Fabric/Color attribute - show UNIQUE values only with image swatches -->
                <div class="swatches-grid">
                    <?php 
                    // Get unique values for this attribute and find first variation for each
                    $shown_values = array();
                    foreach ($grouped_variations as $category => $category_variations) : 
                        foreach ($category_variations as $variation) : 
                            // Get the value of THIS attribute from the variation
                            $attr_key = 'attribute_' . $attr_name;
                            $attr_value = isset($variation['attributes'][$attr_key]) ? $variation['attributes'][$attr_key] : '';
                            
                            // Skip if we already showed this value
                            if (empty($attr_value) || in_array($attr_value, $shown_values)) {
                                continue;
                            }
                            $shown_values[] = $attr_value;
                            
                            // Get term and its swatch image
                            $term = get_term_by('slug', $attr_value, $attr_data['taxonomy']);
                            $swatch_image = '';
                            $large_image = '';
                            
                            if ($term) {
                                // Get the 'image' meta which is a serialized array with 'url' and 'id'
                                $image_meta = get_term_meta($term->term_id, 'image', true);
                                
                                if ($image_meta) {
                                    // Check if it's serialized
                                    if (is_string($image_meta) && strpos($image_meta, 'a:') === 0) {
                                        $image_data = @unserialize($image_meta);
                                        if ($image_data && isset($image_data['url'])) {
                                            $swatch_image = $image_data['url'];
                                            $large_image = $image_data['url'];
                                            
                                            // If we have an ID, get proper sized images
                                            if (isset($image_data['id']) && $image_data['id']) {
                                                $thumb = wp_get_attachment_image_url($image_data['id'], 'thumbnail');
                                                $medium = wp_get_attachment_image_url($image_data['id'], 'medium');
                                                if ($thumb) $swatch_image = $thumb;
                                                if ($medium) $large_image = $medium;
                                            }
                                        }
                                    } elseif (is_array($image_meta) && isset($image_meta['url'])) {
                                        // Already unserialized
                                        $swatch_image = $image_meta['url'];
                                        $large_image = $image_meta['url'];
                                        
                                        if (isset($image_meta['id']) && $image_meta['id']) {
                                            $thumb = wp_get_attachment_image_url($image_meta['id'], 'thumbnail');
                                            $medium = wp_get_attachment_image_url($image_meta['id'], 'medium');
                                            if ($thumb) $swatch_image = $thumb;
                                            if ($medium) $large_image = $medium;
                                        }
                                    } elseif (is_numeric($image_meta)) {
                                        // It's just an ID
                                        $swatch_image = wp_get_attachment_image_url($image_meta, 'thumbnail');
                                        $large_image = wp_get_attachment_image_url($image_meta, 'medium');
                                    }
                                }
                            }
                            
                            // Fallback to variation image if no term image
                            if (empty($swatch_image)) {
                                $swatch_image = $variation['image']['thumb_src'] ?? '';
                                $large_image = $variation['image']['src'] ?? $swatch_image;
                            }
                            $variation_id = $variation['variation_id'];
                            
                            // Get fabric details
                            $fabric_content = get_post_meta($variation_id, '_fabric_content', true);
                            $rub_count = get_post_meta($variation_id, '_rub_count', true);
                            $price_tier = get_post_meta($variation_id, '_price_tier', true);
                            $care_instructions = get_post_meta($variation_id, '_care_instructions', true);
                            $fabric_description = get_post_meta($variation_id, '_fabric_description', true);
                            
                            // Get display name for this attribute value
                            $term = get_term_by('slug', $attr_value, $attr_data['taxonomy']);
                            $display_name = $term ? $term->name : ucfirst($attr_value);
                    ?>
                        <div class="swatch-item" 
                             data-attribute="<?php echo esc_attr($attr_name); ?>"
                             data-value="<?php echo esc_attr($attr_value); ?>"
                             data-name="<?php echo esc_attr($display_name); ?>"
                             data-image="<?php echo esc_attr($large_image); ?>">
                            
                            <!-- Hover Popup -->
                            <div class="swatch-popup">
                                <div class="popup-image">
                                    <img src="<?php echo esc_url($large_image); ?>" alt="<?php echo esc_attr($display_name); ?>">
                                </div>
                                <div class="popup-content">
                                    <h5 class="popup-name"><?php echo esc_html($display_name); ?></h5>
                                    <?php if ($fabric_description) : ?>
                                        <p class="popup-desc"><?php echo esc_html($fabric_description); ?></p>
                                    <?php endif; ?>
                                    <div class="popup-specs">
                                        <?php if ($fabric_content) : ?>
                                            <div><strong>Content:</strong> <?php echo esc_html($fabric_content); ?></div>
                                        <?php endif; ?>
                                        <?php if ($rub_count) : ?>
                                            <div><strong>Rub Count:</strong> <?php echo esc_html($rub_count); ?></div>
                                        <?php endif; ?>
                                        <?php if ($price_tier) : ?>
                                            <div><strong>Price Tier:</strong> <?php echo esc_html($price_tier); ?></div>
                                        <?php endif; ?>
                                        <?php if ($care_instructions) : ?>
                                            <div><strong>Care:</strong> <?php echo esc_html($care_instructions); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="swatch-image">
                                <?php if ($swatch_image) : ?>
                                    <img src="<?php echo esc_url($swatch_image); ?>" alt="<?php echo esc_attr($display_name); ?>" loading="lazy">
                                <?php else : ?>
                                    <div class="swatch-placeholder"></div>
                                <?php endif; ?>
                            </div>
                            <div class="swatch-label">
                                <span class="swatch-color"><?php echo esc_html($display_name); ?></span>
                            </div>
                        </div>
                    <?php endforeach; endforeach; ?>
                </div>
                
                <?php else : ?>
                <!-- Other attributes - show simple buttons -->
                <div class="attribute-options-grid">
                    <?php foreach ($attr_data['options'] as $option_slug => $option_data) : ?>
                        <button type="button" class="attribute-option" 
                                data-attribute="<?php echo esc_attr($attr_name); ?>"
                                data-value="<?php echo esc_attr($option_slug); ?>">
                            <span class="option-name"><?php echo esc_html($option_data['name']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                </div><!-- .accordion-content -->
                
            </div>
        <?php $panel_index++; endforeach; ?>
        <?php else : ?>
        
        <!-- Filters Row (single attribute mode) -->
        <div class="swatches-filters">
                    <div class="filter-dropdown">
                        <button type="button" class="filter-btn" data-filter="color">
                            Color
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="filter-dropdown-menu" data-filter-type="color">
                            <button type="button" class="filter-option active" data-value="">All</button>
                            <?php foreach ($all_colors as $color => $v) : ?>
                                <button type="button" class="filter-option" data-value="<?php echo esc_attr($color); ?>">
                                    <?php echo esc_html($color_labels[$color] ?? ucfirst($color)); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="filter-dropdown">
                        <button type="button" class="filter-btn" data-filter="fabric">
                            Fabric
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="filter-dropdown-menu" data-filter-type="fabric">
                            <button type="button" class="filter-option active" data-value="">All</button>
                            <?php foreach ($all_fabrics as $fabric => $v) : ?>
                                <button type="button" class="filter-option" data-value="<?php echo esc_attr($fabric); ?>">
                                    <?php echo esc_html($fabric_labels[$fabric] ?? ucfirst($fabric)); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Swatches Grid -->
                <div class="swatches-grid-container">
                    <?php foreach ($grouped_variations as $category => $category_variations) : ?>
                        <div class="fabric-category-section" data-category="<?php echo esc_attr($category); ?>">
                            <?php if ($category !== 'other' && count($grouped_variations) > 1) : ?>
                                <h5 class="category-title"><?php echo esc_html($fabric_labels[$category] ?? ucfirst($category)); ?></h5>
                            <?php endif; ?>
                            <div class="swatches-grid">
                                <?php foreach ($category_variations as $variation) : 
                                    $swatch_image = $variation['image']['thumb_src'] ?? '';
                                    $large_image = $variation['image']['src'] ?? $swatch_image;
                                    $variation_id = $variation['variation_id'];
                                    
                                    // Get fabric details
                                    $fabric_content = get_post_meta($variation_id, '_fabric_content', true);
                                    $rub_count = get_post_meta($variation_id, '_rub_count', true);
                                    $price_tier = get_post_meta($variation_id, '_price_tier', true);
                                    $care_instructions = get_post_meta($variation_id, '_care_instructions', true);
                                    $fabric_description = get_post_meta($variation_id, '_fabric_description', true);
                                    
                                    // Build variation name
                                    $variation_name = '';
                                    $fabric_type_attr = Decor_Fabric_Variations::get_instance()->get_fabric_type_attribute();
                                    foreach ($variation['attributes'] as $attr_name => $attr_value) {
                                        $taxonomy = str_replace('attribute_', '', $attr_name);
                                        if ($taxonomy === $fabric_type_attr) continue;
                                        $term = get_term_by('slug', $attr_value, $taxonomy);
                                        $variation_name .= ($term ? $term->name : $attr_value) . ' ';
                                    }
                                    $variation_name = trim($variation_name);
                                    $fabric_type_name = $fabric_labels[$category] ?? ucfirst($category);
                                    $full_name = $variation_name . ', ' . $fabric_type_name;
                                ?>
                                    <div class="swatch-item" 
                                         data-variation-id="<?php echo esc_attr($variation_id); ?>"
                                         data-color-group="<?php echo esc_attr($variation['color_group'] ?? ''); ?>"
                                         data-fabric-type="<?php echo esc_attr($variation['fabric_type'] ?? ''); ?>"
                                         data-attributes='<?php echo esc_attr(json_encode($variation['attributes'])); ?>'
                                         data-name="<?php echo esc_attr($full_name); ?>"
                                         data-image="<?php echo esc_attr($large_image); ?>">
                                        
                                        <!-- Hover Popup -->
                                        <div class="swatch-popup">
                                            <div class="popup-image">
                                                <img src="<?php echo esc_url($large_image); ?>" alt="<?php echo esc_attr($full_name); ?>">
                                            </div>
                                            <div class="popup-content">
                                                <h5 class="popup-name"><?php echo esc_html($full_name); ?></h5>
                                                <?php if ($fabric_description) : ?>
                                                    <p class="popup-desc"><?php echo esc_html($fabric_description); ?></p>
                                                <?php endif; ?>
                                                <div class="popup-specs">
                                                    <?php if ($fabric_content) : ?>
                                                        <div><strong>Content:</strong> <?php echo esc_html($fabric_content); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($rub_count) : ?>
                                                        <div><strong>Rub Count:</strong> <?php echo esc_html($rub_count); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($price_tier) : ?>
                                                        <div><strong>Price Tier:</strong> <?php echo esc_html($price_tier); ?></div>
                                                    <?php endif; ?>
                                                    <?php if ($care_instructions) : ?>
                                                        <div><strong>Care:</strong> <?php echo esc_html($care_instructions); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="swatch-image">
                                            <?php if ($swatch_image) : ?>
                                                <img src="<?php echo esc_url($swatch_image); ?>" alt="<?php echo esc_attr($variation_name); ?>" loading="lazy">
                                            <?php else : ?>
                                                <div class="swatch-placeholder"></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="swatch-label">
                                            <span class="swatch-color"><?php echo esc_html($variation_name); ?></span>
                                            <span class="swatch-fabric"><?php echo esc_html($fabric_type_name); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
        <?php endif; ?>
                
        <!-- Order Swatches Button -->
        <?php if ($enable_order_swatches) : ?>
        <div class="order-swatches-wrap">
            <button type="button" class="order-swatches-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="3" y1="9" x2="21" y2="9"></line>
                    <line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>
                ORDER FREE SWATCHES
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Selection Summary (inside the selector) -->
        <div class="selection-summary-inline" id="inline-summary">
            <h4 class="summary-title">Your Selection Summary</h4>
            <div class="summary-selections" id="summary-selections">
                <?php foreach ($attributes_data as $attr_name => $attr_data) : ?>
                    <div class="summary-row" data-attribute="<?php echo esc_attr($attr_name); ?>">
                        <span class="summary-label"><?php echo esc_html($attr_data['label']); ?>:</span>
                        <span class="summary-value">Make Selection</span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Fabric Details Section -->
            <div class="summary-fabric-details" id="summary-fabric-details" style="display: none;">
                <div class="fabric-details-title">Fabric Details</div>
                <div class="summary-row" data-detail="content">
                    <span class="summary-label">Content:</span>
                    <span class="summary-value">-</span>
                </div>
                <div class="summary-row" data-detail="rub-count">
                    <span class="summary-label">Rub Count:</span>
                    <span class="summary-value">-</span>
                </div>
                <div class="summary-row" data-detail="price-tier">
                    <span class="summary-label">Price Tier:</span>
                    <span class="summary-value">-</span>
                </div>
                <div class="summary-row" data-detail="care">
                    <span class="summary-label">Care:</span>
                    <span class="summary-value">-</span>
                </div>
            </div>
            
            <div class="summary-pricing">
                <div class="summary-row">
                    <span class="summary-label">Base Price:</span>
                    <span class="summary-value" id="summary-base-price"><?php echo $product->get_price_html(); ?></span>
                </div>
                <div class="summary-row summary-total">
                    <span class="summary-label">Total:</span>
                    <span class="summary-value" id="summary-total-price">-</span>
                </div>
            </div>
        </div>
        
    </div>
</div>
