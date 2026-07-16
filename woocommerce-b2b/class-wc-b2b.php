<?php
/**
 * WooCommerce B2B Module
 * Handles dealer registration, price hiding for non-logged users, and B2B functionality
 * 
 * @package Decor_B2B
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Decor_WC_B2B {
    
    private static $instance = null;
    
    const VERSION = '1.2.4';
    const DEALER_ROLE = 'dealer';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        // Register dealer role on activation
        add_action('init', array($this, 'register_dealer_role'));
        
        // Hide prices and add to cart for non-logged users
        add_filter('woocommerce_get_price_html', array($this, 'hide_price_for_guests'), 100, 2);
        add_filter('woocommerce_is_purchasable', array($this, 'hide_add_to_cart_for_guests'), 100, 2);
        add_action('woocommerce_single_product_summary', array($this, 'show_dealer_login_button'), 11); // After price (priority 10)
        add_action('woocommerce_after_shop_loop_item', array($this, 'show_dealer_login_button_loop'), 11);
        
        // Hide add to cart button completely for guests
        add_action('wp', array($this, 'hide_add_to_cart_button'));
        
        // Registration form shortcode
        add_shortcode('dealer_registration_form', array($this, 'render_registration_form'));
        
        // Trade portal login shortcode
        add_shortcode('trade_portal_login', array($this, 'render_trade_portal_login'));
        
        // Handle form submission
        add_action('wp_ajax_submit_dealer_registration', array($this, 'handle_registration_submission'));
        add_action('wp_ajax_nopriv_submit_dealer_registration', array($this, 'handle_registration_submission'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Admin menu for pending dealers
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Handle dealer approval
        add_action('wp_ajax_approve_dealer', array($this, 'handle_dealer_approval'));
        add_action('wp_ajax_reject_dealer', array($this, 'handle_dealer_rejection'));
        
        // Create registration page on init (if not exists)
        add_action('init', array($this, 'create_registration_page'));
        
        // Redirect WooCommerce registration to dealer registration
        add_filter('woocommerce_registration_redirect', array($this, 'redirect_after_registration'));
        add_action('woocommerce_register_form_start', array($this, 'add_dealer_registration_link'));
        
        // Redirect ?action=register to dealer registration page
        add_action('template_redirect', array($this, 'redirect_register_action'));
    }
    
    /**
     * Hide add to cart button for guests (but keep variations visible)
     */
    public function hide_add_to_cart_button() {
        if (!is_user_logged_in()) {
            // Remove add to cart button in loop
            remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
            
            // Hide add to cart button and quantity via CSS (but keep variations visible)
            add_action('wp_head', function() {
                echo '<style>
                    /* Hide add to cart button and quantity for guests */
                    .single-product .single_add_to_cart_button,
                    .single-product .woocommerce-variation-add-to-cart,
                    .single-product form.cart .quantity,
                    .single-product form.cart button[type="submit"],
                    .products .add_to_cart_button {
                        display: none !important;
                    }
                    
                    /* Keep variations form visible */
                    .single-product form.cart,
                    .single-product .variations_form,
                    .single-product .variations {
                        display: block !important;
                    }
                </style>';
            });
        }
    }
    
    /**
     * Register dealer role
     */
    public function register_dealer_role() {
        if (!get_role(self::DEALER_ROLE)) {
            add_role(
                self::DEALER_ROLE,
                'Dealer',
                array(
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                )
            );
        }
    }
    
    /**
     * Check if current user can view prices
     */
    public function can_view_prices() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        $allowed_roles = array('administrator', 'shop_manager', 'dealer', 'customer');
        
        return array_intersect($allowed_roles, $user->roles) ? true : false;
    }
    
    /**
     * Check if user is approved dealer
     */
    public function is_approved_dealer() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        
        // Admins and shop managers always have access
        if (array_intersect(array('administrator', 'shop_manager'), $user->roles)) {
            return true;
        }
        
        // Check if dealer is approved
        if (in_array(self::DEALER_ROLE, $user->roles)) {
            $approved = get_user_meta($user->ID, '_dealer_approved', true);
            return $approved === 'yes';
        }
        
        return false;
    }
    
    /**
     * Hide price for guests
     */
    public function hide_price_for_guests($price, $product) {
        if (!is_user_logged_in()) {
            return '<span class="price-hidden">Login to see price</span>';
        }
        return $price;
    }
    
    /**
     * Hide add to cart for guests
     */
    public function hide_add_to_cart_for_guests($purchasable, $product) {
        if (!is_user_logged_in()) {
            return false;
        }
        return $purchasable;
    }
    
    /**
     * Show dealer login/register button on product page
     */
    public function show_dealer_login_button() {
        if (is_user_logged_in()) {
            return;
        }
        
        $login_url = wc_get_page_permalink('myaccount');
        $register_url = $this->get_registration_page_url();
        
        ?>
        <div class="dealer-access-buttons">
            <a href="<?php echo esc_url($login_url); ?>" class="button dealer-login-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
                Login as Dealer
            </a>
            <a href="<?php echo esc_url($register_url); ?>" class="button dealer-register-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="8.5" cy="7" r="4"></circle>
                    <line x1="20" y1="8" x2="20" y2="14"></line>
                    <line x1="23" y1="11" x2="17" y2="11"></line>
                </svg>
                Register as Dealer
            </a>
        </div>
        <?php
    }
    
    /**
     * Show dealer login button in shop loop
     */
    public function show_dealer_login_button_loop() {
        if (is_user_logged_in()) {
            return;
        }
        
        $login_url = wc_get_page_permalink('myaccount');
        ?>
        <a href="<?php echo esc_url($login_url); ?>" class="button dealer-login-btn-loop">Login to Purchase</a>
        <?php
    }
    
    /**
     * Get registration page URL
     */
    public function get_registration_page_url() {
        $page = get_page_by_path('dealer-registration');
        if ($page) {
            return get_permalink($page->ID);
        }
        return home_url('/dealer-registration/');
    }
    
    /**
     * Render registration form
     */
    public function render_registration_form() {
        ob_start();
        include(get_stylesheet_directory() . '/woocommerce-b2b/templates/registration-form.php');
        return ob_get_clean();
    }
    
    /**
     * Render trade portal login page
     */
    public function render_trade_portal_login() {
        ob_start();
        include(get_stylesheet_directory() . '/woocommerce-b2b/templates/trade-portal-login.php');
        return ob_get_clean();
    }
    
    /**
     * Handle registration submission
     */
    public function handle_registration_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['dealer_nonce'], 'dealer_registration')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        // Validate required fields
        $required_fields = array(
            'first_name', 'last_name', 'company', 'address_1', 'city', 
            'state', 'zip', 'country', 'phone', 'email', 'business_type'
        );
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array('message' => 'Please fill in all required fields.'));
            }
        }
        
        // Validate email
        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Please enter a valid email address.'));
        }
        
        // Check if email already exists
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'An account with this email already exists. Please login or use a different email.'));
        }
        
        // Generate username and password
        $username = sanitize_user(strtolower($_POST['first_name'] . '_' . $_POST['last_name']));
        $username = $this->generate_unique_username($username);
        $password = wp_generate_password(12, true);
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => 'Error creating account: ' . $user_id->get_error_message()));
        }
        
        // Set user role to dealer
        $user = new WP_User($user_id);
        $user->set_role(self::DEALER_ROLE);
        
        // Save user meta
        update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
        update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
        update_user_meta($user_id, 'billing_first_name', sanitize_text_field($_POST['first_name']));
        update_user_meta($user_id, 'billing_last_name', sanitize_text_field($_POST['last_name']));
        update_user_meta($user_id, 'billing_company', sanitize_text_field($_POST['company']));
        update_user_meta($user_id, 'billing_address_1', sanitize_text_field($_POST['address_1']));
        update_user_meta($user_id, 'billing_address_2', sanitize_text_field($_POST['address_2']));
        update_user_meta($user_id, 'billing_city', sanitize_text_field($_POST['city']));
        update_user_meta($user_id, 'billing_state', sanitize_text_field($_POST['state']));
        update_user_meta($user_id, 'billing_postcode', sanitize_text_field($_POST['zip']));
        update_user_meta($user_id, 'billing_country', sanitize_text_field($_POST['country']));
        update_user_meta($user_id, 'billing_phone', sanitize_text_field($_POST['phone']));
        update_user_meta($user_id, 'billing_email', $email);
        
        // Save dealer-specific meta
        update_user_meta($user_id, '_dealer_company', sanitize_text_field($_POST['company']));
        update_user_meta($user_id, '_dealer_alternate_phone', sanitize_text_field($_POST['alternate_phone']));
        update_user_meta($user_id, '_dealer_website', esc_url_raw($_POST['website']));
        update_user_meta($user_id, '_dealer_sales_tax_id', sanitize_text_field($_POST['sales_tax_id']));
        update_user_meta($user_id, '_dealer_business_type', sanitize_text_field($_POST['business_type']));
        update_user_meta($user_id, '_dealer_years_in_business', sanitize_text_field($_POST['years_in_business']));
        update_user_meta($user_id, '_dealer_in_business_since', sanitize_text_field($_POST['in_business_since']));
        update_user_meta($user_id, '_dealer_company_type', sanitize_text_field($_POST['company_type']));
        update_user_meta($user_id, '_dealer_hear_about_us', sanitize_text_field($_POST['hear_about_us']));
        update_user_meta($user_id, '_dealer_notes', sanitize_textarea_field($_POST['notes']));
        update_user_meta($user_id, '_dealer_use_same_shipping', isset($_POST['use_same_shipping']) ? 'yes' : 'no');
        update_user_meta($user_id, '_dealer_approved', 'pending');
        update_user_meta($user_id, '_dealer_registration_date', current_time('mysql'));
        
        // Handle file upload
        if (!empty($_FILES['resale_certificate']['name'])) {
            $upload = $this->handle_file_upload($_FILES['resale_certificate'], $user_id);
            if (!is_wp_error($upload)) {
                update_user_meta($user_id, '_dealer_resale_certificate', $upload['url']);
                update_user_meta($user_id, '_dealer_resale_certificate_id', $upload['id']);
            }
        }
        
        // Copy billing to shipping if checkbox checked
        if (isset($_POST['use_same_shipping'])) {
            update_user_meta($user_id, 'shipping_first_name', sanitize_text_field($_POST['first_name']));
            update_user_meta($user_id, 'shipping_last_name', sanitize_text_field($_POST['last_name']));
            update_user_meta($user_id, 'shipping_company', sanitize_text_field($_POST['company']));
            update_user_meta($user_id, 'shipping_address_1', sanitize_text_field($_POST['address_1']));
            update_user_meta($user_id, 'shipping_address_2', sanitize_text_field($_POST['address_2']));
            update_user_meta($user_id, 'shipping_city', sanitize_text_field($_POST['city']));
            update_user_meta($user_id, 'shipping_state', sanitize_text_field($_POST['state']));
            update_user_meta($user_id, 'shipping_postcode', sanitize_text_field($_POST['zip']));
            update_user_meta($user_id, 'shipping_country', sanitize_text_field($_POST['country']));
        }
        
        // Send notification to admin
        $this->send_admin_notification($user_id);
        
        // Send confirmation email to dealer
        $this->send_dealer_confirmation($user_id, $password);
        
        wp_send_json_success(array(
            'message' => 'Thank you for registering! Your application is being reviewed. You will receive an email once your account is approved.',
            'redirect' => wc_get_page_permalink('myaccount')
        ));
    }
    
    /**
     * Generate unique username
     */
    private function generate_unique_username($username) {
        $original = $username;
        $counter = 1;
        
        while (username_exists($username)) {
            $username = $original . '_' . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Handle file upload
     */
    private function handle_file_upload($file, $user_id) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'application/pdf');
        
        if (!in_array($file['type'], $allowed_types)) {
            return new WP_Error('invalid_type', 'Invalid file type. Please upload an image or PDF.');
        }
        
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            return new WP_Error('upload_error', $upload['error']);
        }
        
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => 'Resale Certificate - User ' . $user_id,
            'post_content' => '',
            'post_status' => 'private'
        );
        
        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        
        return array(
            'url' => $upload['url'],
            'id' => $attach_id
        );
    }
    
    /**
     * Send admin notification
     */
    private function send_admin_notification($user_id) {
        $user = get_userdata($user_id);
        $admin_email = get_option('admin_email');
        
        $subject = 'New Dealer Registration - ' . get_user_meta($user_id, '_dealer_company', true);
        
        $message = "A new dealer has registered on your website.\n\n";
        $message .= "Company: " . get_user_meta($user_id, '_dealer_company', true) . "\n";
        $message .= "Name: " . $user->first_name . " " . $user->last_name . "\n";
        $message .= "Email: " . $user->user_email . "\n";
        $message .= "Phone: " . get_user_meta($user_id, 'billing_phone', true) . "\n";
        $message .= "Business Type: " . get_user_meta($user_id, '_dealer_business_type', true) . "\n\n";
        $message .= "Please review and approve this dealer in the WordPress admin:\n";
        $message .= admin_url('admin.php?page=pending-dealers');
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Send dealer confirmation email
     */
    private function send_dealer_confirmation($user_id, $password) {
        $user = get_userdata($user_id);
        
        $subject = 'Your Dealer Account Registration - ' . get_bloginfo('name');
        
        $message = "Dear " . $user->first_name . ",\n\n";
        $message .= "Thank you for registering as a dealer with " . get_bloginfo('name') . ".\n\n";
        $message .= "Your account is currently pending approval. Once approved, you will receive another email with confirmation.\n\n";
        $message .= "Your login details:\n";
        $message .= "Username: " . $user->user_login . "\n";
        $message .= "Password: " . $password . "\n\n";
        $message .= "Login URL: " . wc_get_page_permalink('myaccount') . "\n\n";
        $message .= "Please note: You will not be able to view prices or make purchases until your account is approved.\n\n";
        $message .= "Best regards,\n";
        $message .= get_bloginfo('name');
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'decor-b2b',
            get_stylesheet_directory_uri() . '/woocommerce-b2b/assets/css/b2b.css',
            array(),
            self::VERSION
        );
        
        // Signature Pad library from CDN
        wp_enqueue_script(
            'signature-pad',
            'https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js',
            array(),
            '4.1.7',
            true
        );
        
        wp_enqueue_script(
            'decor-b2b',
            get_stylesheet_directory_uri() . '/woocommerce-b2b/assets/js/b2b.js',
            array('jquery', 'signature-pad'),
            self::VERSION,
            true
        );
        
        wp_localize_script('decor-b2b', 'decorB2B', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dealer_registration'),
        ));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Pending Dealers',
            'Pending Dealers',
            'manage_woocommerce',
            'pending-dealers',
            array($this, 'render_admin_page'),
            'dashicons-groups',
            56
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        include(get_stylesheet_directory() . '/woocommerce-b2b/templates/admin-pending-dealers.php');
    }
    
    /**
     * Handle dealer approval
     */
    public function handle_dealer_approval() {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized');
        }
        
        $user_id = intval($_POST['user_id']);
        
        update_user_meta($user_id, '_dealer_approved', 'yes');
        update_user_meta($user_id, '_dealer_approval_date', current_time('mysql'));
        
        // Send approval email
        $user = get_userdata($user_id);
        $subject = 'Your Dealer Account Has Been Approved - ' . get_bloginfo('name');
        
        $message = "Dear " . $user->first_name . ",\n\n";
        $message .= "Great news! Your dealer account has been approved.\n\n";
        $message .= "You can now login to view wholesale prices and place orders:\n";
        $message .= wc_get_page_permalink('myaccount') . "\n\n";
        $message .= "Best regards,\n";
        $message .= get_bloginfo('name');
        
        wp_mail($user->user_email, $subject, $message);
        
        wp_send_json_success('Dealer approved successfully.');
    }
    
    /**
     * Handle dealer rejection
     */
    public function handle_dealer_rejection() {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Unauthorized');
        }
        
        $user_id = intval($_POST['user_id']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        update_user_meta($user_id, '_dealer_approved', 'rejected');
        update_user_meta($user_id, '_dealer_rejection_reason', $reason);
        
        // Send rejection email
        $user = get_userdata($user_id);
        $subject = 'Your Dealer Application Status - ' . get_bloginfo('name');
        
        $message = "Dear " . $user->first_name . ",\n\n";
        $message .= "Thank you for your interest in becoming a dealer with " . get_bloginfo('name') . ".\n\n";
        $message .= "Unfortunately, we are unable to approve your dealer application at this time.\n\n";
        if ($reason) {
            $message .= "Reason: " . $reason . "\n\n";
        }
        $message .= "If you have any questions, please contact us.\n\n";
        $message .= "Best regards,\n";
        $message .= get_bloginfo('name');
        
        wp_mail($user->user_email, $subject, $message);
        
        wp_send_json_success('Dealer rejected.');
    }
    
    /**
     * Redirect after registration
     */
    public function redirect_after_registration($redirect) {
        return wc_get_page_permalink('myaccount');
    }
    
    /**
     * Redirect ?action=register to dealer registration page
     */
    public function redirect_register_action() {
        // Check if WooCommerce is active
        if (!function_exists('is_account_page')) {
            return;
        }
        
        if (is_account_page() && isset($_GET['action']) && $_GET['action'] === 'register') {
            wp_safe_redirect($this->get_registration_page_url());
            exit;
        }
    }
    
    
    /**
     * Replace WooCommerce register form with dealer registration link only
     */
    public function add_dealer_registration_link() {
        $register_url = $this->get_registration_page_url();
        ?>
        <style>
            /* Hide the entire left register column and show dealer link on right side */
            .u-column1.col-1.woocommerce-form-register__wrapper,
            .woocommerce-form-register {
                display: none !important;
            }
            
            /* Replace right side content */
            .u-column2.col-2 h2 {
                display: none;
            }
            
            .u-column2.col-2 .woocommerce-form-login {
                text-align: center;
            }
            
            .u-column2.col-2 .woocommerce-form-login::before {
                content: 'Login or Register';
                display: block;
                font-size: 24px;
                font-weight: 600;
                margin-bottom: 20px;
            }
            
            /* Style for dealer register button added via JS */
            .dealer-register-btn-myaccount {
                display: block;
                width: 100%;
                margin-top: 15px;
                padding: 12px 20px;
                background: #4a5d23;
                color: #fff;
                text-align: center;
                text-decoration: none;
                border: none;
                cursor: pointer;
            }
            
            .dealer-register-btn-myaccount:hover {
                background: #3a4a1c;
                color: #fff;
            }
        </style>
        <script>
            jQuery(document).ready(function($) {
                var dealerUrl = '<?php echo esc_url($register_url); ?>';
                
                // Add dealer register button after login form
                var $loginForm = $('.woocommerce-form-login');
                if ($loginForm.length && !$('.dealer-register-btn-myaccount').length) {
                    $loginForm.after('<a href="' + dealerUrl + '" class="dealer-register-btn-myaccount">Register as Dealer</a>');
                }
                
                // Replace right side REGISTER button - wrap in link or change to link
                $('.u-column2 button, .col-2 button').each(function() {
                    var $btn = $(this);
                    var btnText = $btn.text().toLowerCase().trim();
                    if (btnText === 'register' || btnText.includes('register')) {
                        // Replace button with a link
                        var $link = $('<a href="' + dealerUrl + '" class="button">' + $btn.text() + '</a>');
                        $btn.replaceWith($link);
                    }
                });
                
                // Handle any existing register links
                $('a').each(function() {
                    var $link = $(this);
                    var href = $link.attr('href') || '';
                    var text = $link.text().toLowerCase().trim();
                    
                    // Check if it's a register link
                    if (href.includes('action=register') || (text === 'register' && !$link.hasClass('dealer-register-btn-myaccount'))) {
                        $link.attr('href', dealerUrl);
                    }
                });
            });
        </script>
        <?php
    }
    
    /**
     * Create registration page
     */
    public function create_registration_page() {
        // Only run once
        if (get_option('decor_b2b_page_created')) {
            return;
        }
        
        $page = get_page_by_path('dealer-registration');
        
        if (!$page) {
            $page_id = wp_insert_post(array(
                'post_title' => 'Become A Dealer',
                'post_name' => 'dealer-registration',
                'post_content' => '[dealer_registration_form]',
                'post_status' => 'publish',
                'post_type' => 'page',
            ));
            
            if ($page_id) {
                update_option('decor_b2b_page_created', true);
            }
        } else {
            update_option('decor_b2b_page_created', true);
        }
    }
}

// Initialize
Decor_WC_B2B::get_instance();
