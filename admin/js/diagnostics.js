/**
 * Diagnostics Admin JavaScript
 *
 * @package Fullworks_Anti_Spam
 */

(function($) {
	'use strict';

	let currentPage = 1;
	let totalPages = 1;

	/**
	 * Load diagnostics logs
	 */
	function loadLogs() {
		$('#fwas-logs-loading').show();
		$('#fwas-logs-table').hide();
		$('#fwas-no-logs').hide();

		const data = {
			action: 'fwas_get_diagnostics_logs',
			nonce: fwasDiagnostics.nonce,
			page: currentPage,
			per_page: 50,
			level: $('#fwas-log-level').val(),
			search: $('#fwas-log-search').val()
		};

		$.post(fwasDiagnostics.ajaxurl, data, function(response) {
			$('#fwas-logs-loading').hide();

			if (response.success && response.data.logs && response.data.logs.length > 0) {
				displayLogs(response.data.logs);
				updatePagination(response.data.pages, response.data.total);
			} else {
				$('#fwas-no-logs').show();
				updatePagination(0, 0);
			}
		});
	}

	/**
	 * Display logs in table
	 */
	function displayLogs(logs) {
		const $tbody = $('#fwas-logs-tbody');
		$tbody.empty();

		logs.forEach(function(log) {
			const levelClass = 'log-level-' + log.level;
			const $row = $('<tr>').addClass(levelClass);

			$row.append($('<td>').addClass('column-time').text(log.time));
			$row.append($('<td>').addClass('column-level').text(log.level));
			$row.append($('<td>').addClass('column-message').text(log.message));
			$row.append($('<td>').addClass('column-source').text(log.source));
			$row.append($('<td>').addClass('column-ip').text(log.ip));
			$row.append($('<td>').addClass('column-user').text(log.user));

			$tbody.append($row);
		});

		$('#fwas-logs-table').show();
	}

	/**
	 * Update pagination
	 */
	function updatePagination(pages, total) {
		totalPages = pages;

		$('#fwas-prev-page').prop('disabled', currentPage === 1);
		$('#fwas-next-page').prop('disabled', currentPage === totalPages || totalPages === 0);

		if (total > 0) {
			$('#fwas-page-info').text('Page ' + currentPage + ' of ' + totalPages + ' (' + total + ' total logs)');
		} else {
			$('#fwas-page-info').text('No logs');
		}
	}

	/**
	 * Clear all logs
	 */
	function clearLogs() {
		if (!confirm('Are you sure you want to clear all diagnostics logs? This action cannot be undone.')) {
			return;
		}

		const data = {
			action: 'fwas_clear_diagnostics_logs',
			nonce: fwasDiagnostics.nonce
		};

		$.post(fwasDiagnostics.ajaxurl, data, function(response) {
			if (response.success) {
				alert('All logs cleared successfully.');
				currentPage = 1;
				loadLogs();
			} else {
				alert('Failed to clear logs.');
			}
		});
	}

	/**
	 * Export logs as CSV
	 */
	function exportLogs() {
		const data = {
			action: 'fwas_export_diagnostics_logs',
			nonce: fwasDiagnostics.nonce
		};

		$.post(fwasDiagnostics.ajaxurl, data, function(response) {
			if (response.success && response.data.data) {
				downloadCSV(response.data.data);
			} else {
				alert('Failed to export logs.');
			}
		});
	}

	/**
	 * Download CSV file
	 */
	function downloadCSV(data) {
		let csv = '';
		data.forEach(function(row) {
			row.forEach(function(field, index) {
				field = String(field).replace(/"/g, '""');
				csv += '"' + field + '"';
				if (index < row.length - 1) {
					csv += ',';
				}
			});
			csv += '\n';
		});

		const blob = new Blob([csv], { type: 'text/csv' });
		const url = window.URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = 'fullworks-antispam-diagnostics-' + new Date().toISOString().slice(0, 10) + '.csv';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		window.URL.revokeObjectURL(url);
	}

	/**
	 * Copy REST URL to clipboard
	 */
	function copyURL() {
		const $input = $('#fwas-rest-url');
		const url = $input.val();

		// Try modern Clipboard API first
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(url).then(function() {
				alert('URL copied to clipboard!');
			}).catch(function(err) {
				console.error('Failed to copy: ', err);
				fallbackCopy($input);
			});
		} else {
			// Fallback for older browsers
			fallbackCopy($input);
		}
	}

	/**
	 * Fallback copy method for older browsers
	 */
	function fallbackCopy($input) {
		$input.select();
		$input[0].setSelectionRange(0, 99999); // For mobile devices

		try {
			document.execCommand('copy');
			alert('URL copied to clipboard!');
		} catch (err) {
			alert('Failed to copy URL. Please copy manually.');
		}
	}

	/**
	 * Regenerate access token
	 */
	function regenerateToken() {
		if (!confirm('Are you sure you want to regenerate the access token? The current URL will no longer work.')) {
			return;
		}

		const data = {
			action: 'fwas_regenerate_diagnostics_token',
			nonce: fwasDiagnostics.nonce
		};

		$.post(fwasDiagnostics.ajaxurl, data, function(response) {
			if (response.success) {
				$('#fwas-rest-url').val(response.data.url);
				alert('Token regenerated successfully!');
			} else {
				alert('Failed to regenerate token.');
			}
		});
	}

	/**
	 * Generate analysis report
	 */
	function generateReport() {
		const days = $('#fwas-report-days').val();
		const $button = $('#fwas-generate-report');
		const $container = $('#fwas-report-container');

		// Show loading state
		$button.prop('disabled', true).text('Generating...');
		$container.html('<p>Generating report...</p>').show();

		const data = {
			action: 'fwas_generate_diagnostics_report',
			nonce: fwasDiagnostics.nonce,
			days: days
		};

		$.post(fwasDiagnostics.ajaxurl, data, function(response) {
			$button.prop('disabled', false).text('Generate Report');

			if (response.success && response.data) {
				displayReport(response.data);
			} else {
				const errorMsg = response.data || 'Failed to generate report.';
				$container.html('<div class="notice notice-error"><p>' + errorMsg + '</p></div>');
			}
		}).fail(function() {
			$button.prop('disabled', false).text('Generate Report');
			$container.html('<div class="notice notice-error"><p>Failed to generate report. Please try again.</p></div>');
		});
	}

	/**
	 * Display analysis report
	 */
	function displayReport(report) {
		const $container = $('#fwas-report-container');
		let html = '<div class="fwas-report">';

		// Executive Summary
		html += '<div class="fwas-report-section">';
		html += '<h4>Executive Summary</h4>';
		html += '<div class="fwas-stats-grid">';
		html += '<div class="fwas-stat"><span class="label">Total Submissions:</span><span class="value">' + report.executive_summary.total_submissions + '</span></div>';
		html += '<div class="fwas-stat"><span class="label">Spam Detected:</span><span class="value fwas-spam">' + report.executive_summary.spam_detected + '</span></div>';
		html += '<div class="fwas-stat"><span class="label">Legitimate:</span><span class="value fwas-legitimate">' + report.executive_summary.legitimate + '</span></div>';
		html += '<div class="fwas-stat"><span class="label">Spam Rate:</span><span class="value">' + report.executive_summary.spam_rate + '%</span></div>';
		html += '</div>';

		if (report.executive_summary.primary_methods.length > 0) {
			html += '<h5>Primary Detection Methods:</h5><ul>';
			report.executive_summary.primary_methods.forEach(function(method) {
				html += '<li>' + method.name + ': ' + method.count + '</li>';
			});
			html += '</ul>';
		}
		html += '</div>';

		// Spam Breakdown
		html += '<div class="fwas-report-section">';
		html += '<h4>Spam Breakdown</h4>';
		html += '<div class="fwas-stats-grid">';
		html += '<div class="fwas-stat"><span class="label">Bot Spam:</span><span class="value">' + report.spam_breakdown.by_type.bot + '</span></div>';
		html += '<div class="fwas-stat"><span class="label">Deny List Spam:</span><span class="value">' + report.spam_breakdown.by_type.deny + '</span></div>';
		html += '<div class="fwas-stat"><span class="label">Single Word Spam:</span><span class="value">' + report.spam_breakdown.by_type.single_word + '</span></div>';
		html += '<div class="fwas-stat"><span class="label">IP Blocklist Spam:</span><span class="value">' + report.spam_breakdown.by_type.blocklist + '</span></div>';
		html += '<div class="fwas-stat"><span class="label">Human Spam:</span><span class="value">' + report.spam_breakdown.by_type.human + '</span></div>';
		html += '</div>';

		// Allow matches section
		if (report.spam_breakdown.allow_matches && report.spam_breakdown.allow_matches.count > 0) {
			html += '<h5>Allow List Matches:</h5>';
			html += '<p><strong>Total:</strong> ' + report.spam_breakdown.allow_matches.count + ' submission(s) matched allow list rules</p>';

			if (report.spam_breakdown.allow_matches.rules_used && report.spam_breakdown.allow_matches.rules_used.length > 0) {
				html += '<table class="widefat"><thead><tr><th>Rule Type</th><th>Rule</th><th>Matches</th></tr></thead><tbody>';
				report.spam_breakdown.allow_matches.rules_used.forEach(function(rule) {
					html += '<tr><td>' + rule.type + '</td><td>' + rule.rule + '</td><td>' + rule.count + '</td></tr>';
				});
				html += '</tbody></table>';
			}
		}

		// Deny rules used section
		if (report.spam_breakdown.deny_rules_used && report.spam_breakdown.deny_rules_used.length > 0) {
			html += '<h5>Deny List Rules Triggered:</h5>';
			html += '<table class="widefat"><thead><tr><th>Rule Type</th><th>Rule</th><th>Matches</th></tr></thead><tbody>';
			report.spam_breakdown.deny_rules_used.forEach(function(rule) {
				html += '<tr><td>' + rule.type + '</td><td>' + rule.rule + '</td><td>' + rule.count + '</td></tr>';
			});
			html += '</tbody></table>';
		}

		if (report.spam_breakdown.overlap_analysis.multiple_methods > 0) {
			html += '<p><strong>Multiple Method Detection:</strong> ' + report.spam_breakdown.overlap_analysis.multiple_methods +
					' submissions (' + report.spam_breakdown.overlap_analysis.multiple_method_percentage + '%) detected by multiple methods</p>';
		}
		html += '</div>';

		// Human Spam Analysis
		if (report.human_spam_analysis.enabled) {
			html += '<div class="fwas-report-section">';
			html += '<h4>Human Spam Analysis</h4>';
			html += '<div class="fwas-stats-grid">';
			html += '<div class="fwas-stat"><span class="label">AI Only:</span><span class="value">' + report.human_spam_analysis.detection_methods.ai_only + '</span></div>';
			html += '<div class="fwas-stat"><span class="label">Statistical Only:</span><span class="value">' + report.human_spam_analysis.detection_methods.statistical_only + '</span></div>';
			html += '<div class="fwas-stat"><span class="label">Both Methods:</span><span class="value">' + report.human_spam_analysis.detection_methods.both + '</span></div>';
			html += '</div>';

			if (Object.keys(report.human_spam_analysis.score_distribution).length > 0) {
				html += '<h5>Score Distribution:</h5><ul>';
				for (const [range, count] of Object.entries(report.human_spam_analysis.score_distribution)) {
					html += '<li>' + range + ': ' + count + '</li>';
				}
				html += '</ul>';
			}
			html += '</div>';
		}

		// Form Statistics
		if (report.form_statistics.length > 0) {
			html += '<div class="fwas-report-section">';
			html += '<h4>Form Statistics</h4>';
			html += '<table class="widefat"><thead><tr><th>Form</th><th>Total</th><th>Spam</th><th>Spam Rate</th></tr></thead><tbody>';
			report.form_statistics.forEach(function(form) {
				html += '<tr><td>' + form.form + '</td><td>' + form.total + '</td><td>' + form.spam + '</td><td>' + form.spam_rate + '%</td></tr>';
			});
			html += '</tbody></table>';
			html += '</div>';
		}

		// IP Analysis
		if (report.ip_analysis.high_spam_ips > 0) {
			html += '<div class="fwas-report-section">';
			html += '<h4>IP Analysis</h4>';
			html += '<p><strong>High-Spam IPs:</strong> ' + report.ip_analysis.high_spam_ips + ' IP addresses with spam rate ≥ 80%</p>';

			if (report.ip_analysis.top_spam_ips.length > 0) {
				html += '<h5>Top Spam IPs:</h5>';
				html += '<table class="widefat"><thead><tr><th>IP Address</th><th>Total</th><th>Spam</th><th>Spam Rate</th></tr></thead><tbody>';
				report.ip_analysis.top_spam_ips.forEach(function(ip) {
					html += '<tr><td>' + ip.ip + '</td><td>' + ip.total + '</td><td>' + ip.spam + '</td><td>' + ip.spam_rate + '%</td></tr>';
				});
				html += '</tbody></table>';
			}
			html += '</div>';
		}

		// Temporal Analysis
		if (report.temporal_analysis) {
			html += '<div class="fwas-report-section">';
			html += '<h4>Temporal Analysis</h4>';

			// Daily distribution
			if (report.temporal_analysis.daily_distribution && report.temporal_analysis.daily_distribution.length > 0) {
				html += '<h5>Submissions by Day of Week:</h5>';
				html += '<table class="widefat"><thead><tr><th>Day</th><th>Total</th><th>Spam</th><th>Spam Rate</th></tr></thead><tbody>';
				report.temporal_analysis.daily_distribution.forEach(function(day) {
					html += '<tr><td>' + day.day + '</td><td>' + day.total + '</td><td>' + day.spam + '</td><td>' + day.spam_rate + '%</td></tr>';
				});
				html += '</tbody></table>';
			}

			html += '</div>';
		}

		// Issues
		if (report.issues.length > 0) {
			html += '<div class="fwas-report-section fwas-issues">';
			html += '<h4>Issues Identified</h4><ul>';
			report.issues.forEach(function(issue) {
				const severityClass = 'fwas-severity-' + issue.severity;
				html += '<li class="' + severityClass + '">' + issue.message + '</li>';
			});
			html += '</ul></div>';
		}

		// Recommendations
		if (report.recommendations.length > 0) {
			html += '<div class="fwas-report-section fwas-recommendations">';
			html += '<h4>Recommendations</h4><ul>';
			report.recommendations.forEach(function(rec) {
				const priorityClass = 'fwas-priority-' + rec.priority;
				html += '<li class="' + priorityClass + '">' + rec.message + '</li>';
			});
			html += '</ul></div>';
		}

		html += '</div>';

		$container.html(html).show();
	}

	/**
	 * Check remote server status
	 */
	function checkRemoteServer() {
		const $button = $('#fwas-check-remote-server');
		const $container = $('#fwas-server-check-result');

		// Show loading state
		$button.prop('disabled', true).text('Checking...');
		$container.html('<p>Checking remote server status...</p>').show();

		const data = {
			action: 'fwas_check_remote_server',
			nonce: fwasDiagnostics.nonce
		};

		$.post(fwasDiagnostics.ajaxurl, data, function(response) {
			$button.prop('disabled', false).text('Check Remote Server');

			if (response.success && response.data) {
				displayServerCheck(response.data);
			} else {
				const errorMsg = response.data || 'Failed to check remote server.';
				$container.html('<div class="notice notice-error"><p>' + errorMsg + '</p></div>');
			}
		}).fail(function() {
			$button.prop('disabled', false).text('Check Remote Server');
			$container.html('<div class="notice notice-error"><p>Failed to check remote server. Please try again.</p></div>');
		});
	}

	/**
	 * Check custom URL status (diagnostic feature)
	 */
	function checkCustomURL() {
		const $button = $('#fwas-check-custom-url');
		const $input = $('#fwas-custom-url');
		const $container = $('#fwas-server-check-result');
		const customURL = $input.val().trim();

		// Validate URL input
		if (!customURL) {
			alert('Please enter a URL to check.');
			return;
		}

		// Basic URL validation
		try {
			new URL(customURL);
		} catch (e) {
			alert('Invalid URL format. Please enter a valid HTTP or HTTPS URL (e.g., https://example.com/api/endpoint)');
			return;
		}

		// Show loading state
		$button.prop('disabled', true).text('Checking...');
		$container.html('<p>Checking custom URL: ' + escapeHtml(customURL) + '...</p>').show();

		const data = {
			action: 'fwas_check_remote_server',
			nonce: fwasDiagnostics.nonce,
			custom_url: customURL
		};

		$.post(fwasDiagnostics.ajaxurl, data, function(response) {
			$button.prop('disabled', false).text('Check Custom URL');

			if (response.success && response.data) {
				displayServerCheck(response.data);
			} else {
				const errorMsg = response.data || 'Failed to check custom URL.';
				$container.html('<div class="notice notice-error"><p>' + escapeHtml(errorMsg) + '</p></div>');
			}
		}).fail(function() {
			$button.prop('disabled', false).text('Check Custom URL');
			$container.html('<div class="notice notice-error"><p>Failed to check custom URL. Please try again.</p></div>');
		});
	}

	/**
	 * Display remote server check results
	 */
	function displayServerCheck(data) {
		const $container = $('#fwas-server-check-result');
		let html = '<div class="fwas-server-check-results">';

		// Overall Status Badge
		html += '<div class="fwas-status-section">';
		if (data.status === 'success') {
			html += '<div class="fwas-status-badge fwas-status-success">✓ Server Operational</div>';
		} else if (data.status === 'degraded') {
			html += '<div class="fwas-status-badge fwas-status-warning">⚠ Server Degraded</div>';
		} else {
			html += '<div class="fwas-status-badge fwas-status-error">✗ Server Error</div>';
		}
		html += '<div class="fwas-status-meta">';
		html += '<span><strong>Response Time:</strong> ' + data.request_time_ms + ' ms</span>';
		html += '<span><strong>Timestamp:</strong> ' + data.timestamp + '</span>';
		html += '</div>';
		html += '</div>';

		// Client IP Section (for firewall diagnostics)
		html += '<div class="fwas-tech-section">';
		html += '<h4>Connection Details</h4>';
		html += '<div class="fwas-tech-details">';
		html += '<div class="fwas-tech-row"><span class="fwas-tech-label">Client IP:</span><span class="fwas-tech-value fwas-ip">' + escapeHtml(data.client_ip) + '</span></div>';
		html += '<div class="fwas-tech-row"><span class="fwas-tech-label">Endpoint:</span><span class="fwas-tech-value">' + escapeHtml(data.endpoint) + '</span></div>';
		html += '</div>';
		html += '</div>';

		// Error Details (if any)
		if (data.status === 'error') {
			html += '<div class="fwas-tech-section fwas-error-section">';
			html += '<h4>Error Details</h4>';

			if (data.error_type === 'network') {
				html += '<div class="fwas-error-box">';
				html += '<div class="fwas-error-type">Network Error: ' + escapeHtml(data.error_classification.type) + '</div>';
				html += '<div class="fwas-error-message">' + escapeHtml(data.error_message) + '</div>';
				html += '<div class="fwas-error-guidance"><strong>Guidance:</strong> ' + escapeHtml(data.error_classification.guidance) + '</div>';
				html += '</div>';

				// Technical details
				html += '<div class="fwas-tech-details">';
				html += '<div class="fwas-tech-row"><span class="fwas-tech-label">Error Code:</span><span class="fwas-tech-value">' + escapeHtml(data.error_code) + '</span></div>';
				html += '<div class="fwas-tech-row"><span class="fwas-tech-label">Severity:</span><span class="fwas-tech-value">' + escapeHtml(data.error_classification.severity) + '</span></div>';
				html += '</div>';
			} else if (data.error_type === 'http_error') {
				html += '<div class="fwas-error-box">';
				html += '<div class="fwas-error-type">HTTP Error: ' + data.http_status_code + ' ' + escapeHtml(data.http_status_message) + '</div>';
				html += '<div class="fwas-error-guidance"><strong>Guidance:</strong> ' + escapeHtml(data.error_classification.guidance) + '</div>';
				html += '</div>';
			} else if (data.error_type === 'invalid_response') {
				html += '<div class="fwas-error-box">';
				html += '<div class="fwas-error-type">Invalid Response</div>';
				html += '<div class="fwas-error-message">' + escapeHtml(data.error_message) + '</div>';
				html += '</div>';
			}
			html += '</div>';
		}

		// Server Status (success/degraded)
		if (data.status === 'success' || data.status === 'degraded') {
			html += '<div class="fwas-tech-section">';
			html += '<h4>Server Status</h4>';
			html += '<div class="fwas-tech-details">';
			html += '<div class="fwas-tech-row"><span class="fwas-tech-label">Overall Status:</span><span class="fwas-tech-value">' + escapeHtml(data.server_status) + '</span></div>';

			if (data.checks) {
				for (const [check, status] of Object.entries(data.checks)) {
					const statusClass = status === 'ok' || status === 'configured' ? 'fwas-check-ok' : 'fwas-check-fail';
					html += '<div class="fwas-tech-row"><span class="fwas-tech-label">' + escapeHtml(check) + ':</span><span class="fwas-tech-value ' + statusClass + '">' + escapeHtml(status) + '</span></div>';
				}
			}

			if (data.server_message) {
				html += '<div class="fwas-tech-row"><span class="fwas-tech-label">Message:</span><span class="fwas-tech-value">' + escapeHtml(data.server_message) + '</span></div>';
			}
			html += '</div>';
			html += '</div>';
		}

		// HTTP Response Details (always show - no hiding)
		if (data.http_status_code) {
			html += '<div class="fwas-tech-section">';
			html += '<h4>HTTP Response</h4>';
			html += '<div class="fwas-tech-details">';
			html += '<div class="fwas-tech-row"><span class="fwas-tech-label">Status Code:</span><span class="fwas-tech-value">' + data.http_status_code + ' ' + escapeHtml(data.http_status_message) + '</span></div>';
			html += '</div>';

			// Response Headers
			if (data.response_headers && Object.keys(data.response_headers).length > 0) {
				html += '<details class="fwas-tech-details-expandable">';
				html += '<summary>Response Headers</summary>';
				html += '<pre class="fwas-code-block">' + escapeHtml(JSON.stringify(data.response_headers, null, 2)) + '</pre>';
				html += '</details>';
			}

			// Response Body
			if (data.response_body) {
				html += '<details class="fwas-tech-details-expandable" open>';
				html += '<summary>Response Body</summary>';
				html += '<pre class="fwas-code-block">' + escapeHtml(data.response_body) + '</pre>';
				html += '</details>';
			}

			// Decoded Response (if JSON)
			if (data.decoded_response) {
				html += '<details class="fwas-tech-details-expandable">';
				html += '<summary>Decoded JSON Response</summary>';
				html += '<pre class="fwas-code-block">' + escapeHtml(JSON.stringify(data.decoded_response, null, 2)) + '</pre>';
				html += '</details>';
			}

			html += '</div>';
		}

		// Manual Testing Section
		if (data.curl_command) {
			html += '<div class="fwas-tech-section">';
			html += '<h4>Manual Testing</h4>';
			html += '<p>Use this curl command to test connectivity manually from your server:</p>';
			html += '<div class="fwas-curl-command">';
			html += '<code>' + escapeHtml(data.curl_command) + '</code>';
			html += '<button type="button" class="button button-small fwas-copy-curl" data-curl="' + escapeHtml(data.curl_command) + '">Copy</button>';
			html += '</div>';
			html += '</div>';
		}

		// Raw Diagnostic Data (complete transparency)
		html += '<div class="fwas-tech-section">';
		html += '<details class="fwas-tech-details-expandable">';
		html += '<summary>Complete Raw Diagnostic Data</summary>';
		html += '<pre class="fwas-code-block">' + escapeHtml(JSON.stringify(data, null, 2)) + '</pre>';
		html += '<button type="button" class="button fwas-copy-diagnostics" data-diagnostics="' + escapeHtml(JSON.stringify(data, null, 2)) + '">Copy All Diagnostic Data</button>';
		html += '</details>';
		html += '</div>';

		html += '</div>';

		$container.html(html).show();

		// Attach copy handlers
		$('.fwas-copy-curl').on('click', function() {
			const curl = $(this).data('curl');
			copyToClipboard(curl, 'Curl command copied!');
		});

		$('.fwas-copy-diagnostics').on('click', function() {
			const diagnostics = $(this).data('diagnostics');
			copyToClipboard(diagnostics, 'Diagnostic data copied!');
		});
	}

	/**
	 * Copy text to clipboard
	 */
	function copyToClipboard(text, successMsg) {
		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(text).then(function() {
				alert(successMsg);
			}).catch(function(err) {
				console.error('Failed to copy: ', err);
				fallbackCopyText(text, successMsg);
			});
		} else {
			fallbackCopyText(text, successMsg);
		}
	}

	/**
	 * Fallback copy for older browsers
	 */
	function fallbackCopyText(text, successMsg) {
		const $temp = $('<textarea>');
		$('body').append($temp);
		$temp.val(text).select();
		try {
			document.execCommand('copy');
			alert(successMsg);
		} catch (err) {
			alert('Failed to copy. Please copy manually.');
		}
		$temp.remove();
	}

	/**
	 * Escape HTML to prevent XSS
	 */
	function escapeHtml(text) {
		if (typeof text !== 'string') {
			text = String(text);
		}
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
	 * Export training data as JSON
	 */
	function exportTrainingData() {
		const $button = $('#fwas-export-training-data');

		// Show loading state
		$button.prop('disabled', true).text('Exporting...');

		const data = {
			action: 'fwas_export_training_data',
			nonce: fwasDiagnostics.nonce
		};

		$.post(fwasDiagnostics.ajaxurl, data, function(response) {
			$button.prop('disabled', false).text('Export to JSON');

			if (response.success && response.data.data) {
				downloadJSON(response.data.data, 'fullworks-antispam-training-data-' + new Date().toISOString().slice(0, 10) + '.json');
				alert('Training data exported successfully! (' + response.data.count + ' entries)');
			} else {
				const errorMsg = response.data || 'Failed to export training data.';
				alert(errorMsg);
			}
		}).fail(function() {
			$button.prop('disabled', false).text('Export to JSON');
			alert('Failed to export training data. Please try again.');
		});
	}

	/**
	 * Download data as JSON file
	 */
	function downloadJSON(data, filename) {
		const json = JSON.stringify(data, null, 2);
		const blob = new Blob([json], { type: 'application/json' });
		const url = window.URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = filename;
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		window.URL.revokeObjectURL(url);
	}

	/**
	 * Import training data from JSON file
	 */
	function importTrainingData() {
		const $button = $('#fwas-import-training-data');
		const $fileInput = $('#fwas-training-file');
		const $resultContainer = $('#fwas-training-import-result');
		const file = $fileInput[0].files[0];

		if (!file) {
			alert('Please select a JSON file to import.');
			return;
		}

		// Get import mode
		const mode = $('input[name="fwas-import-mode"]:checked').val();

		// Confirm if replace mode
		if (mode === 'replace') {
			if (!confirm('Replace mode will DELETE ALL existing training data and replace it with the imported data. This action cannot be undone. Are you sure you want to continue?')) {
				return;
			}
		}

		// Show loading state
		$button.prop('disabled', true).text('Importing...');
		$resultContainer.html('<p>Importing training data...</p>').show();

		// Create FormData for file upload
		const formData = new FormData();
		formData.append('action', 'fwas_import_training_data');
		formData.append('nonce', fwasDiagnostics.nonce);
		formData.append('mode', mode);
		formData.append('file', file);

		$.ajax({
			url: fwasDiagnostics.ajaxurl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				$button.prop('disabled', false).text('Import from JSON');

				if (response.success && response.data) {
					displayImportResults(response.data);
				} else {
					const errorMsg = response.data || 'Failed to import training data.';
					$resultContainer.html('<div class="notice notice-error"><p>' + escapeHtml(errorMsg) + '</p></div>');
				}
			},
			error: function() {
				$button.prop('disabled', false).text('Import from JSON');
				$resultContainer.html('<div class="notice notice-error"><p>Failed to import training data. Please try again.</p></div>');
			}
		});
	}

	/**
	 * Display training data import results
	 */
	function displayImportResults(results) {
		const $container = $('#fwas-training-import-result');
		let html = '<div class="notice notice-success"><h4>Training Data Import Complete</h4>';

		html += '<ul>';
		html += '<li><strong>Total entries processed:</strong> ' + results.total + '</li>';
		html += '<li><strong>Entries added:</strong> ' + results.added + '</li>';
		html += '<li><strong>Entries updated:</strong> ' + results.updated + '</li>';

		if (results.errors && results.errors.length > 0) {
			html += '<li><strong>Errors:</strong> ' + results.errors.length + '</li>';
		}
		html += '</ul>';

		// Show errors if any
		if (results.errors && results.errors.length > 0) {
			html += '<h5>Errors:</h5>';
			html += '<div class="fwas-import-errors" style="max-height: 200px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd;">';
			html += '<ul>';
			results.errors.forEach(function(error) {
				html += '<li>' + escapeHtml(error) + '</li>';
			});
			html += '</ul>';
			html += '</div>';
		}

		html += '</div>';

		$container.html(html).show();

		// Clear file input
		$('#fwas-training-file').val('');
	}

	/**
	 * Initialize
	 */
	$(document).ready(function() {
		// Load logs on page load
		loadLogs();

		// Filter button
		$('#fwas-filter-logs').on('click', function() {
			currentPage = 1;
			loadLogs();
		});

		// Refresh button
		$('#fwas-refresh-logs').on('click', function() {
			loadLogs();
		});

		// Enter key on search
		$('#fwas-log-search').on('keypress', function(e) {
			if (e.which === 13) {
				currentPage = 1;
				loadLogs();
			}
		});

		// Clear logs button
		$('#fwas-clear-logs').on('click', function() {
			clearLogs();
		});

		// Export button
		$('#fwas-export-logs').on('click', function() {
			exportLogs();
		});

		// Pagination
		$('#fwas-prev-page').on('click', function() {
			if (currentPage > 1) {
				currentPage--;
				loadLogs();
			}
		});

		$('#fwas-next-page').on('click', function() {
			if (currentPage < totalPages) {
				currentPage++;
				loadLogs();
			}
		});

		// Copy URL button
		$('#fwas-copy-url').on('click', function() {
			copyURL();
		});

		// Regenerate token button
		$('#fwas-regenerate-token').on('click', function() {
			regenerateToken();
		});

		// Generate report button
		$('#fwas-generate-report').on('click', function() {
			generateReport();
		});

		// Check remote server button
		$('#fwas-check-remote-server').on('click', function() {
			checkRemoteServer();
		});

		// Check custom URL button
		$('#fwas-check-custom-url').on('click', function() {
			checkCustomURL();
		});

		// Export training data button
		$('#fwas-export-training-data').on('click', function() {
			exportTrainingData();
		});

		// Import training data button
		$('#fwas-import-training-data').on('click', function() {
			importTrainingData();
		});

		// Enable/disable import button based on file selection
		$('#fwas-training-file').on('change', function() {
			const hasFile = this.files && this.files.length > 0;
			$('#fwas-import-training-data').prop('disabled', !hasFile);
		});
	});

})(jQuery);
