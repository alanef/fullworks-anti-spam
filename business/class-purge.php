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
namespace Fullworks_Anti_Spam\Business;

use  GFAPI ;
class Purge
{
    /**
     * The ID of this plugin.
     *
     */
    private  $plugin_name ;
    private  $spam_options ;
    private  $detect_404_options ;
    /**
     * The version of this plugin.
     *
     */
    private  $version ;
    /**
     * Initialize the class and set its properties.
     *
     */
    public function __construct( $plugin_name, $version )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    public function daily()
    {
        /**
         * @var \Freemius $fwantispam_fs freemius SDK.
         */
        global  $fwantispam_fs ;
        set_time_limit( 0 );
        $this->spam_options = get_option( 'fullworks-anti-spam' );
        if ( $this->spam_options['days'] > 0 ) {
            $this->purge_comment_spam();
        }
        $this->purge_local_log();
        $l = ini_get( 'max_execution_time' );
        if ( $l ) {
            set_time_limit( $l );
        }
    }
    
    private function purge_comment_spam()
    {
        $args = array(
            'order'      => 'ASC',
            'status'     => 'spam',
            'date_query' => array(
            'before' => gmdate( 'Y-m-d', strtotime( '-' . $this->spam_options['days'] . ' days' ) ),
        ),
        );
        $comments = get_comments( $args );
        if ( $comments ) {
            foreach ( $comments as $comment ) {
                wp_delete_comment( $comment->comment_ID, true );
            }
        }
    }
    
    private function purge_local_log()
    {
        global  $wpdb ;
        $table_name = $wpdb->prefix . 'fwantispam_log';
        $result = $wpdb->query( "DELETE FROM `{$table_name}` WHERE logdate < CURRENT_DATE - INTERVAL 30 DAY" );
        $a = 1;
    }

}