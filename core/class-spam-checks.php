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

use Error;
use Fullworks_Anti_Spam\Data\Log;
use GFAPI;
/**
 * Class Process_Spam_Checks
 * @package Fullworks_Anti_Spam\Control
 */
class Spam_Checks {
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
     * @var Spam_Analysis
     */
    private $spam_analysis;

    /** @var array $forms_registrations */
    private $forms_registrations;

    public static $spam_level;

    /** @var string $submission_id Unique ID for this spam check */
    private $submission_id;

    /**
     * Process_Spam_Checks constructor.
     *
     * @param $log
     * @param $options
     * @param $utilities
     * @param $freemius
     */
    public function __construct() {
        global $fwas_fs;
        $this->options = get_option( 'fullworks-anti-spam' );
        $this->utilities = new Utilities();
        $this->log = new Log($this->utilities);
        $this->freemius = $fwas_fs;
        // Don't load forms here - they'll be loaded lazily when needed
    }

    /**
     * Get registered forms (lazy loading)
     *
     * @return array
     */
    private function get_forms_registrations() {
        if ( null === $this->forms_registrations ) {
            $this->forms_registrations = Forms_Registrations::get_registered_forms();
        }
        return $this->forms_registrations;
    }

    public function get_spam_level() {
        return self::$spam_level;
    }

    private function set_spam_level( $spam_level ) {
        self::$spam_level = $spam_level;
    }

    /**
     * Logs a spam event.
     *
     * @param string $form_system The form system.
     * @param string $type The type of spam event.
     */
    public function spam_log( $form_system, $type ) {
        $forms = $this->get_forms_registrations();
        $this->log->run( sprintf( 
            // translators: %s: Form plugin name e.g. Gravity Forms
            esc_html__( '%1$s (%2$s)', 'fullworks-anti-spam' ),
            $forms[$form_system]['name'],
            $type
         ), $form_system . ',' . $type );
    }

    /**
     * Check if an email is spam.
     *
     * @param boolean|string $spam The initial spam status (if already determined. False if not currently spam).
     * @param string $form_system The form system.
     * @param string $email The email address.
     * @param string $message The email message content.
     *
     * @return boolean|string       False if not spam, or the spam status ('DENY', 'BOT', 'IP_BLK_LST', 'SNGL_WRD', 'HUMAN').
     */
    public function is_spam(
        $spam,
        $form_system,
        $email,
        $message,
        $offline = false,
        $options = array()
    ) {
        // Generate unique submission ID for tracking logs
        $this->submission_id = uniqid( 'sub_', true );
        $this->options = array_merge( $this->options, $options );
        $this->utilities->debug_log( array(
            'submission_id' => $this->submission_id,
            'step:'         => 'is_spam: About to check',
            'content:'      => $message,
            'is_spam:'      => $spam,
            'form_system'   => $form_system,
            'email'         => $email,
        ) );
        $this->set_spam_level( 0 );
        if ( false !== $spam ) {
            $this->set_spam_level( 0 );
            return $spam;
        }
        $forms = $this->get_forms_registrations();
        $headers = '';
        $message = trim( $message );
        $deny_result = $this->is_denied( $email, $message );
        if ( $deny_result['matched'] ) {
            $this->utilities->debug_log( array(
                'submission_id'  => $this->submission_id,
                'step:'          => 'is_spam: DENY rule matched',
                'rule_type:'     => $deny_result['type'],
                'rule:'          => $deny_result['rule'],
                'checked_value:' => $deny_result['checked_value'],
            ) );
            $this->set_spam_level( 100 );
            $spam = 'DENY';
        }
        $allow_result = $this->is_allowed( $email, $message );
        if ( $allow_result['matched'] ) {
            $this->utilities->debug_log( array(
                'submission_id'  => $this->submission_id,
                'step:'          => 'is_spam: ALLOW rule matched',
                'rule_type:'     => $allow_result['type'],
                'rule:'          => $allow_result['rule'],
                'checked_value:' => $allow_result['checked_value'],
            ) );
            $this->set_spam_level( 0 );
            return false;
            // on allow list so no other checks
        }
        $option = ( 'comments' == $form_system ? 'comments' : 'forms' );
        if ( !$spam && isset( $this->options[$option] ) && $this->options[$option] && $forms[$form_system]['protection_level'] > 0 ) {
            if ( !$offline ) {
                if ( !$this->is_valid() ) {
                    $this->set_spam_level( 100 );
                    $spam = 'BOT';
                }
            }
        }
        $this->utilities->debug_log( array(
            'submission_id' => $this->submission_id,
            'step:'         => 'is_spam: Checks complete',
            'is_spam:'      => $spam,
        ) );
        if ( false !== $spam ) {
            $this->spam_log( $form_system, $spam );
        }
        return $spam;
    }

