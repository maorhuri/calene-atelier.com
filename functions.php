<?php
/**
 * Enqueue script and styles for child theme
 */
function woodmart_child_enqueue_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'woodmart-style' ), woodmart_get_theme_info( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010 );

/**
 * Include Fabric Variations System
 * Advanced variation system with fabric categories and custom fields
 */
require_once get_stylesheet_directory() . '/includes/class-fabric-variations.php';

/**
 * Include WooCommerce B2B Module
 * Dealer registration, price hiding for guests, and B2B functionality
 */
require_once get_stylesheet_directory() . '/woocommerce-b2b/class-wc-b2b.php';

/**
 * Include Product Specs Module
 * Custom fields for product: Technical Description, Dimensions, Specs
 */
require_once get_stylesheet_directory() . '/includes/class-product-specs.php';

/**
 * Include Attribute Pricing System
 * Dynamic pricing based on attribute selections (no variations needed!)
 */
require_once get_stylesheet_directory() . '/includes/class-attribute-pricing.php';

/**
 * Customize WooCommerce Product Tabs - Remove all default tabs
 * We use custom Product Specs module instead
 */
add_filter('woocommerce_product_tabs', 'decor_customize_product_tabs', 98);
function decor_customize_product_tabs($tabs) {
    // Remove all default tabs - we use custom specs module
    return array();
}

/**
 * Hide default WooCommerce tabs container
 */
add_action('wp_head', 'decor_hide_default_tabs');
function decor_hide_default_tabs() {
    if (!is_product()) return;
    ?>
    <style>
        .woocommerce-tabs {
            display: none !important;
        }
    </style>
    <?php
}

add_filter( 'woocommerce_get_price_html', 'mvn_add_from_before_price_loop', 10, 2 );

function mvn_add_from_before_price_loop( $price, $product ) {
    if ( is_shop() || is_product_category() || is_product_tag() ) {
        if ( strpos( $price, 'From ' ) === false ) {
            $price = 'From ' . $price;
        }
    }

    return $price;
}

/**
 * Load side cart CSS globally (not just on product pages)
 */
add_action('wp_enqueue_scripts', 'decor_global_cart_styles', 20);
function decor_global_cart_styles() {
    // Load fabric-variations.css on all pages for cart styling
    wp_enqueue_style(
        'decor-fabric-variations-global',
        get_stylesheet_directory_uri() . '/assets/css/fabric-variations.css',
        array(),
        '3.0.2'
    );
}

/**
 * Woodmart Cart Icon Shortcode
 * Creates a cart icon that opens Woodmart's side cart
 * Usage: [woodmart_cart_icon]
 */
add_shortcode('woodmart_cart_icon', 'decor_woodmart_cart_icon_shortcode');
function decor_woodmart_cart_icon_shortcode($atts) {
    $atts = shortcode_atts(array(
        'color' => '#534A4B',
        'size' => '30',
    ), $atts);
    
    $cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    
    $size = intval($atts['size']);
    $color = esc_attr($atts['color']);
    
    // Return a simple clickable link that wraps everything
    $html = '<a href="#" class="decor-cart-icon" onclick="return false;" style="
        cursor: pointer;
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        padding: 10px;
    ">';
    
    $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="21" r="1"></circle>
        <circle cx="20" cy="21" r="1"></circle>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
    </svg>';
    
    if ($cart_count > 0) {
        $html .= '<span class="decor-cart-count" style="
            position: absolute;
            top: 0;
            right: 0;
            background: #c4a47c;
            color: #fff;
            font-size: 11px;
            font-weight: 600;
            min-width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        ">' . esc_html($cart_count) . '</span>';
    }
    
    $html .= '</a>';
    
    return $html;
}

/**
 * Enqueue cart icon script on all pages
 */
