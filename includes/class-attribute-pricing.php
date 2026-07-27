<?php
/**
 * Attribute Pricing System
 * Adds price fields to attribute terms and calculates dynamic pricing
 * 
 * @package Decor_Attribute_Pricing
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Decor_Attribute_Pricing {
    
    private static $instance = null;
    
    const VERSION = '2.8.5';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Add price field to attribute term forms
        add_action('init', array($this, 'register_term_meta'));
        
        // Add fields to all product attribute taxonomies
        add_action('admin_init', array($this, 'add_attribute_term_fields'));
        
        // Add price field to attribute edit page
        add_action('woocommerce_after_edit_attribute_fields', array($this, 'add_attribute_price_field'));
        add_action('woocommerce_attribute_updated', array($this, 'save_attribute_price'), 10, 3);
        add_action('woocommerce_attribute_added', array($this, 'save_attribute_price'), 10, 2);
        
        // AJAX handler for price calculation
        add_action('wp_ajax_calculate_attribute_price', array($this, 'ajax_calculate_price'));
        add_action('wp_ajax_nopriv_calculate_attribute_price', array($this, 'ajax_calculate_price'));
        
        // Enqueue frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Modify add to cart to include attribute selections
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'get_cart_item_from_session'), 10, 2);
        add_filter('woocommerce_cart_item_name', array($this, 'cart_item_name'), 10, 3);
        add_action('woocommerce_before_calculate_totals', array($this, 'calculate_cart_totals'), 10, 1);
        
        // Add attribute data to order
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_order_item_meta'), 10, 4);
        
        // Add product-level attribute pricing panel
        add_action('woocommerce_product_data_panels', array($this, 'add_product_attribute_pricing_panel'));
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_attribute_pricing_tab'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_attribute_pricing'));
    }
    
    /**
     * Register term meta for price and fabric details
     */
    public function register_term_meta() {
        register_meta('term', 'attribute_price', array(
            'type' => 'number',
            'description' => 'Price modifier for this attribute option',
            'single' => true,
            'show_in_rest' => true,
        ));
        
        register_meta('term', 'attribute_price_type', array(
            'type' => 'string',
            'description' => 'Price type: fixed or percentage',
            'single' => true,
            'show_in_rest' => true,
            'default' => 'fixed',
        ));
        
        // Fabric detail fields
        register_meta('term', 'fabric_content', array(
            'type' => 'string',
            'description' => 'Material composition (e.g., 78% polyester, 22% linen)',
            'single' => true,
            'show_in_rest' => true,
        ));
        
        register_meta('term', 'fabric_rub_count', array(
            'type' => 'string',
            'description' => 'Rub count (e.g., 40,000 rubs)',
            'single' => true,
            'show_in_rest' => true,
        ));
        
        register_meta('term', 'fabric_care', array(
            'type' => 'string',
            'description' => 'Care instructions',
            'single' => true,
            'show_in_rest' => true,
        ));
        
        register_meta('term', 'fabric_description', array(
            'type' => 'string',
            'description' => 'Short description of the fabric',
            'single' => true,
            'show_in_rest' => true,
        ));
        
        // Price Tier label (A, B, C, etc.)
        register_meta('term', 'price_tier_label', array(
            'type' => 'string',
            'description' => 'Price Tier label',
            'single' => true,
            'show_in_rest' => true,
        ));
    }
    
    /**
     * Add price fields to attribute term forms
     */
    public function add_attribute_term_fields() {
        // Get all product attribute taxonomies
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        
        foreach ($attribute_taxonomies as $attribute) {
            $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
            
            // Add field to "Add new term" form
            add_action("{$taxonomy}_add_form_fields", array($this, 'add_term_price_field'));
            
            // Add field to "Edit term" form
            add_action("{$taxonomy}_edit_form_fields", array($this, 'edit_term_price_field'), 10, 2);
            
            // Save the field
            add_action("created_{$taxonomy}", array($this, 'save_term_price_field'));
            add_action("edited_{$taxonomy}", array($this, 'save_term_price_field'));
            
            // Add column to term list
            add_filter("manage_edit-{$taxonomy}_columns", array($this, 'add_price_column'));
            add_filter("manage_{$taxonomy}_custom_column", array($this, 'render_price_column'), 10, 3);
        }
    }
    
    /**
     * Add price field to "Add new term" form
     */
    public function add_term_price_field($taxonomy) {
        ?>
        <h3 style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;"><?php _e('Fabric Details', 'decor'); ?></h3>
        
        <div class="form-field">
            <label for="fabric_content"><?php _e('Content (Material Composition)', 'decor'); ?></label>
            <input type="text" name="fabric_content" id="fabric_content" placeholder="e.g., 78% polyester, 22% linen">
        </div>
        <div class="form-field">
            <label for="fabric_rub_count"><?php _e('Rub Count', 'decor'); ?></label>
            <input type="text" name="fabric_rub_count" id="fabric_rub_count" placeholder="e.g., 40,000 rubs">
        </div>
        <div class="form-field">
            <label for="fabric_care"><?php _e('Care Instructions', 'decor'); ?></label>
            <textarea name="fabric_care" id="fabric_care" rows="2" placeholder="e.g., Spot clean. Dry clean safe."></textarea>
        </div>
        <div class="form-field">
            <label for="fabric_description"><?php _e('Fabric Description', 'decor'); ?></label>
            <textarea name="fabric_description" id="fabric_description" rows="3" placeholder="Short description of the fabric and its characteristics"></textarea>
        </div>
        <div class="form-field">
            <label for="price_tier_label"><?php _e('Price Tier Label', 'decor'); ?></label>
            <input type="text" name="price_tier_label" id="price_tier_label" placeholder="e.g., A, B, C, Grade 1">
            <p class="description"><?php _e('Custom label for price tier (e.g., A, B, C, Grade 1)', 'decor'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Add price field to "Edit term" form
     */
    public function edit_term_price_field($term, $taxonomy) {
        $fabric_content = get_term_meta($term->term_id, 'fabric_content', true);
        $fabric_rub_count = get_term_meta($term->term_id, 'fabric_rub_count', true);
        $fabric_care = get_term_meta($term->term_id, 'fabric_care', true);
        $fabric_description = get_term_meta($term->term_id, 'fabric_description', true);
        ?>
        <tr>
            <th colspan="2"><h3 style="margin: 20px 0 10px; padding-top: 20px; border-top: 1px solid #ddd;"><?php _e('Fabric Details', 'decor'); ?></h3></th>
        </tr>
        
        <tr class="form-field">
            <th scope="row"><label for="fabric_content"><?php _e('Content (Material Composition)', 'decor'); ?></label></th>
            <td>
                <input type="text" name="fabric_content" id="fabric_content" value="<?php echo esc_attr($fabric_content); ?>" placeholder="e.g., 78% polyester, 22% linen">
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="fabric_rub_count"><?php _e('Rub Count', 'decor'); ?></label></th>
            <td>
                <input type="text" name="fabric_rub_count" id="fabric_rub_count" value="<?php echo esc_attr($fabric_rub_count); ?>" placeholder="e.g., 40,000 rubs">
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="fabric_care"><?php _e('Care Instructions', 'decor'); ?></label></th>
            <td>
                <textarea name="fabric_care" id="fabric_care" rows="2" placeholder="e.g., Spot clean. Dry clean safe."><?php echo esc_textarea($fabric_care); ?></textarea>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="fabric_description"><?php _e('Fabric Description', 'decor'); ?></label></th>
            <td>
                <textarea name="fabric_description" id="fabric_description" rows="3" placeholder="Short description of the fabric"><?php echo esc_textarea($fabric_description); ?></textarea>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="price_tier_label"><?php _e('Price Tier Label', 'decor'); ?></label></th>
            <td>
                <input type="text" name="price_tier_label" id="price_tier_label" value="<?php echo esc_attr(get_term_meta($term->term_id, 'price_tier_label', true)); ?>" placeholder="e.g., A, B, C, Grade 1">
                <p class="description"><?php _e('Custom label for price tier (e.g., A, B, C, Grade 1)', 'decor'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save price field
     */
    public function save_term_price_field($term_id) {
        if (isset($_POST['attribute_price'])) {
            update_term_meta($term_id, 'attribute_price', floatval($_POST['attribute_price']));
        }
        if (isset($_POST['attribute_price_type'])) {
            update_term_meta($term_id, 'attribute_price_type', sanitize_text_field($_POST['attribute_price_type']));
        }
        
        // Save fabric details
        if (isset($_POST['fabric_content'])) {
            update_term_meta($term_id, 'fabric_content', sanitize_text_field($_POST['fabric_content']));
        }
        if (isset($_POST['fabric_rub_count'])) {
            update_term_meta($term_id, 'fabric_rub_count', sanitize_text_field($_POST['fabric_rub_count']));
        }
        if (isset($_POST['fabric_care'])) {
            update_term_meta($term_id, 'fabric_care', sanitize_textarea_field($_POST['fabric_care']));
        }
        if (isset($_POST['fabric_description'])) {
            update_term_meta($term_id, 'fabric_description', sanitize_textarea_field($_POST['fabric_description']));
        }
        if (isset($_POST['price_tier_label'])) {
            update_term_meta($term_id, 'price_tier_label', sanitize_text_field($_POST['price_tier_label']));
        }
    }
    
    /**
     * Add price field to attribute edit page (for all terms under this attribute)
     */
    public function add_attribute_price_field() {
        $attribute_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        $price = get_option('attribute_price_' . $attribute_id, '');
        $price_type = get_option('attribute_price_type_' . $attribute_id, 'fixed');
        $group = get_option('attribute_group_' . $attribute_id, '');
        $frontend_name = get_option('attribute_frontend_name_' . $attribute_id, '');
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="attribute_frontend_name"><?php _e('Frontend Name', 'decor'); ?></label>
            </th>
            <td>
                <input type="text" name="attribute_frontend_name" id="attribute_frontend_name" value="<?php echo esc_attr($frontend_name); ?>" style="width: 300px;" placeholder="<?php _e('Leave empty to use the Name above', 'decor'); ?>">
                <p class="description"><?php _e('Display name shown to customers on the frontend. The Name above will be used for admin/orders. Leave empty to use the Name for both.', 'decor'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="attribute_price"><?php _e('Price Modifier', 'decor'); ?></label>
            </th>
            <td>
                <input type="number" name="attribute_price" id="attribute_price" step="0.01" value="<?php echo esc_attr($price); ?>" style="width: 200px;">
                <select name="attribute_price_type" id="attribute_price_type" style="margin-left: 10px;">
                    <option value="fixed" <?php selected($price_type, 'fixed'); ?>><?php _e('Fixed Amount (+$)', 'decor'); ?></option>
                    <option value="percentage" <?php selected($price_type, 'percentage'); ?>><?php _e('Percentage (+%)', 'decor'); ?></option>
                </select>
                <p class="description"><?php _e('Price modifier for ALL options under this attribute. For example: if Grade 2 = +$50, all colors in Grade 2 will add $50 to the price.', 'decor'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="attribute_group"><?php _e('Exclusive Group', 'decor'); ?></label>
            </th>
            <td>
                <input type="text" name="attribute_group" id="attribute_group" value="<?php echo esc_attr($group); ?>" style="width: 200px;" placeholder="e.g., fabric">
                <p class="description"><?php _e('Attributes in the same group are exclusive (customer chooses only ONE). For example: all Fabric Grades should have group "fabric". Leave empty for independent attributes like Size.', 'decor'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save attribute price
     */
    public function save_attribute_price($attribute_id, $attribute = null, $old_attribute = null) {
        if (isset($_POST['attribute_frontend_name'])) {
            update_option('attribute_frontend_name_' . $attribute_id, sanitize_text_field($_POST['attribute_frontend_name']));
        }
        if (isset($_POST['attribute_price'])) {
            update_option('attribute_price_' . $attribute_id, floatval($_POST['attribute_price']));
        }
        if (isset($_POST['attribute_price_type'])) {
            update_option('attribute_price_type_' . $attribute_id, sanitize_text_field($_POST['attribute_price_type']));
        }
        if (isset($_POST['attribute_group'])) {
            update_option('attribute_group_' . $attribute_id, sanitize_text_field($_POST['attribute_group']));
        }
    }
    
    /**
     * Get attribute group by taxonomy name
     */
    public static function get_attribute_group($taxonomy) {
        $attribute_name = str_replace('pa_', '', $taxonomy);
        $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_name);
        
        if ($attribute_id) {
            return get_option('attribute_group_' . $attribute_id, '');
        }
        
        return '';
    }
    
    /**
     * Get attribute frontend name by taxonomy name
     * Returns frontend name if set, otherwise returns the default attribute label
     */
    public static function get_attribute_frontend_name($taxonomy) {
        $attribute_name = str_replace('pa_', '', $taxonomy);
        $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_name);
        
        if ($attribute_id) {
            $frontend_name = get_option('attribute_frontend_name_' . $attribute_id, '');
            if (!empty($frontend_name)) {
                return $frontend_name;
            }
        }
        
        // Fall back to default WooCommerce label
        return wc_attribute_label($taxonomy);
    }
    
    /**
     * Get attribute price by taxonomy name
     */
    public static function get_attribute_price($taxonomy) {
        // Get attribute ID from taxonomy name
        $attribute_name = str_replace('pa_', '', $taxonomy);
        $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_name);
        
        if ($attribute_id) {
            $price = get_option('attribute_price_' . $attribute_id, 0);
            $price_type = get_option('attribute_price_type_' . $attribute_id, 'fixed');
            return array(
                'price' => floatval($price),
                'type' => $price_type,
            );
        }
        
        return array('price' => 0, 'type' => 'fixed');
    }
    
    /**
     * Add price column to term list
     */
    public function add_price_column($columns) {
        $columns['attribute_price'] = __('Price Modifier', 'decor');
        return $columns;
    }
    
    /**
     * Render price column
     */
    public function render_price_column($content, $column_name, $term_id) {
        if ($column_name === 'attribute_price') {
            $price = get_term_meta($term_id, 'attribute_price', true);
            $price_type = get_term_meta($term_id, 'attribute_price_type', true) ?: 'fixed';
            
            if ($price) {
                if ($price_type === 'percentage') {
                    return '+' . $price . '%';
                } else {
                    return '+' . wc_price($price);
                }
            }
            return '-';
        }
        return $content;
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        if (!is_product()) {
            return;
        }
        
        wp_enqueue_script(
            'attribute-pricing',
            get_stylesheet_directory_uri() . '/assets/js/attribute-pricing.js',
            array('jquery'),
            self::VERSION,
            true
        );
        
        global $product;
        
        // Make sure we have a valid product object
        if (!$product || !is_object($product)) {
            $product = wc_get_product(get_the_ID());
        }
        
        // Still check if product is valid
        if (!$product || !is_object($product)) {
            return;
        }
        
        $base_price = $product->get_price();
        
        wp_localize_script('attribute-pricing', 'attributePricing', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('attribute_pricing_nonce'),
            'basePrice' => $base_price,
            'currencySymbol' => get_woocommerce_currency_symbol(),
            'priceFormat' => get_woocommerce_price_format(),
            'decimals' => wc_get_price_decimals(),
            'decimalSeparator' => wc_get_price_decimal_separator(),
            'thousandSeparator' => wc_get_price_thousand_separator(),
        ));
    }
    
    /**
     * AJAX: Calculate price based on selected attributes
     */
    public function ajax_calculate_price() {
        check_ajax_referer('attribute_pricing_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $selections = isset($_POST['selections']) ? $_POST['selections'] : array();
        
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error('Invalid product');
        }
        
        $base_price = floatval($product->get_price());
        
        // Check if product uses price matrix
        $matrix_price = self::get_matrix_price($product_id, $selections);
        
        // Debug logging (remove in production)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Matrix Price Check - Product: ' . $product_id);
            error_log('Selections: ' . print_r($selections, true));
            error_log('Matrix Price Result: ' . ($matrix_price !== false ? $matrix_price : 'false'));
        }
        
        if ($matrix_price !== false) {
            // Build breakdown for matrix pricing
            $breakdown = array();
            foreach ($selections as $attribute => $term_slug) {
                if (empty($term_slug)) continue;
                
                $taxonomy = strpos($attribute, 'pa_') === 0 ? $attribute : 'pa_' . $attribute;
                $term = get_term_by('slug', sanitize_title($term_slug), $taxonomy);
                $option_name = $term ? $term->name : $term_slug;
                
                $attr_label = self::get_attribute_frontend_name($taxonomy);
                if (!$attr_label || $attr_label === $taxonomy) {
                    $attr_label = ucfirst(str_replace(array('pa_', '-', '_'), array('', ' ', ' '), $attribute));
                }
                
                $breakdown[] = array(
                    'attribute' => $attr_label,
                    'option' => $option_name,
                    'modifier' => 0,
                    'type' => 'matrix',
                );
            }
            
            wp_send_json_success(array(
                'base_price' => $matrix_price,
                'modifier' => 0,
                'final_price' => $matrix_price,
                'formatted_price' => wc_price($matrix_price),
                'breakdown' => $breakdown,
                'pricing_mode' => 'matrix',
            ));
            return;
        }
        
        // Fallback to additive pricing
        $total_modifier = 0;
        $breakdown = array();
        
        foreach ($selections as $attribute => $term_slug) {
            if (empty($term_slug)) {
                continue;
            }
            
            // Sanitize term_slug to match how it's stored
            $term_slug = sanitize_title($term_slug);
            
            // Use the attribute name as pricing key (works for both taxonomy and custom)
            // Try both original and uppercase versions for custom attributes
            $pricing_key = $attribute;
            
            // Get product-level pricing (includes per-variation pricing)
            $price_data = self::get_product_attribute_price($product_id, $pricing_key, $term_slug);
            
            // If no price found, try uppercase attribute name (for custom attributes like SIZE)
            if (empty($price_data['price'])) {
                $price_data = self::get_product_attribute_price($product_id, strtoupper($attribute), $term_slug);
            }
            $price = floatval($price_data['price']);
            $price_type = $price_data['type'];
            
            // Get display name for breakdown - use Frontend Name if set
            $taxonomy = strpos($attribute, 'pa_') === 0 ? $attribute : 'pa_' . $attribute;
            $term = get_term_by('slug', $term_slug, $taxonomy);
            $option_name = $term ? $term->name : $term_slug;
            
            // Use frontend name if available, otherwise fall back to default label
            $attr_label = self::get_attribute_frontend_name($taxonomy);
            if (!$attr_label || $attr_label === $taxonomy) {
                $attr_label = ucfirst(str_replace(array('pa_', '-', '_'), array('', ' ', ' '), $attribute));
            }
            
            // Calculate modifier if price exists
            $modifier = 0;
            if ($price > 0) {
                if ($price_type === 'percentage') {
                    $modifier = $base_price * ($price / 100);
                } else {
                    $modifier = $price;
                }
                $total_modifier += $modifier;
            }
            
            // Always add to breakdown (even if no price)
            $breakdown[] = array(
                'attribute' => $attr_label,
                'option' => $option_name,
                'modifier' => $modifier,
                'type' => $price_type,
            );
        }
        
        $final_price = $base_price + $total_modifier;
        
        wp_send_json_success(array(
            'base_price' => $base_price,
            'modifier' => $total_modifier,
            'final_price' => $final_price,
            'formatted_price' => wc_price($final_price),
            'breakdown' => $breakdown,
        ));
    }
    
    /**
     * Add attribute selections to cart item data
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $product = wc_get_product($product_id);
        if (!$product) {
            return $cart_item_data;
        }
        
        $attributes = $product->get_attributes();
        $selections = array();
        $base_price = floatval($product->get_price());
        
        foreach ($attributes as $attribute) {
            $name = $attribute->get_name();
            $post_key = 'attribute_' . sanitize_title($name);
            
            if (isset($_POST[$post_key]) && !empty($_POST[$post_key])) {
                $value = sanitize_text_field($_POST[$post_key]);
                $selections[$name] = $value;
            }
        }
        
        if (!empty($selections)) {
            $cart_item_data['attribute_selections'] = $selections;
            
            // Check if product uses price matrix
            $matrix_price = self::get_matrix_price($product_id, $selections);
            if ($matrix_price !== false) {
                // Use matrix price (absolute price, not modifier)
                $cart_item_data['attribute_price_modifier'] = $matrix_price - $base_price;
                $cart_item_data['use_matrix_price'] = true;
                $cart_item_data['matrix_final_price'] = $matrix_price;
            } else {
                // Use additive pricing
                $price_modifier = 0;
                foreach ($selections as $name => $value) {
                    $price_data = self::get_product_attribute_price($product_id, $name, $value);
                    $price = floatval($price_data['price']);
                    $price_type = $price_data['type'];
                    
                    if ($price > 0) {
                        if ($price_type === 'percentage') {
                            $price_modifier += $base_price * ($price / 100);
                        } else {
                            $price_modifier += $price;
                        }
                    }
                }
                $cart_item_data['attribute_price_modifier'] = $price_modifier;
            }
        }
        
        return $cart_item_data;
    }
    
    /**
     * Get cart item from session
     */
    public function get_cart_item_from_session($cart_item, $values) {
        if (isset($values['attribute_selections'])) {
            $cart_item['attribute_selections'] = $values['attribute_selections'];
        }
        if (isset($values['attribute_price_modifier'])) {
            $cart_item['attribute_price_modifier'] = $values['attribute_price_modifier'];
        }
        if (isset($values['use_matrix_price'])) {
            $cart_item['use_matrix_price'] = $values['use_matrix_price'];
        }
        if (isset($values['matrix_final_price'])) {
            $cart_item['matrix_final_price'] = $values['matrix_final_price'];
        }
        return $cart_item;
    }
    
    /**
     * Display attribute selections in cart with prices
     */
    public function cart_item_name($name, $cart_item, $cart_item_key) {
        if (isset($cart_item['attribute_selections']) && !empty($cart_item['attribute_selections'])) {
            $product = $cart_item['data'];
            $product_id = $cart_item['product_id'];
            $base_price = wc_get_product($product_id)->get_price();
            
            // Add base price next to product name
            $name .= ' <span class="cart-item-base-price" style="color: #666; font-weight: normal;">(' . wc_price($base_price) . ')</span>';
            
            $name .= '<dl class="variation" style="margin-top: 8px;">';
            
            foreach ($cart_item['attribute_selections'] as $attribute => $value) {
                $taxonomy = strpos($attribute, 'pa_') === 0 ? $attribute : 'pa_' . $attribute;
                $term = get_term_by('slug', $value, $taxonomy);
                $display_value = $term ? $term->name : $value;
                
                // Get label - use Frontend Name if available
                $label = self::get_attribute_frontend_name($taxonomy);
                if (!$label || $label === $taxonomy) {
                    $label = ucfirst(str_replace(array('pa_', '-', '_'), array('', ' ', ' '), $attribute));
                }
                
                // Get price for this selection
                $price_data = self::get_product_attribute_price($product_id, $attribute, $value);
                $option_price = floatval($price_data['price']);
                $price_type = $price_data['type'];
                
                // Format price display
                $price_display = '';
                if ($option_price > 0) {
                    if ($price_type === 'percentage') {
                        $actual_price = $base_price * ($option_price / 100);
                        $price_display = ' <span style="color: #4a9c5d;">+' . wc_price($actual_price) . '</span>';
                    } else {
                        $price_display = ' <span style="color: #4a9c5d;">+' . wc_price($option_price) . '</span>';
                    }
                }
                
                $name .= '<dt>' . esc_html($label) . ':</dt>';
                $name .= '<dd>' . esc_html($display_value) . $price_display . '</dd>';
            }
            
            $name .= '</dl>';
        }
        
        return $name;
    }
    
    /**
     * Calculate cart totals with price modifiers
     */
    public function calculate_cart_totals($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['attribute_price_modifier']) && $cart_item['attribute_price_modifier'] > 0) {
                $base_price = floatval($cart_item['data']->get_price());
                $new_price = $base_price + $cart_item['attribute_price_modifier'];
                $cart_item['data']->set_price($new_price);
            }
        }
    }
    
    /**
     * Add attribute selections to order item meta
     */
    public function add_order_item_meta($item, $cart_item_key, $values, $order) {
        if (isset($values['attribute_selections'])) {
            foreach ($values['attribute_selections'] as $attribute => $value) {
                $taxonomy = strpos($attribute, 'pa_') === 0 ? $attribute : 'pa_' . $attribute;
                $term = get_term_by('slug', $value, $taxonomy);
                
                // Use Frontend Name if available
                $label = self::get_attribute_frontend_name($taxonomy);
                if (!$label || $label === $taxonomy) {
                    $label = ucfirst(str_replace(array('pa_', '-', '_'), array('', ' ', ' '), $attribute));
                }
                
                $display_value = $term ? $term->name : $value;
                
                $item->add_meta_data($label, $display_value);
            }
        }
        
        if (isset($values['attribute_price_modifier']) && $values['attribute_price_modifier'] > 0) {
            $item->add_meta_data(__('Options Price', 'decor'), wc_price($values['attribute_price_modifier']));
        }
    }
    
    /**
     * Get price for a specific term
     */
    public static function get_term_price($term_id) {
        return floatval(get_term_meta($term_id, 'attribute_price', true));
    }
    
    /**
     * Get all terms with prices for an attribute
     */
    public static function get_attribute_terms_with_prices($taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));
        
        $result = array();
        foreach ($terms as $term) {
            $result[] = array(
                'term_id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'price' => floatval(get_term_meta($term->term_id, 'attribute_price', true)),
                'price_type' => get_term_meta($term->term_id, 'attribute_price_type', true) ?: 'fixed',
            );
        }
        
        return $result;
    }
    
    /**
     * Add product attribute pricing tab
     */
    public function add_product_attribute_pricing_tab($tabs) {
        $tabs['attribute_pricing'] = array(
            'label'    => __('Attribute Pricing', 'decor'),
            'target'   => 'attribute_pricing_data',
            'class'    => array('show_if_simple'),
            'priority' => 65,
        );
        return $tabs;
    }
    
    /**
     * Add product attribute pricing panel
     */
    public function add_product_attribute_pricing_panel() {
        global $post;
        $product_id = $post->ID;
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return;
        }
        
        // Get product attributes
        $attributes = $product->get_attributes();
        
        // Get saved product-level pricing
        $product_attribute_pricing = get_post_meta($product_id, '_product_attribute_pricing', true);
        if (!is_array($product_attribute_pricing)) {
            $product_attribute_pricing = array();
        }
        
        // Get price matrix settings
        $use_price_matrix = get_post_meta($product_id, '_use_price_matrix', true);
        $price_matrix = get_post_meta($product_id, '_price_matrix', true);
        if (!is_array($price_matrix)) {
            $price_matrix = array();
        }
        
        ?>
        <div id="attribute_pricing_data" class="panel woocommerce_options_panel">
            
            <!-- Price Matrix Toggle -->
            <div class="options_group" style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                <p class="form-field">
                    <label for="_use_price_matrix">
                        <input type="checkbox" name="_use_price_matrix" id="_use_price_matrix" value="yes" <?php checked($use_price_matrix, 'yes'); ?>>
                        <strong><?php _e('Use Price Matrix', 'decor'); ?></strong>
                    </label>
                    <span class="description" style="display: block; margin-top: 5px;">
                        <?php _e('Enable to use a price lookup table instead of additive pricing. Each attribute combination has a specific price.', 'decor'); ?>
                    </span>
                </p>
            </div>
            
            <!-- Price Matrix Table -->
            <div class="options_group price-matrix-section" style="<?php echo $use_price_matrix !== 'yes' ? 'display:none;' : ''; ?>">
                <p class="form-field">
                    <strong><?php _e('Price Matrix by Groups', 'decor'); ?></strong><br>
                    <span class="description"><?php _e('Define prices by SIZE + ATTRIBUTE GROUP combination. The group is determined by the attribute\'s "Group" field.', 'decor'); ?></span>
                </p>
                
                <?php if (empty($attributes)) : ?>
                    <p class="form-field">
                        <em><?php _e('No attributes assigned to this product. Add attributes in the Attributes tab first.', 'decor'); ?></em>
                    </p>
                <?php else : 
                    // Collect SIZE attribute options
                    $size_options = array();
                    $size_taxonomy = '';
                    
                    // Collect all attribute groups (non-SIZE attributes)
                    // The GROUP is the attribute NAME itself (e.g., "TREVISO FABRIC GRADE 1")
                    $all_groups = array();
                    
                    foreach ($attributes as $attribute) {
                        $taxonomy = $attribute->get_name();
                        $label = wc_attribute_label($taxonomy);
                        
                        // Check if this is SIZE attribute
                        if (stripos($taxonomy, 'size') !== false || stripos($label, 'size') !== false) {
                            $size_taxonomy = $taxonomy;
                            if ($attribute->is_taxonomy()) {
                                $terms = wc_get_product_terms($product_id, $taxonomy, array('fields' => 'all'));
                                foreach ($terms as $term) {
                                    $size_options[] = array('slug' => $term->slug, 'name' => $term->name);
                                }
                            } else {
                                $custom_options = $attribute->get_options();
                                foreach ($custom_options as $option) {
                                    $size_options[] = array('slug' => sanitize_title($option), 'name' => $option);
                                }
                            }
                        } else {
                            // Non-SIZE attributes ARE the groups
                            // The attribute name/label IS the group name
                            $all_groups[] = array(
                                'taxonomy' => $taxonomy,
                                'label' => $label,
                                'slug' => sanitize_title($taxonomy)
                            );
                        }
                    }
                    
                    // Sort groups by label
                    usort($all_groups, function($a, $b) {
                        return strnatcmp($a['label'], $b['label']);
                    });
                    
                    // Also keep all_options for fallback
                    $all_options = array();
                    foreach ($attributes as $attribute) {
                        $taxonomy = $attribute->get_name();
                        $label = wc_attribute_label($taxonomy);
                        $options = array();
                        
                        if ($attribute->is_taxonomy()) {
                            $terms = wc_get_product_terms($product_id, $taxonomy, array('fields' => 'all'));
                            foreach ($terms as $term) {
                                $options[] = array('slug' => $term->slug, 'name' => $term->name);
                            }
                        } else {
                            $custom_options = $attribute->get_options();
                            foreach ($custom_options as $option) {
                                $options[] = array('slug' => sanitize_title($option), 'name' => $option);
                            }
                        }
                        
                        if (!empty($options)) {
                            $all_options[$taxonomy] = array(
                                'label' => $label,
                                'options' => $options
                            );
                        }
                    }
                ?>
                
                <?php if (!empty($size_options) && !empty($all_groups)) : ?>
                <!-- GROUP-BASED MATRIX (SIZE + GROUP) -->
                <div style="margin: 10px 0; display: flex; gap: 10px; align-items: center;">
                    <input type="text" id="matrix-search" placeholder="<?php _e('Search...', 'decor'); ?>" style="width: 200px;">
                    <span id="matrix-count" style="color: #666;"></span>
                </div>
                
                <div id="price-matrix-container" style="margin: 10px 0; max-height: 400px; overflow-y: auto;">
                    <table class="widefat price-matrix-table" style="table-layout: auto;">
                        <thead style="position: sticky; top: 0; background: #fff; z-index: 1;">
                            <tr>
                                <th><?php _e('SIZE', 'decor'); ?></th>
                                <th><?php _e('Attribute Group', 'decor'); ?></th>
                                <th style="width: 120px;"><?php _e('Price ($)', 'decor'); ?></th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="price-matrix-rows">
                            <?php 
                            $row_index = 0;
                            foreach ($price_matrix as $row) : 
                                $row_size = isset($row['size']) ? $row['size'] : '';
                                $row_group = isset($row['group']) ? $row['group'] : '';
                                $row_price = isset($row['price']) ? $row['price'] : '';
                            ?>
                                <tr class="matrix-row">
                                    <td>
                                        <select name="_price_matrix[<?php echo $row_index; ?>][size]" style="width: 100%;">
                                            <option value=""><?php _e('— Select Size —', 'decor'); ?></option>
                                            <?php foreach ($size_options as $option) : ?>
                                                <option value="<?php echo esc_attr($option['slug']); ?>" <?php selected($row_size, $option['slug']); ?>>
                                                    <?php echo esc_html($option['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="_price_matrix[<?php echo $row_index; ?>][group]" style="width: 100%;">
                                            <option value=""><?php _e('— Select Group —', 'decor'); ?></option>
                                            <?php foreach ($all_groups as $group) : ?>
                                                <option value="<?php echo esc_attr($group['taxonomy']); ?>" <?php selected($row_group, $group['taxonomy']); ?>>
                                                    <?php echo esc_html($group['label']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="_price_matrix[<?php echo $row_index; ?>][price]" value="<?php echo esc_attr($row_price); ?>" step="0.01" style="width: 100%;">
                                    </td>
                                    <td>
                                        <button type="button" class="button remove-matrix-row" style="color: #a00;">×</button>
                                    </td>
                                </tr>
                            <?php 
                                $row_index++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                    
                    <p style="margin-top: 10px;">
                        <button type="button" class="button" id="add-matrix-row"><?php _e('+ Add Row', 'decor'); ?></button>
                        <button type="button" class="button" id="generate-all-combinations" style="margin-left: 10px;"><?php _e('Generate All Combinations', 'decor'); ?></button>
                    </p>
                </div>
                
                <!-- Template row for JS (GROUP-BASED) -->
                <script type="text/template" id="matrix-row-template">
                    <tr class="matrix-row">
                        <td>
                            <select name="_price_matrix[{{INDEX}}][size]" style="width: 100%;">
                                <option value=""><?php _e('— Select Size —', 'decor'); ?></option>
                                <?php foreach ($size_options as $option) : ?>
                                    <option value="<?php echo esc_attr($option['slug']); ?>"><?php echo esc_html($option['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="_price_matrix[{{INDEX}}][group]" style="width: 100%;">
                                <option value=""><?php _e('— Select Group —', 'decor'); ?></option>
                                <?php foreach ($all_groups as $group) : ?>
                                    <option value="<?php echo esc_attr($group['taxonomy']); ?>"><?php echo esc_html($group['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="_price_matrix[{{INDEX}}][price]" value="" step="0.01" style="width: 100%;">
                        </td>
                        <td>
                            <button type="button" class="button remove-matrix-row" style="color: #a00;">×</button>
                        </td>
                    </tr>
                </script>
                
                <!-- All combinations data for JS -->
                <script type="application/json" id="all-options-data">
                    <?php echo json_encode(array('sizes' => $size_options, 'groups' => $all_groups)); ?>
                </script>
                
                <?php else : ?>
                <!-- FALLBACK: No groups defined -->
                <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 10px 0;">
                    <strong style="color: #856404;"><?php _e('⚠️ Price Matrix Setup Required', 'decor'); ?></strong>
                    <p style="margin: 10px 0 0; color: #856404;">
                        <?php 
                        if (empty($size_options)) {
                            _e('No SIZE attribute found. Add an attribute with "size" in its name.', 'decor');
                            echo '<br>';
                        }
                        if (empty($all_groups)) {
                            _e('No attribute groups found. Go to Products → Attributes, edit each fabric/material term and set a "Group" value (e.g., "Grade 1", "Grade 2").', 'decor');
                        }
                        ?>
                    </p>
                    <p style="margin: 10px 0 0; font-size: 12px; color: #666;">
                        <?php _e('Debug: Found ' . count($size_options) . ' sizes, ' . count($all_groups) . ' groups', 'decor'); ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <?php endif; ?>
            </div>
            
            <!-- Additive Pricing Section (original) -->
            <div class="options_group additive-pricing-section" style="<?php echo $use_price_matrix === 'yes' ? 'display:none;' : ''; ?>">
                <p class="form-field">
                    <strong><?php _e('Additive Attribute Pricing', 'decor'); ?></strong><br>
                    <span class="description"><?php _e('Set price modifiers for attributes. Final price = Base Price + Sum of all modifiers.', 'decor'); ?></span>
                </p>
                
                <?php if (empty($attributes)) : ?>
                    <p class="form-field">
                        <em><?php _e('No attributes assigned to this product. Add attributes in the Attributes tab first.', 'decor'); ?></em>
                    </p>
                <?php else : ?>
                    <table class="widefat" style="margin: 10px 0; table-layout: fixed;">
                        <thead>
                            <tr>
                                <th style="width: 30%;"><?php _e('Attribute', 'decor'); ?></th>
                                <th style="width: 15%;"><?php _e('Global Price', 'decor'); ?></th>
                                <th style="width: 15%;"><?php _e('Type', 'decor'); ?></th>
                                <th style="width: 40%;"><?php _e('Price Per Variation', 'decor'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attributes as $attribute_name => $attribute) : 
                                $taxonomy = $attribute->get_name();
                                $label = wc_attribute_label($taxonomy);
                                
                                // Get product-level price
                                $product_price = isset($product_attribute_pricing[$taxonomy]['price']) ? $product_attribute_pricing[$taxonomy]['price'] : '';
                                $product_type = isset($product_attribute_pricing[$taxonomy]['type']) ? $product_attribute_pricing[$taxonomy]['type'] : 'fixed';
                                $price_per_variation = isset($product_attribute_pricing[$taxonomy]['per_variation']) ? $product_attribute_pricing[$taxonomy]['per_variation'] : false;
                                $variation_prices = isset($product_attribute_pricing[$taxonomy]['variation_prices']) ? $product_attribute_pricing[$taxonomy]['variation_prices'] : array();
                                
                                // Get options for this attribute (taxonomy or custom)
                                $options = array();
                                if ($attribute->is_taxonomy()) {
                                    $terms = wc_get_product_terms($product_id, $taxonomy, array('fields' => 'all'));
                                    foreach ($terms as $term) {
                                        $options[] = array('slug' => $term->slug, 'name' => $term->name);
                                    }
                                } else {
                                    // Custom attribute - get options directly
                                    $custom_options = $attribute->get_options();
                                    foreach ($custom_options as $option) {
                                        $options[] = array('slug' => sanitize_title($option), 'name' => $option);
                                    }
                                }
                                $has_options = !empty($options);
                            ?>
                                <tr class="attribute-row" data-taxonomy="<?php echo esc_attr($taxonomy); ?>" style="vertical-align: middle;">
                                    <td style="padding: 10px;"><strong><?php echo esc_html($label); ?></strong></td>
                                    <td style="padding: 10px;">
                                        <input type="number" 
                                               name="_product_attribute_pricing[<?php echo esc_attr($taxonomy); ?>][price]" 
                                               value="<?php echo esc_attr($product_price); ?>" 
                                               step="0.01" 
                                               style="width: 80px;"
                                               class="single-price-input"
                                               data-taxonomy="<?php echo esc_attr($taxonomy); ?>"
                                               <?php echo $price_per_variation ? 'disabled' : ''; ?>
                                               placeholder="0">
                                    </td>
                                    <td style="padding: 10px; vertical-align: middle;">
                                        <select name="_product_attribute_pricing[<?php echo esc_attr($taxonomy); ?>][type]" 
                                                style="width: 100px; display: block;"
                                                class="single-type-select"
                                                data-taxonomy="<?php echo esc_attr($taxonomy); ?>"
                                                <?php echo $price_per_variation ? 'disabled' : ''; ?>>
                                            <option value="fixed" <?php selected($product_type, 'fixed'); ?>><?php _e('Fixed', 'decor'); ?></option>
                                            <option value="percentage" <?php selected($product_type, 'percentage'); ?>><?php _e('Percentage', 'decor'); ?></option>
                                        </select>
                                    </td>
                                    <td style="padding: 10px; vertical-align: middle; text-align: center;">
                                        <?php if ($has_options) : ?>
                                        <input type="checkbox" 
                                               name="_product_attribute_pricing[<?php echo esc_attr($taxonomy); ?>][per_variation]" 
                                               value="1" 
                                               class="price-per-variation-toggle"
                                               data-taxonomy="<?php echo esc_attr($taxonomy); ?>"
                                               style="width: 18px; height: 18px; cursor: pointer;"
                                               <?php checked($price_per_variation, true); ?>>
                                        <?php else : ?>
                                        <em style="color: #999;"><?php _e('—', 'decor'); ?></em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <?php if ($has_options) : ?>
                                <tr class="variation-prices-row" data-taxonomy="<?php echo esc_attr($taxonomy); ?>" style="display: <?php echo $price_per_variation ? 'table-row' : 'none'; ?>;">
                                    <td colspan="4" style="padding: 10px; background: #f9f9f9;">
                                        <table class="widefat" style="margin: 0;">
                                            <thead>
                                                <tr>
                                                    <th><?php _e('Option', 'decor'); ?></th>
                                                    <th style="width: 100px;"><?php _e('Price', 'decor'); ?></th>
                                                    <th style="width: 100px;"><?php _e('Type', 'decor'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($options as $option) : 
                                                    $option_slug = $option['slug'];
                                                    $option_name = $option['name'];
                                                    $option_price = isset($variation_prices[$option_slug]['price']) ? $variation_prices[$option_slug]['price'] : '';
                                                    $option_type = isset($variation_prices[$option_slug]['type']) ? $variation_prices[$option_slug]['type'] : 'fixed';
                                                ?>
                                                    <tr>
                                                        <td><?php echo esc_html($option_name); ?></td>
                                                        <td>
                                                            <input type="number" 
                                                                   name="_product_attribute_pricing[<?php echo esc_attr($taxonomy); ?>][variation_prices][<?php echo esc_attr($option_slug); ?>][price]" 
                                                                   value="<?php echo esc_attr($option_price); ?>" 
                                                                   step="0.01" 
                                                                   style="width: 80px;"
                                                                   placeholder="0">
                                                        </td>
                                                        <td>
                                                            <select name="_product_attribute_pricing[<?php echo esc_attr($taxonomy); ?>][variation_prices][<?php echo esc_attr($option_slug); ?>][type]" style="width: 100px;">
                                                                <option value="fixed" <?php selected($option_type, 'fixed'); ?>><?php _e('Fixed', 'decor'); ?></option>
                                                                <option value="percentage" <?php selected($option_type, 'percentage'); ?>><?php _e('Percentage', 'decor'); ?></option>
                                                            </select>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <script>
                    jQuery(document).ready(function($) {
                        // Toggle between matrix and additive pricing
                        $('#_use_price_matrix').on('change', function() {
                            if ($(this).is(':checked')) {
                                $('.price-matrix-section').show();
                                $('.additive-pricing-section').hide();
                            } else {
                                $('.price-matrix-section').hide();
                                $('.additive-pricing-section').show();
                            }
                        });
                        
                        // Add matrix row
                        var rowIndex = <?php echo $row_index; ?>;
                        $('#add-matrix-row').on('click', function() {
                            var template = $('#matrix-row-template').html();
                            template = template.replace(/\{\{INDEX\}\}/g, rowIndex);
                            $('#price-matrix-rows').append(template);
                            rowIndex++;
                        });
                        
                        // Remove matrix row
                        $(document).on('click', '.remove-matrix-row', function() {
                            $(this).closest('tr').remove();
                        });
                        
                        // Search/filter rows
                        var searchTimeout;
                        $('#matrix-search').on('input', function() {
                            clearTimeout(searchTimeout);
                            var query = $(this).val().toLowerCase();
                            searchTimeout = setTimeout(function() {
                                var visible = 0;
                                $('#price-matrix-rows tr').each(function() {
                                    var text = $(this).text().toLowerCase();
                                    if (query === '' || text.indexOf(query) > -1) {
                                        $(this).show();
                                        visible++;
                                    } else {
                                        $(this).hide();
                                    }
                                });
                                $('#matrix-count').text(visible + ' rows');
                            }, 200);
                        });
                        
                        // Update count on load
                        $('#matrix-count').text($('#price-matrix-rows tr').length + ' rows');
                        
                        // Generate all combinations (SIZE x GROUP)
                        $('#generate-all-combinations').on('click', function() {
                            var allOptions = JSON.parse($('#all-options-data').text());
                            var sizes = allOptions.sizes || [];
                            var groups = allOptions.groups || [];
                            
                            var total = sizes.length * groups.length;
                            
                            if (total === 0) {
                                alert('No sizes or groups available.');
                                return;
                            }
                            
                            if (!confirm('This will add ' + total + ' rows (SIZE × GROUP). Continue?')) {
                                return;
                            }
                            
                            var $btn = $(this);
                            $btn.prop('disabled', true).text('Generating...');
                            
                            // Generate all SIZE x GROUP combinations
                            sizes.forEach(function(size) {
                                groups.forEach(function(group) {
                                    var template = $('#matrix-row-template').html();
                                    template = template.replace(/\{\{INDEX\}\}/g, rowIndex);
                                    var $row = $(template);
                                    
                                    $row.find('select[name*="[size]"]').val(size.slug);
                                    $row.find('select[name*="[group]"]').val(group);
                                    
                                    $('#price-matrix-rows').append($row);
                                    rowIndex++;
                                });
                            });
                            
                            $('#matrix-count').text(rowIndex + ' rows');
                            $btn.prop('disabled', false).text('Generate All Combinations');
                        });
                        
                        // Per-variation toggle (additive pricing)
                        $('.price-per-variation-toggle').on('change', function() {
                            var taxonomy = $(this).data('taxonomy');
                            var isChecked = $(this).is(':checked');
                            var $row = $('.variation-prices-row[data-taxonomy="' + taxonomy + '"]');
                            var $priceInput = $('.single-price-input[data-taxonomy="' + taxonomy + '"]');
                            var $typeSelect = $('.single-type-select[data-taxonomy="' + taxonomy + '"]');
                            
                            if (isChecked) {
                                $row.show();
                                $priceInput.prop('disabled', true);
                                $typeSelect.prop('disabled', true);
                            } else {
                                $row.hide();
                                $priceInput.prop('disabled', false);
                                $typeSelect.prop('disabled', false);
                            }
                        });
                    });
                    </script>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save product attribute pricing
     */
    public function save_product_attribute_pricing($product_id) {
        // Save price matrix toggle
        $use_price_matrix = isset($_POST['_use_price_matrix']) ? 'yes' : 'no';
        update_post_meta($product_id, '_use_price_matrix', $use_price_matrix);
        
        // Save price matrix data (GROUP-BASED: size + group)
        if (isset($_POST['_price_matrix']) && is_array($_POST['_price_matrix'])) {
            $matrix_data = array();
            foreach ($_POST['_price_matrix'] as $row) {
                $size = isset($row['size']) ? sanitize_text_field($row['size']) : '';
                $group = isset($row['group']) ? sanitize_text_field($row['group']) : '';
                $price = isset($row['price']) ? $row['price'] : '';
                
                // Only save rows with size and group selected
                if (!empty($size) && !empty($group)) {
                    $matrix_data[] = array(
                        'size' => $size,
                        'group' => $group,
                        'price' => $price !== '' ? floatval($price) : '',
                    );
                }
            }
            update_post_meta($product_id, '_price_matrix', $matrix_data);
        }
        
        if (isset($_POST['_product_attribute_pricing'])) {
            $pricing_data = array();
            
            foreach ($_POST['_product_attribute_pricing'] as $taxonomy => $data) {
                $taxonomy = sanitize_text_field($taxonomy);
                $price = isset($data['price']) ? $data['price'] : '';
                $type = isset($data['type']) ? sanitize_text_field($data['type']) : 'fixed';
                $per_variation = isset($data['per_variation']) ? true : false;
                $variation_prices = array();
                
                // Process variation prices if per_variation is enabled
                if ($per_variation && isset($data['variation_prices']) && is_array($data['variation_prices'])) {
                    foreach ($data['variation_prices'] as $term_slug => $term_data) {
                        $term_price = isset($term_data['price']) ? $term_data['price'] : '';
                        $term_type = isset($term_data['type']) ? sanitize_text_field($term_data['type']) : 'fixed';
                        
                        if ($term_price !== '') {
                            $variation_prices[sanitize_text_field($term_slug)] = array(
                                'price' => floatval($term_price),
                                'type' => $term_type,
                            );
                        }
                    }
                }
                
                // Save attribute pricing data
                $pricing_data[$taxonomy] = array(
                    'price' => $price !== '' ? floatval($price) : '',
                    'type' => $type,
                    'per_variation' => $per_variation,
                    'variation_prices' => $variation_prices,
                );
            }
            
            update_post_meta($product_id, '_product_attribute_pricing', $pricing_data);
        }
    }
    
    /**
     * Get attribute price for a specific product (checks product-level first, then global)
     * If term_slug is provided, checks for per-variation pricing
     */
    public static function get_product_attribute_price($product_id, $taxonomy, $term_slug = '') {
        // Check product-level pricing first
        $product_pricing = get_post_meta($product_id, '_product_attribute_pricing', true);
        
        if (is_array($product_pricing) && isset($product_pricing[$taxonomy])) {
            $attr_pricing = $product_pricing[$taxonomy];
            
            // Check if per-variation pricing is enabled
            if (!empty($attr_pricing['per_variation']) && !empty($term_slug)) {
                // Look for specific term price
                if (isset($attr_pricing['variation_prices'][$term_slug]) && $attr_pricing['variation_prices'][$term_slug]['price'] !== '') {
                    return array(
                        'price' => floatval($attr_pricing['variation_prices'][$term_slug]['price']),
                        'type' => $attr_pricing['variation_prices'][$term_slug]['type'],
                        'source' => 'product_variation',
                    );
                }
            }
            
            // Use attribute-level price if set
            if (isset($attr_pricing['price']) && $attr_pricing['price'] !== '') {
                return array(
                    'price' => floatval($attr_pricing['price']),
                    'type' => $attr_pricing['type'],
                    'source' => 'product',
                );
            }
        }
        
        // Fall back to global pricing
        $global_price = self::get_attribute_price($taxonomy);
        $global_price['source'] = 'global';
        
        return $global_price;
    }
    
    /**
     * Get all variation prices for an attribute on a product
     */
    public static function get_product_variation_prices($product_id, $taxonomy) {
        $product_pricing = get_post_meta($product_id, '_product_attribute_pricing', true);
        
        if (is_array($product_pricing) && isset($product_pricing[$taxonomy])) {
            $attr_pricing = $product_pricing[$taxonomy];
            
            if (!empty($attr_pricing['per_variation']) && !empty($attr_pricing['variation_prices'])) {
                return $attr_pricing['variation_prices'];
            }
        }
        
        return array();
    }
    
    /**
     * Check if product uses price matrix
     */
    public static function uses_price_matrix($product_id) {
        return get_post_meta($product_id, '_use_price_matrix', true) === 'yes';
    }
    
    /**
     * Get price from matrix for given attribute combination (GROUP-BASED)
     * Looks up price by SIZE + GROUP (attribute taxonomy) combination
     * Returns false if no match found
     */
    public static function get_matrix_price($product_id, $selections) {
        if (!self::uses_price_matrix($product_id)) {
            return false;
        }
        
        $matrix = get_post_meta($product_id, '_price_matrix', true);
        if (!is_array($matrix) || empty($matrix)) {
            return false;
        }
        
        // Find SIZE value and collect selected attribute taxonomies (groups)
        $selected_size = '';
        $selected_groups = array(); // These are the attribute taxonomies that have selections
        
        foreach ($selections as $attr => $value) {
            if (empty($value)) continue;
            
            $attr_clean = str_replace('pa_', '', $attr);
            $value_slug = sanitize_title($value);
            
            // Check if this is a SIZE attribute
            if (stripos($attr_clean, 'size') !== false) {
                $selected_size = $value_slug;
            } else {
                // The GROUP is the attribute taxonomy itself
                // Store both with and without pa_ prefix for matching
                $taxonomy = strpos($attr, 'pa_') === 0 ? $attr : 'pa_' . $attr;
                $selected_groups[] = $taxonomy;
                $selected_groups[] = str_replace('pa_', '', $taxonomy);
            }
        }
        
        if (empty($selected_size)) {
            return false;
        }
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Matrix lookup - Size: ' . $selected_size . ', Groups: ' . implode(', ', $selected_groups));
            error_log('Matrix data: ' . print_r($matrix, true));
        }
        
        // Find matching row in matrix by SIZE + GROUP (attribute taxonomy)
        foreach ($matrix as $row) {
            if (!isset($row['price']) || $row['price'] === '') {
                continue;
            }
            
            $row_size = isset($row['size']) ? sanitize_title($row['size']) : '';
            $row_group = isset($row['group']) ? $row['group'] : '';
            
            // Check if size matches
            if ($row_size !== $selected_size) {
                continue;
            }
            
            // Check if the row's group (attribute taxonomy) is in selected groups
            // Try matching with and without pa_ prefix
            $row_group_clean = str_replace('pa_', '', $row_group);
            if (in_array($row_group, $selected_groups) || in_array($row_group_clean, $selected_groups) || in_array('pa_' . $row_group_clean, $selected_groups)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Matrix match found! Size: ' . $row_size . ', Group: ' . $row_group . ', Price: ' . $row['price']);
                }
                return floatval($row['price']);
            }
        }
        
        return false;
    }
}

// Initialize
Decor_Attribute_Pricing::get_instance();
