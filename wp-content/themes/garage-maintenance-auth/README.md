# TrackMyWrench WordPress Theme

A custom WordPress theme for the TrackMyWrench garage maintenance tracking application. Provides user authentication, subscription management, and seamless integration with the GarageMinder PHP app.

## Features

- **Frontend Authentication**: Custom login, register, forgot password, and reset password pages
- **Subscription Tiers**: Free, Paid, and Fleet plans with configurable limits
- **Simple Membership Plugin Integration**: Ready-to-use with Stripe payments
- **Membership Adapter Pattern**: Easy migration to other membership plugins
- **Light/Dark Theme**: User preference saved across devices
- **Mobile Responsive**: Touch-friendly design with hamburger navigation
- **Security**: Non-admin users blocked from wp-admin
- **REST API**: Endpoints for subdomain/cross-origin support
- **AJAX Forms**: Smooth form submissions without page reloads

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Simple Membership Plugin (recommended) or compatible membership plugin
- GarageMinder PHP application (optional, for full integration)

## Installation

### 1. Upload Theme

Upload the `garage-maintenance-auth` folder to `/wp-content/themes/`

### 2. Activate Theme

Go to **Appearance > Themes** and activate "TrackMyWrench"

### 3. Install Simple Membership Plugin

1. Go to **Plugins > Add New**
2. Search for "Simple Membership"
3. Install and activate

### 4. Configure Simple Membership

1. Go to **WP Membership > Settings**
2. Enable registration
3. Set up Stripe payments in **Payment Settings**

### 5. Create Membership Levels

Create three membership levels in **WP Membership > Membership Levels**:

| Level Name | Suggested ID | Role |
|------------|--------------|------|
| Free | 1 | Subscriber |
| Paid | 2 | Subscriber |
| Fleet | 3 | Subscriber |

### 6. Configure Theme Settings

Go to **Settings > TrackMyWrench** and configure:

**General Tab:**
- App URL: Your GarageMinder app URL (e.g., `https://example.com/garage/`)
- Default Theme: Dark (recommended)
- Membership Plugin: Simple Membership

**Level Mapping Tab:**
- Free Level ID: (from Simple Membership)
- Paid Level ID: (from Simple Membership)  
- Fleet Level ID: (from Simple Membership)

**Subscription Limits Tab:**
Configure limits for each tier as needed.

### 7. Create Required Pages

Create the following pages and assign their templates:

| Page Title | Slug | Template |
|------------|------|----------|
| Login | login | Login |
| Register | register | Register |
| Forgot Password | forgot-password | Forgot Password |
| Reset Password | reset-password | Reset Password |
| My Profile | my-profile | My Profile |
| Pricing | pricing | Pricing |
| Membership Renewal | renewal | Membership Renewal |
| Terms of Service | terms | Terms of Service |
| Privacy Policy | privacy | Privacy Policy |
| Logged Out | logout | Logout |

### 8. Set Homepage

Go to **Settings > Reading** and set:
- Your homepage displays: A static page
- Homepage: (select your home page or leave as posts)

### 9. Create Navigation Menu

Go to **Appearance > Menus**:
1. Create a menu called "Primary Menu"
2. Add pages: Home, Pricing
3. Assign to "Primary Menu" location

## GarageMinder Integration

### Option A: Same Server (Subdirectory)

If GarageMinder is at `example.com/garage/`:

1. Set App URL to `/garage/` in theme settings
2. Ensure WordPress path in GarageMinder `config.php` is correct

### Option B: Subdomain

If GarageMinder is at `app.example.com`:

1. Set App URL to `https://app.example.com/` in theme settings
2. The theme's REST API handles CORS automatically

### Adding Subscription Integration to GarageMinder

Copy `garageminder-integration/wp-subscription-integration.php` to your GarageMinder folder and add to `config.php`:

```php
require_once(__DIR__ . '/wp-subscription-integration.php');
```

Then use the subscription functions:

```php
// Get user's tier
$tier = gm_get_user_subscription_tier();

// Check if user can add vehicle
if (!gm_can_add_vehicle($pdo)) {
    echo "Vehicle limit reached";
}

// Check feature access
if (gm_user_can('recalls')) {
    // Show recall info
}

// Get remaining counts
$counts = gm_get_remaining_counts($pdo);
```

## Subscription Tiers

### Free
- 2 vehicles
- 50 total entries
- 3 templates
- No attachments
- No recalls
- No export