add_action('wp_footer', 'decor_cart_icon_script');
function decor_cart_icon_script() {
    ?>
    <style>
    /* Show/Hide elements based on login status */
    <?php if (is_user_logged_in()): ?>
    .only-login { display: block !important; }
    .only-guest { display: none !important; }
    <?php else: ?>
    .only-login { display: none !important; }
    .only-guest { display: block !important; }
    /* Hide price range filter for guests */
    .wd-pf-price-range,
    .widget_price_filter,
    .wd-pf-checkboxes.multi_select.widget_price_filter { display: none !important; }
    <?php endif; ?>
    
    /* Fix cart icon styling and clickability */
    .decor-cart-icon {
        cursor: pointer !important;
        position: relative !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-width: 40px !important;
        min-height: 40px !important;
        padding: 5px !important;
    }
    .decor-cart-icon * {
        pointer-events: none !important;
    }
    .decor-cart-icon svg {
        width: 30px !important;
        height: 30px !important;
        stroke: #534A4B !important;
    }
    
    /* ===== Mega Menu Styling - Premium Design ===== */
    
    /* All mega menus - Interior (35500) and Exterior (36004) */
    .elementor-35500,
    .elementor-36004,
    [data-elementor-id="35500"],
    [data-elementor-id="36004"],
    [data-elementor-type="jet-menu"] {
        padding: 35px 40px !important;
        background: transparent !important;
    }
    
    /* First column - no extra padding needed */
    
    /* Category headings - line full width, text indented */
    .elementor-35500 .elementor-heading-title,
    .elementor-36004 .elementor-heading-title,
    [data-elementor-type="jet-menu"] .elementor-heading-title {
        font-size: 11px !important;
        font-weight: 600 !important;
        letter-spacing: 2px !important;
        text-transform: uppercase !important;
        color: #1a1a1a !important;
        margin: 0 0 12px 0 !important;
        padding: 0 0 12px 0 !important;
        border-bottom: 1px solid rgba(0,0,0,0.08) !important;
        line-height: 1.2 !important;
        text-indent: 15px !important;
    }
    
    /* Menu items - add left padding */
    .elementor-35500 .elementor-nav-menu .menu-item a.elementor-item,
    .elementor-36004 .elementor-nav-menu .menu-item a.elementor-item,
    [data-elementor-type="jet-menu"] .elementor-nav-menu .menu-item a.elementor-item,
    .elementor-35500 .elementor-nav-menu .menu-item a,
    .elementor-36004 .elementor-nav-menu .menu-item a,
    [data-elementor-type="jet-menu"] .elementor-nav-menu .menu-item a {
        margin-left: 15px !important;
    }
    
    /* Menu items */
    .elementor-35500 .elementor-nav-menu .menu-item,
    .elementor-36004 .elementor-nav-menu .menu-item,
    [data-elementor-type="jet-menu"] .elementor-nav-menu .menu-item {
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
    }
    
    .elementor-35500 .elementor-nav-menu .menu-item a,
    .elementor-36004 .elementor-nav-menu .menu-item a,
    [data-elementor-type="jet-menu"] .elementor-nav-menu .menu-item a {
        font-size: 13px !important;
        font-weight: 400 !important;
        color: #555 !important;
        padding: 5px 0 !important;
        display: block !important;
        text-decoration: none !important;
        transition: color 0.15s ease, padding-left 0.15s ease !important;
        letter-spacing: 0.2px !important;
        line-height: 1.5 !important;
    }
    
    .elementor-35500 .elementor-nav-menu .menu-item a:hover,
    .elementor-36004 .elementor-nav-menu .menu-item a:hover,
    [data-elementor-type="jet-menu"] .elementor-nav-menu .menu-item a:hover {
        color: #1a1a1a !important;
        padding-left: 5px !important;
    }
    
    /* Column spacing */
    .elementor-35500 .e-con-inner,
    .elementor-36004 .e-con-inner,
    [data-elementor-type="jet-menu"] .e-con-inner {
        gap: 60px !important;
    }
    
    .elementor-35500 .e-con.e-child,
    .elementor-36004 .e-con.e-child,
    [data-elementor-type="jet-menu"] .e-con.e-child {
        padding: 0 !important;
        gap: 0 !important;
    }
    
    /* Hide menu toggle icons */
    .elementor-35500 .elementor-menu-toggle,
    .elementor-36004 .elementor-menu-toggle,
    [data-elementor-type="jet-menu"] .elementor-menu-toggle {
        display: none !important;
    }
    
    /* Widget spacing - tighter */
    .elementor-35500 .elementor-widget-heading,
    .elementor-36004 .elementor-widget-heading,
    [data-elementor-type="jet-menu"] .elementor-widget-heading {
        margin-bottom: 0 !important;
    }
    
    .elementor-35500 .elementor-widget-nav-menu,
    .elementor-36004 .elementor-widget-nav-menu,
    [data-elementor-type="jet-menu"] .elementor-widget-nav-menu {
        margin-bottom: 20px !important;
    }
    
    /* Last nav menu no margin */
    .elementor-35500 .elementor-widget-nav-menu:last-child,
    .elementor-36004 .elementor-widget-nav-menu:last-child,
    [data-elementor-type="jet-menu"] .elementor-widget-nav-menu:last-child {
        margin-bottom: 0 !important;
    }
    
    /* Menu container reset */
    .elementor-35500 .elementor-nav-menu--main,
    .elementor-36004 .elementor-nav-menu--main,
    [data-elementor-type="jet-menu"] .elementor-nav-menu--main {
        padding: 0 !important;
    }
    
    .elementor-35500 .elementor-nav-menu,
    .elementor-36004 .elementor-nav-menu,
    [data-elementor-type="jet-menu"] .elementor-nav-menu {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    /* Container parent padding */
    .elementor-35500 .e-con.e-parent,
    .elementor-36004 .e-con.e-parent,
    [data-elementor-type="jet-menu"] .e-con.e-parent {
        padding: 0 !important;
    }
    </style>
    <script>
    jQuery(document).ready(function($) {
        // Cart icon click - open side cart
        $(document).on('click', '.decor-cart-icon', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $cart = $('.cart-widget-side');
            
            if ($cart.hasClass('wd-opened')) {
                // Close
                $cart.removeClass('wd-opened');
                $('body').removeClass('wd-side-hidden-opened');
                $('.wd-close-side').removeClass('wd-opened');
            } else {
                // Open
                $cart.addClass('wd-opened');
                $('body').addClass('wd-side-hidden-opened');
                $('.wd-close-side').addClass('wd-opened');
            }
        });
        
        // Update cart count after add to cart
        $(document.body).on('added_to_cart wc_fragments_refreshed', function() {
            var count = $('.wd-cart-number, .cart-count').first().text() || '0';
            count = parseInt(count) || 0;
            
            if (count > 0) {
                $('.decor-cart-count').text(count).show();
            } else {
                $('.decor-cart-count').hide();
            }
        });
        
        // Sticky gallery - transform approach (doesn't break layout)
        if ($('body').hasClass('single-product') && $(window).width() >= 992) {
            var $galleryInner = $('.woocommerce-product-gallery');
            var $galleryContainer = $('.product-images.wd-grid-col');
            
            if ($galleryInner.length && $galleryContainer.length) {
                var containerTop = $galleryContainer.offset().top;
                var headerHeight = 100;
                var maxScroll = 0;
                
                function updateMaxScroll() {
                    // Find the add to cart button or the end of product content
                    var $addToCart = $('.single_add_to_cart_button, .cart button[type="submit"]').last();
                    var $summary = $('.summary-inner, .product-summary, .entry-summary').first();
                    
                    var contentBottom = 0;
                    if ($addToCart.length) {
                        contentBottom = $addToCart.offset().top + $addToCart.outerHeight();
                    } else if ($summary.length) {
                        contentBottom = $summary.offset().top + $summary.outerHeight();
                    }
                    
                    var galleryHeight = $galleryInner.outerHeight() || 0;
                    maxScroll = Math.max(0, contentBottom - containerTop - galleryHeight);
                }
                
                // Delay to ensure DOM is ready
                setTimeout(updateMaxScroll, 500);
                
                $(window).on('scroll', function() {
                    var scrollTop = $(window).scrollTop();
                    var offset = scrollTop + headerHeight - containerTop;
                    
                    if (offset > 0 && offset < maxScroll) {
                        $galleryInner.css('transform', 'translateY(' + offset + 'px)');
                    } else if (offset >= maxScroll) {
                        $galleryInner.css('transform', 'translateY(' + maxScroll + 'px)');
                    } else {
                        $galleryInner.css('transform', 'translateY(0)');
                    }
                });
                
                $(window).on('resize', function() {
                    setTimeout(updateMaxScroll, 100);
                });
                
                // Recalculate when accordions open/close
                $(document).on('click', '.accordion-header, .attribute-panel', function() {
                    setTimeout(updateMaxScroll, 300);
                });
            }
        }
    });
    </script>
    <?php
}

