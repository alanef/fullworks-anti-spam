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
namespace Fullworks_Anti_Spam\Core;

use  DateTime ;
use  DateTimeZone ;
use  WP_Http ;
use  WP_Error ;
/**
 * Class Utilities
 * @package Fullworks_Anti_Spam\Control
 */
class Utilities
{
    /**
     * @var
     */
    protected static  $instance ;
    /**
     * @var
     */
    protected  $utility_data ;
    protected  $settings_page_tabs ;
    /**
     * Utilities constructor.
     */
    public function __construct()
    {
    }
    
    /**
     * @return Utilities
     */
    public static function get_instance()
    {
        if ( null == self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * @param  $message ( WP_Error or array or string )
     */
    public static function error_log( $message, $called_from = 'Log' )
    {
        
        if ( WP_DEBUG === true ) {
            
            if ( is_wp_error( $message ) ) {
                $error_string = $message->get_error_message();
                $error_code = $message->get_error_code();
                error_log( $called_from . ':' . $error_code . ':' . $error_string );
                return;
            }
            
            
            if ( is_array( $message ) || is_object( $message ) ) {
                error_log( $called_from . ':' . print_r( $message, true ) );
                return;
            }
            
            error_log( 'Log:' . $message );
            return;
        }
    
    }
    
    /**
     * @param null $ip
     *
     * @return string  Sanitized IP address
     */
    public function get_ip( $ipaddress = null )
    {
        
        if ( !empty($ipaddress) ) {
            if ( false === WP_Http::is_ip_address( $ipaddress ) ) {
                return '0.0.0.0';
            }
            return $ipaddress;
        }
        
        $ipaddress = '0.0.0.0';
        
        if ( getenv( 'HTTP_CF_CONNECTING_IP' ) ) {
            $ipaddress = getenv( 'HTTP_CLIENT_IP' );
        } elseif ( getenv( 'HTTP_CLIENT_IP' ) ) {
            $ipaddress = getenv( 'HTTP_CLIENT_IP' );
        } elseif ( getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
            $ipaddress = getenv( 'HTTP_X_FORWARDED_FOR' );
        } elseif ( getenv( 'HTTP_X_FORWARDED' ) ) {
            $ipaddress = getenv( 'HTTP_X_FORWARDED' );
        } elseif ( getenv( 'HTTP_FORWARDED_FOR' ) ) {
            $ipaddress = getenv( 'HTTP_FORWARDED_FOR' );
        } elseif ( getenv( 'HTTP_FORWARDED' ) ) {
            $ipaddress = getenv( 'HTTP_FORWARDED' );
        } elseif ( getenv( 'REMOTE_ADDR' ) ) {
            $ipaddress = getenv( 'REMOTE_ADDR' );
        }
        
        // sanitize IP address
        if ( false === WP_Http::is_ip_address( $ipaddress ) ) {
            $ipaddress = '0.0.0.0';
        }
        return $ipaddress;
    }
    
    /**
     * @param $option
     * @param $length
     *
     * @return mixed|void
     */
    public function get_uid( $option, $length )
    {
        
        if ( !($uid = get_transient( $option )) ) {
            $uid = wp_generate_password( $length, false, false );
            set_transient( $option, $uid );
        }
        
        return $uid;
    }
    
    public function register_settings_page_tab(
        $title,
        $page,
        $href,
        $position
    )
    {
        $this->settings_page_tabs[$page][$position] = array(
            'title' => $title,
            'href'  => $href,
        );
    }
    
    public function get_settings_page_tabs( $page )
    {
        $tabs = $this->settings_page_tabs[$page];
        ksort( $tabs );
        return $tabs;
    }
    
    public function get_spam_stats()
    {
        /** @var \Freemius $fwantispam_fs Freemius global object. */
        global  $fwantispam_fs ;
        global  $wpdb ;
        $table_name = $wpdb->prefix . 'fwantispam_log';
        $counts = $wpdb->get_results( "SELECT eventcode, count(*) 'count' from {$table_name} WHERE eventcode > 999 AND eventcode < 2000  GROUP BY eventcode" );
        $rows = [];
        $total = 0;
        foreach ( $counts as $count ) {
            $line = '';
            $total += $count->count;
            
            if ( 1410 == $count->eventcode ) {
                $link = '<a href="' . admin_url( 'edit-comments.php?comment_status=spam' ) . '">' . __( 'Review', 'fullworks-anti-spam' ) . '</a>';
                $line = [
                    'source' => __( 'Comments', 'fullworks-anti-spam' ),
                    'type'   => __( 'Bot', 'fullworks-anti-spam' ),
                    'count'  => $count->count,
                    'link'   => $link,
                ];
            }
            
            if ( !empty($line) ) {
                $rows[] = $line;
            }
        }
        $rows[] = [
            'source' => '',
            'type'   => __( 'Total', 'fullworks-anti-spam' ),
            'count'  => $total,
            'link'   => '',
        ];
        return $rows;
    }
    
    public function debug_log( $data )
    {
        if ( !defined( 'WP_DEBUG' ) || true !== WP_DEBUG ) {
            return;
        }
        if ( is_array( $data ) ) {
            $data = print_r( $data, true );
        }
        error_log( '>>> fullworks anti spam debug: ' . $data );
    }

}