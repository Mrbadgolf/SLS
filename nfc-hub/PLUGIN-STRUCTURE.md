# NFC Hub Plugin Structure

## Complete File Tree

```
nfc-hub/
├── nfc-hub.php                 # Main plugin file (entry point)
├── uninstall.php               # Cleanup on uninstall
├── README.md                   # Comprehensive documentation + security
├── INSTALLATION.md             # Installation & testing guide
├── PLUGIN-STRUCTURE.md         # This file
│
├── includes/                   # Core PHP classes
│   ├── class-cpt.php          # Custom Post Type registration
│   ├── class-rewrite.php      # URL routing (/nfc/<slug>)
│   ├── class-meta.php         # Meta fields & admin metabox
│   ├── class-db.php           # Database operations
│   ├── class-rest-public.php  # Public tracking endpoint
│   ├── class-rest-admin.php   # Admin analytics endpoints
│   ├── class-admin-ui.php     # Analytics dashboard
│   ├── class-elementor-init.php # Elementor integration loader
│   │
│   └── elementor/             # Elementor components
│       ├── dynamic-tags/      # Dynamic Tags (Elementor Pro)
│       │   ├── action-url.php
│       │   ├── action-label.php
│       │   └── action-enabled.php
│       │
│       └── widgets/           # Elementor Widgets
│           ├── action-button.php
│           └── action-list.php
│
└── assets/                    # Front-end assets
    ├── js/                    # JavaScript files
    │   ├── tracker.js        # Front-end event tracking
    │   └── admin.js          # Admin dashboard functionality
    │
    └── css/                   # Stylesheets
        └── admin.css         # Admin dashboard styles
```

## File Descriptions

### Core Files

**nfc-hub.php**
- Main plugin file with header comment
- Defines constants (NFCHUB_VERSION, paths, etc.)
- Defines NFCHUB_ALLOWED_ACTIONS constant
- Loads all class dependencies
- Initializes all components
- Registers activation/deactivation hooks
- Enqueues front-end tracking script

**uninstall.php**
- Drops custom database table
- Deletes all nfchub_page posts
- Removes all plugin meta data
- Cleans up options

### Core Classes (includes/)

**class-cpt.php**
- Registers 'nfchub_page' custom post type
- Adds Elementor support
- Defines action configuration (labels, icons, defaults)

**class-rewrite.php**
- Adds rewrite rule for /nfc/<slug>
- Adds custom query var
- Handles template redirect
- Sets up proper WordPress query

**class-meta.php**
- Registers admin metabox
- Renders action configuration UI
- Handles save with nonce verification
- Sanitizes all inputs (esc_url_raw, sanitize_text_field)
- Provides helper methods (get_meta, update_meta)
- get_actions_data() returns enabled actions
- is_action_enabled() checks if action can be tracked

**class-db.php**
- Creates wp_nfchub_events table on activation
- insert_event() with prepared statements
- hash_ip() for privacy (SHA-256 with salt)
- Query methods: get_recent_events, get_summary, get_top_actions, get_top_pages, get_timeseries
- Rate limiting helpers: count_events_by_ip, count_events_by_page

**class-rest-public.php**
- Registers POST /wp-json/nfchub/v1/event
- Public endpoint (no auth required)
- Validates: page exists, slug matches, action enabled
- Rate limiting: 30/5min per IP, 300/1hr per page
- Payload size limit: 4KB
- Generic error messages (no enumeration)
- Never accepts URLs from client

**class-rest-admin.php**
- Registers GET /wp-json/nfchub/v1/admin/summary
- Registers GET /wp-json/nfchub/v1/admin/recent
- Registers GET /wp-json/nfchub/v1/admin/timeseries
- All require manage_options capability
- Returns sanitized, safe data (no IP/user agent by default)

**class-admin-ui.php**
- Adds "Analytics" submenu under NFC Pages
- Renders analytics dashboard HTML
- Enqueues admin.js and admin.css
- Localizes API URL and nonce for JavaScript

