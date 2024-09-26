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
 * to mark as spam or not spam
 * and if allowed log to server
 *
 */
namespace Fullworks_Anti_Spam\Admin;

use Fullworks_Anti_Spam\Core\Utilities;
use GFAPI;
/**
 * Class Admin
 * @package Fullworks_Anti_Spam\Admin
 */
class Mark_Spam {
    /** @var Utilities $utilities */
    protected $utilities;

    /** @var \Freemius $freemius Object for freemius. */
    protected $freemius;

    /**
     * The ID of this plugin.
     *
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     */
    private $version;

    private $body;

    private $route;

    const REPLACE = array(
        '\\r',
        '\\n',
        '[',
        ']',
        ',',
        '.',
        ':',
        '<',
        '>',
        '/',
        '#',
        '"',
        '\'',
        '\\',
        '`',
        '~',
        '!'
    );

    /**
     * Admin constructor.
     *
     * @param $plugin_name
     * @param $version
     * @param $utilities
     */
    public function __construct(
        $plugin_name,
        $version,
        $utilities,
        $freemius
    ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->utilities = $utilities;
        $this->freemius = $freemius;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     */
    public function hooks() {
        add_action(
            'unspammed_comment',
            array($this, 'unspam_comment'),
            10,
            2
        );
        add_action(
            'spammed_comment',
            array($this, 'spam_comment'),
            10,
            2
        );
    }

    public function unspam_comment( $comment_id, $comment ) {
        $this->build_comment_message( $comment );
        $this->post_unspam_event();
    }

    public function spam_comment( $comment_id, $comment ) {
        $this->build_comment_message( $comment );
        $this->post_spam_event();
    }

    private function build_comment_message( $comment ) {
        $this->body = array_merge( array(
            'author'       => $comment->comment_author,
            'author_email' => $comment->comment_author_email,
            'author_url'   => $comment->comment_author_url,
            'author_IP'    => $comment->comment_author_IP,
            'date'         => $comment->comment_date,
            'content'      => $comment->comment_content,
            'type'         => $comment->comment_type,
        ), $this->get_site_info() );
    }

    private function get_site_info() {
        return array(
            'site_url'         => get_site_url(),
            'site_admin_email' => get_bloginfo( 'admin_email' ),
            'detected_ip'      => $this->utilities->get_ip(),
        );
    }

    private function post_unspam_event() {
        $this->route = 'logham';
        $this->post_event();
    }

    private function post_spam_event() {
        $this->route = 'logspam';
        $this->post_event();
    }

    private function post_event() {
        // dont send if option sendspam = 0
        $this->body['content'] = preg_replace( '/\\R/', ' ', str_replace( self::REPLACE, ' ', $this->body['content'] ) );
        $options = get_option( 'fullworks-anti-spam' );
        if ( isset( $options['sendspam'] ) && 0 == $options['sendspam'] || $this->freemius->is_plan_or_trial( 'gdpr, true' ) ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'fwantispam_training_data';
            //check if $this->body['content'] message is in db update if so
            $existing = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE message = %s", wp_unslash( $this->body['content'] ) ), ARRAY_A );
            if ( !empty( $existing ) ) {
                $wpdb->update( $table_name, array(
                    'spam_or_ham' => ( $this->route == 'logspam' ? 'spam' : 'nospam' ),
                ), array(
                    'message' => wp_unslash( $this->body['content'] ),
                ) );
            } else {
                // insert into db
                $wpdb->insert( $table_name, array(
                    'message'     => wp_unslash( $this->body['content'] ),
                    'spam_or_ham' => ( $this->route == 'logspam' ? 'spam' : 'nospam' ),
                    'remote_key'  => 0,
                ) );
            }
        } else {
            $args = array(
                'method' => 'POST',
                'body'   => $this->body,
            );
            $response = wp_safe_remote_post( trailingslashit( FWAS_SERVER ) . $this->route, $args );
        }
    }

}
