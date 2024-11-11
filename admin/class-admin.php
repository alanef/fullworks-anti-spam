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
 *
 */
namespace Fullworks_Anti_Spam\Admin;

use Fullworks_Anti_Spam\Core\Utilities;
/**
 * Class Admin
 * @package Fullworks_Anti_Spam\Admin
 */
class Admin {
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

    /**
     * Represents an instance of the API class.
     * @var Api
     */
    private $api;

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
        $freemius,
        $api
    ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->utilities = $utilities;
        $this->freemius = $freemius;
        $this->api = $api;
    }

    public function init() {
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_styles') );
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );
        add_action( 'admin_menu', array($this, 'admin_pages') );
        add_action( 'admin_post_fwas_ad_csv_import', array($this, 'handle_ad_csv_import') );
        add_action( 'admin_post_fwas_ad_csv_export', array($this, 'handle_ad_csv_export') );
    }

    public function admin_pages() {
    }

    public function handle_ad_csv_export() {
        // Verify nonce for security
        check_admin_referer( 'fwas_ad_csv_export_nonce', '_fwas_ad_wpnonce_csv_export' );
        // check capability is manage options
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'fullworks-anti-spam' ) );
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'fwantispam_allow_deny';
        $results = $wpdb->get_results( "SELECT allow_deny,type,value,notes FROM {$table_name}", ARRAY_A );
        if ( count( $results ) > 0 ) {
            $delimiter = ",";
            $enclosure = '"';
            $filename = "fwas_allow_deny_" . date( 'Y-m-d' ) . ".csv";
            // create a file pointer
            $f = fopen( 'php://memory', 'w' );
            // set column headers
            $fields = array_keys( $results[0] );
            fputcsv(
                $f,
                $fields,
                $delimiter,
                $enclosure
            );
            // output each row of the data, format line as csv and write to file pointer
            foreach ( $results as $row ) {
                $lineData = array_values( $row );
                fputcsv(
                    $f,
                    $lineData,
                    $delimiter,
                    $enclosure
                );
            }
            fseek( $f, 0 );
            header( 'Content-Type: text/csv' );
            header( 'Content-Disposition: attachment; filename="' . $filename . '";' );
            fpassthru( $f );
        }
    }

    public function handle_ad_csv_import() {
        // Verify nonce for security
        check_admin_referer( 'fwas_ad_csv_import_nonce', '_fwas_ad_wpnonce_csv_import' );
        // check capability is manage options
        if ( !current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'fullworks-anti-spam' ) );
        }
        // Check if file was uploaded
        if ( isset( $_FILES['csv_file'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- uploaded file data name sanitized next step
            $uploadedfile = $_FILES['csv_file'];
            $uploadedfile['name'] = sanitize_file_name( $uploadedfile['name'] );
            // Check the uploaded file type
            $csv_mimes = array(
                'csv' => 'text/csv',
                'txt' => 'text/plain',
            );
            $upload_overrides = array(
                'test_form' => false,
                'mimes'     => $csv_mimes,
                'test_type' => true,
            );
            $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
            // Process the file
            if ( $movefile && !isset( $movefile['error'] ) ) {
                // get file data
                $csv_file_data = file_get_contents( $movefile['file'] );
                // Convert CSV data into an array
                $csv_data = array_map( "str_getcsv", explode( "\n", $csv_file_data ) );
                // process CSV data
                global $wpdb;
                $table_name = $wpdb->prefix . 'fwantispam_allow_deny';
                // Loop through your CSV data
                foreach ( $csv_data as $row ) {
                    if ( empty( trim( $row[0] ) ) ) {
                        continue;
                    }
                    // Prepare data
                    $data = array(
                        'allow_deny' => $row[0] ?? '',
                        'type'       => $row[1] ?? '',
                        'value'      => $row[2] ?? '',
                        'notes'      => sanitize_text_field( $row[3] ?? '' ),
                    );
                    if ( !in_array( $data['allow_deny'], array('allow', 'deny') ) ) {
                        continue;
                    }
                    if ( !in_array( $data['type'], array('IP', 'email', 'string') ) ) {
                        continue;
                    }
                    if ( 'email' == $data['type'] ) {
                        if ( !is_email( $data['type'] ) ) {
                            if ( !is_email( $data['value'] ) ) {
                                // check if it is a regular expression containing an email
                                if ( false === @preg_match_all( $data['value'], '' ) ) {
                                    continue;
                                }
                            }
                        }
                    } elseif ( 'string' == $data['type'] ) {
                        if ( false === @preg_match_all( $data['value'], '' ) ) {
                            // Not a proper regex pattern, consider as a string and enclose in slashes
                            $value = '/' . $data['value'] . '/im';
                        }
                        if ( false === @preg_match_all( $data['value'], '' ) ) {
                            continue;
                        }
                    } elseif ( 'IP' == $data['type'] ) {
                        // check IP is valid
                        if ( false === $this->utilities->validate_ip_or_subnet( $data['value'] ) ) {
                            continue;
                        }
                    }
                    // Prepare where clause to check existing row
                    $where = array(
                        'allow_deny' => $data['allow_deny'],
                        'type'       => $data['type'],
                        'value'      => $data['value'],
                    );
                    // Check if the record already exists
                    $exists = $wpdb->get_row( $wpdb->prepare(
                        "SELECT * FROM {$table_name} WHERE allow_deny = %s AND type = %s AND value = %s",
                        $where['allow_deny'],
                        $where['type'],
                        $where['value']
                    ) );
                    if ( $exists ) {
                        // Update the notes field if the record exists
                        $wpdb->update( $table_name, array(
                            'notes' => $data['notes'],
                        ), $where );
                    } else {
                        // Insert a new record if it doesn't exist
                        $wpdb->insert( $table_name, $data );
                    }
                }
            } else {
                // Failed to move file.
                wp_die( esc_html( $movefile['error'] ) );
            }
        } else {
            // No file uploaded.
            wp_die( esc_html__( 'No file was uploaded.', 'fullworks-anti-spam' ) );
        }
        // Redirect back to the same page
        wp_safe_redirect( admin_url( 'admin.php?page=fullworks-anti-spam-settings-allow-deny-settings' ) );
        exit;
    }

    public function upgrade_db() {
        global $wpdb;
        $dbv = get_option( 'fullworks_anti_spam_db_version' );
        if ( '1.0' == $dbv ) {
            // if upgrade.php not yet included
            if ( !function_exists( 'dbDelta' ) ) {
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            }
            $charset_collate = $wpdb->get_charset_collate();
            // add the Allow_Deny Table
            $table_name = $wpdb->prefix . 'fwantispam_allow_DENY';
            $sql = "CREATE TABLE {$table_name} (\n\t\tID int NOT NULL AUTO_INCREMENT,\n\t\tallow_DENY varchar(8) NOT NULL,\n\t\ttype varchar(16) NOT NULL,\n\t\tvalue varchar(256) NOT NULL,\n\t\tPRIMARY KEY  (ID),\n\t\tINDEX type_idx (type)\n\t) {$charset_collate};";
            dbDelta( $sql );
            $dbv = '2.0';
            update_option( 'fullworks_anti_spam_db_version', $dbv );
        }
        if ( '2.0' == $dbv ) {
            if ( !function_exists( 'dbDelta' ) ) {
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            }
            $charset_collate = $wpdb->get_charset_collate();
            $table_name = $wpdb->prefix . 'fwantispam_log';
            $sql = "CREATE TABLE {$table_name} (\n\t\tID int NOT NULL AUTO_INCREMENT,\n\t\tIP varbinary(16) NOT NULL,\n\t\tlogdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,\n\t\tserver text,\n\t\tevent varchar(256),\n\t\teventcode int,\n\t\teventcode_str varchar(256),\n\t\tPRIMARY KEY  (ID)\n\t) {$charset_collate};";
            dbDelta( $sql );
            // map log eventcode to eventcode_str  using a map array
            $map = array(
                '1410' => 'comments,BOT',
            );
            $table_name = $wpdb->prefix . 'fwantispam_log';
            foreach ( $map as $code => $code_str ) {
                $wpdb->query( $wpdb->prepare( "UPDATE {$table_name} SET eventcode_str = %s WHERE eventcode = %d", $code_str, $code ) );
            }
            $table_name = $wpdb->prefix . 'fwantispam_allow_deny';
            $sql = "CREATE TABLE {$table_name} (\n\t\tID int NOT NULL AUTO_INCREMENT,\n\t\tallow_deny varchar(8) NOT NULL,\n\t\ttype varchar(16) NOT NULL,\n\t\tvalue varchar(256) NOT NULL,\n\t\tnotes varchar(256),\n\t\tPRIMARY KEY  (ID),\n\t\tINDEX type_idx (type)\n\t) {$charset_collate};";
            dbDelta( $sql );
            $dbv = '2.1';
            update_option( 'fullworks_anti_spam_db_version', $dbv );
        }
    }

    /**
     * Register the stylesheets for the admin area.
     *
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/admin.css',
            array(),
            $this->version,
            'all'
        );
        $current_screen = get_current_screen();
        // Check if the current screen is the one we want to enqueue our scripts and styles
        if ( $current_screen->id == "admin_page_fullworks-anti-spam-settings-allow-deny-settings" ) {
            wp_enqueue_style( 'thickbox' );
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/admin.js',
            array('jquery', 'wp-i18n'),
            $this->version,
            false
        );
        // add a nonce to the script
        wp_localize_script( $this->plugin_name, 'fwantispam_ajax_object', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'fwantispam_ajax_nonce' ),
        ) );
        $current_screen = get_current_screen();
        // Check if the current screen is the one we want to enqueue our scripts and styles
        if ( $current_screen->id == "admin_page_fullworks-anti-spam-settings-allow-deny-settings" ) {
            wp_enqueue_script( 'thickbox' );
        }
    }

}
