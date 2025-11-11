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
            $blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
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
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'fwantispam_log';
        $sql = "CREATE TABLE {$table_name} (\n\t\tID int NOT NULL AUTO_INCREMENT,\n\t\tIP varbinary(16) NOT NULL,\n\t\tlogdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,\n\t\tserver text,\n\t\tevent varchar(256),\n\t\teventcode int,\n\t\teventcode_str varchar(256),\n\t\tPRIMARY KEY  (ID)\n\t) {$charset_collate};";
        dbDelta( $sql );
        /*
         * @since 2.0.0
         * create  Allow_Deny table keyed on type - type is email or IP or ( country premium ) and the value with allow or deny as a value, when email can be a regex when IP can be subnet
         *
         */
        $table_name = $wpdb->prefix . 'fwantispam_allow_deny';
        $sql = "CREATE TABLE {$table_name} (\n\t\tID int NOT NULL AUTO_INCREMENT,\n\t\tallow_deny varchar(8) NOT NULL,\n\t\ttype varchar(16) NOT NULL,\n\t\tvalue varchar(256) NOT NULL,\n\t\tnotes varchar(256),\n\t\tPRIMARY KEY  (ID),\n\t\tINDEX type_idx (type)\n\t) {$charset_collate};";
        dbDelta( $sql );
        update_option( 'fullworks_anti_spam_db_version', '2.0' );
        /** @var \Freemius $fwantispam_fs Freemius global object. */
        global $fwas_fs;
        // create an email message log table
        $table_name = $wpdb->prefix . 'fwantispam_email_log';
        $sql = "CREATE TABLE {$table_name} (\n\t\t\tID int NOT NULL AUTO_INCREMENT,\n\t\t\tlogdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,\n\t\t\tformsystem varchar(256),\n\t\t\tformid varchar(256),\n\t\t\tsubject varchar(998),\n\t\t\treplyto varchar(998),\n\t\t\ttoemail varchar(998),\n\t\t\tspam_or_ham varchar(16),\n\t\t\theaders text,\n\t\t\tmessage\ttext,\n\t\t\tPRIMARY KEY  (ID)\n\t\t) {$charset_collate};";
        dbDelta( $sql );
    }

    /**
     * @param $blog_id
     * @param $user_id
     * @param $domain
     * @param $path
     * @param $site_id
     * @param $meta
     */
    public static function on_create_blog_tables(
        $blog_id,
        $user_id,
        $domain,
        $path,
        $site_id,
        $meta
    ) {
        if ( is_plugin_active_for_network( trailingslashit( basename( FULLWORKS_ANTI_SPAM_PLUGIN_DIR ) ) . 'fullworks-anti-spam.php' ) ) {
            switch_to_blog( $blog_id );
            self::create_tables();
            restore_current_blog();
        }
    }

}
