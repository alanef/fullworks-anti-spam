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

use Fullworks_Anti_Spam\Core\Utilities;

/**
 * Class Activator
 * @package Fullworks_Anti_Spam\Control
 */
class Activator {

	/**
	 * @param $network_wide
	 */
	public static function activate( $network_wide ) {
		global $wpdb;
		if ( is_multisite() && $network_wide ) {
			// Get all blogs in the network and add tables on each
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				self::create_tables();
				restore_current_blog();
			}
		} else {
			self::create_tables();
		}
	}

	/**
	 *
	 */
	public static function create_tables() {
		// database set up
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$charset_collate = $wpdb->get_charset_collate();


		$table_name = $wpdb->prefix . 'fwantispam_log';
		$sql        = "CREATE TABLE $table_name (
		ID int NOT NULL AUTO_INCREMENT,
		IP varbinary(16) NOT NULL,
		logdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		server text,
		event varchar(256),
		eventcode int,
		PRIMARY KEY  (ID)
	) $charset_collate;";
		dbDelta( $sql );

		update_option( 'fullworks_anti_spam_db_version', '1.0' );

	}

	/**
	 * @param $blog_id
	 * @param $user_id
	 * @param $domain
	 * @param $path
	 * @param $site_id
	 * @param $meta
	 */
	public static function on_create_blog_tables( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
		if ( is_plugin_active_for_network( trailingslashit( basename( FULLWORKS_ANTI_SPAM_PLUGIN_DIR ) ) . 'fullworks-anti-spam.php' ) ) {
			switch_to_blog( $blog_id );
			self::create_tables();
			restore_current_blog();
		}
	}

}
