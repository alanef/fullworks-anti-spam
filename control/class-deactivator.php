<?php
/**
 * @copyright (c) 2019.
 * @author            Alan Fuller (support@fullworks)
 * @licence           GPL V3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @link                  https://fullworks.net
 *
 * This file is part of Fullworks Security.
 *
 *     Fullworks Security is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     (at your option) any later version.
 *
 *     Fullworks Security is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with   Fullworks Security.  https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 *
 */

namespace Fullworks_Anti_Spam\Control;

/**
 * Class Deactivator
 * @package Fullworks_Anti_Spam\Control
 */
class Deactivator {

	/**
	 *
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'fwas_admin_daily' );
		wp_clear_scheduled_hook( 'fullworks_anti_spam_monthly_reports' );
		wp_clear_scheduled_hook( 'fullworks_anti_spam_alerts' );
		wp_clear_scheduled_hook( 'fullworks_anti_spam_daily_training' );
	}

	/**
	 * Drop this plugin's tables with a deleted multisite site.
	 *
	 * Hooked to wpmu_drop_tables, which WordPress runs as DROP TABLE IF
	 * EXISTS, so listing a table the build never created is harmless.
	 *
	 * @param array $tables  Tables WordPress will drop.
	 * @param int   $blog_id The site being deleted.
	 *
	 * @return array
	 */
	public static function on_delete_blog_tables( $tables, $blog_id ) {
		global $wpdb;

		$prefix = $wpdb->get_blog_prefix( $blog_id );
		// The same tables Uninstall drops.
		$ours = array(
			'fwantispam_log',
			'fwantispam_allow_deny',
			'fwantispam_training_data',
			'fwantispam_email_log',
			'fwantispam_quarantine',
			'fwas_diagnostics_log',
		);
		foreach ( $ours as $table ) {
			$tables[] = $prefix . $table;
		}

		return $tables;
	}
}
