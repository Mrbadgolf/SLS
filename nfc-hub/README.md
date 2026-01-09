# NFC Hub - Elementor-Editable NFC Landing Pages with Secure Analytics

## Overview

NFC Hub is a WordPress plugin that lets you create fully customizable NFC landing pages using Elementor Pro, while managing action links (reviews, payments, social) from WordPress admin with secure real-time analytics tracking.

**Version:** 1.0.0
**Requires WordPress:** 5.8+
**Requires PHP:** 7.4+
**Requires Elementor:** 3.0+
**Requires Elementor Pro:** 3.0+ (for Dynamic Tags)

## Features

- ✅ **Custom Post Type** for NFC landing pages
- ✅ **Custom URL Routing** (`/nfc/<slug>`)
- ✅ **Full Elementor Integration** (widgets + dynamic tags)
- ✅ **Secure Analytics Tracking** with rate limiting
- ✅ **Real-time Dashboard** with auto-refresh
- ✅ **Admin Metabox** for managing actions
- ✅ **No WiFi functionality** (as specified)

## Supported Actions

1. **Google Review** - Direct link to Google Business review page
2. **Facebook Review** - Direct link to Facebook review page
3. **Facebook Follow** - Direct link to Facebook page
4. **Trust Payment** - Link to trust payment processor
5. **Consultation Payment** - Link to consultation payment
6. **Invoice Payment** - Link to invoice payment

## Installation

### 1. Upload Plugin

```bash
# Upload the nfc-hub folder to wp-content/plugins/
# OR install via WordPress admin (Plugins > Add New > Upload)
```

### 2. Activate Plugin

```bash
# Navigate to Plugins page in WordPress admin
# Click "Activate" on NFC Hub
```

### 3. Verify Requirements

- Ensure Elementor and Elementor Pro are installed and activated
- PHP 7.4+ and WordPress 5.8+ required

## Usage Guide

### Creating an NFC Landing Page

1. **Create New Page**
   - Navigate to **NFC Pages > Add New**
   - Enter a title (e.g., "John's Barber Shop")
   - The slug will be used in the NFC URL

2. **Configure Actions**
   - Scroll to **NFC Hub Actions** metabox
   - For each action you want to enable:
     - ✅ Check "Enable this action"
     - Enter the destination URL
     - (Optional) Enter a custom button label
   - Click **Publish**

3. **Design with Elementor**
   - Click **Edit with Elementor**
   - Design your page freely:
     - Add images, logos, text, sections
     - Use any Elementor widgets
     - Customize colors, fonts, spacing
   - Add NFC Hub widgets:
     - **NFCHub Action Button** - Single action button
     - **NFCHub Action List** - All enabled actions
   - Publish when ready

4. **Encode NFC Tag**
   - After publishing, copy the NFC Tag URL shown in the metabox
   - Use an NFC writing app to encode this URL to your NFC tag
   - Example URL: `https://yourdomain.com/nfc/johns-barber-shop`

5. **Test**
   - Tap the NFC tag with your phone
   - Verify the page loads correctly
   - Click actions to ensure tracking works
   - Check analytics in **NFC Pages > Analytics**

### Using Elementor Widgets

#### NFCHub Action Button Widget

Displays a single action button.

**Controls:**
- **Action** - Select which action to display
- **Custom Label** - Override the default label
- **Open in New Tab** - Link target (default: yes)
- **Show Icon** - Display Font Awesome icon
- **Style** - Typography, colors, borders, padding

#### NFCHub Action List Widget

Displays all enabled actions.

**Controls:**
- **Layout** - Vertical, Horizontal, or Grid
- **Columns** - Number of columns (for grid layout)
- **Show Icons** - Display Font Awesome icons
- **Open in New Tab** - Link target (default: yes)
- **Style** - Typography, colors, borders, spacing

### Using Elementor Dynamic Tags

Dynamic tags allow you to insert action data anywhere in Elementor.

**Available Tags (in "NFC Hub" group):**

1. **Action URL** - Returns the URL for a specific action
2. **Action Label** - Returns the label for a specific action
3. **Action Enabled** - Returns "yes" or "no" (for conditional display)

**Example Use Case:**
- Add a Button widget
- Set the link to **Dynamic** > **NFC Hub** > **Action URL**
- Select the action
- The button will automatically use the configured URL