**class-elementor-init.php**
- Checks if Elementor is active
- Creates "NFC Hub" widget category
- Registers widgets on elementor/widgets/register
- Registers dynamic tags on elementor/dynamic_tags/register

### Elementor Components

**dynamic-tags/action-url.php**
- Returns URL for selected action
- Used in URL fields (buttons, links)

**dynamic-tags/action-label.php**
- Returns label for selected action
- Falls back to default label if empty
- Used in text fields

**dynamic-tags/action-enabled.php**
- Returns "yes" or "no"
- Used for conditional display

**widgets/action-button.php**
- Single action button widget
- Controls: action selector, custom label, new tab toggle, show icon
- Style controls: typography, colors, borders, padding, alignment
- Only renders if action is enabled
- Shows placeholder in editor if disabled
- Adds data-nfchub-action attribute for tracking

**widgets/action-list.php**
- Displays all enabled actions
- Controls: layout (vertical/horizontal/grid), columns, show icons, new tab
- Style controls: typography, colors, borders, spacing
- Iterates through get_actions_data()
- Shows placeholder if no actions enabled

### Assets

**assets/js/tracker.js**
- Auto-tracks page_view on load
- Tracks clicks on [data-nfchub-action] elements
- Extracts UTM parameters from URL
- Uses sendBeacon (preferred) or fetch with keepalive
- Non-blocking, never delays navigation
- Minimal security exposure (no sensitive data)

**assets/js/admin.js**
- Fetches summary via AJAX
- Fetches recent events via AJAX
- Updates DOM with data
- Auto-refreshes every 10 seconds
- Handles page filter
- Formats dates, action keys, referrers
- Escapes HTML for security

**assets/css/admin.css**
- Styles analytics dashboard
- Card-based layout with CSS Grid
- Responsive breakpoints
- Stat display styling
- Table styling
- Metabox field styling
- NFC URL display box
- Animations (fadeIn)

## Key Security Features

### Input Validation & Sanitization
- ✅ All URLs: `esc_url_raw()` with http/https only
- ✅ All text: `sanitize_text_field()`
- ✅ All keys: `sanitize_key()`
- ✅ All integers: `absint()`
- ✅ All SQL: `$wpdb->prepare()`

### Output Escaping
- ✅ HTML: `esc_html()`
- ✅ Attributes: `esc_attr()`
- ✅ URLs: `esc_url()`
- ✅ JavaScript: `esc_js()` / `wp_localize_script()`

### Access Control
- ✅ Metabox save: `wp_verify_nonce()` + `current_user_can('edit_post')`
- ✅ Admin endpoints: `current_user_can('manage_options')`
- ✅ Public endpoint: Action allowlist + enabled check

### Rate Limiting
- ✅ 30 events per IP per 5 minutes
- ✅ 300 events per page per hour
- ✅ Payload size: 4KB max

### Privacy
- ✅ IP addresses hashed with NONCE_SALT
- ✅ User agents truncated to 500 chars
- ✅ No raw IPs stored
- ✅ Admin endpoints don't expose sensitive data by default

### Anti-Enumeration
- ✅ Generic error messages
- ✅ No distinction between invalid page and disabled action
- ✅ No success/failure details in responses

## Database Schema

### Table: wp_nfchub_events

```sql
CREATE TABLE wp_nfchub_events (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    occurred_at DATETIME NOT NULL,
    page_id BIGINT(20) UNSIGNED NOT NULL,
    page_slug VARCHAR(200) NOT NULL,
    action_key VARCHAR(64) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    user_agent TEXT,
    referrer TEXT,
    utm_source VARCHAR(255),
    utm_medium VARCHAR(255),
    utm_campaign VARCHAR(255),
    utm_term VARCHAR(255),
    utm_content VARCHAR(255),
    meta_json LONGTEXT,
    PRIMARY KEY (id),
    KEY occurred_at (occurred_at),
    KEY page_id (page_id),
    KEY page_slug (page_slug),
    KEY action_key (action_key),
    KEY ip_hash (ip_hash)
);
```

