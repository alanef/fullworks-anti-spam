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
        add_filter(
            'wpforms_process_honeypot',
            array($this, 'wpforms_is_spam'),
            10,
            4
        );
    }

    public function set_init_hooks() {
        add_filter(
            'wpcf7_spam',
            array($this, 'cf7_entry_is_spam'),
            10,
            2
        );
        add_filter(
            'fluentform/validations',
            array($this, 'fluentform'),
            10,
            3
        );
        add_filter(
            'jetpack_contact_form_is_spam',
            array($this, 'jetpack_entry_is_spam'),
            10,
            2
        );
        add_filter(
            'srfm_before_fields_processing',
            array($this, 'sureforms_is_spam'),
            10,
            1
        );
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

    public function fluentform( $original_validations, $form, $form_data ) {
        $s = $form;
        $s1 = $s['attributes'];
        $ff = json_decode( $form['attributes']['form_fields'] );
        $email = '';
        foreach ( $ff->fields as $key => $value ) {
            if ( 'input_email' === $value->element ) {
                $email = $form_data[$value->attributes->name];
                break;
            }
        }
        $textareas = array_filter( $ff->fields, function ( $value ) {
            return 'textarea' === $value->element;
        } );
        $message = '';
        foreach ( $textareas as $key => $value ) {
            $message .= $form_data[$value->attributes->name] . ' ';
        }
        $is_spam = $this->api->is_spam(
            false,
            'fluent',
            $email,
            $message
        );
        if ( false === $is_spam ) {
            return false;
        }
        if ( 'BOT' === $is_spam ) {
            throw new \FluentForm\Framework\Validator\ValidationException(
                '',
                422,
                null,
                array(
                    'errors' => array(
                        'restricted' => array(esc_html__( 'You seem to be a bot, sorry but you are unable to submit this form', 'fullworks-anti-spam' )),
                    ),
                )
            );
        } elseif ( 'DENY' === $is_spam ) {
            throw new \FluentForm\Framework\Validator\ValidationException(
                '',
                422,
                null,
                array(
                    'errors' => array(
                        'restricted' => array(esc_html__( 'Your IP or email of content looks like spam. If this is an issue contact the website owner.', 'fullworks-anti-spam' )),
                    ),
                )
            );
        }
        return $original_validations;
    }

    /**
     * @param $result
     * @param $tags
     *
     * @return mixed
     */
    public function cf7_entry_is_spam( $is_already_spam, $submission ) {
        $message = '';
        $contact_form = $submission->get_contact_form();
        $tags = $contact_form->scan_form_tags( array(
            'feature' => '! file-uploading',
        ) );
        $textareas = array_filter( $tags, function ( $value ) {
            return 'textarea' === $value->type;
        } );
        // build message
        foreach ( $textareas as $key => $value ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No action as from external source and custom nonce applied
            if ( isset( $_POST[$value->name] ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No action as from external source and custom nonce applied
                $message .= sanitize_textarea_field( wp_unslash( $_POST[$value->name] ) ) . ' ';
            }
        }
        // get primary email
        $email = '';
        foreach ( $tags as $key => $value ) {
            if ( 'email' === $value->basetype ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- No action as from external source and custom nonce applied
                $email = sanitize_email( wp_unslash( $_POST[$value->name] ) );
                break;
            }
        }
        $is_spam = $this->api->is_spam(
            false,
            'cf7',
            $email,
            $message
        );
        if ( false !== $is_spam ) {
            $message = '<strong>' . esc_html__( 'ERROR:', 'fullworks-anti-spam' ) . '</strong> ';
            switch ( $is_spam ) {
                case 'BOT':
                    $message .= esc_html__( 'Are you sure you are human as that was very fast typing, please try again', 'fullworks-anti-spam' );
                    break;
                case 'DENY':
                    $message .= esc_html__( 'The website owner is blocking your email or IP', 'fullworks-anti-spam' );
                    break;
                default:
                    $message = '';
                    break;
            }
            if ( !empty( $message ) ) {
                add_filter(
                    'wpcf7_skip_mail',
                    function ( $skip_mail, $contact_form ) {
                        return true;
                    },
                    99,
                    2
                );
                if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
                    add_filter(
                        'wpcf7_display_message',
                        function ( $cf7_message, $status ) use($message) {
                            if ( $status === 'spam' ) {
                                return $message;
                            }
                            return $message;
                        },
                        99,
                        2
                    );
                    return true;
                } else {
                    wp_die( wp_kses_post( $message ), 403 );
                }
            }
        }
        return false;
    }

    public function jetpack_entry_is_spam( $result, $akismet_data ) {
        /**
         * Array
         * (
         * [comment_author] => ddd
         * [comment_author_email] => alan@fullerfamily.uk
         * [comment_author_url] =>
         * [contact_form_subject] => [anti-spam] Jet Pack Contact Form
         * [comment_author_IP] => 192.168.160.1
         * [comment_content] => ddddf
         * [comment_type] => contact_form
         * [user_ip] => 192.168.160.1
         * [user_agent] => Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36
         * [referrer] => http://localhost:8350/2022/11/26/jet-pack-contact-form/
         * [blog] => http://localhost:8350
         */
        $is_spam = $this->api->is_spam(
            false,
            'grunion',
            $akismet_data['comment_author_email'] ?? '',
            $akismet_data['comment_content'] ?? ''
        );
        if ( 'BOT' === $is_spam ) {
            return new WP_Error('jetpack-contact--bot', esc_html__( 'Are you sure you are human as that was very fast typing, please try again', 'fullworks-anti-spam' ), 403);
        } elseif ( 'DENY' === $is_spam ) {
            return new WP_Error('jetpack-contact--deny', esc_html__( 'Message submitted from blocklisted email or IP or contains banned text', 'fullworks-anti-spam' ), 403);
        } elseif ( 'IP_BLK_LST' === $is_spam ) {
            if ( 0 === (int) $this->options['days'] ) {
                return new WP_Error('jetpack-contact--human', esc_html__( 'IP address is blocklisted, check your IP reputation', 'fullworks-anti-spam' ), 403);
            } else {
                return true;
            }
        } elseif ( 'SNGL_WRD' === $is_spam ) {
            if ( 0 === (int) $this->options['days'] ) {
                return new WP_Error('jetpack-contact--human', esc_html__( 'Please write more, one word submissions are not allowed', 'fullworks-anti-spam' ), 403);
            } else {
                return true;
            }
        } elseif ( 'HUMAN' === $is_spam ) {
            if ( 0 === (int) $this->options['days'] ) {
                return new WP_Error('jetpack-contact--human', esc_html__( 'Your submission looks suspicious', 'fullworks-anti-spam' ), 403);
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $honeypot
     * @param $fields
     * @param $entry
     * @param $form_data
     *
     * @return mixed|string|void
     */
    public function wpforms_is_spam(
        $honeypot,
        $fields,
        $entry,
        $form_data
    ) {
        $textareas = array_filter( $form_data['fields'], function ( $value ) {
            return 'textarea' === $value['type'];
        } );
        $message = '';
        foreach ( $textareas as $key => $value ) {
            $message .= $entry['fields'][$key] . ' ';
        }
        $email = '';
        foreach ( $form_data['fields'] as $key => $value ) {
            if ( 'email' === $value['type'] ) {
                $email = $entry['fields'][$key];
                break;
            }
        }
        $is_spam = $this->api->is_spam(
            false,
            'wpforms',
            $email,
            $message
        );
        add_filter(
            'wpforms_entry_email',
            function ( $allow ) use($is_spam) {
                if ( $this->utilities->is_wp_forms_pro_installed() ) {
                    if ( $is_spam ) {
                        $allow = false;
                    }
                }
                return $allow;
            },
            9999,
            1
        );
        if ( false !== $is_spam ) {
            if ( 'BOT' === $is_spam || 'DENY' === $is_spam ) {
                return true;
                // fail silently
            } else {
                add_filter(
                    'wpforms_entry_save_args',
                    function ( $args, $data ) {
                        $args['status'] = 'spam';
                        return $args;
                    },
                    99,
                    2
                );
                // @TODO WPFORMS premium testing
                if ( function_exists( 'wpforms_log' ) ) {
                    wpforms_log( 'Spam Entry ' . uniqid( '', true ), array($is_spam, $entry), array(
                        'type'    => ['spam'],
                        'form_id' => $form_data['id'],
                    ) );
                }
                if ( 'IP_BLK_LST' === $is_spam ) {
                    return false;
                    // needs to be false so wp forms sends the email so we can log it
                } elseif ( 'SNGL_WRD' === $is_spam ) {
                    return false;
                    // needs to be false so wp forms sends the email so we can log it
                } elseif ( 'HUMAN' === $is_spam ) {
                    return false;
                    // needs to be false so wp forms sends the email so we can log it
                }
            }
        }
        return false;
        // needs to be false so wp forms sends the email so we can log it
    }

    /**
     * Check if SureForms submission is spam
     *
     * @param array $entry_data The entry data before storage
     *
     * @return array Modified entry data or error array
     */
    public function sureforms_is_spam( $entry_data ) {
        // Extract form data
        $form_data = $entry_data;
        // Find email field
        $email = '';
        foreach ( $form_data as $key => $value ) {
            // Check if it's an email field (SureForms email fields typically have 'email' in the key)
            if ( strpos( $key, 'email' ) !== false && is_email( $value ) ) {
                $email = $value;
                break;
            }
        }
        // Collect message content from text fields and textareas
        $message_parts = array();
        foreach ( $form_data as $key => $value ) {
            // Just text areas
            if ( strpos( $key, 'textarea' ) !== false ) {
                if ( !empty( $value ) && is_string( $value ) ) {
                    $message_parts[] = $value;
                }
            }
        }
        $message = implode( " ", $message_parts );
        // Run spam check
        $is_spam = $this->api->is_spam(
            false,
            'sureforms',
            $email,
            $message
        );
        if ( false !== $is_spam ) {
            // Hybrid approach: BOT/DENY block completely, others allow with tracking
            if ( in_array( $is_spam, array('BOT', 'DENY') ) ) {
                // Block submission completely
                return array(
                    'error'   => true,
                    'message' => $this->get_sureforms_error_message( $is_spam ),
                );
            } else {
                // Allow storage but add spam tracking to extras field
                if ( !isset( $entry_data['extras'] ) ) {
                    $entry_data['extras'] = array();
                }
                $entry_data['extras']['spam_check'] = $is_spam;
                $entry_data['extras']['spam_level'] = $this->api->spam_level;
                // Block email notifications for all spam types
                add_filter(
                    'srfm_should_send_email',
                    function ( $should_send ) use($is_spam) {
                        // Block emails for spam entries
                        return false;
                    },
                    10,
                    1
                );
            }
        }
        return $entry_data;
    }

    /**
     * Get error message for SureForms spam detection
     *
     * @param string $spam_type The type of spam detected
     *
     * @return string Error message
     */
    private function get_sureforms_error_message( $spam_type ) {
        switch ( $spam_type ) {
            case 'BOT':
                return esc_html__( 'Are you sure you are human as that was very fast typing, please try again', 'fullworks-anti-spam' );
            case 'DENY':
                return esc_html__( 'The website owner is blocking your email or IP', 'fullworks-anti-spam' );
            case 'IP_BLK_LST':
                return esc_html__( 'IP address is blocklisted, check your IP reputation', 'fullworks-anti-spam' );
            case 'SNGL_WRD':
                return esc_html__( 'Please write more, one word submissions are not allowed', 'fullworks-anti-spam' );
            case 'HUMAN':
                return esc_html__( 'Your submission looks suspicious', 'fullworks-anti-spam' );
            default:
                return esc_html__( 'Your submission has been blocked', 'fullworks-anti-spam' );
        }
    }

}
