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