/**
 * ===== CHECKOUT & USER EXPERIENCE IMPROVEMENTS =====
 */

/**
 * 6. Hide "New to Calene Atelier" for logged in users
 * Show only Sign Out option
 */
add_action('wp_head', 'decor_logged_in_user_styles');
function decor_logged_in_user_styles() {
    if (is_user_logged_in()) {
        ?>
        <style>
            /* Hide "New to Calene Atelier" and registration prompts for logged in users */
            .woocommerce-account .woocommerce-MyAccount-navigation-link--customer-logout ~ *,
            .new-to-calene,
            .register-prompt,
            .woocommerce-form-register,
            .u-column2.col-2 {
                /* Keep visible but hide registration */
            }
            
            /* Simplify header for logged in users */
            .logged-in .header-login-text,
            .logged-in .register-link {
                display: none !important;
            }
        </style>
        <?php
    }
}

/**
 * 7. Separate Billing and Shipping addresses in checkout
 * Ship to different address checked by default for B2B
 */
add_filter('woocommerce_ship_to_different_address_checked', '__return_true');

/**
 * Add note about billing vs shipping for designers
 */
add_action('woocommerce_before_checkout_shipping_form', 'decor_shipping_note');
function decor_shipping_note() {
    echo '<div class="shipping-note" style="background: #f8f5f0; padding: 15px; margin-bottom: 20px; border-left: 3px solid #c4a47c;">';
    echo '<strong>Note for Trade Members:</strong> Enter your client\'s shipping address below. Billing will be processed to your account.';
    echo '</div>';
}

