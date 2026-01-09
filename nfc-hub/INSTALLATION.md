# NFC Hub - Installation & Testing Guide

## Prerequisites

Before installing NFC Hub, ensure you have:

- ✅ WordPress 5.8 or higher
- ✅ PHP 7.4 or higher
- ✅ Elementor (free version) installed and activated
- ✅ Elementor Pro installed and activated (for Dynamic Tags)
- ✅ HTTPS enabled on your site (recommended for NFC)
- ✅ Admin access to WordPress
- ✅ FTP/SFTP access or file manager (for installation)

## Installation Steps

### Method 1: Manual Installation (Recommended)

1. **Download/Clone the Plugin**
   ```bash
   # If using git
   cd /path/to/wordpress/wp-content/plugins/
   git clone [repository-url] nfc-hub

   # If using a zip file, extract it to wp-content/plugins/
   ```

2. **Set Proper Permissions**
   ```bash
   cd nfc-hub
   chmod 755 .
   chmod 644 *.php
   chmod 755 includes/ assets/
   chmod 644 includes/*.php
   chmod 644 assets/js/*.js
   chmod 644 assets/css/*.css
   ```

3. **Activate the Plugin**
   - Log in to WordPress admin
   - Navigate to **Plugins**
   - Find "NFC Hub"
   - Click **Activate**

4. **Verify Activation**
   - You should see "Plugin activated" message
   - Check for "NFC Pages" in admin menu
   - Database table `wp_nfchub_events` should be created

### Method 2: WordPress Admin Upload

1. **Zip the Plugin**
   ```bash
   cd /path/to/plugins/
   zip -r nfc-hub.zip nfc-hub/
   ```

2. **Upload via WordPress**
   - Navigate to **Plugins > Add New**
   - Click **Upload Plugin**
   - Choose `nfc-hub.zip`
   - Click **Install Now**
   - Click **Activate Plugin**

## Post-Installation Configuration

### 1. Flush Rewrite Rules

After activation, flush permalinks:

1. Navigate to **Settings > Permalinks**
2. Click **Save Changes** (no need to change anything)
3. This ensures `/nfc/<slug>` URLs work correctly

### 2. Verify Elementor Integration

1. Create a test NFC page:
   - Go to **NFC Pages > Add New**
   - Enter title: "Test Page"
   - Click **Publish**

2. Check for "Edit with Elementor" button
   - If missing, check Elementor is activated
   - Ensure you're using Elementor 3.0+

3. Click **Edit with Elementor**
   - Look for "NFC Hub" in widgets panel (left sidebar)
   - You should see:
     - NFCHub Action Button
     - NFCHub Action List

### 3. Verify Dynamic Tags (Elementor Pro)

If you have Elementor Pro:

1. Add a Button widget to the page
2. Click on the link field
3. Click the Dynamic icon (database/link icon)
4. Look for "NFC Hub" group
5. You should see:
   - Action URL
   - Action Label
   - Action Enabled

## Testing Guide

### Test 1: Create an NFC Landing Page

#### Step 1: Create Page

1. Navigate to **NFC Pages > Add New**
2. Enter title: "Test Barber Shop"
3. Slug will auto-generate: `test-barber-shop`

#### Step 2: Configure Actions

In the **NFC Hub Actions** metabox:

1. **Google Review:**
   - ✅ Enable this action
   - URL: `https://g.page/r/YOUR_PLACE_ID/review`
   - Label: "Leave us a Google Review" (or leave empty for default)

2. **Facebook Review:**
   - ✅ Enable this action
   - URL: `https://www.facebook.com/YOUR_PAGE/reviews`
   - Label: (leave empty)

3. **Payment Trust:**
   - ✅ Enable this action
   - URL: `https://example.com/pay/trust`
   - Label: "Pay Trust Account"

4. Click **Publish**

5. **Copy NFC URL** from the green box at bottom of metabox
   - Should be: `https://yourdomain.com/nfc/test-barber-shop`

#### Step 3: Design with Elementor

1. Click **Edit with Elementor**

2. **Add Header Section:**
   - Drag a "Section" widget
   - Add "Heading" widget: "Welcome to Test Barber Shop"
   - Add "Image" widget: Upload a logo

3. **Add Action List Widget:**
   - Search for "NFCHub Action List" in widgets
   - Drag to page
   - Configure:
     - Layout: Vertical
     - Show Icons: Yes
     - Open in New Tab: Yes
   - Style as desired

4. **OR Add Individual Buttons:**
   - Search for "NFCHub Action Button"
   - Drag to page
   - Select Action: "Google Review Click"
   - Style as desired
   - Repeat for other actions

5. Click **Update** to save

#### Step 4: Test Front-End

