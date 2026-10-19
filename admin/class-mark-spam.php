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

use Fullworks_Anti_Spam\Core\Training_Data;
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
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

    private $classification;

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
        $this->record_ham();
    }

    public function spam_comment( $comment_id, $comment ) {
        $this->build_comment_message( $comment );
        $this->record_spam();
    }

    private function build_comment_message( $comment ) {
        $this->body = array(
            'content' => $comment->comment_content,
            'type'    => $comment->comment_type,
        );
    }

    private function record_ham() {
        $this->classification = 'nospam';
        $this->record_classification();
    }

    private function record_spam() {
        $this->classification = 'spam';
        $this->record_classification();
    }

    /**
     * Record a spam/ham classification.
     *
     * Always written to the site's own training table. This used to POST the
     * submission to the Fullworks server whenever transmission was enabled,
     * carrying the submitter's name, email address and IP along with the site
     * URL and the site administrator's email. Nothing is sent anywhere now --
     * classifications stay on the site that made them.
     *
     * Premium only, and the guard is load-bearing in two ways. The free build
     * has no classifier to read the corpus and no table to hold it -- the
     * activator creates fwantispam_training_data under is__premium_only() --
     * so free was inserting into a table that does not exist and failing
     * silently on every comment marked as spam. It also has no Training_Data
     * class, that file being __premium_only, so LOCAL_KEY_BASE below would be
     * a fatal call on a stripped class if this ever ran there.
     */
    private function record_classification() {
    }

    /**
     * Collapse newlines and strip punctuation, without corrupting UTF-8.
     *
     * The pattern used to be /\R/ with no unicode modifier. In byte mode \R
     * also matches \x85, which is a continuation byte inside many multi-byte
     * UTF-8 characters -- so it cut Korean, Arabic and Russian text mid-character
     * and produced invalid UTF-8. wpdb then rejected the row with "The supplied
     * value ... contains invalid data" and the entry was silently dropped.
     *
     * @param string $text
     *
     * @return string
     */
    private function normalise_training_text( $text ) {
        $text = str_replace( self::REPLACE, ' ', (string) $text );
        $collapsed = preg_replace( '/\\R/u', ' ', $text );
        // preg_replace returns null on malformed input; keep the original rather
        // than replacing the text with nothing.
        return ( null === $collapsed ? $text : $collapsed );
    }

}
