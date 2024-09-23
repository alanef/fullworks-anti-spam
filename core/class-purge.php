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
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 */
namespace Fullworks_Anti_Spam\Core;

use GFAPI;
class Purge {
    /**
     * The ID of this plugin.
     *
     */
    private $plugin_name;

    private $spam_options;

    private $detect_404_options;

    /**
     * The version of this plugin.
     *
     */
    private $version;

    private $forms_registrations;

    /**
     * Initialize the class and set its properties.
     *
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        // get registered forms
        $forms_registrations_obj = new Forms_Registrations();
        $this->forms_registrations = $forms_registrations_obj->get_registered_forms();
        add_action( 'fwas_purge_daily', array(__CLASS__, 'purge_email_and_spam_log__premium_onl') );
        foreach ( $this->forms_registrations as $forms_registration ) {
            if ( isset( $forms_registration['spam_purge_cb'] ) && is_callable( $forms_registration['spam_purge_cb'] ) ) {
                if ( !isset( $forms_registration['email_log'] ) || !$forms_registration['email_log'] ) {
                    add_action( 'fwas_purge_daily', $forms_registration['spam_purge_cb'] );
                }
            }
        }
    }

    public function daily() {
        set_time_limit( 0 );
        $this->spam_options = get_option( 'fullworks-anti-spam' );
        if ( $this->spam_options['days'] > 0 ) {
            do_action( 'fwas_purge_daily' );
        }
        $this->purge_local_logs();
        $l = ini_get( 'max_execution_time' );
        if ( $l ) {
            set_time_limit( $l );
        }
    }

    public static function purge_comment_spam() {
        $options = get_option( 'fullworks-anti-spam' );
        $limit = 200;
        $args = array(
            'order'      => 'ASC',
            'status'     => 'spam',
            'number'     => $limit,
            'date_query' => array(
                'before' => gmdate( 'Y-m-d', strtotime( '-' . $options['days'] . ' days' ) ),
            ),
        );
        $comments = get_comments( $args );
        if ( $comments ) {
            foreach ( $comments as $comment ) {
                wp_delete_comment( $comment->comment_ID, true );
            }
        }
        if ( count( $comments ) >= $limit ) {
            wp_schedule_single_event( time() + 120, 'fullworks_anti_spam_daily_admin' );
        }
    }

    public static function grunion_delete_old_spam() {
        $options = get_option( 'fullworks-anti-spam' );
        /*
         * following code from
         *
         * Grunion Contact Form
         *
         * Author: automattic
         *
         * GPL 2
         *
         * modified to allow variable days deletion
         */
        global $wpdb;
        $grunion_delete_limit = 100;
        $now_gmt = current_time( 'mysql', 1 );
        $sql = $wpdb->prepare(
            "\n\t\tSELECT `ID`\n\t\tFROM {$wpdb->posts}\n\t\tWHERE DATE_SUB( %s, INTERVAL %d DAY ) > `post_date_gmt`\n\t\t\tAND `post_type` = 'feedback'\n\t\t\tAND `post_status` = 'spam'\n\t\tLIMIT %d\n\t",
            $now_gmt,
            $options['days'],
            $grunion_delete_limit
        );
        $post_ids = $wpdb->get_col( $sql );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        foreach ( (array) $post_ids as $post_id ) {
            // force a full delete, skip the trash
            wp_delete_post( $post_id, true );
        }
        if ( apply_filters( 'grunion_optimize_table', false ) ) {
            $wpdb->query( "OPTIMIZE TABLE {$wpdb->posts}" );
        }
        // if we hit the max then schedule another run
        if ( count( $post_ids ) >= $grunion_delete_limit ) {
            wp_schedule_single_event( time() + 700, 'fullworks_anti_spam_daily_admin' );
        }
    }

    private function purge_local_logs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fwantispam_log';
        $result = $wpdb->query( "DELETE FROM {$table_name} WHERE logdate < CURRENT_DATE - INTERVAL 30 DAY" );
        /** @var \Freemius $fwantispam_fs Freemius global object. */
        global $fwantispam_fs;
    }

}
