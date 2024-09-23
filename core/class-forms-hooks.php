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

use Cassandra\Exception\ValidationException;
use Fullworks_Anti_Spam\Anti_Spam_Api;
use Fullworks_Anti_Spam\Data\Log;
use GFAPI;
use WP_Error;
class Forms_Hooks {
    /** @var Log $log */
    protected $log;

    /** @var Utilities $utilities */
    protected $utilities;

    /**
     * @var $options array
     */
    protected $options;

    /** @var \Freemius $freemius Object for freemius. */
    protected $freemius;

    /**
     * @var Anti_Spam_Api $api
     */
    private $api;

    /**
     * @var Spam_Checks
     */
    private $spam_checks;

    /**
     * Process_Spam_Checks constructor.
     *
     * @param $log
     * @param $options
     * @param $utilities
     * @param $freemius
     */
    public function __construct(
        $log,
        $options,
        $utilities,
        $freemius,
        $api
    ) {
        $this->log = $log;
        $this->options = $options;
        $this->utilities = $utilities;
        $this->freemius = $freemius;
        $this->api = $api;
        $this->spam_checks = new Spam_Checks();
    }

    public function set_plugins_loaded_hooks() {
        if ( !current_user_can( 'edit_posts' ) ) {
            add_filter(
                'pre_comment_approved',
                array($this, 'wp_comment_is_spam'),
                99,
                2
            );
        }
    }

    public function set_init_hooks() {
    }

    /**
     * @param $status
     * @param $commentdata
     *
     * @return string|WP_Error
     */
    public function wp_comment_is_spam( $status, $commentdata ) {
        $offline = false;
        if ( isset( $commentdata['bulk_run'] ) ) {
            $offline = true;
        }
        $is_spam = $this->api->is_spam(
            false,
            'comments',
            $commentdata['comment_author_email'],
            $commentdata['comment_author'] . ' ' . $commentdata['comment_content'],
            $offline
        );
        if ( false !== $is_spam ) {
            if ( 'BOT' === $is_spam ) {
                return 'spam';
            } else {
                return $status;
            }
            return $status;
        }
        return $status;
    }

}