/**
 * Add styling to make billing/shipping separation clearer
 */
add_action('wp_head', 'decor_checkout_styles');
function decor_checkout_styles() {
    if (!is_checkout()) return;
    ?>
    <style>
        /* Clear separation between billing and shipping */
        .woocommerce-billing-fields,
        .woocommerce-shipping-fields {
            background: #fff;
            padding: 30px;
            border: 1px solid #eee;
            margin-bottom: 30px;
        }
        
        .woocommerce-billing-fields h3,
        .woocommerce-shipping-fields h3,
        #ship-to-different-address {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #c4a47c;
        }
        
        /* Highlight shipping section */
        .woocommerce-shipping-fields {
            background: #faf9f7;
            border-color: #c4a47c;
        }
        
        #ship-to-different-address label {
            font-size: 16px;
            font-weight: 600;
        }
        
        /* Add icons */
        .woocommerce-billing-fields h3::before {
            content: '💳 ';
        }
        
        #ship-to-different-address label::before {
            content: '📦 ';
        }
    </style>
    <?php
}

/**
 * 8. Terms & Conditions checkbox required before checkout
 */
add_action('woocommerce_review_order_before_submit', 'decor_add_checkout_terms');
function decor_add_checkout_terms() {
    ?>
    <div class="checkout-terms-wrapper" style="margin-bottom: 20px;">
        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
            <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox" name="terms_agreed" id="terms_agreed" required>
            <span class="woocommerce-terms-and-conditions-checkbox-text">
                I have read and agree to the <a href="/terms/" target="_blank">Terms & Conditions</a> and <a href="/privacy-policy/" target="_blank">Privacy Policy</a>
            </span>
            <span class="required">*</span>
        </label>
    </div>
    <?php
}

add_action('woocommerce_checkout_process', 'decor_validate_checkout_terms');
function decor_validate_checkout_terms() {
    if (!isset($_POST['terms_agreed'])) {
        wc_add_notice(__('Please agree to the Terms & Conditions before placing your order.', 'decor'), 'error');
    }
}

/**
 * 9. Invoice email after order - WooCommerce handles this by default
 * Just ensure PDF invoices are attached (if plugin installed)
 */

/**
 * ===== CONTACT & INQUIRY FEATURES =====
 */

/**
 * 10 & 12. Add WhatsApp contact button (floating)
 */
add_action('wp_footer', 'decor_whatsapp_button');
function decor_whatsapp_button() {
    $whatsapp_number = '+1234567890'; // Replace with actual number
    ?>
    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp_number); ?>" 
       class="decor-whatsapp-btn" 
       target="_blank" 
       rel="noopener"
       title="Contact us on WhatsApp">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="#fff">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
        </svg>
    </a>
    <style>
        .decor-whatsapp-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 60px;
            height: 60px;
            background: #25D366;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4);
            z-index: 9999;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .decor-whatsapp-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.5);
        }
        
        /* Hide on mobile if cart is open */
        .wd-side-hidden-opened .decor-whatsapp-btn {
            display: none;
        }
    </style>
    <?php
}

/**
 * 11. Add "Inquire" button next to each product - Opens popup form
 */