    /**
     * @return bool
     */
    private function is_valid() {
        // Generate uniq keys by help of get_uid function
        $key = $this->utilities->get_uid( 'fullworks_anti_spam_key_name', 12 );
        $value = $this->utilities->get_uid( 'fullworks_anti_spam_key_value', 64 );
        // First condition: Check if the POST variable exists and its value matches
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No action as fullworks_anti_spam_key_name is a custom style of nonce dynamically generated
        $is_valid_type_1 = !empty( $_POST[$key] ) && $_POST[$key] === $value;
        // Second condition: Check if 'data' key in POST variable contains the key-value pair
        // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- No action as fullworks_anti_spam_key_name is a custom style of nonce dynamically generated plus not stored
        $is_valid_type_2 = !empty( $_POST['data'] ) && false !== strpos( $_POST['data'], $key . '=' . $value );
        // Return true if any of the conditions is true
        $this->utilities->debug_log( array(
            'submission_id' => $this->submission_id,
            'step:'         => 'is_valid: Checking keys',
            'key:'          => $key,
            'value:'        => $value,
            'valid1:'       => ( !empty( $_POST[$key] ) ? sanitize_text_field( $_POST[$key] ) : '' ),
            'valid2:'       => ( !empty( $_POST['data'] ) ? sanitize_text_field( $_POST['data'] ) : '' ),
        ) );
        return $is_valid_type_1 || $is_valid_type_2;
    }

    public function ip_in_subnet( $ip, $subnet ) {
        if ( strpos( $subnet, '/' ) === false ) {
            $subnet .= '/32';
        }
        list( $subnet, $mask ) = explode( '/', $subnet );
        // convert IPs from human readable format to binary
        $ip_dec = ip2long( $ip );
        $subnet_dec = ip2long( $subnet );
        // apply the subnet mask to the IP and the subnet
        $ip_masked = $ip_dec & -1 << 32 - $mask;
        $subnet_masked = $subnet_dec & -1 << 32 - $mask;
        // if the resulting masked IPs are identical, the original IP is part of this subnet
        return $ip_masked == $subnet_masked;
    }

    public function check_allow_deny(
        $email,
        $content,
        $target_type,
        $type,
        $pattern
    ) {
        $ip = $this->utilities->get_ip();
        global $wpdb;
        $table_name = $wpdb->prefix . 'fwantispam_allow_deny';
        $allow_deny = $wpdb->get_results( $wpdb->prepare( "SELECT value FROM {$table_name} WHERE allow_deny = %s AND type = %s", $type, $target_type ) );
        if ( !is_wp_error( $allow_deny ) ) {
            foreach ( $allow_deny as $entry ) {
                if ( 'IP' === $target_type ) {
                    if ( $this->ip_in_subnet( $ip, $entry->value ) ) {
                        return array(
                            'matched'       => true,
                            'type'          => $target_type,
                            'rule'          => $entry->value,
                            'checked_value' => $ip,
                        );
                    }
                } else {
                    if ( @preg_match_all( $entry->value, '' ) !== false ) {
                        // regex is valid
                        if ( preg_match_all( $entry->value, $pattern ) >= 1 ) {
                            return array(
                                'matched'       => true,
                                'type'          => $target_type,
                                'rule'          => $entry->value,
                                'checked_value' => $pattern,
                            );
                        }
                    } else {
                        if ( $entry->value === $pattern ) {
                            return array(
                                'matched'       => true,
                                'type'          => $target_type,
                                'rule'          => $entry->value,
                                'checked_value' => $pattern,
                            );
                        }
                    }
                }
            }
        }
        return array(
            'matched' => false,
        );
    }

    public function is_allowed( $email, $content ) {
        $result = $this->check_allow_deny(
            $email,
            $content,
            'IP',
            'allow',
            $this->utilities->get_ip()
        );
        if ( $result['matched'] ) {
            return $result;
        }
        $result = $this->check_allow_deny(
            $email,
            $content,
            'email',
            'allow',
            $email
        );
        if ( $result['matched'] ) {
            return $result;
        }
        $result = $this->check_allow_deny(
            $email,
            $content,
            'string',
            'allow',
            $content
        );
        if ( $result['matched'] ) {
            return $result;
        }
        return array(
            'matched' => false,
        );
    }

    public function is_denied( $email, $content ) {
        $result = $this->check_allow_deny(
            $email,
            $content,
            'IP',
            'deny',
            $this->utilities->get_ip()
        );
        if ( $result['matched'] ) {
            return $result;
        }
        $result = $this->check_allow_deny(
            $email,
            $content,
            'email',
            'deny',
            $email
        );
        if ( $result['matched'] ) {
            return $result;
        }
        $result = $this->check_allow_deny(
            $email,
            $content,
            'string',
            'deny',
            $content
        );
        if ( $result['matched'] ) {
            return $result;
        }
        return array(
            'matched' => false,
        );
    }

}
