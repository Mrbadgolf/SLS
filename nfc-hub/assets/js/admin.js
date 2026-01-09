/**
 * NFC Hub Admin Analytics
 *
 * Handles the analytics dashboard functionality.
 *
 * @package NFCHub
 */

(function($) {
	'use strict';

	// Check if config is available
	if (typeof nfcHubAdmin === 'undefined') {
		console.warn('NFC Hub Admin: Configuration not found');
		return;
	}

	const config = nfcHubAdmin;
	let refreshInterval = null;

	/**
	 * Fetch and display summary statistics
	 */
	function loadSummary() {
		$.ajax({
			url: config.apiUrl + '/admin/summary',
			method: 'GET',
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', config.nonce);
			},
			success: function(response) {
				// Update 7-day stats
				if (response.last_7_days) {
					$('#stat-7d-events').text(response.last_7_days.total_events || 0);
					$('#stat-7d-pages').text(response.last_7_days.unique_pages || 0);
					$('#stat-7d-visitors').text(response.last_7_days.unique_visitors || 0);
				}

				// Update 30-day stats
				if (response.last_30_days) {
					$('#stat-30d-events').text(response.last_30_days.total_events || 0);
					$('#stat-30d-pages').text(response.last_30_days.unique_pages || 0);
					$('#stat-30d-visitors').text(response.last_30_days.unique_visitors || 0);
				}

				// Update top actions
				if (response.top_actions) {
					updateTopActions(response.top_actions);
				}

				// Update top pages
				if (response.top_pages) {
					updateTopPages(response.top_pages);
				}
			},
			error: function(xhr, status, error) {
				console.error('Failed to load summary:', error);
			}
		});
	}

	/**
	 * Update top actions table
	 */
	function updateTopActions(actions) {
		const tbody = $('#nfchub-top-actions tbody');
		tbody.empty();

		if (actions.length === 0) {
			tbody.append('<tr><td colspan="2">No data yet</td></tr>');
			return;
		}

		actions.forEach(function(action) {
			const row = $('<tr>');
			row.append($('<td>').text(formatActionKey(action.action_key)));
			row.append($('<td>').text(action.count));
			tbody.append(row);
		});
	}

	/**
	 * Update top pages table
	 */
	function updateTopPages(pages) {
		const tbody = $('#nfchub-top-pages tbody');
		tbody.empty();

		if (pages.length === 0) {
			tbody.append('<tr><td colspan="2">No data yet</td></tr>');
			return;
		}

		pages.forEach(function(page) {
			const row = $('<tr>');
			row.append($('<td>').text(page.page_title || page.page_slug));
			row.append($('<td>').text(page.count));
			tbody.append(row);
		});
	}

	/**
	 * Fetch and display recent events
	 */
	function loadRecentEvents() {
		const pageId = $('#nfchub-page-filter').val();

		const params = {
			limit: 50
		};

		if (pageId) {
			params.page_id = pageId;
		}

		$.ajax({
			url: config.apiUrl + '/admin/recent',
			method: 'GET',
			data: params,
			beforeSend: function(xhr) {
				xhr.setRequestHeader('X-WP-Nonce', config.nonce);
			},
			success: function(response) {
				updateRecentEvents(response);
			},
			error: function(xhr, status, error) {
				console.error('Failed to load recent events:', error);
			}
		});
	}

	/**
	 * Update recent events table
	 */
	function updateRecentEvents(events) {
		const tbody = $('#nfchub-recent-events tbody');
		tbody.empty();

		if (events.length === 0) {
			tbody.append('<tr><td colspan="5">No events yet</td></tr>');
			return;
		}

		events.forEach(function(event) {
			const row = $('<tr>');
			row.append($('<td>').text(formatDateTime(event.occurred_at)));
			row.append($('<td>').text(event.page_slug));
			row.append($('<td>').text(formatActionKey(event.action_key)));
			row.append($('<td>').html(formatReferrer(event.referrer)));
			row.append($('<td>').text(event.utm_source || '-'));
			tbody.append(row);
		});
	}

	/**
	 * Format action key for display
	 */
	function formatActionKey(key) {
		return key.replace(/_/g, ' ')
			.replace(/\b\w/g, function(l) { return l.toUpperCase(); });
	}

	/**
	 * Format date/time for display
	 */
	function formatDateTime(datetime) {
		const date = new Date(datetime);
		return date.toLocaleString();
	}

	/**
	 * Format referrer with link
	 */
	function formatReferrer(referrer) {
		if (!referrer) {
			return '-';
		}
		try {
			const url = new URL(referrer);
			return '<a href="' + escapeHtml(referrer) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(url.hostname) + '</a>';
		} catch (e) {
			return escapeHtml(referrer);
		}
	}

	/**
	 * Escape HTML
	 */
	function escapeHtml(text) {
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}

	/**
	 * Refresh all data
	 */
	function refreshAll() {
		loadSummary();
		loadRecentEvents();
	}

	/**
	 * Setup auto-refresh
	 */
	function setupAutoRefresh() {
		// Refresh summary every 10 seconds
		refreshInterval = setInterval(function() {
			loadSummary();
			loadRecentEvents();
		}, 10000);
	}

	/**
	 * Initialize
	 */
	function init() {
		// Load initial data
		refreshAll();

		// Setup auto-refresh
		setupAutoRefresh();

		// Page filter change handler
		$('#nfchub-page-filter').on('change', function() {
			loadRecentEvents();
		});

		// Manual refresh button
		$('#nfchub-refresh-btn').on('click', function() {
			refreshAll();
		});
	}

	// Initialize when document is ready
	$(document).ready(init);

})(jQuery);