### Paid ($9/month)
- 10 vehicles
- Unlimited entries
- 15 templates
- 2 attachments per entry
- Recall alerts
- CSV & PDF export
- Email support

### Fleet ($29/month)
- Unlimited vehicles
- Unlimited entries
- Unlimited templates
- 5 attachments per entry
- Recall alerts
- Bulk export + API
- Team members (coming soon)
- Phone support

## Theme Customization

### Colors

Edit `/assets/css/variables.css` to change the color scheme:

```css
:root {
    --tmw-accent: #38bdf8;        /* Primary brand color */
    --tmw-bg-primary: #0f172a;    /* Main background */
    --tmw-text-primary: #f1f5f9;  /* Main text color */
}
```

### Logo

Replace `/assets/images/logo.png` with your logo (recommended: 256x256px)

### Fonts

The theme uses Inter from Google Fonts. To change, edit `/inc/enqueue.php`:

```php
$google_fonts_url = 'https://fonts.googleapis.com/css2?family=YourFont:wght@400;500;600;700&display=swap';
```

## File Structure

```
garage-maintenance-auth/
├── assets/
│   ├── css/
│   │   ├── variables.css      # CSS custom properties
│   │   ├── base.css           # Reset & typography
│   │   ├── components.css     # Buttons, forms, cards
│   │   ├── layout.css         # Containers, grids
│   │   ├── header.css         # Header & navigation
│   │   ├── footer.css         # Footer
│   │   ├── responsive.css     # Media queries
│   │   ├── admin.css          # WP admin styles
│   │   └── pages/
│   │       ├── front-page.css # Landing page
│   │       ├── auth.css       # Login/register pages
│   │       └── pricing.css    # Pricing page
│   ├── js/
│   │   ├── main.js            # Core JavaScript
│   │   ├── theme-toggle.js    # Light/dark toggle
│   │   ├── mobile-nav.js      # Mobile menu
│   │   ├── forms.js           # Form validation
│   │   └── admin.js           # WP admin scripts
│   └── images/
│       └── logo.png           # Theme logo
├── inc/
│   ├── setup.php              # Theme setup
│   ├── enqueue.php            # Scripts & styles
│   ├── admin-settings.php     # Admin settings page
│   ├── subscription.php       # Subscription logic
│   ├── membership-adapter.php # Adapter interface
│   ├── security.php           # Security & access
│   ├── rest-api.php           # REST API endpoints
│   ├── ajax-handlers.php      # AJAX handlers
│   ├── template-functions.php # Helper functions
│   └── adapters/
│       ├── simple-membership.php
│       └── user-meta.php
├── templates/
│   ├── template-login.php
│   ├── template-register.php
│   ├── template-forgot-password.php
│   ├── template-reset-password.php
│   ├── template-logout.php
│   ├── template-profile.php
│   ├── template-pricing.php
│   ├── template-renewal.php
│   ├── template-terms.php
│   └── template-privacy.php
├── functions.php
├── header.php
├── footer.php
├── front-page.php
├── index.php
├── style.css
├── screenshot.png
└── README.md
```

## REST API Endpoints

All endpoints use namespace `tmw/v1`:

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/user/current` | GET | Yes | Current user data |
| `/subscription` | GET | Yes | Subscription info |
| `/subscription/limits` | GET | Yes | User's limits |
| `/subscription/tiers` | GET | No | All tier configs |
| `/user/theme` | POST | Yes | Update theme preference |
| `/auth/check` | GET | No | Auth status check |
| `/config` | GET | No | App configuration |

## Troubleshooting

### Login not working

1. Check page has "Login" template assigned
2. Verify JavaScript is loading (check console for errors)
3. Clear browser cache

### Redirects not working

1. Go to **Settings > Permalinks** and click Save (flushes rewrite rules)
2. Check `.htaccess` is writable

### Simple Membership levels not mapping

1. Verify level IDs in **WP Membership > Membership Levels**
2. Match IDs in theme settings **Level Mapping** tab
3. Clear any caching plugins

### CORS errors on subdomain

1. Check App URL is set correctly with full URL including protocol
2. Verify REST API endpoints are accessible
3. Check server CORS headers

## Support

For issues with this theme, please create an issue on GitHub or contact support@trackmywrench.com.

## License

This theme is proprietary software. All rights reserved.

---

Made with ♥ for TrackMyWrench
