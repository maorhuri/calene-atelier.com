<?php
/**
 * Trade Portal Login Page Template
 * Elegant split-screen login page for trade partners
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get trade application page URL
$trade_application_url = home_url('/dealer-registration/');
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');

// Check if user is already logged in as dealer
$is_dealer_logged_in = false;
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    if (in_array('dealer', (array) $user->roles) || in_array('administrator', (array) $user->roles)) {
        $is_dealer_logged_in = true;
    }
}

// Handle login
$login_error = '';
if (isset($_POST['trade_login']) && isset($_POST['trade_login_nonce']) && wp_verify_nonce($_POST['trade_login_nonce'], 'trade_login')) {
    $creds = array(
        'user_login'    => sanitize_text_field($_POST['email']),
        'user_password' => sanitize_text_field($_POST['password']),
        'remember'      => isset($_POST['remember_me']),
    );
    
    $user = wp_signon($creds, false);
    
    if (is_wp_error($user)) {
        $login_error = 'Invalid email or password. Please try again.';
    } else {
        // Redirect via JavaScript since headers already sent
        echo '<script>window.location.href = "' . esc_url($shop_url) . '";</script>';
        return;
    }
}
?>

<?php if ($is_dealer_logged_in): ?>
<div class="trade-portal-logged-in-compact">
    <div class="logged-in-box">
        <p>Welcome back! You are logged in as a Trade Partner.</p>
        <a href="<?php echo esc_url($shop_url); ?>" class="shop-btn">Go to Shop</a>
    </div>
</div>
<style>
.trade-portal-logged-in-compact {
    padding: 40px 30px;
    display: flex;
    justify-content: center;
}
.trade-portal-logged-in-compact .logged-in-box {
    background: #fff;
    padding: 30px 50px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.trade-portal-logged-in-compact p {
    font-size: 14px;
    color: #555;
    margin: 0 0 20px 0;
}
.trade-portal-logged-in-compact .shop-btn {
    display: inline-block;
    background: #1a1a1a;
    color: #fff;
    padding: 12px 30px;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: 1px;
    text-transform: uppercase;
    text-decoration: none;
    border-radius: 3px;
    transition: background 0.2s ease;
}
.trade-portal-logged-in-compact .shop-btn:hover {
    background: #333;
}
</style>
<?php else: ?>
<div class="trade-portal-page">
    <div class="trade-portal-container">
        
        <!-- Left Side - Image -->
        <div class="trade-portal-image">
            <?php 
            $image_path = get_stylesheet_directory() . '/assets/images/trade-portal-bg.jpg';
            if (file_exists($image_path)): ?>
                <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/trade-portal-bg.jpg" alt="Calene Atelier Interior">
            <?php endif; ?>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="trade-portal-content">
            <div class="trade-portal-inner">
                
                <!-- Logo -->
                <div class="trade-portal-logo">
                    <span class="logo-text">CALENE</span>
                    <span class="logo-subtext">ATELIER</span>
                    <div class="logo-icon">CA</div>
                </div>
                
                <!-- Title -->
                <h1 class="trade-portal-title">Enter Trade Portal</h1>
                
                <!-- Description -->
                <p class="trade-portal-desc">
                    Calene Atelier is available exclusively<br>
                    to approved trade partners.
                </p>
                <p class="trade-portal-subdesc">
                    Access pricing, specifications, and ordering<br>
                    through your private account.
                </p>
                
                <!-- Login Form -->
                <form class="trade-login-form" method="post">
                    <?php wp_nonce_field('trade_login', 'trade_login_nonce'); ?>
                    
                    <?php if ($login_error): ?>
                        <div class="trade-login-error"><?php echo esc_html($login_error); ?></div>
                    <?php endif; ?>
                    
                    <div class="form-field">
                        <label for="email">EMAIL ADDRESS</label>
                        <input type="email" name="email" id="email" placeholder="Enter your email address" required>
                    </div>
                    
                    <div class="form-field">
                        <label for="password">PASSWORD</label>
                        <div class="password-field">
                            <input type="password" name="password" id="password" placeholder="Enter your password" required>
                            <button type="button" class="toggle-password" aria-label="Toggle password visibility">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember_me">
                            <span>Remember me</span>
                        </label>
                        <a href="<?php echo wp_lostpassword_url(); ?>" class="forgot-password">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" name="trade_login" class="trade-login-btn">ENTER PORTAL</button>
                </form>
                
                <!-- Apply Link -->
                <div class="trade-apply-section">
                    <p class="apply-text">New to Calene Atelier?</p>
                    <a href="<?php echo esc_url($trade_application_url); ?>" class="apply-link">APPLY FOR TRADE ACCESS</a>
                </div>
                
                <!-- Footer Note -->
                <div class="trade-portal-footer">
                    <div class="footer-logo">CA</div>
                    <p class="footer-note">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        By accessing this portal, you confirm your status<br>
                        as an approved Calene Atelier Trade Partner.
                    </p>
                </div>
                
            </div>
        </div>
        
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        var $input = $(this).siblings('input');
        var type = $input.attr('type') === 'password' ? 'text' : 'password';
        $input.attr('type', type);
        $(this).toggleClass('visible');
    });
});
</script>
<?php endif; ?>
