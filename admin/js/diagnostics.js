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
	});

})(jQuery);