1. Open the page in a new browser tab:
   - URL: `https://yourdomain.com/nfc/test-barber-shop`
   - Should display your Elementor-designed page

2. **Verify Actions:**
   - Only enabled actions should be visible
   - Buttons should have correct labels
   - Icons should appear (if enabled)

3. **Click a Button:**
   - Should open the correct URL
   - Should open in new tab (if configured)
   - Should NOT block navigation

### Test 2: Verify Tracking

#### Step 1: Trigger Events

1. Open the NFC page in incognito/private window
2. Wait for page to load (page_view event)
3. Click "Google Review" button (google_review_click event)
4. Go back, click "Facebook Review" (facebook_review_click event)
5. Open in another browser/device and repeat

#### Step 2: Check Analytics

1. Navigate to **NFC Pages > Analytics**

2. **Verify Summary Stats:**
   - Last 7 Days should show:
     - Total Events: at least 4 (1 page_view + 2 clicks × 2 sessions)
     - Unique Pages: 1
     - Unique Visitors: 1-2 (depending on IP)

3. **Check Top Actions:**
   - Should show:
     - page_view
     - google_review_click
     - facebook_review_click

4. **Check Recent Events:**
   - Should show your test events
   - With correct timestamps
   - Correct page slug
   - Correct action keys

5. **Test Auto-Refresh:**
   - Trigger a new event in another tab
   - Wait 10 seconds
   - Analytics should update automatically

#### Step 3: Test Page Filter

1. Create a second NFC page with different actions
2. Trigger some events on it
3. In Analytics, select first page from dropdown
4. Recent events should filter to that page only

### Test 3: Verify Security

#### Test Rate Limiting

1. **IP Rate Limit Test:**
   ```bash
   # Send 31 requests rapidly (should hit limit at 30)
   for i in {1..31}; do
     curl -X POST https://yourdomain.com/wp-json/nfchub/v1/event \
       -H "Content-Type: application/json" \
       -d '{"page_id":123,"page_slug":"test-barber-shop","action_key":"page_view"}'
   done
   ```
   - First 30 should return `{"success":true}`
   - 31st should return `{"success":false}` with HTTP 429

2. **Page Rate Limit Test:**
   - Would require 301 requests in 1 hour
   - Best tested with automated script
   - Verify 301st request returns HTTP 429

#### Test Input Validation

1. **Invalid Action Key:**
   ```bash
   curl -X POST https://yourdomain.com/wp-json/nfchub/v1/event \
     -H "Content-Type: application/json" \
     -d '{"page_id":123,"page_slug":"test-barber-shop","action_key":"invalid_action"}'
   ```
   - Should return `{"success":false}` with HTTP 400

2. **Mismatched Page ID and Slug:**
   ```bash
   curl -X POST https://yourdomain.com/wp-json/nfchub/v1/event \
     -H "Content-Type: application/json" \
     -d '{"page_id":123,"page_slug":"wrong-slug","action_key":"page_view"}'
   ```
   - Should return `{"success":false}` with HTTP 400

3. **Disabled Action:**
   - Disable "Facebook Review" in page meta
   - Try to log facebook_review_click event
   - Should return `{"success":false}` with HTTP 400

#### Test XSS Prevention

1. **Malicious Label:**
   - In page meta, try entering: `<script>alert('XSS')</script>`
   - Save page
   - View page source
   - Script should be escaped as `&lt;script&gt;...`

2. **Malicious URL:**
   - Try entering: `javascript:alert('XSS')`
   - Save page
   - URL should be rejected or sanitized

### Test 4: Elementor Features

#### Test Dynamic Tags

1. **Action URL Tag:**
   - Add Button widget
   - Set link to Dynamic > NFC Hub > Action URL
   - Select "Google Review"
   - Preview page
   - Button should link to configured Google Review URL

2. **Action Label Tag:**
   - Add Text Editor widget
   - Insert Dynamic > NFC Hub > Action Label
   - Select "Google Review"
   - Preview page
   - Should display configured or default label

3. **Action Enabled Tag:**
   - Add Section widget
   - Set Visibility Condition: Dynamic > NFC Hub > Action Enabled
   - Select "Google Review"
   - Condition: equals "yes"
   - Section should only show if Google Review is enabled

#### Test Widget Placeholders

1. **Disabled Action:**
   - Disable all actions
   - Edit page with Elementor
   - Action List widget should show placeholder message
   - Action Button widget should show grayed placeholder

2. **Enabled Action:**
   - Enable one action
   - Action List should show that action
   - Action Button for that action should render normally

### Test 5: URL Routing

#### Test Public URLs

1. **Valid Slug:**
   - Visit: `https://yourdomain.com/nfc/test-barber-shop`
   - Should load page correctly
   - Should trigger page_view tracking