## Analytics Dashboard

Navigate to **NFC Pages > Analytics** to view:

- **Summary Statistics** (7-day and 30-day totals)
  - Total events
  - Unique pages
  - Unique visitors

- **Top Actions** (last 30 days)
- **Top Pages** (last 30 days)
- **Recent Events** (last 50)
  - Date/time
  - Page
  - Action
  - Referrer
  - UTM source

**Auto-refresh:** Data refreshes every 10 seconds automatically.

**Filtering:** Use the page filter dropdown to view data for a specific page.

## Security Documentation

### Threat Model

NFC Hub is designed with a security-first approach. We've identified and mitigated these potential threats:

#### 1. **Page Enumeration**
**Threat:** Attackers attempt to discover valid page slugs by probing the tracking endpoint.

**Mitigation:**
- Generic error messages (no distinction between invalid page and disabled action)
- No enumeration feedback in API responses

#### 2. **Database Spam / Resource Exhaustion**
**Threat:** Attackers flood the tracking endpoint to fill the database or exhaust server resources.

**Mitigation:**
- **Rate Limiting:**
  - 30 events per IP per 5 minutes
  - 300 events per page per hour
- **Payload Size Limit:** 4KB maximum
- **IP Hashing:** IPs are hashed with salt, never stored raw

#### 3. **Malicious Data Injection**
**Threat:** Attackers inject XSS, SQL injection, or other malicious payloads.

**Mitigation:**
- **Strict Input Validation:**
  - Action keys validated against fixed allowlist
  - Page ID and slug must match existing published page
  - All inputs sanitized via WordPress functions
- **Prepared SQL Statements:** All database queries use `$wpdb->prepare()`
- **Output Escaping:** All admin output uses `esc_html()`, `esc_attr()`, `esc_url()`

#### 4. **Action Hijacking**
**Threat:** Attackers attempt to log clicks for disabled actions or arbitrary URLs.

**Mitigation:**
- **Server-side Validation:**
  - Action must be enabled in page meta
  - URL must be non-empty
  - Client cannot specify destination URLs
- **Fixed Allowlist:** Only predefined actions can be tracked

#### 5. **Data Exfiltration**
**Threat:** Attackers use tracking to exfiltrate sensitive data.

**Mitigation:**
- **No URL Acceptance:** Plugin never accepts destination URLs from client
- **No Sensitive Data:** IP addresses hashed, user agents truncated
- **Admin-only Access:** Analytics endpoints require `manage_options` capability

#### 6. **Stored XSS**
**Threat:** Malicious content stored in labels or meta could execute in admin.

**Mitigation:**
- **Sanitization on Input:** `sanitize_text_field()` for all text, `esc_url_raw()` for URLs
- **Escaping on Output:** All output properly escaped
- **Capability Checks:** Only admins can edit pages

### Security Best Practices

1. **Keep WordPress and Plugins Updated**
2. **Use Strong Passwords** for WordPress admin
3. **Limit Admin Access** to trusted users only
4. **Enable HTTPS** for your site
5. **Regular Backups** of database and files
6. **Monitor Analytics** for unusual activity
7. **Review Rate Limits** if you have high-traffic pages

### Code Security Features

- ✅ Nonce verification on all form submissions
- ✅ Capability checks (`current_user_can()`)
- ✅ Prepared SQL statements (`$wpdb->prepare()`)
- ✅ Input sanitization (`sanitize_*` functions)
- ✅ Output escaping (`esc_*` functions)
- ✅ HTTPS-only URL schemes
- ✅ No code execution from user input
- ✅ No file system access from user input
- ✅ No database export endpoints

## Data Model

### Custom Post Type: `nfchub_page`

**Post Meta Fields (prefixed with `_nfchub_`):**

For each action type (google_review, facebook_review, facebook_follow, payment_trust, payment_consult, payment_invoice):
- `enable_{action}` - Boolean (enabled/disabled)
- `{action}_url` - String (destination URL, sanitized)
- `{action}_label` - String (custom label, sanitized)

### Custom Database Table: `wp_nfchub_events`

