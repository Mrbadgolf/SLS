/**
 * NFC Hub Tracker
 *
 * Handles front-end event tracking for NFC Hub pages.
 *
 * Security:
 * - Does not expose any sensitive data
 * - Uses sendBeacon for reliability without blocking navigation
 * - Validates data before sending
 *
 * @package NFCHub
 */

(function() {
	'use strict';

	// Check if tracking config is available
	if (typeof nfcHubTracker === 'undefined') {
		console.warn('NFC Hub Tracker: Configuration not found');
		return;
	}

	const config = nfcHubTracker;

	/**
	 * Send event to API
	 *
	 * @param {string} actionKey - The action key to track
	 */
	function sendEvent(actionKey) {
		// Validate action key
		if (!actionKey || typeof actionKey !== 'string') {
			return;
		}

		// Get UTM parameters from URL
		const urlParams = new URLSearchParams(window.location.search);
		const utmParams = {
			utm_source: urlParams.get('utm_source') || '',
			utm_medium: urlParams.get('utm_medium') || '',
			utm_campaign: urlParams.get('utm_campaign') || '',
			utm_term: urlParams.get('utm_term') || '',
			utm_content: urlParams.get('utm_content') || ''
		};

		// Prepare event data
		const eventData = {
			page_id: config.pageId,
			page_slug: config.pageSlug,
			action_key: actionKey,
			referrer: document.referrer || ''
		};

		// Add UTM parameters if present
		Object.keys(utmParams).forEach(function(key) {
			if (utmParams[key]) {
				eventData[key] = utmParams[key];
			}
		});

		// Send using sendBeacon if available (preferred - doesn't block navigation)
		if (navigator.sendBeacon) {
			const blob = new Blob([JSON.stringify(eventData)], { type: 'application/json' });
			navigator.sendBeacon(config.apiUrl, blob);
		} else {
			// Fallback to fetch with keepalive
			fetch(config.apiUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': config.nonce
				},
				body: JSON.stringify(eventData),
				keepalive: true
			}).catch(function(error) {
				// Silently fail - don't block user experience
				console.debug('NFC Hub Tracker: Event send failed', error);
			});
		}
	}

	/**
	 * Track page view on load
	 */
	function trackPageView() {
		sendEvent('page_view');
	}

	/**
	 * Track action clicks
	 */
	function setupClickTracking() {
		document.addEventListener('click', function(event) {
			// Find closest element with data-nfchub-action attribute
			let target = event.target;

			// Traverse up to 5 levels to find the action element
			for (let i = 0; i < 5 && target; i++) {
				const actionKey = target.getAttribute('data-nfchub-action');

				if (actionKey) {
					// Send event (non-blocking)
					sendEvent(actionKey);
					break;
				}

				target = target.parentElement;
			}
		}, true); // Use capture phase to ensure we catch the event
	}

	/**
	 * Initialize tracker
	 */
	function init() {
		// Track page view immediately
		trackPageView();

		// Setup click tracking
		setupClickTracking();
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

})();
