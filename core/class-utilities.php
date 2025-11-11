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

use DateTime;
use DateTimeZone;
use WP_Http;
use WP_Error;
/**
 * Class Utilities
 *
 * This class provides utility methods for various tasks.
 */
class Utilities {
    /**
     * The array holding registered form instances.
     *
     * @var array
     */
    private $forms_registrations;

    /**
     * @var
     */
    protected static $instance;

    /**
     * @var
     */
    protected $utility_data;

    protected $settings_page_tabs;

    private static $user_ip = false;

    /**
     * Utilities constructor.
     */
    public function __construct() {
    }

    public static function get_user_ip() {
        return self::$user_ip;
    }

    public static function set_user_ip( $ip ) {
        if ( false !== WP_Http::is_ip_address( $ip ) ) {
            self::$user_ip = $ip;
        }
    }

    /**
     * @return Utilities
     */
    public static function get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param  $message ( WP_Error or array or string )
     */
    public static function error_log( $message, $called_from = 'Log' ) {
        if ( WP_DEBUG === true ) {
            if ( is_wp_error( $message ) ) {
                $error_string = $message->get_error_message();
                $error_code = $message->get_error_code();
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- is a debug function
                error_log( $called_from . ':' . $error_code . ':' . $error_string );
                return;
            }
            if ( is_array( $message ) || is_object( $message ) ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r -- is a debug function
                error_log( $called_from . ':' . print_r( $message, true ) );
                return;
            }
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- is a debug function
            error_log( 'Log:' . $message );
        }
    }

    public function validate_ip_or_subnet( $value ) {
        // Check if value is a valid IPv4 or IPv6 address
        if ( filter_var( $value, FILTER_VALIDATE_IP ) ) {
            return true;
        }
        // Check if value is a valid IPv4 CIDR subnet
        $parts = explode( '/', $value );
        if ( count( $parts ) == 2 && filter_var( $parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) && is_numeric( $parts[1] ) && $parts[1] >= 0 && $parts[1] <= 32 ) {
            return true;
        }
        return false;
    }

