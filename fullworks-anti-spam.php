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

/**
 *
 * Plugin Name:       Spam Protection for Contact Form 7 & WPForms ++ - No CAPTCHA
 * Plugin URI:        https://fullworksplugins.com/products/anti-spam/
 * Description:       Stop spam on Contact Form 7, WPForms, Jetpack forms & comments. Actually FREE for business use (unlike Akismet). No CAPTCHA needed, works instantly.
 * Version:           2.6.1
 * Author:            Fullworks
 * Author URI:        https://fullworksplugins.com/
 * Requires at least: 5.3.0
 * Requires PHP:      7.4
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       fullworks-anti-spam
 * Domain Path:       /languages
 *
 * @package fullworks-anti-spam
 *
  *
 *
 */

namespace Fullworks_Anti_Spam;

use Fullworks_Anti_Spam\Control\Core;
use Fullworks_Anti_Spam\Control\Freemius_Config;
use Fullworks_WP_Autoloader\AutoloaderPlugin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'FULLWORKS_ANTI_SPAM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FULLWORKS_ANTI_SPAM_CONTENT_DIR', dirname( plugin_dir_path( __DIR__ ) ) );
define( 'FULLWORKS_ANTI_SPAM_PLUGINS_TOP_DIR', plugin_dir_path( __DIR__ ) );
define( 'FULLWORKS_ANTI_SPAM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'FULLWORKS_ANTI_SPAM_PLUGIN_NAME', 'fullworks-anti-spam' );
define( 'FULLWORKS_ANTI_SPAM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'FULLWORKS_ANTI_SPAM_PLUGIN_VERSION', '2.6.1' );

/**
 * Debug helper to trace early translation loading (WP 6.7+)
 * Only active when WP_DEBUG is true
 * Remove this block after identifying and fixing all early translation issues
 */
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	add_action(
		'doing_it_wrong_run',
		function ( $function, $message, $version ) {
			if ( $function === '_load_textdomain_just_in_time' ) {
				// Only log if it's our plugin's domain
				if ( strpos( $message, 'fullworks-anti-spam' ) !== false ) {
					error_log( '=== TRANSLATION LOADED TOO EARLY ===' );
					error_log( 'Function: ' . $function );
					error_log( 'Message: ' . $message );
					error_log( 'Version: ' . $version );

					// Show current hook/action
					error_log( 'Current action: ' . ( current_action() ?: 'none' ) );
					error_log( "Did 'init' fire? " . ( did_action( 'init' ) ? 'yes' : 'NO' ) );

					// Full backtrace
					error_log( 'Backtrace:' );
					$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
					foreach ( $backtrace as $i => $trace ) {
						if ( isset( $trace['file'] ) && isset( $trace['line'] ) ) {
							$file          = str_replace( ABSPATH, '', $trace['file'] ); // Shorten path
							$function_name = $trace['function'] ?? 'unknown';
							error_log( "  #$i $file:{$trace['line']} -> $function_name()" );
						}
					}
					error_log( "=== END BACKTRACE ===\n" );
				}
			}
		},
		10,
		3
	);
}

require_once FULLWORKS_ANTI_SPAM_PLUGIN_DIR . 'vendor/autoload.php';
new AutoloaderPlugin( __NAMESPACE__, __DIR__ );
/** @var \Freemius $fwas_fs Freemius global object. */
global $fwas_fs;
$fwas_freemius = new Freemius_Config();
$fwas_freemius->init();

if ( ! defined( 'FWAS_SERVER' ) ) {
	define( 'FWAS_SERVER', 'https://spam01.fullworks.net/apiv1/' );
}


if ( ! function_exists( 'Fullworks_Anti_Spam\run_fullworks_anti_spam' ) ) {
	function run_fullworks_anti_spam() {
		/** @var \Freemius $fwas_fs Freemius global object. */
		global $fwas_fs;
		do_action( 'fwas_fs_loaded' );
		register_activation_hook( __FILE__, array( '\Fullworks_Anti_Spam\Control\Activator', 'activate' ) );
		add_action(
			'wpmu_new_blog',
			array(
				'\Fullworks_Anti_Spam\Control\Activator',
				'on_create_blog_tables',
			),
			10,
			6
		);
		register_deactivation_hook( __FILE__, array( '\Fullworks_Anti_Spam\Control\Deactivator', 'deactivate' ) );
		add_filter( 'wpmu_drop_tables', array( '\Fullworks_Anti_Spam\Control\Deactivator', 'on_delete_blog_tables' ) );
		$fwas_fs->add_action( 'after_uninstall', array( '\Fullworks_Anti_Spam\Control\Uninstall', 'uninstall' ) );
		$plugin = new Core( $fwas_fs );
		add_action(
			'plugins_loaded',
			function () use ( $plugin ) {
				$plugin->run();
			},
			- 1 // run early but need to make sure other plugins are loaded
		);
		do_action( 'fwas_loaded' );
	}

	run_fullworks_anti_spam();

} else {
	$fwas_fs->set_basename( true, __FILE__ );
}

