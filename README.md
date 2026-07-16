# Calene Atelier - WordPress Theme Customization

Premium furniture e-commerce website built on WordPress with WooCommerce and Woodmart theme.

## 🏗️ Project Structure

```
decor/
├── functions.php              # Main theme functions & customizations
├── style.css                  # Child theme styles
├── assets/
│   ├── css/
│   │   └── fabric-variations.css   # Fabric selector & cart styling
│   └── js/
│       ├── attribute-pricing.js    # Dynamic pricing system
│       └── fabric-variations.js    # Fabric selection functionality
├── includes/
│   ├── class-attribute-pricing.php # Attribute-based pricing system
│   ├── class-fabric-variations.php # Fabric variations handler
│   └── class-product-specs.php     # Product specifications module
└── woocommerce-b2b/
    ├── class-wc-b2b.php            # B2B functionality core
    ├── assets/css/b2b.css          # B2B styling
    └── templates/
        ├── trade-portal-login.php  # Trade partner login page
        └── dealer-registration.php # Dealer registration form
```

## ✨ Features

### 🛒 Custom Cart System
- Custom cart icon shortcode: `[woodmart_cart_icon]`
- Elegant side cart with professional styling
- Real-time cart count updates

### 🎨 Fabric Variations System
- West Elm-style fabric selector
- Accordion-based attribute selection
- Hover popups with fabric details
- Dynamic pricing based on selections

### 💼 B2B / Trade Portal
- Dealer registration system
- Trade partner login page
- Price hiding for guests
- Role-based access control

### 🎯 Conditional Display
- `.only-login` - Elements visible only to logged-in users
- `.only-guest` - Elements visible only to guests
- Price range filter hidden from guests

### 📱 Responsive Design
- Mobile-optimized layouts
- Sticky product gallery on desktop
- Professional mega menu styling

## 🔧 Shortcodes

| Shortcode | Description |
|-----------|-------------|
| `[woodmart_cart_icon]` | Custom cart icon with count badge |
| `[trade_portal_login]` | Trade partner login form |
| `[dealer_registration_form]` | Dealer registration form |

## 🎨 CSS Classes

| Class | Usage |
|-------|-------|
| `.only-login` | Show element only to logged-in users |
| `.only-guest` | Show element only to guests |

## ⚙️ Configuration

### Theme Requirements
- WordPress 6.0+
- WooCommerce 8.0+
- Woodmart Theme 8.0+
- Elementor Pro (for mega menus)

### Recommended Plugins
- JetMenu (for mega menus)
- WooCommerce

## 📝 Customization Notes

### Mega Menu Styling
Mega menus are styled for Elementor IDs `35500` (Interior) and `36004` (Exterior).

### Price Display
Prices are hidden from guests. Only logged-in dealers/administrators can see prices.

### Side Cart
The side cart uses Woodmart's built-in cart widget with custom styling applied via `fabric-variations.css`.

## 🚀 Deployment

1. Upload to WordPress child theme directory
2. Activate the child theme
3. Configure WooCommerce settings
4. Set up B2B user roles

## 📄 License

Private - Calene Atelier © 2024