    /**
     * @param null $ip
     *
     * @return string  Sanitized IP address
     */
    public function get_ip( $ipaddress = null ) {
        if ( !empty( $ipaddress ) ) {
            if ( false === WP_Http::is_ip_address( $ipaddress ) ) {
                return '0.0.0.0';
            }
            return $ipaddress;
        }
        $ipaddress = Utilities::get_user_ip();
        if ( false !== $ipaddress ) {
            return $ipaddress;
        }
        $ipaddress = '0.0.0.0';
        if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
            $ipaddress = filter_var( $_SERVER['HTTP_CF_CONNECTING_IP'] );
        } elseif ( isset( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
            $ipaddress = filter_var( $_SERVER['HTTP_CLIENT_IP'] );
        } elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
            $ipaddress = filter_var( $_SERVER['HTTP_X_FORWARDED_FOR'] );
        } elseif ( isset( $_SERVER['HTTP_X_FORWARDED'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
            $ipaddress = filter_var( $_SERVER['HTTP_X_FORWARDED'] );
        } elseif ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
            $ipaddress = filter_var( $_SERVER['HTTP_FORWARDED_FOR'] );
        } elseif ( isset( $_SERVER['HTTP_FORWARDED'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
            $ipaddress = filter_var( $_SERVER['HTTP_FORWARDED'] );
        } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- not form input
            $ipaddress = filter_var( $_SERVER['REMOTE_ADDR'] );
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
    public function get_uid( $option, $length ) {
        $this->debug_log( 'getting UID' );
        $uid = get_transient( $option );
        $this->debug_log( 'got UID: ' . $uid );
        if ( false === $uid ) {
            $this->debug_log( 'setting UID' );
            $uid = wp_generate_password( $length, false, false );
            set_transient( $option, $uid, 3 * DAY_IN_SECONDS );
            $this->debug_log( 'got UID: ' . $uid );
            $random_version = $this->get_random_version();
            set_transient( 'fullworks_anti_spam_random_version', $random_version + 1 );
        }
        return $uid;
    }

    public function get_random_version() {
        $random_version = get_transient( 'fullworks_anti_spam_random_version' );
        if ( false === $random_version ) {
            $random_version = wp_rand( 0, 1000 );
        }
        return $random_version;
    }

    public function register_settings_page_tab(
        $title,
        $page,
        $href,
        $position
    ) {
        $this->settings_page_tabs[$page][$position] = array(
            'title' => $title,
            'href'  => $href,
        );
    }

    public function display_tabs() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
        $page = ( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '' );
        $split = explode( '-', $page );
        $page_type = $split[count( $split ) - 1];
        $tabs = Utilities::get_instance()->get_settings_page_tabs( $page_type );
        if ( count( $tabs ) < 1 ) {
            return;
        }
        ?>
        <h2 class="nav-tab-wrapper">
			<?php 
        foreach ( $tabs as $key => $tab ) {
            $active = '';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- No action, nonce is not required
            if ( preg_match( '#' . $page . '$#', $tab['href'] ) ) {
                $active = ' nav-tab-active';
            }
            echo '<a href="' . esc_url( $tab['href'] ) . '" class="fs-tab nav-tab' . esc_attr( $active ) . '" ' . (( $this->is_external( $tab['href'] ) ? 'target="_blank"' : '' )) . '>' . esc_html( $tab['title'] ) . (( $this->is_external( $tab['href'] ) ? '<svg style="height:1em" class="feather feather-external-link" fill="none" height="24" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>' : '' )) . '</a>';
        }
        ?>


        </h2>
		<?php 
    }

    private function is_external( $href ) {
        $parsed_home_url = wp_parse_url( home_url() );
        $parsed_href = wp_parse_url( $href );
        return isset( $parsed_home_url['host'], $parsed_href['host'] ) && $parsed_home_url['host'] !== $parsed_href['host'];
    }

    public function get_settings_page_tabs( $page ) {
        $tabs = $this->settings_page_tabs[$page];
        ksort( $tabs );
        return $tabs;
    }

    public function get_spam_stats() {
        $forms_registrations_obj = new Forms_Registrations();
        $this->forms_registrations = $forms_registrations_obj->get_registered_forms();
        /** @var \Freemius $fwantispam_fs Freemius global object. */
        global $fwas_fs;
        global $wpdb;
        $table_name = $wpdb->prefix . 'fwantispam_log';
        $counts = $wpdb->get_results( "SELECT eventcode_str, count(*) AS count from {$table_name}  GROUP BY eventcode_str" );
        $rows = array();
        $total = 0;
        foreach ( $counts as $count ) {
            if ( empty( $count->eventcode_str ) ) {
                continue;
            }
            list( $system, $type ) = explode( ',', $count->eventcode_str );
            if ( !isset( $this->forms_registrations[$system] ) ) {
                continue;
            }
            if ( $type == "ALLOW" ) {
                continue;
            }
            $line = '';
            $total += $count->count;
            if ( $type == "DENY" || $type == "BOT" ) {
                $link = '';
            } else {
                $link = $this->forms_registrations[$system]['spam_admin_url'];
            }
            $line = array(
                'source' => $this->forms_registrations[$system]['name'],
                'type'   => $type,
                'count'  => $count->count,
                'link'   => $link,
            );
            $rows[] = $line;
        }
        $rows[] = array(
            'source' => '',
            'type'   => __( 'Total', 'fullworks-anti-spam' ),
            'count'  => $total,
            'link'   => '',
        );
        return $rows;
    }

    public function debug_log( $data ) {
        // Save original data for action hook (before any string conversion)
        $original_data = $data;
        // Allow other plugins to intercept the debug function
        $debug_function = apply_filters( 'fwas_diagnostics_log_function', '' );
        if ( !empty( $debug_function ) && function_exists( $debug_function ) ) {
            // Call the custom debug function provided by the filter
            call_user_func( $debug_function, $data );
        }
        // Default debug behavior
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'FWAS_DEBUG' ) && FWAS_DEBUG ) {
            if ( is_array( $data ) ) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- is a debug function
                $data = print_r( $data, true );
            }
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- is a debug function
            error_log( '[' . gmdate( 'Y-m-d H:i:s' ) . '] Fullworks Anti Spam: ' . $data );
        }
        // Fire an action for plugins to hook into (use original data, not string-converted version)
        do_action( 'fwas_diagnostics_log', $original_data );
    }

    public function is_gravity_forms_installed() {
        return apply_filters( 'fwas_is_gravity_forms_installed', class_exists( 'GFForms' ) );
    }

    public function is_clean_and_simple_installed() {
        return apply_filters( 'fwas_is_gravity_forms_installed', class_exists( 'cscf' ) );
    }

    public function is_woocommerce_installed() {
        return apply_filters( 'fwas_is_woocommerce_installed', class_exists( 'WooCommerce' ) );
    }

    public function is_registrations_open() {
        return apply_filters( 'fwas_is_registrations_open', get_option( 'users_can_register', false ) );
    }

    public function is_quick_contact_forms_installed() {
        // needs latest version of QCF with qcf_get_messages function
        return apply_filters( 'fwas_is_quick_contact_forms_installed', class_exists( '\\Quick_Contact_Form\\Control\\Plugin' ) && function_exists( 'qcf_get_messages' ) );
    }

    public function is_quick_event_manager_installed() {
        return apply_filters( 'fwas_is_quick_event_manager_installed', class_exists( '\\Quick_Event_Manager\\Plugin\\Control\\Plugin' ) );
    }

    public function is_wp_user_registrion_enabled() {
        return apply_filters( 'fwas_is_wp_user_registrion_enabled', (bool) get_option( 'users_can_register' ) );
    }

    public function is_jetpack_contact_form_installed() {
        return apply_filters( 'fwas_is_jetpack_contact_form_installed', class_exists( 'Automattic\\Jetpack\\Forms\\ContactForm\\Contact_Form' ) );
    }

    public function is_contact_form_7_installed() {
        return apply_filters( 'fwas_is_contact_form_7_installed', class_exists( 'WPCF7' ) );
    }

    public function is_fluent_forms_installed() {
        return apply_filters( 'fwas_is_fluent_forms_installed', defined( 'FLUENTFORM' ) );
    }

    public function is_wp_forms_lite_installed() {
        $installed = false;
        if ( defined( 'WPFORMS_PLUGIN_SLUG' ) && WPFORMS_PLUGIN_SLUG === 'wpforms-lite' ) {
            $installed = true;
        }
        return apply_filters( 'fwas_is_wp_forms_lite_installed', $installed );
    }

    public function is_wp_forms_pro_installed() {
        $installed = false;
        if ( defined( 'WPFORMS_PLUGIN_SLUG' ) && WPFORMS_PLUGIN_SLUG === 'wpforms' ) {
            $installed = true;
        }
        return apply_filters( 'fwas_is_wp_forms_pro_installed', $installed );
    }

    public function is_ws_form_installed() {
        return apply_filters( 'fwas_is_ws_form_installed', defined( 'WS_FORM_NAME' ) );
    }

    public function is_sureforms_installed() {
        return apply_filters( 'fwas_is_sureforms_installed', defined( 'SRFM_VER' ) );
    }

    public function is_comments_open() {
        return apply_filters( 'fwas_is_comments_open', 'open' === get_default_comment_status() );
    }

    public function search_array( array $existing_data, $target, $index ) {
        foreach ( $existing_data as $key => $val ) {
            if ( $val[$target] == $index ) {
                return $val;
            }
        }
        return false;
    }

}
