<?php
/**
 * Product Specifications Module
 * Custom fields for product: Technical Description, Dimensions, Specs
 * 
 * @package Decor_Product_Specs
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Decor_Product_Specs {
    
    private static $instance = null;
    
    const VERSION = '1.0.6';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Add custom fields to product edit page
        add_action('woocommerce_product_data_tabs', array($this, 'add_product_data_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_product_data_panel'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_data'));
        
        // Display on frontend
        add_action('woocommerce_after_single_product_summary', array($this, 'display_product_specs'), 15);
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Enqueue frontend styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
    }
    
    /**
     * Add custom tab to product data
     */
    public function add_product_data_tab($tabs) {
        $tabs['product_specs'] = array(
            'label'    => __('Product Specs', 'decor'),
            'target'   => 'product_specs_data',
            'class'    => array(),
            'priority' => 80,
        );
        return $tabs;
    }
    
    /**
     * Add custom panel content
     */
    public function add_product_data_panel() {
        global $post;
        ?>
        <div id="product_specs_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <h4 style="padding: 10px 12px; margin: 0; border-bottom: 1px solid #eee;">
                    <?php _e('Technical Description', 'decor'); ?>
                </h4>
                <p class="form-field" style="padding: 12px;">
                    <textarea name="_product_technical_description" rows="6" style="width: 100%;"><?php echo esc_textarea(get_post_meta($post->ID, '_product_technical_description', true)); ?></textarea>
                    <span class="description"><?php _e('Enter technical description. HTML allowed.', 'decor'); ?></span>
                </p>
            </div>
            
            <div class="options_group">
                <h4 style="padding: 10px 12px; margin: 0; border-bottom: 1px solid #eee;">
                    <?php _e('Dimensions', 'decor'); ?>
                </h4>
                
                <p class="form-field">
                    <label><?php _e('Dimensions Text', 'decor'); ?></label>
                    <textarea name="_product_dimensions_text" rows="3" style="width: 100%;"><?php echo esc_textarea(get_post_meta($post->ID, '_product_dimensions_text', true)); ?></textarea>
                </p>
                
                <p class="form-field">
                    <label><?php _e('Dimensions Image', 'decor'); ?></label>
                    <input type="hidden" name="_product_dimensions_image" id="product_dimensions_image" value="<?php echo esc_attr(get_post_meta($post->ID, '_product_dimensions_image', true)); ?>">
                    <button type="button" class="button upload_dimensions_image"><?php _e('Upload Image', 'decor'); ?></button>
                    <button type="button" class="button remove_dimensions_image" style="<?php echo get_post_meta($post->ID, '_product_dimensions_image', true) ? '' : 'display:none;'; ?>"><?php _e('Remove Image', 'decor'); ?></button>
                </p>
                <div class="dimensions_image_preview" style="margin: 10px 12px;">
                    <?php 
                    $image_id = get_post_meta($post->ID, '_product_dimensions_image', true);
                    if ($image_id) {
                        echo wp_get_attachment_image($image_id, 'medium');
                    }
                    ?>
                </div>
            </div>
            
            <div class="options_group">
                <h4 style="padding: 10px 12px; margin: 0; border-bottom: 1px solid #eee;">
                    <?php _e('Specifications', 'decor'); ?>
                    <span style="font-weight: normal; font-size: 12px; color: #666;">
                        (<?php _e('Add specification items', 'decor'); ?>)
                    </span>
                </h4>
                
                <div class="specs_container" style="padding: 12px;">
                    <?php
                    $specs = get_post_meta($post->ID, '_product_specs', true);
                    if (!is_array($specs)) {
                        $specs = array();
                    }
                    
                    if (empty($specs)) {
                        $specs[] = array('label' => '', 'value' => '');
                    }
                    
                    foreach ($specs as $index => $spec) :
                    ?>
                    <div class="spec_row" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                        <input type="text" name="_product_specs[<?php echo $index; ?>][label]" placeholder="<?php _e('Label (e.g., Material)', 'decor'); ?>" value="<?php echo esc_attr($spec['label'] ?? ''); ?>" style="flex: 1;">
                        <input type="text" name="_product_specs[<?php echo $index; ?>][value]" placeholder="<?php _e('Value (e.g., Wood)', 'decor'); ?>" value="<?php echo esc_attr($spec['value'] ?? ''); ?>" style="flex: 1;">
                        <button type="button" class="button remove_spec" style="color: #a00;">&times;</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <p style="padding: 0 12px;">
                    <button type="button" class="button add_spec"><?php _e('+ Add Specification', 'decor'); ?></button>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save custom product data
     */
    public function save_product_data($post_id) {
        // Technical Description
        if (isset($_POST['_product_technical_description'])) {
            update_post_meta($post_id, '_product_technical_description', wp_kses_post($_POST['_product_technical_description']));
        }
        
        // Dimensions Text
        if (isset($_POST['_product_dimensions_text'])) {
            update_post_meta($post_id, '_product_dimensions_text', sanitize_textarea_field($_POST['_product_dimensions_text']));
        }
        
        // Dimensions Image
        if (isset($_POST['_product_dimensions_image'])) {
            update_post_meta($post_id, '_product_dimensions_image', absint($_POST['_product_dimensions_image']));
        }
        
        // Specifications
        if (isset($_POST['_product_specs']) && is_array($_POST['_product_specs'])) {
            $specs = array();
            foreach ($_POST['_product_specs'] as $spec) {
                if (!empty($spec['label']) || !empty($spec['value'])) {
                    $specs[] = array(
                        'label' => sanitize_text_field($spec['label']),
                        'value' => sanitize_text_field($spec['value']),
                    );
                }
            }
            update_post_meta($post_id, '_product_specs', $specs);
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post;
        
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        if (!$post || $post->post_type !== 'product') {
            return;
        }
        
        wp_enqueue_media();
        
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                // Upload dimensions image
                $(document).on("click", ".upload_dimensions_image", function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var frame = wp.media({
                        title: "Select Dimensions Image",
                        button: { text: "Use this image" },
                        multiple: false
                    });
                    
                    frame.on("select", function() {
                        var attachment = frame.state().get("selection").first().toJSON();
                        $("#product_dimensions_image").val(attachment.id);
                        $(".dimensions_image_preview").html("<img src=\"" + attachment.url + "\" style=\"max-width: 300px;\">");
                        $(".remove_dimensions_image").show();
                    });
                    
                    frame.open();
                });
                
                // Remove dimensions image
                $(document).on("click", ".remove_dimensions_image", function(e) {
                    e.preventDefault();
                    $("#product_dimensions_image").val("");
                    $(".dimensions_image_preview").html("");
                    $(this).hide();
                });
                
                // Add specification row
                $(document).on("click", ".add_spec", function(e) {
                    e.preventDefault();
                    var index = $(".spec_row").length;
                    var row = "<div class=\"spec_row\" style=\"display: flex; gap: 10px; margin-bottom: 10px; align-items: center;\">" +
                        "<input type=\"text\" name=\"_product_specs[" + index + "][label]\" placeholder=\"Label (e.g., Material)\" style=\"flex: 1;\">" +
                        "<input type=\"text\" name=\"_product_specs[" + index + "][value]\" placeholder=\"Value (e.g., Wood)\" style=\"flex: 1;\">" +
                        "<button type=\"button\" class=\"button remove_spec\" style=\"color: #a00;\">&times;</button>" +
                        "</div>";
                    $(".specs_container").append(row);
                });
                
                // Remove specification row
                $(document).on("click", ".remove_spec", function(e) {
                    e.preventDefault();
                    $(this).closest(".spec_row").remove();
                });
            });
        ');
    }
    
    /**
     * Enqueue frontend styles
     */
    public function enqueue_frontend_styles() {
        if (!is_product()) {
            return;
        }
        
        // Output CSS directly in head
        add_action('wp_head', array($this, 'output_frontend_css'), 999);
    }
    
    /**
     * Output CSS in head
     */
    public function output_frontend_css() {
        echo '<style id="product-specs-css">' . $this->get_frontend_css() . '</style>';
    }
    
    /**
     * Get frontend CSS - West Elm style sticky tabs
     */
    private function get_frontend_css() {
        return '
            /* Sticky Navigation Bar */
            .product-specs-nav {
                position: sticky;
                top: 92px; /* Below Woodmart header */
                z-index: 99;
                background: #fff;
                border-top: 1px solid #e5e5e5;
                border-bottom: 1px solid #e5e5e5;
                padding: 0;
                margin: 40px 0 0 0;
                width: 100vw;
                margin-left: calc(-50vw + 50%);
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            }
            
            /* Adjust for sticky header */
            .whb-sticky-shadow .product-specs-nav,
            .whb-sticky-real .product-specs-nav {
                top: 80px;
            }
            
            .product-specs-nav ul {
                display: flex !important;
                list-style: none !important;
                margin: 0 auto !important;
                padding: 0 !important;
                gap: 0 !important;
                justify-content: center;
                max-width: 1200px;
            }
            
            .product-specs-nav li {
                margin: 0 !important;
                padding: 0 !important;
                list-style: none !important;
            }
            
            .product-specs-nav li::before {
                display: none !important;
            }
            
            .product-specs-nav a {
                display: block;
                padding: 18px 30px;
                color: #666;
                text-decoration: none !important;
                font-size: 13px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1px;
                border-bottom: 3px solid transparent;
                transition: all 0.2s ease;
                margin-bottom: -1px;
            }
            
            .product-specs-nav a:hover {
                color: #333;
                text-decoration: none !important;
            }
            
            .product-specs-nav a.active {
                color: #333;
                border-bottom-color: #333;
            }
            
            /* Sections Container - Premium Design */
            .product-specs-section {
                margin: 0;
                padding: 0;
                max-width: 1100px;
                margin: 0 auto;
            }
            
            .product-spec-panel {
                padding: 80px 40px;
                border-bottom: 1px solid #e8e8e8;
                position: relative;
            }
            
            .product-spec-panel:last-child {
                border-bottom: none;
            }
            
            /* Premium Section Title */
            .product-spec-panel h3 {
                font-size: 11px;
                font-weight: 700;
                margin: 0 0 40px 0;
                color: #1a1a1a;
                text-transform: uppercase;
                letter-spacing: 3px;
                position: relative;
                display: inline-block;
            }
            
            .product-spec-panel h3::after {
                content: "";
                position: absolute;
                bottom: -12px;
                left: 0;
                width: 40px;
                height: 2px;
                background: #1a1a1a;
            }
            
            /* Content Typography */
            .product-spec-panel .content {
                color: #4a4a4a;
                line-height: 1.9;
                font-size: 15px;
                font-weight: 300;
                max-width: 700px;
            }
            
            .product-spec-panel .content p {
                margin-bottom: 20px;
            }
            
            .product-spec-panel .content strong {
                font-weight: 500;
                color: #1a1a1a;
            }
            
            /* Dimensions - Premium Layout */
            .product-dimensions-panel .dimensions-content {
                display: grid;
                grid-template-columns: 1fr 1.2fr;
                gap: 60px;
                align-items: start;
            }
            
            .product-dimensions-panel .dimensions-text {
                font-size: 15px;
                line-height: 2;
                color: #4a4a4a;
                font-weight: 300;
            }
            
            .product-dimensions-panel .dimensions-text strong {
                display: block;
                font-size: 13px;
                font-weight: 600;
                color: #1a1a1a;
                text-transform: uppercase;
                letter-spacing: 1px;
                margin-bottom: 15px;
            }
            
            .product-dimensions-panel .dimensions-image {
                background: linear-gradient(145deg, #fafafa 0%, #f0f0f0 100%);
                padding: 40px;
                text-align: center;
                border: 1px solid #e8e8e8;
            }
            
            .product-dimensions-panel .dimensions-image img {
                max-width: 100%;
                height: auto;
                filter: drop-shadow(0 4px 12px rgba(0,0,0,0.08));
            }
            
            /* Specifications - Premium Grid */
            .product-specs-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 0;
                border-top: 1px solid #e8e8e8;
            }
            
            .spec-item {
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                padding: 20px 0;
                border-bottom: 1px solid #e8e8e8;
            }
            
            .spec-item:nth-child(odd) {
                padding-right: 40px;
                border-right: 1px solid #e8e8e8;
            }
            
            .spec-item:nth-child(even) {
                padding-left: 40px;
            }
            
            .spec-label {
                font-size: 11px;
                font-weight: 600;
                color: #1a1a1a;
                text-transform: uppercase;
                letter-spacing: 1.5px;
            }
            
            .spec-value {
                font-size: 14px;
                color: #4a4a4a;
                font-weight: 300;
                text-align: right;
            }
            
            /* Responsive */
            @media (max-width: 768px) {
                .product-specs-nav {
                    top: 60px;
                }
                
                .product-specs-nav ul {
                    overflow-x: auto;
                }
                
                .product-spec-panel {
                    padding: 50px 20px;
                }
                
                .product-dimensions-panel .dimensions-content {
                    grid-template-columns: 1fr;
                    gap: 30px;
                }
                
                .product-specs-grid {
                    grid-template-columns: 1fr;
                }
                
                .spec-item:nth-child(odd),
                .spec-item:nth-child(even) {
                    padding-left: 0;
                    padding-right: 0;
                    border-right: none;
                }
            }
        ';
    }
    
    /**
     * Display product specs on frontend - West Elm style
     */
    public function display_product_specs() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $product_id = $product->get_id();
        
        $technical_description = get_post_meta($product_id, '_product_technical_description', true);
        $dimensions_text = get_post_meta($product_id, '_product_dimensions_text', true);
        $dimensions_image = get_post_meta($product_id, '_product_dimensions_image', true);
        $specs = get_post_meta($product_id, '_product_specs', true);
        
        // Check if any content exists
        $has_description = !empty($technical_description);
        $has_dimensions = !empty($dimensions_text) || !empty($dimensions_image);
        $has_specs = false;
        
        if (!empty($specs) && is_array($specs)) {
            foreach ($specs as $spec) {
                if (!empty($spec['label']) && !empty($spec['value'])) {
                    $has_specs = true;
                    break;
                }
            }
        }
        
        if (!$has_description && !$has_dimensions && !$has_specs) {
            return;
        }
        
        // Build navigation items
        $nav_items = array();
        if ($has_description) {
            $nav_items['description'] = __('Description', 'decor');
        }
        if ($has_dimensions) {
            $nav_items['dimensions'] = __('Dimensions', 'decor');
        }
        if ($has_specs) {
            $nav_items['specifications'] = __('Specifications', 'decor');
        }
        
        ?>
        <!-- Sticky Navigation -->
        <nav class="product-specs-nav">
            <ul>
                <?php $first = true; foreach ($nav_items as $id => $label) : ?>
                <li>
                    <a href="#spec-<?php echo esc_attr($id); ?>" class="<?php echo $first ? 'active' : ''; ?>" data-target="spec-<?php echo esc_attr($id); ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                </li>
                <?php $first = false; endforeach; ?>
            </ul>
        </nav>
        
        <div class="product-specs-section">
            <?php if ($has_description) : ?>
            <div id="spec-description" class="product-spec-panel product-description-panel">
                <h3><?php _e('Description', 'decor'); ?></h3>
                <div class="content">
                    <?php echo wp_kses_post($technical_description); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($has_dimensions) : ?>
            <div id="spec-dimensions" class="product-spec-panel product-dimensions-panel">
                <h3><?php _e('Dimensions', 'decor'); ?></h3>
                <div class="dimensions-content">
                    <?php if (!empty($dimensions_text)) : ?>
                    <div class="dimensions-text">
                        <?php echo nl2br(esc_html($dimensions_text)); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($dimensions_image)) : ?>
                    <div class="dimensions-image">
                        <?php echo wp_get_attachment_image($dimensions_image, 'large'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($has_specs) : ?>
            <div id="spec-specifications" class="product-spec-panel product-specifications-panel">
                <h3><?php _e('Specifications', 'decor'); ?></h3>
                <div class="product-specs-grid">
                    <?php foreach ($specs as $spec) : ?>
                        <?php if (!empty($spec['label']) && !empty($spec['value'])) : ?>
                        <div class="spec-item">
                            <span class="spec-label"><?php echo esc_html($spec['label']); ?></span>
                            <span class="spec-value"><?php echo esc_html($spec['value']); ?></span>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Smooth scroll to section
            $('.product-specs-nav a').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                var navHeight = $('.product-specs-nav').outerHeight();
                
                $('html, body').animate({
                    scrollTop: $(target).offset().top - navHeight - 20
                }, 500);
                
                // Update active state
                $('.product-specs-nav a').removeClass('active');
                $(this).addClass('active');
            });
            
            // Update active nav on scroll
            $(window).on('scroll', function() {
                var scrollPos = $(window).scrollTop();
                var navHeight = $('.product-specs-nav').outerHeight();
                
                $('.product-spec-panel').each(function() {
                    var top = $(this).offset().top - navHeight - 100;
                    var bottom = top + $(this).outerHeight();
                    
                    if (scrollPos >= top && scrollPos < bottom) {
                        var id = $(this).attr('id');
                        $('.product-specs-nav a').removeClass('active');
                        $('.product-specs-nav a[data-target="' + id + '"]').addClass('active');
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// Initialize
Decor_Product_Specs::get_instance();
