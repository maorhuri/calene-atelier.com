<?php
/**
 * Fabric Selector Template
 * West Elm style variation selector with left panel
 * 
 * Available variables:
 * - $product: WC_Product
 * - $grouped_variations: array of variations grouped by fabric type
 * - $all_colors: array of color groups used
 * - $all_fabrics: array of fabric types used
 * - $total_choices: int total number of variations
 * - $fabric_types: array of WP_Term objects for fabric types
 * - $color_groups: array of WP_Term objects for color groups
 * - $enable_order_swatches: bool whether to show order swatches button
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
$first_variation_id = null;
foreach ($grouped_variations as $cat => $vars) {
    if (!empty($vars)) {
        $first_variation = $vars[0];
        $first_variation_id = $first_variation['variation_id'];
        break;
    }
}
?>

<div class="decor-fabric-selector" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
    
    <!-- Header -->
    <div class="fabric-selector-header">
        <h3 class="fabric-selector-title">Fabric and Color</h3>
        <span class="fabric-choices-count"><?php echo esc_html($total_choices); ?> Choices</span>
        <button type="button" class="fabric-selector-toggle" aria-expanded="true">
            <svg class="toggle-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        </button>
    </div>
    
    <!-- Main Content Area with Left Panel -->
    <div class="fabric-selector-content">
        
        <!-- Left Panel - Fabric Details (Fixed) -->
        <div class="fabric-detail-panel">
            <div class="fabric-detail-image">
                <img src="" alt="" class="detail-preview-image" id="fabric-detail-image">
            </div>
            <div class="fabric-detail-info">
                <h4 class="fabric-detail-name" id="fabric-detail-name">Hover over a swatch to see details</h4>
                <p class="fabric-detail-description" id="fabric-detail-description"></p>
                <div class="fabric-detail-specs">
                    <div class="detail-spec" id="spec-content" style="display:none;">
                        <span class="spec-label">Content:</span>
                        <span class="spec-value"></span>
                    </div>
                    <div class="detail-spec" id="spec-rub-count" style="display:none;">
                        <span class="spec-label">Rub Count:</span>
                        <span class="spec-value"></span>
                    </div>
                    <div class="detail-spec" id="spec-price-tier" style="display:none;">
                        <span class="spec-label">Price Tier:</span>
                        <span class="spec-value"></span>
                    </div>
                    <div class="detail-spec" id="spec-care" style="display:none;">
                        <span class="spec-label">Care:</span>
                        <span class="spec-value"></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Panel - Swatches Grid -->
        <div class="fabric-swatches-panel">
            
            <!-- Filters -->
            <div class="fabric-filters">
                <div class="filter-dropdown">
                    <button type="button" class="filter-btn" data-filter="color">
                        Color
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="filter-dropdown-content" data-filter-type="color">
                        <button type="button" class="filter-option active" data-value="">All</button>
                        <?php foreach ($all_colors as $color => $v) : ?>
                            <button type="button" class="filter-option" data-value="<?php echo esc_attr($color); ?>">
                                <span class="color-dot color-<?php echo esc_attr($color); ?>"></span>
                                <?php echo esc_html($color_labels[$color] ?? ucfirst($color)); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="filter-dropdown">
                    <button type="button" class="filter-btn" data-filter="fabric">
                        Fabric
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="filter-dropdown-content" data-filter-type="fabric">
                        <button type="button" class="filter-option active" data-value="">All</button>
                        <?php foreach ($all_fabrics as $fabric => $v) : ?>
                            <button type="button" class="filter-option" data-value="<?php echo esc_attr($fabric); ?>">
                                <?php echo esc_html($fabric_labels[$fabric] ?? ucfirst($fabric)); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
    
    <!-- Fabric Grid by Category -->
    <div class="fabric-categories-container">
        <?php foreach ($grouped_variations as $category => $category_variations) : ?>
            <div class="fabric-category" data-category="<?php echo esc_attr($category); ?>">
                <h4 class="fabric-category-title"><?php echo esc_html($fabric_labels[$category] ?? $category); ?></h4>
                <div class="fabric-swatches-grid">
                    <?php foreach ($category_variations as $variation) : 
                        $swatch_image = $variation['swatch_image'] ?? ($variation['image']['thumb_src'] ?? '');
                        $large_image = $variation['image']['src'] ?? $swatch_image;
                        $variation_id = $variation['variation_id'];
                        
                        // Get fabric details from variation meta
                        $fabric_content = get_post_meta($variation_id, '_fabric_content', true);
                        $rub_count = get_post_meta($variation_id, '_rub_count', true);
                        $price_tier = get_post_meta($variation_id, '_price_tier', true);
                        $care_instructions = get_post_meta($variation_id, '_care_instructions', true);
                        $fabric_description = get_post_meta($variation_id, '_fabric_description', true);
                        
                        // Build variation name from color attribute only
                        $variation_name = '';
                        $fabric_type_attr = Decor_Fabric_Variations::get_instance()->get_fabric_type_attribute();
                        foreach ($variation['attributes'] as $attr_name => $attr_value) {
                            $taxonomy = str_replace('attribute_', '', $attr_name);
                            // Skip fabric type attribute - we only want color name
                            if ($taxonomy === $fabric_type_attr) {
                                continue;
                            }
                            $term = get_term_by('slug', $attr_value, $taxonomy);
                            $variation_name .= ($term ? $term->name : $attr_value) . ' ';
                        }
                        $variation_name = trim($variation_name);
                        
                        // Full name includes fabric type
                        $full_name = $variation_name . ', ' . ($fabric_labels[$category] ?? ucfirst($category));
                    ?>
                        <div class="fabric-swatch" 
                             data-variation-id="<?php echo esc_attr($variation['variation_id']); ?>"
                             data-color-group="<?php echo esc_attr($variation['color_group'] ?? ''); ?>"
                             data-fabric-type="<?php echo esc_attr($variation['fabric_type'] ?? ''); ?>"
                             data-attributes='<?php echo esc_attr(json_encode($variation['attributes'])); ?>'
                             data-image="<?php echo esc_attr($large_image); ?>"
                             data-name="<?php echo esc_attr($full_name); ?>"
                             data-description="<?php echo esc_attr($fabric_description); ?>"
                             data-content="<?php echo esc_attr($fabric_content); ?>"
                             data-rub-count="<?php echo esc_attr($rub_count); ?>"
                             data-price-tier="<?php echo esc_attr($price_tier); ?>"
                             data-care="<?php echo esc_attr($care_instructions); ?>">
                            <div class="swatch-image-wrapper">
                                <?php if ($swatch_image) : ?>
                                    <img src="<?php echo esc_url($swatch_image); ?>" 
                                         alt="<?php echo esc_attr($variation_name); ?>"
                                         loading="lazy">
                                <?php else : ?>
                                    <div class="swatch-placeholder"></div>
                                <?php endif; ?>
                            </div>
                            <div class="swatch-info">
                                <span class="swatch-name"><?php echo esc_html($variation_name); ?></span>
                                <span class="swatch-fabric"><?php echo esc_html($fabric_labels[$category] ?? ucfirst($category)); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
            </div>
            
            <!-- Order Swatches Button -->
            <?php if ($enable_order_swatches) : ?>
            <div class="order-swatches-section">
                <button type="button" class="order-swatches-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="9" y1="21" x2="9" y2="9"></line>
                    </svg>
                    Order Free Swatches
                </button>
            </div>
            <?php endif; ?>
            
        </div><!-- .fabric-swatches-panel -->
    </div><!-- .fabric-selector-content -->
    
</div>