### Post Meta (prefix: _nfchub_)

For each action type:
- `_nfchub_enable_{action}` - Boolean
- `_nfchub_{action}_url` - String (URL)
- `_nfchub_{action}_label` - String (custom label)

Action types:
- google_review
- facebook_review
- facebook_follow
- payment_trust
- payment_consult
- payment_invoice

## WordPress Hooks Used

### Activation/Deactivation
- `register_activation_hook` - Creates DB table, flushes rewrites
- `register_deactivation_hook` - Flushes rewrites

### Initialization
- `plugins_loaded` - Initializes all components
- `init` - Registers CPT, rewrite rules

### Admin
- `add_meta_boxes` - Adds action metabox
- `save_post_nfchub_page` - Saves meta with validation
- `admin_menu` - Adds analytics submenu
- `admin_enqueue_scripts` - Loads admin assets

### Front-end
- `wp_enqueue_scripts` - Loads tracker.js (nfchub_page only)
- `template_redirect` - Handles /nfc/ routing

### REST API
- `rest_api_init` - Registers endpoints

### Elementor
- `elementor/init` - Creates widget category
- `elementor/widgets/register` - Registers widgets
- `elementor/dynamic_tags/register` - Registers dynamic tags

### Query
- `query_vars` - Adds custom query var
- `rewrite_rules_array` - Adds custom rewrite rule

## Constants Defined

```php
NFCHUB_VERSION          // Plugin version (1.0.0)
NFCHUB_PLUGIN_FILE      // Full path to main plugin file
NFCHUB_PLUGIN_DIR       // Plugin directory path
NFCHUB_PLUGIN_URL       // Plugin URL
NFCHUB_PLUGIN_BASENAME  // Plugin basename
NFCHUB_ALLOWED_ACTIONS  // Array of allowed action keys
```

## Quick Reference: Common Tasks

### Add New Action Type

1. Edit `class-cpt.php`, method `get_action_config()`
2. Add to return array with label, action_key, icon, default_label
3. Add action_key to NFCHUB_ALLOWED_ACTIONS in nfc-hub.php

### Adjust Rate Limits

Edit `class-rest-public.php` constants:
```php
const RATE_LIMIT_IP = 30;           // Events per IP
const RATE_LIMIT_IP_WINDOW = 5;     // Minutes
const RATE_LIMIT_PAGE = 300;        // Events per page
const RATE_LIMIT_PAGE_WINDOW = 1;   // Hours
```

### Change Auto-Refresh Interval

Edit `assets/js/admin.js`:
```javascript
setInterval(function() {
    // ...
}, 10000); // Change 10000 (10 seconds) to desired milliseconds
```

### Add Custom Styling

Edit `assets/css/admin.css` for admin styles.
For front-end, use Elementor's style controls or add custom CSS via Elementor or theme.

## Dependencies

### Required
- WordPress 5.8+
- PHP 7.4+
- Elementor 3.0+

### Optional
- Elementor Pro 3.0+ (for Dynamic Tags)

### WordPress Functions Used
- Custom Post Types API
- Rewrite API
- REST API
- Post Meta API
- Options API
- Database API ($wpdb)
- Nonce functions
- Capability checks
- Sanitization functions
- Escaping functions

### External Libraries
- None! Pure WordPress + Elementor APIs

## Performance Considerations

- Database queries use indexes on frequently queried columns
- Tracking uses sendBeacon (non-blocking)
- Admin dashboard uses AJAX (no page reload)
- CSS/JS minification recommended for production
- Consider CDN for static assets
- Implement data retention policy (auto-delete old events)

## Extensibility

This plugin is designed to be extended:

- Add new action types easily
- Customize rate limits
- Add custom analytics reports
- Integrate with external APIs
- Add export functionality
- Implement email notifications
- Add custom Dynamic Tags
- Create additional Elementor widgets

## License

GPL v2 or later (as specified in nfc-hub.php)
