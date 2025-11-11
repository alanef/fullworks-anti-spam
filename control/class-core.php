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
namespace Fullworks_Anti_Spam\Control;

use Fullworks_Anti_Spam\Admin\Admin;
use Fullworks_Anti_Spam\Admin\Admin_Email_View;
use Fullworks_Anti_Spam\Admin\Admin_Settings;
use Fullworks_Anti_Spam\Admin\Admin_Table_Allow_Deny;
use Fullworks_Anti_Spam\Admin\Admin_Table_Email_Log;
use Fullworks_Anti_Spam\Admin\Mark_Spam;
use Fullworks_Anti_Spam\Anti_Spam_Api;
use Fullworks_Anti_Spam\Core\Email_Log;
use Fullworks_Anti_Spam\Core\Email_Reports;
use Fullworks_Anti_Spam\Core\Forms_Hooks;
use Fullworks_Anti_Spam\Core\Purge;
use Fullworks_Anti_Spam\Core\Training_Data;
use Fullworks_Anti_Spam\Core\Utilities;
use Fullworks_Anti_Spam\Data\Log;
use Fullworks_Anti_Spam\FrontEnd\FrontEnd;
use Fullworks_Anti_Spam\Integrations\WS_Form\WS_Form_Action_Fullworks_Anti_Spam;
/**
 * Class Core
 * @package Fullworks_Anti_Spam\Control
 */
class Core {
    /**
     * @var string
     */
    protected $plugin_name;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var Log
     */
    protected $log;

    /** @var Utilities $utilities */
    protected $utilities;

    /**
     * @var \Freemius $freemius Object for freemius.
     */
    protected $freemius;

    /**
     * @var false|mixed|null
     */
    private $options;

    /**
     * @var Anti_Spam_Api $api
     */
    private $api;

    /**
     *
     * @param \Freemius $freemius Object for freemius.
     */
    public function __construct( $freemius ) {
        $this->plugin_name = FULLWORKS_ANTI_SPAM_PLUGIN_NAME;
        $this->version = FULLWORKS_ANTI_SPAM_PLUGIN_VERSION;
        $this->freemius = $freemius;
    }

    /**
     *
     */
    public function run() {
        $this->utilities = new Utilities();
        $this->log = new Log($this->utilities);
        $this->api = new Anti_Spam_Api();
        $this->options = get_option( 'fullworks-anti-spam' );
        $this->set_options_data();
        $this->settings_pages();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_core_hooks();
    }

    /**
     *
     */
    private function set_options_data() {
        // set  cron - do it here rather than activator to cover multi sites
        if ( false === $this->options ) {
            add_option( 'fullworks-anti-spam', Admin_Settings::option_defaults( 'fullworks-anti-spam' ) );
        } else {
            if ( !isset( $this->options['freemius_state_set'] ) || !$this->options['freemius_state_set'] ) {
                if ( $this->freemius->is_anonymous() ) {
                    $this->options['freemius_state_set'] = true;
                    $this->options['sendspam'] = 0;
                    update_option( 'fullworks-anti-spam', $this->options );
                }
            }
            if ( !isset( $this->options['sendspam'] ) ) {
                if ( $this->freemius->is_anonymous() && $this->freemius->is_plan_or_trial( 'gdpr', true ) ) {
                    $this->options['sendspam'] = 0;
                } else {
                    $this->options['sendspam'] = 1;
                }
                update_option( 'fullworks-anti-spam', $this->options );
            }
            // Dashboard widget default for upgrades
            if ( !isset( $this->options['show_dashboard_widget'] ) ) {
                $this->options['show_dashboard_widget'] = 1;
                update_option( 'fullworks-anti-spam', $this->options );
            }
        }
        if ( !wp_next_scheduled( 'fullworks_anti_spam_daily_admin' ) ) {
            wp_schedule_event( time() - 30, 'daily', 'fullworks_anti_spam_daily_admin' );
        }
        if ( !wp_next_scheduled( 'fullworks_anti_spam_daily_training' ) ) {
            wp_schedule_event( time() - 30, 'daily', 'fullworks_anti_spam_daily_training' );
        }
    }

    /**
     *
     */
    private function settings_pages() {
        $settings = new Admin_Settings(
            $this->get_plugin_name(),
            $this->get_version(),
            $this->freemius,
            $this->utilities,
            $this->api
        );
        add_action( 'admin_menu', array($settings, 'settings_setup') );
        add_action( 'admin_menu', array($settings, 'hooks') );
        add_action( 'init', array($settings, 'plugin_action_links') );
        $allow_deny = new Admin_Table_Allow_Deny($this->get_plugin_name(), $this->get_version(), $this->freemius);
        add_filter(
            'set-screen-option',
            array($allow_deny, 'set_screen'),
            10,
            3
        );
        add_action( 'admin_menu', array($allow_deny, 'add_table_page') );
        add_action( 'init', function () {
        } );
        // Diagnostics Admin
        add_action( 'init', function () {
        } );
    }

    /**
     * @return string
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * @return string
     */
    public function get_version() {
        return $this->version;
    }

    /**
     *
     */
    private function define_admin_hooks() {
        $plugin_admin = new Admin(
            $this->get_plugin_name(),
            $this->get_version(),
            $this->utilities,
            $this->freemius,
            $this->api
        );
        $plugin_admin->init();
        $mark_spam = new Mark_Spam(
            $this->get_plugin_name(),
            $this->get_version(),
            $this->utilities,
            $this->freemius
        );
        $mark_spam->hooks();
        // Cron events
        $purge = new Purge($this->get_plugin_name(), $this->get_version());
        add_action( 'fullworks_anti_spam_daily_admin', array($purge, 'daily') );
        add_action( 'admin_init', array($plugin_admin, 'upgrade_db') );
    }

    /**
     *
     */
    private function define_public_hooks() {
        $plugin_public = new FrontEnd($this->get_plugin_name(), $this->get_version(), $this->utilities);
        add_action( 'wp_enqueue_scripts', array($plugin_public, 'enqueue_scripts') );
        add_action( 'wp_ajax_nopriv_fwas_get_keys', array($plugin_public, 'get_keys') );
        add_action( 'wp_ajax_fwas_get_keys', array($plugin_public, 'get_keys') );
        add_action( 'login_footer', array($plugin_public, 'enqueue_scripts') );
        // anti spam
    }

    /**
     *
     */
    private function define_core_hooks() {
        $forms_hooks = new Forms_Hooks(
            $this->log,
            $this->options,
            $this->utilities,
            $this->freemius,
            $this->api
        );
        add_action( 'init', array($forms_hooks, 'set_init_hooks'), 0 );
        add_action( 'plugins_loaded', array($forms_hooks, 'set_plugins_loaded_hooks'), 0 );
    }

}