add_action('woocommerce_after_add_to_cart_button', 'decor_add_inquire_button');
function decor_add_inquire_button() {
    global $product;
    ?>
    <button type="button" class="button alt decor-inquire-btn" 
            data-product-name="<?php echo esc_attr($product->get_name()); ?>"
            data-product-url="<?php echo esc_url(get_permalink($product->get_id())); ?>"
            data-product-image="<?php echo esc_url(wp_get_attachment_url($product->get_image_id())); ?>">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px; vertical-align: middle;">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
            <polyline points="22,6 12,13 2,6"></polyline>
        </svg>
        Inquire
    </button>
    <?php
}

/**
 * Also add Inquire button in product loop (shop page)
 */
add_action('woocommerce_after_shop_loop_item', 'decor_add_inquire_button_loop', 15);
function decor_add_inquire_button_loop() {
    global $product;
    echo '<button type="button" class="button inquire-loop-btn decor-inquire-btn" 
            data-product-name="' . esc_attr($product->get_name()) . '"
            data-product-url="' . esc_url(get_permalink($product->get_id())) . '"
            data-product-image="' . esc_url(wp_get_attachment_url($product->get_image_id())) . '"
            style="font-size: 11px; padding: 8px 12px; margin-top: 5px;">Inquire</button>';
}

/**
 * Add Inquire popup form and styles
 */
