<?php
/**
 * Fabric Variations System
 * Advanced variation system with fabric categories and custom fields
 * Inspired by West Elm's product configurator
 * 
 * Fabric Category and Color Group are managed via WooCommerce Product Attributes:
 * - pa_fabric_type (Fabric Type attribute)
 * - pa_color_group (Color Group attribute)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Decor_Fabric_Variations {
    
    private static $instance = null;
    
    // Version for cache busting - update this on every change
    const VERSION = '3.3.0';
    
    // Attribute slugs - these should match your WooCommerce attributes
    const FABRIC_TYPE_ATTRIBUTE = 'pa_fabric_type';
    const COLOR_GROUP_ATTRIBUTE = 'pa_color_group';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Admin hooks
        add_action('woocommerce_product_after_variable_attributes', array($this, 'add_variation_custom_fields'), 10, 3);
        add_action('woocommerce_save_product_variation', array($this, 'save_variation_custom_fields'), 10, 2);
        
        // Frontend hooks
        add_filter('woocommerce_available_variation', array($this, 'add_variation_data'), 10, 3);
        add_action('woocommerce_before_variations_form', array($this, 'render_fabric_selector'));
        
        // Also render for simple products with attributes
        add_action('woocommerce_before_add_to_cart_button', array($this, 'render_simple_product_selector'));
        // Removed - summary is now inline inside the fabric selector
        // add_action('woocommerce_after_single_product_summary', array($this, 'render_selection_summary'), 5);
        
        // AJAX handlers
        add_action('wp_ajax_get_fabric_variations', array($this, 'ajax_get_fabric_variations'));
        add_action('wp_ajax_nopriv_get_fabric_variations', array($this, 'ajax_get_fabric_variations'));
        
        // Cart hooks - add fabric details to cart
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_fabric_data_to_cart'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_fabric_data_in_cart'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_fabric_data_to_order'), 10, 4);
        
        // Validate required attributes before add to cart
        // DISABLED - using JavaScript popup instead for better UX
        // add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_required_attributes'), 10, 5);
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Register attributes on init
        add_action('init', array($this, 'register_attributes'));
        
        // Add admin menu for settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Register WooCommerce attributes if they don't exist
     * DISABLED - attributes should be created manually in WooCommerce
     */
    public function register_attributes() {
        // Disabled - this was causing duplicate attributes
        return;
    }
    
    /**
     * Add admin menu for fabric settings
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Fabric Variations Settings',
            'Fabric Settings',
            'manage_woocommerce',
            'decor-fabric-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('decor_fabric_settings', 'decor_fabric_type_attribute');
        register_setting('decor_fabric_settings', 'decor_color_group_attribute');
        register_setting('decor_fabric_settings', 'decor_enable_swatches');
        register_setting('decor_fabric_settings', 'decor_enable_order_swatches');
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        $attributes = wc_get_attribute_taxonomies();
        $fabric_type_attr = get_option('decor_fabric_type_attribute', 'pa_fabric_type');
        $color_group_attr = get_option('decor_color_group_attribute', 'pa_color_group');
        $enable_swatches = get_option('decor_enable_swatches', '1');
        $enable_order_swatches = get_option('decor_enable_order_swatches', '1');
        ?>
        <div class="wrap">
            <h1>Fabric Variations Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('decor_fabric_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Fabric Type Attribute</th>
                        <td>
                            <select name="decor_fabric_type_attribute">
                                <option value="">-- Select Attribute --</option>
                                <?php foreach ($attributes as $attr) : ?>
                                    <option value="pa_<?php echo esc_attr($attr->attribute_name); ?>" 
                                            <?php selected($fabric_type_attr, 'pa_' . $attr->attribute_name); ?>>
                                        <?php echo esc_html($attr->attribute_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Select the attribute used for fabric types (e.g., Linen, Velvet, Leather)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Color Group Attribute</th>
                        <td>
                            <select name="decor_color_group_attribute">
                                <option value="">-- Select Attribute --</option>
                                <?php foreach ($attributes as $attr) : ?>
                                    <option value="pa_<?php echo esc_attr($attr->attribute_name); ?>" 
                                            <?php selected($color_group_attr, 'pa_' . $attr->attribute_name); ?>>
                                        <?php echo esc_html($attr->attribute_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Select the attribute used for color groups (e.g., White, Gray, Blue)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Swatch Display</th>
                        <td>
                            <label>
                                <input type="checkbox" name="decor_enable_swatches" value="1" <?php checked($enable_swatches, '1'); ?>>
                                Show fabric swatches on product page
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Order Swatches Button</th>
                        <td>
                            <label>
                                <input type="checkbox" name="decor_enable_order_swatches" value="1" <?php checked($enable_order_swatches, '1'); ?>>
                                Show "Order Free Swatches" button
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            <h2>Manage Fabric Types</h2>
            <?php 
            $fabric_type_attr = get_option('decor_fabric_type_attribute', 'pa_fabric_type');
            $color_group_attr = get_option('decor_color_group_attribute', 'pa_color_group');
            
            if ($fabric_type_attr) : 
                $fabric_taxonomy = str_replace('pa_', '', $fabric_type_attr);
            ?>
                <p>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=' . $fabric_type_attr . '&post_type=product'); ?>" class="button button-primary">
                        Manage Fabric Types
                    </a>
                    <span class="description" style="margin-left: 10px;">Add, edit, or remove fabric types (e.g., Linen, Velvet, Leather)</span>
                </p>
                
                <h3>Current Fabric Types:</h3>
                <table class="widefat" style="max-width: 600px;">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $fabric_terms = get_terms(array(
                            'taxonomy' => $fabric_type_attr,
                            'hide_empty' => false,
                        ));
                        if (!is_wp_error($fabric_terms) && !empty($fabric_terms)) :
                            foreach ($fabric_terms as $term) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html($term->name); ?></strong></td>
                                    <td><code><?php echo esc_html($term->slug); ?></code></td>
                                    <td><?php echo esc_html($term->count); ?></td>
                                </tr>
                            <?php endforeach;
                        else : ?>
                            <tr>
                                <td colspan="3"><em>No fabric types defined yet. Click "Manage Fabric Types" to add some.</em></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="description" style="color: #d63638;">
                    <strong>Note:</strong> Please select a Fabric Type Attribute above first, then save settings.
                </p>
            <?php endif; ?>
            
            <?php if ($color_group_attr) : ?>
                <h2 style="margin-top: 30px;">Manage Color Groups</h2>
                <p>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=' . $color_group_attr . '&post_type=product'); ?>" class="button">
                        Manage Color Groups
                    </a>
                    <span class="description" style="margin-left: 10px;">Add, edit, or remove color groups for filtering</span>
                </p>
            <?php endif; ?>
            
            <hr>
            <h2>Setup Instructions</h2>
            <ol>
                <li>Go to <strong>Products → Attributes</strong> and create attributes named "Fabric Type" and "Color Group"</li>
                <li>Select those attributes in the settings above and save</li>
                <li>Click "Manage Fabric Types" to add fabric categories (e.g., Linen, Velvet, Basketweave)</li>
                <li>When creating variable products, use these attributes for variations</li>
                <li>The fabric type will be used to group swatches by category on the product page</li>
            </ol>
        </div>
        <?php
    }
    
    /**
     * Get the fabric type attribute slug
     */
    public function get_fabric_type_attribute() {
        return get_option('decor_fabric_type_attribute', self::FABRIC_TYPE_ATTRIBUTE);
    }
    
    /**
     * Get the color group attribute slug
     */
    public function get_color_group_attribute() {
        return get_option('decor_color_group_attribute', self::COLOR_GROUP_ATTRIBUTE);
    }
    
    /**
     * Add custom fields to variation admin
     */
    public function add_variation_custom_fields($loop, $variation_data, $variation) {
        $variation_id = $variation->ID;
        
        echo '<div class="decor-variation-fields" style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">';
        echo '<h4 style="margin-bottom: 10px;">Advanced Fabric Details</h4>';
        
        // Content (material composition)
        woocommerce_wp_text_input(array(
            'id' => '_fabric_content[' . $loop . ']',
            'label' => 'Content (Material Composition)',
            'placeholder' => 'e.g., 78% polyester, 22% linen',
            'value' => get_post_meta($variation_id, '_fabric_content', true),
            'wrapper_class' => 'form-row form-row-first',
        ));
        
        // Rub Count
        woocommerce_wp_text_input(array(
            'id' => '_rub_count[' . $loop . ']',
            'label' => 'Rub Count',
            'placeholder' => 'e.g., 40,000 rubs',
            'value' => get_post_meta($variation_id, '_rub_count', true),
            'wrapper_class' => 'form-row form-row-last',
        ));
        
        // Price Tier
        woocommerce_wp_select(array(
            'id' => '_price_tier[' . $loop . ']',
            'label' => 'Price Tier',
            'value' => get_post_meta($variation_id, '_price_tier', true),
            'options' => array(
                '' => '-- Select Price Tier --',
                'A' => 'A - Basic',
                'B' => 'B - Standard',
                'C' => 'C - Premium',
                'D' => 'D - Luxury',
                'E' => 'E - Exclusive',
            ),
            'wrapper_class' => 'form-row form-row-first',
        ));
        
        // Care Instructions
        woocommerce_wp_textarea_input(array(
            'id' => '_care_instructions[' . $loop . ']',
            'label' => 'Care Instructions',
            'placeholder' => 'e.g., Spot clean. Dry clean safe.',
            'value' => get_post_meta($variation_id, '_care_instructions', true),
            'wrapper_class' => 'form-row form-row-last',
        ));
        
        // Fabric Description
        woocommerce_wp_textarea_input(array(
            'id' => '_fabric_description[' . $loop . ']',
            'label' => 'Fabric Description',
            'placeholder' => 'Short description of the fabric and its characteristics',
            'value' => get_post_meta($variation_id, '_fabric_description', true),
            'wrapper_class' => 'form-row form-row-full',
        ));
        
        echo '</div>';
    }
    
    /**
     * Save variation custom fields
     */
    public function save_variation_custom_fields($variation_id, $loop) {
        $fields = array(
            '_fabric_content',
            '_rub_count',
            '_price_tier',
            '_care_instructions',
            '_fabric_description',
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field][$loop])) {
                update_post_meta($variation_id, $field, sanitize_text_field($_POST[$field][$loop]));
            }
        }
    }
    
    /**
     * Add custom data to variation response
     */
    public function add_variation_data($variation_data, $product, $variation) {
        $variation_id = $variation->get_id();
        
        // Get fabric type from attribute
        $fabric_type_attr = $this->get_fabric_type_attribute();
        $color_group_attr = $this->get_color_group_attribute();
        
        // Get attribute values from variation
        $fabric_type = '';
        $color_group = '';
        
        if ($fabric_type_attr && isset($variation_data['attributes']['attribute_' . $fabric_type_attr])) {
            $fabric_type = $variation_data['attributes']['attribute_' . $fabric_type_attr];
        }
        
        if ($color_group_attr && isset($variation_data['attributes']['attribute_' . $color_group_attr])) {
            $color_group = $variation_data['attributes']['attribute_' . $color_group_attr];
        }
        
        $variation_data['fabric_type'] = $fabric_type;
        $variation_data['color_group'] = $color_group;
        $variation_data['fabric_content'] = get_post_meta($variation_id, '_fabric_content', true);
        $variation_data['rub_count'] = get_post_meta($variation_id, '_rub_count', true);
        $variation_data['price_tier'] = get_post_meta($variation_id, '_price_tier', true);
        $variation_data['care_instructions'] = get_post_meta($variation_id, '_care_instructions', true);
        $variation_data['fabric_description'] = get_post_meta($variation_id, '_fabric_description', true);
        
        // Use variation image as swatch
        $variation_data['swatch_image'] = $variation_data['image']['thumb_src'] ?? '';
        
        return $variation_data;
    }
    
    /**
     * Get fabric type terms from WooCommerce attribute
     */
    public function get_fabric_types() {
        $fabric_type_attr = $this->get_fabric_type_attribute();
        if (!$fabric_type_attr) {
            return array();
        }
        
        $terms = get_terms(array(
            'taxonomy' => $fabric_type_attr,
            'hide_empty' => false,
        ));
        
        if (is_wp_error($terms)) {
            return array();
        }
        
        return $terms;
    }
    
    /**
     * Get color group terms from WooCommerce attribute
     */
    public function get_color_groups() {
        $color_group_attr = $this->get_color_group_attribute();
        if (!$color_group_attr) {
            return array();
        }
        
        $terms = get_terms(array(
            'taxonomy' => $color_group_attr,
            'hide_empty' => false,
        ));
        
        if (is_wp_error($terms)) {
            return array();
        }
        
        return $terms;
    }
    
    /**
     * Render fabric selector on product page
     */
    public function render_fabric_selector() {
        global $product;
        
        if (!$product || !$product->is_type('variable')) {
            return;
        }
        
        // Check if swatches are enabled
        if (!get_option('decor_enable_swatches', '1')) {
            return;
        }
        
        $variations = $product->get_available_variations();
        if (empty($variations)) {
            return;
        }
        
        // Get all product attributes
        $product_attributes = $product->get_variation_attributes();
        
        // Build attribute data with terms
        $attributes_data = array();
        foreach ($product_attributes as $attribute_name => $options) {
            // Get taxonomy name
            $taxonomy = strpos($attribute_name, 'pa_') === 0 ? $attribute_name : 'pa_' . sanitize_title($attribute_name);
            // Use frontend name if set, otherwise fall back to default label
            $attribute_label = Decor_Attribute_Pricing::get_attribute_frontend_name($attribute_name);
            
            $terms_data = array();
            foreach ($options as $option) {
                $term = get_term_by('slug', $option, $taxonomy);
                $terms_data[$option] = array(
                    'slug' => $option,
                    'name' => $term ? $term->name : ucfirst($option),
                );
            }
            
            $attributes_data[$attribute_name] = array(
                'name' => $attribute_name,
                'label' => $attribute_label,
                'taxonomy' => $taxonomy,
                'options' => $terms_data,
            );
        }
        
        // Get attribute settings (for backward compatibility)
        $fabric_type_attr = $this->get_fabric_type_attribute();
        $color_group_attr = $this->get_color_group_attribute();
        
        // Group variations by fabric type (for backward compatibility)
        $grouped_variations = array();
        $all_colors = array();
        $all_fabrics = array();
        
        foreach ($variations as $variation) {
            // Get fabric type from variation attributes
            $fabric_type = '';
            $color_group = '';
            
            if ($fabric_type_attr && isset($variation['attributes']['attribute_' . $fabric_type_attr])) {
                $fabric_type = $variation['attributes']['attribute_' . $fabric_type_attr];
            }
            
            if ($color_group_attr && isset($variation['attributes']['attribute_' . $color_group_attr])) {
                $color_group = $variation['attributes']['attribute_' . $color_group_attr];
            }
            
            $category = $fabric_type ?: 'other';
            
            if (!isset($grouped_variations[$category])) {
                $grouped_variations[$category] = array();
            }
            
            // Add fabric_type and color_group to variation data
            $variation['fabric_type'] = $fabric_type;
            $variation['color_group'] = $color_group;
            $grouped_variations[$category][] = $variation;
            
            if ($color_group && $color_group !== 'other') {
                $all_colors[$color_group] = true;
            }
            if ($fabric_type && $fabric_type !== 'other') {
                $all_fabrics[$fabric_type] = true;
            }
        }
        
        $total_choices = count($variations);
        $fabric_types = $this->get_fabric_types();
        $color_groups = $this->get_color_groups();
        $enable_order_swatches = get_option('decor_enable_order_swatches', '1');
        
        include(get_stylesheet_directory() . '/templates/fabric-selector.php');
    }
    
    /**
     * Render selection summary
     */
    public function render_selection_summary() {
        global $product;
        
        if (!$product || !$product->is_type('variable')) {
            return;
        }
        
        include(get_stylesheet_directory() . '/templates/selection-summary.php');
    }
    
    /**
     * Render attribute selector for Simple products (no variations needed)
     * Uses attribute pricing instead of variations
     */
    public function render_simple_product_selector() {
        global $product;
        
        // Only for simple products with attributes
        if (!$product || $product->is_type('variable')) {
            return;
        }
        
        // Check if product has attributes
        $attributes = $product->get_attributes();
        if (empty($attributes)) {
            return;
        }
        
        // Check if swatches are enabled
        if (!get_option('decor_enable_swatches', '1')) {
            return;
        }
        
        // Build attribute data with terms and prices
        $attributes_data = array();
        $has_priced_attributes = false;
        
        foreach ($attributes as $attribute) {
            if (!$attribute->get_visible()) {
                continue;
            }
            
            $name = $attribute->get_name();
            // For taxonomy attributes use the taxonomy name, for custom use the attribute name
            $taxonomy = $attribute->is_taxonomy() ? $name : $name;
            // Use frontend name if set, otherwise fall back to default label
            $label = Decor_Attribute_Pricing::get_attribute_frontend_name($name);
            
            $terms_data = array();
            
            if ($attribute->is_taxonomy()) {
                $terms = wc_get_product_terms($product->get_id(), $name, array('fields' => 'all'));
                
                foreach ($terms as $term) {
                    // Get price modifier
                    $price = floatval(get_term_meta($term->term_id, 'attribute_price', true));
                    $price_type = get_term_meta($term->term_id, 'attribute_price_type', true) ?: 'fixed';
                    
                    // Get swatch image
                    $image_meta = get_term_meta($term->term_id, 'image', true);
                    $swatch_image = '';
                    
                    if ($image_meta) {
                        if (is_string($image_meta) && strpos($image_meta, 'a:') === 0) {
                            $image_data = @unserialize($image_meta);
                            if ($image_data && isset($image_data['url'])) {
                                $swatch_image = $image_data['url'];
                            }
                        } elseif (is_array($image_meta) && isset($image_meta['url'])) {
                            $swatch_image = $image_meta['url'];
                        } elseif (is_numeric($image_meta)) {
                            $swatch_image = wp_get_attachment_image_url($image_meta, 'thumbnail');
                        }
                    }
                    
                    if ($price > 0) {
                        $has_priced_attributes = true;
                    }
                    
                    // Get fabric details
                    $fabric_content = get_term_meta($term->term_id, 'fabric_content', true);
                    $fabric_rub_count = get_term_meta($term->term_id, 'fabric_rub_count', true);
                    $fabric_care = get_term_meta($term->term_id, 'fabric_care', true);
                    $fabric_description = get_term_meta($term->term_id, 'fabric_description', true);
                    $price_tier_label = get_term_meta($term->term_id, 'price_tier_label', true);
                    
                    $terms_data[$term->slug] = array(
                        'slug' => $term->slug,
                        'name' => $term->name,
                        'price' => $price,
                        'price_type' => $price_type,
                        'image' => $swatch_image,
                        'content' => $fabric_content,
                        'rub_count' => $fabric_rub_count,
                        'care' => $fabric_care,
                        'description' => $fabric_description,
                        'price_tier_label' => $price_tier_label,
                    );
                }
            } else {
                // Custom attribute (not taxonomy)
                $options = $attribute->get_options();
                foreach ($options as $option) {
                    $terms_data[sanitize_title($option)] = array(
                        'slug' => sanitize_title($option),
                        'name' => $option,
                        'price' => 0,
                        'price_type' => 'fixed',
                        'image' => '',
                        'content' => '',
                        'rub_count' => '',
                        'care' => '',
                        'description' => '',
                    );
                }
            }
            
            if (!empty($terms_data)) {
                // Get attribute group for exclusive selection logic
                $group = '';
                if (!empty($taxonomy)) {
                    $group = Decor_Attribute_Pricing::get_attribute_group($taxonomy);
                }
                
                $attributes_data[$name] = array(
                    'name' => $name,
                    'label' => $label,
                    'taxonomy' => $taxonomy,
                    'options' => $terms_data,
                    'group' => $group,
                );
            }
        }
        
        if (empty($attributes_data)) {
            return;
        }
        
        // Sort attributes - SIZE should always be first
        uksort($attributes_data, function($a, $b) {
            $a_is_size = (stripos($a, 'size') !== false || $a === 'pa_size');
            $b_is_size = (stripos($b, 'size') !== false || $b === 'pa_size');
            
            if ($a_is_size && !$b_is_size) return -1;
            if (!$a_is_size && $b_is_size) return 1;
            return 0;
        });
        
        // Include the simple product selector template
        include(get_stylesheet_directory() . '/templates/simple-product-selector.php');
    }
    
    /**
     * AJAX handler for getting fabric variations
     */
    public function ajax_get_fabric_variations() {
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $color_filter = isset($_POST['color']) ? sanitize_text_field($_POST['color']) : '';
        $fabric_filter = isset($_POST['fabric']) ? sanitize_text_field($_POST['fabric']) : '';
        
        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
        }
        
        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('variable')) {
            wp_send_json_error('Invalid product');
        }
        
        $variations = $product->get_available_variations();
        $filtered = array();
        
        foreach ($variations as $variation) {
            $variation = $this->add_variation_data($variation, $product, wc_get_product($variation['variation_id']));
            
            // Apply filters
            if ($color_filter && $variation['color_group'] !== $color_filter) {
                continue;
            }
            if ($fabric_filter && $variation['fabric_type'] !== $fabric_filter) {
                continue;
            }
            
            $filtered[] = $variation;
        }
        
        wp_send_json_success($filtered);
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_product()) {
            return;
        }
        
        wp_enqueue_style(
            'decor-fabric-variations',
            get_stylesheet_directory_uri() . '/assets/css/fabric-variations.css',
            array(),
            self::VERSION
        );
        
        wp_enqueue_script(
            'decor-fabric-variations',
            get_stylesheet_directory_uri() . '/assets/js/fabric-variations.js',
            array('jquery'),
            self::VERSION,
            true
        );
        
        wp_localize_script('decor-fabric-variations', 'decorFabric', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('decor_fabric_nonce'),
            'i18n' => array(
                'choices' => 'Choices',
                'content' => 'Content',
                'rubCount' => 'Rub Count',
                'priceTier' => 'Price Tier',
                'care' => 'Care',
                'selectFabric' => 'Select Fabric and Color',
                'yourSelection' => 'Your Selection Summary',
                'makeSelection' => 'Make Selection',
            ),
        ));
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
        
        wp_enqueue_script(
            'decor-admin-fabric-variations',
            get_stylesheet_directory_uri() . '/assets/js/admin-fabric-variations.js',
            array('jquery', 'wp-media'),
            self::VERSION,
            true
        );
        
        wp_enqueue_style(
            'decor-admin-fabric-variations',
            get_stylesheet_directory_uri() . '/assets/css/admin-fabric-variations.css',
            array(),
            self::VERSION
        );
    }
    
    /**
     * Add fabric data to cart item
     */
    public function add_fabric_data_to_cart($cart_item_data, $product_id, $variation_id) {
        if ($variation_id) {
            $fabric_content = get_post_meta($variation_id, '_fabric_content', true);
            $rub_count = get_post_meta($variation_id, '_rub_count', true);
            $price_tier = get_post_meta($variation_id, '_price_tier', true);
            $care_instructions = get_post_meta($variation_id, '_care_instructions', true);
            $fabric_description = get_post_meta($variation_id, '_fabric_description', true);
            
            if ($fabric_content) {
                $cart_item_data['fabric_content'] = $fabric_content;
            }
            if ($rub_count) {
                $cart_item_data['fabric_rub_count'] = $rub_count;
            }
            if ($price_tier) {
                $cart_item_data['fabric_price_tier'] = $price_tier;
            }
            if ($care_instructions) {
                $cart_item_data['fabric_care'] = $care_instructions;
            }
            if ($fabric_description) {
                $cart_item_data['fabric_description'] = $fabric_description;
            }
        }
        
        return $cart_item_data;
    }
    
    /**
     * Display fabric data in cart
     */
    public function display_fabric_data_in_cart($item_data, $cart_item) {
        if (isset($cart_item['fabric_content']) && !empty($cart_item['fabric_content'])) {
            $item_data[] = array(
                'key' => 'Content',
                'value' => $cart_item['fabric_content'],
            );
        }
        
        if (isset($cart_item['fabric_rub_count']) && !empty($cart_item['fabric_rub_count'])) {
            $item_data[] = array(
                'key' => 'Rub Count',
                'value' => $cart_item['fabric_rub_count'],
            );
        }
        
        if (isset($cart_item['fabric_price_tier']) && !empty($cart_item['fabric_price_tier'])) {
            $item_data[] = array(
                'key' => 'Price Tier',
                'value' => $cart_item['fabric_price_tier'],
            );
        }
        
        if (isset($cart_item['fabric_care']) && !empty($cart_item['fabric_care'])) {
            $item_data[] = array(
                'key' => 'Care',
                'value' => $cart_item['fabric_care'],
            );
        }
        
        return $item_data;
    }
    
    /**
     * Add fabric data to order item
     */
    public function add_fabric_data_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['fabric_content']) && !empty($values['fabric_content'])) {
            $item->add_meta_data('Content', $values['fabric_content'], true);
        }
        
        if (isset($values['fabric_rub_count']) && !empty($values['fabric_rub_count'])) {
            $item->add_meta_data('Rub Count', $values['fabric_rub_count'], true);
        }
        
        if (isset($values['fabric_price_tier']) && !empty($values['fabric_price_tier'])) {
            $item->add_meta_data('Price Tier', $values['fabric_price_tier'], true);
        }
        
        if (isset($values['fabric_care']) && !empty($values['fabric_care'])) {
            $item->add_meta_data('Care', $values['fabric_care'], true);
        }
    }
    
    /**
     * Validate required attributes before adding to cart
     * Prevents adding to cart without selecting all required attributes
     */
    public function validate_required_attributes($passed, $product_id, $quantity, $variation_id = 0, $variations = array()) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return $passed;
        }
        
        $attributes = $product->get_attributes();
        
        if (empty($attributes)) {
            return $passed;
        }
        
        $missing_attributes = array();
        
        foreach ($attributes as $attribute) {
            // Skip non-visible or non-variation attributes for variable products
            if (!$attribute->get_visible()) {
                continue;
            }
            
            $attr_name = $attribute->get_name();
            $attr_label = wc_attribute_label($attr_name);
            
            // Check if this attribute is required (all visible attributes are required)
            $attr_key = 'attribute_' . sanitize_title($attr_name);
            
            // For variable products, check variations array
            if ($product->is_type('variable')) {
                if (!isset($variations[$attr_key]) || empty($variations[$attr_key])) {
                    // Also check POST data
                    if (!isset($_POST[$attr_key]) || empty($_POST[$attr_key])) {
                        $missing_attributes[] = $attr_label;
                    }
                }
            } else {
                // For simple products with attributes, check POST data
                if (!isset($_POST[$attr_key]) || empty($_POST[$attr_key])) {
                    // Also check if attribute value was passed via request
                    if (!isset($_REQUEST[$attr_key]) || empty($_REQUEST[$attr_key])) {
                        $missing_attributes[] = $attr_label;
                    }
                }
            }
        }
        
        if (!empty($missing_attributes)) {
            $message = sprintf(
                __('Please select %s before adding to cart.', 'decor'),
                implode(', ', $missing_attributes)
            );
            wc_add_notice($message, 'error');
            return false;
        }
        
        return $passed;
    }
}

// Initialize
Decor_Fabric_Variations::get_instance();