**Columns:**
- `id` - BIGINT (primary key)
- `occurred_at` - DATETIME (indexed)
- `page_id` - BIGINT (indexed)
- `page_slug` - VARCHAR(200) (indexed)
- `action_key` - VARCHAR(64) (indexed)
- `ip_hash` - CHAR(64) (hashed IP, indexed)
- `user_agent` - TEXT (truncated)
- `referrer` - TEXT
- `utm_source` - VARCHAR(255)
- `utm_medium` - VARCHAR(255)
- `utm_campaign` - VARCHAR(255)
- `utm_term` - VARCHAR(255)
- `utm_content` - VARCHAR(255)
- `meta_json` - LONGTEXT (optional metadata)

## REST API Endpoints

### Public Endpoint (No Authentication)

**POST** `/wp-json/nfchub/v1/event`

Log an event (page view or action click).

**Parameters:**
- `page_id` (required) - Integer
- `page_slug` (required) - String
- `action_key` (required) - String (must be in allowlist)
- `referrer` (optional) - String
- `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content` (optional) - Strings

**Rate Limits:**
- 30 events per IP per 5 minutes
- 300 events per page per hour

**Response:**
```json
{ "success": true }
```

### Admin Endpoints (Requires `manage_options`)

**GET** `/wp-json/nfchub/v1/admin/summary`

Returns summary statistics.

**GET** `/wp-json/nfchub/v1/admin/recent?limit=50&page_id=123`

Returns recent events.

**GET** `/wp-json/nfchub/v1/admin/timeseries?days=30&page_id=123`

Returns timeseries data.

## Customization

### Action Configuration

To modify action types, edit `/includes/class-cpt.php`, method `get_action_config()`.

### Rate Limits

To adjust rate limits, edit `/includes/class-rest-public.php`:
- `RATE_LIMIT_IP` - Events per IP
- `RATE_LIMIT_IP_WINDOW` - Time window in minutes
- `RATE_LIMIT_PAGE` - Events per page
- `RATE_LIMIT_PAGE_WINDOW` - Time window in hours

### Styling

- Admin styles: `/assets/css/admin.css`
- Widget styles: Use Elementor's built-in style controls

## Troubleshooting

### "Edit with Elementor" button not showing

**Solution:**
- Ensure Elementor is installed and activated
- Go to **Settings > Permalinks** and click "Save Changes" to flush rewrite rules

### /nfc/ URLs showing 404

**Solution:**
- Go to **Settings > Permalinks** and click "Save Changes"
- Verify the page is published (not draft)

### Tracking not working

**Solution:**
- Check browser console for JavaScript errors
- Verify REST API is accessible: `https://yourdomain.com/wp-json/nfchub/v1/event`
- Ensure nonce is being sent correctly
- Check rate limits haven't been exceeded

### Analytics not loading

**Solution:**
- Check browser console for errors
- Verify you have `manage_options` capability
- Ensure jQuery is loaded
- Check REST API nonce

## Performance

- **Database Queries:** Optimized with indexes on frequently queried columns
- **JavaScript:** Minimal footprint, uses `sendBeacon` for non-blocking tracking
- **Auto-refresh:** Uses efficient AJAX polling (10-second interval)
- **Rate Limiting:** Prevents abuse and excessive database writes

## Privacy & GDPR Compliance

### Data Collected

- IP addresses (hashed with salt, not reversible)
- User agents (truncated to 500 characters)
- Referrer URLs
- UTM parameters
- Timestamps

### Data Retention

This plugin does not automatically delete old events. Implement your own retention policy:

```sql
DELETE FROM wp_nfchub_events WHERE occurred_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### User Rights

Under GDPR, users may request:
- **Access:** Export their event data (filter by IP hash)
- **Deletion:** Remove their event data
- **Opt-out:** Do Not Track (implement via cookie check)

Consult with a legal professional to ensure compliance with your jurisdiction.

## Support

For issues, feature requests, or contributions:
- GitHub: [Your Repository URL]
- Email: [Your Email]

## Credits

Developed by [Your Name]
Licensed under GPL v2 or later

## Changelog

### 1.0.0 (2026-01-09)
- Initial release
- Custom Post Type for NFC pages
- Elementor integration (widgets + dynamic tags)
- Secure analytics tracking
- Real-time dashboard
- Rate limiting
- IP hashing