add_action('wp_footer', 'decor_inquire_popup');
function decor_inquire_popup() {
    ?>
    <!-- Inquire Popup -->
    <div class="inquire-popup-overlay" style="display: none;">
        <div class="inquire-popup">
            <button class="inquire-close">&times;</button>
            
            <div class="inquire-product-info">
                <img src="" alt="" class="inquire-product-image">
                <div class="inquire-product-details">
                    <h3 class="inquire-product-name"></h3>
                    <a href="#" class="inquire-product-link" target="_blank">View Product</a>
                </div>
            </div>
            
            <h2>Inquire About This Product</h2>
            <p>Have questions? We'd love to help. Fill out the form below and we'll get back to you shortly.</p>
            
            <form class="inquire-form" id="inquire-form">
                <input type="hidden" name="product_name" class="inquire-hidden-product">
                <input type="hidden" name="product_url" class="inquire-hidden-url">
                <input type="hidden" name="action" value="submit_product_inquiry">
                <?php wp_nonce_field('product_inquiry', 'inquiry_nonce'); ?>
                
                <div class="inquire-form-row">
                    <div class="inquire-form-group">
                        <label for="inquire_name">Your Name <span>*</span></label>
                        <input type="text" name="name" id="inquire_name" required>
                    </div>
                </div>
                
                <div class="inquire-form-row">
                    <div class="inquire-form-group">
                        <label for="inquire_email">Email Address <span>*</span></label>
                        <input type="email" name="email" id="inquire_email" required>
                    </div>
                </div>
                
                <div class="inquire-form-row">
                    <div class="inquire-form-group">
                        <label for="inquire_phone">Phone Number</label>
                        <input type="tel" name="phone" id="inquire_phone">
                    </div>
                </div>
                
                <div class="inquire-form-row">
                    <div class="inquire-form-group">
                        <label for="inquire_message">Your Message <span>*</span></label>
                        <textarea name="message" id="inquire_message" rows="4" required placeholder="Tell us what you'd like to know about this product..."></textarea>
                    </div>
                </div>
                
                <button type="submit" class="inquire-submit-btn">Send Inquiry</button>
                <div class="inquire-form-message"></div>
            </form>
        </div>
    </div>
    
    <style>
        /* Inquire Button */
        .decor-inquire-btn {
            background: transparent !important;
            border: 1px solid #c4a47c !important;
            color: #c4a47c !important;
            margin-left: 10px !important;
            cursor: pointer;
        }
        .decor-inquire-btn:hover {
            background: #c4a47c !important;
            color: #fff !important;
        }
        
        /* Inquire Popup Overlay */
        .inquire-popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        /* Inquire Popup */
        .inquire-popup {
            background: #fff;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 40px;
            position: relative;
            border-radius: 4px;
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .inquire-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            font-size: 28px;
            color: #999;
            cursor: pointer;
            line-height: 1;
        }
        .inquire-close:hover { color: #333; }
        
        /* Product Info */
        .inquire-product-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .inquire-product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .inquire-product-name {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 5px 0;
            color: #1a1a1a;
        }
        
        .inquire-product-link {
            font-size: 12px;
            color: #c4a47c;
        }
        
        /* Form */
        .inquire-popup h2 {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 10px 0;
            color: #1a1a1a;
        }
        
        .inquire-popup > p {
            font-size: 14px;
            color: #666;
            margin: 0 0 25px 0;
        }
        
        .inquire-form-row {
            margin-bottom: 15px;
        }
        
        .inquire-form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }
        
        .inquire-form-group label span {
            color: #c4a47c;
        }
        
        .inquire-form-group input,
        .inquire-form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .inquire-form-group input:focus,
        .inquire-form-group textarea:focus {
            outline: none;
            border-color: #c4a47c;
        }
        
        .inquire-submit-btn {
            width: 100%;
            padding: 15px;
            background: #c4a47c;
            color: #fff;
            border: none;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }
        
        .inquire-submit-btn:hover {
            background: #b39369;
        }
        
        .inquire-submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .inquire-form-message {
            margin-top: 15px;
            padding: 12px;
            border-radius: 4px;
            text-align: center;
            display: none;
        }
        
        .inquire-form-message.success {
            display: block;
            background: #d4edda;
            color: #155724;
        }
        
        .inquire-form-message.error {
            display: block;
            background: #f8d7da;
            color: #721c24;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        // Open popup
        $(document).on('click', '.decor-inquire-btn', function(e) {
            e.preventDefault();
            
            var productName = $(this).data('product-name');
            var productUrl = $(this).data('product-url');
            var productImage = $(this).data('product-image');
            
            // Fill popup with product info
            $('.inquire-product-name').text(productName);
            $('.inquire-product-link').attr('href', productUrl);
            $('.inquire-product-image').attr('src', productImage);
            $('.inquire-hidden-product').val(productName);
            $('.inquire-hidden-url').val(productUrl);
            
            // Show popup
            $('.inquire-popup-overlay').fadeIn(200);
            $('body').css('overflow', 'hidden');
        });
        
        // Close popup
        $(document).on('click', '.inquire-close, .inquire-popup-overlay', function(e) {
            if (e.target === this) {
                $('.inquire-popup-overlay').fadeOut(200);
                $('body').css('overflow', '');
            }
        });
        
        // Submit form
        $('#inquire-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('.inquire-submit-btn');
            var $msg = $form.find('.inquire-form-message');
            
            $btn.prop('disabled', true).text('Sending...');
            $msg.removeClass('success error').hide();
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    $btn.prop('disabled', false).text('Send Inquiry');
                    
                    if (response.success) {
                        $msg.addClass('success').text('Thank you! Your inquiry has been sent. We\'ll get back to you soon.').show();
                        $form[0].reset();
                        
                        // Close popup after 3 seconds
                        setTimeout(function() {
                            $('.inquire-popup-overlay').fadeOut(200);
                            $('body').css('overflow', '');
                            $msg.hide();
                        }, 3000);
                    } else {
                        $msg.addClass('error').text(response.data || 'An error occurred. Please try again.').show();
                    }
                },
                error: function() {
                    $btn.prop('disabled', false).text('Send Inquiry');
                    $msg.addClass('error').text('An error occurred. Please try again.').show();
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Handle inquiry form submission
 */
add_action('wp_ajax_submit_product_inquiry', 'decor_handle_product_inquiry');
add_action('wp_ajax_nopriv_submit_product_inquiry', 'decor_handle_product_inquiry');
function decor_handle_product_inquiry() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['inquiry_nonce'], 'product_inquiry')) {
        wp_send_json_error('Security check failed.');
    }
    
    // Get form data
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $message = sanitize_textarea_field($_POST['message']);
    $product_name = sanitize_text_field($_POST['product_name']);
    $product_url = esc_url($_POST['product_url']);
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($message)) {
        wp_send_json_error('Please fill in all required fields.');
    }
    
    if (!is_email($email)) {
        wp_send_json_error('Please enter a valid email address.');
    }
    
    // Prepare email
    $to = 'info@calene-atelier.com'; // Change to actual email
    $subject = 'Product Inquiry: ' . $product_name;
    
    $body = "New product inquiry received:\n\n";
    $body .= "Product: " . $product_name . "\n";
    $body .= "Product URL: " . $product_url . "\n\n";
    $body .= "From: " . $name . "\n";
    $body .= "Email: " . $email . "\n";
    if ($phone) {
        $body .= "Phone: " . $phone . "\n";
    }
    $body .= "\nMessage:\n" . $message . "\n";
    
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>'
    );
    
    // Send email
    $sent = wp_mail($to, $subject, $body, $headers);
    
    if ($sent) {
        wp_send_json_success('Inquiry sent successfully.');
    } else {
        wp_send_json_error('Failed to send inquiry. Please try again.');
    }
}