2. **Invalid Slug:**
   - Visit: `https://yourdomain.com/nfc/nonexistent-page`
   - Should show WordPress 404 page

3. **Draft Page:**
   - Set page to Draft status
   - Visit: `https://yourdomain.com/nfc/test-barber-shop`
   - Should show 404 (drafts not publicly accessible)

### Test 6: UTM Tracking

1. **Add UTM Parameters:**
   - Visit: `https://yourdomain.com/nfc/test-barber-shop?utm_source=facebook&utm_medium=social&utm_campaign=summer2024`

2. **Trigger Events:**
   - Page should load (page_view)
   - Click an action button

3. **Check Analytics:**
   - Recent events should show:
     - UTM Source: facebook
     - UTM Medium: social
     - UTM Campaign: summer2024

## Verification Checklist

Use this checklist to ensure everything works:

- [ ] Plugin activates without errors
- [ ] Database table `wp_nfchub_events` created
- [ ] Custom post type "NFC Pages" appears in admin menu
- [ ] Can create and publish NFC pages
- [ ] Metabox "NFC Hub Actions" appears on edit screen
- [ ] Can configure action URLs and labels
- [ ] NFC Tag URL displays after publishing
- [ ] "Edit with Elementor" button works
- [ ] NFC Hub widgets appear in Elementor
- [ ] NFCHub Action Button widget renders correctly
- [ ] NFCHub Action List widget renders correctly
- [ ] Dynamic Tags work (if Elementor Pro installed)
- [ ] Public URLs `/nfc/<slug>` load correctly
- [ ] Page view tracking works automatically
- [ ] Action click tracking works
- [ ] Analytics dashboard loads
- [ ] Summary statistics display correctly
- [ ] Recent events display correctly
- [ ] Top actions and pages display correctly
- [ ] Auto-refresh works (wait 10+ seconds)
- [ ] Page filter works
- [ ] Rate limiting prevents spam
- [ ] Invalid action keys rejected
- [ ] Disabled actions cannot be tracked
- [ ] XSS attempts are escaped
- [ ] UTM parameters are captured
- [ ] Only admins can access analytics
- [ ] Uninstall removes all data

## Troubleshooting

### Issue: 404 on /nfc/ URLs

**Solution:**
```bash
# Flush permalinks
wp rewrite flush

# OR via admin:
# Settings > Permalinks > Save Changes
```

### Issue: "Edit with Elementor" Missing

**Solution:**
1. Ensure Elementor is activated
2. Check Elementor version (3.0+ required)
3. Deactivate and reactivate NFC Hub
4. Clear cache

### Issue: Widgets Not Appearing

**Solution:**
1. Clear Elementor cache: **Elementor > Tools > Regenerate CSS & Data**
2. Check browser console for JavaScript errors
3. Ensure no JavaScript conflicts with other plugins

### Issue: Tracking Not Working

**Solution:**
1. Open browser console (F12)
2. Check for errors in Network tab
3. Verify REST API is accessible:
   ```bash
   curl https://yourdomain.com/wp-json/nfchub/v1/event -I
   # Should return 200 or 400, NOT 404
   ```
4. Check if rate limit was hit (wait 5 minutes)

### Issue: Analytics Not Loading

**Solution:**
1. Check browser console for errors
2. Verify you're logged in as admin
3. Clear browser cache
4. Check if jQuery is loaded:
   ```javascript
   // In browser console:
   typeof jQuery
   // Should return "function"
   ```

### Issue: Database Table Not Created

**Solution:**
```sql
-- Run this SQL manually in phpMyAdmin:
CREATE TABLE IF NOT EXISTS wp_nfchub_events (
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
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Next Steps

After successful installation and testing:

1. **Create Production Pages**
   - Design your real NFC landing pages
   - Configure actual review/payment URLs
   - Test thoroughly before encoding NFC tags

2. **Encode NFC Tags**
   - Use NFC Tools app (iOS/Android)
   - Select "Write"
   - Choose "URL/URI"
   - Paste your NFC Hub URL
   - Write to tag
   - Test tap-to-open

3. **Monitor Analytics**
   - Check daily for new events
   - Identify top-performing actions
   - Optimize based on data

4. **Security Hardening**
   - Keep WordPress updated
   - Use strong admin passwords
   - Enable 2FA on admin accounts
   - Monitor for unusual traffic patterns

5. **Backup**
   - Schedule regular backups
   - Include database (especially `wp_nfchub_events` table)
   - Test restore process

## Support

If you encounter issues not covered here:

1. Check `README.md` for additional documentation
2. Review code comments for implementation details
3. Open an issue on GitHub
4. Contact support: [your-email@example.com]

---

**Ready to go?** Follow the installation steps above and use the testing guide to verify everything works correctly!
