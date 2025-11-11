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
 * Class Uninstall
 * @package Fullworks_Anti_Spam\Control
 */
class Uninstall {
    /**
     * @param $network_wide
     */
    public static function uninstall( $network_wide ) {
        global $wpdb;
        if ( is_multisite() && $network_wide ) {
            // Get all blogs in the network and delete tables on each
            $blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
            foreach ( $blog_ids as $blog_id ) {
                switch_to_blog( $blog_id );
                self::delete_tables();
                restore_current_blog();
            }
        } else {
            self::delete_tables();
        }
        delete_option( "fullworks_anti_spam_db_version" );
        // settings
        delete_option( 'fullworks-anti-spam' );
        // transients
        delete_transient( 'fullworks_anti_spam_key_name' );
        delete_transient( 'fullworks_anti_spam_key_value' );
        delete_transient( 'fwantispam_training_data_obj' );
    }

    /**
     *
     */
    private static function delete_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fwantispam_log';
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $table_name is safe, using prefix + constant
        $wpdb->query( "DROP TABLE IF EXISTS " . $table_name );
        /** @var \Freemius $fwantispam_fs Freemius global object. */
        global $fwas_fs;
    }

}
