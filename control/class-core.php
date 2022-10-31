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

use  Fullworks_Anti_Spam\Admin\Admin ;
use  Fullworks_Anti_Spam\Admin\Admin_Settings ;
use  Fullworks_Anti_Spam\Business\Purge ;
use  Fullworks_Anti_Spam\Core\Process_Spam_Checks ;
use  Fullworks_Anti_Spam\Core\Utilities ;
use  Fullworks_Anti_Spam\Data\Log ;
use  Fullworks_Anti_Spam\FrontEnd\FrontEnd ;
use  Fullworks_Anti_Spam\Business\Monthly_Reports ;
/**
 * Class Core
 * @package Fullworks_Anti_Spam\Control
 */
class Core
{
    /**
     * @var string
     */
    protected  $plugin_name ;
    /**
     * @var string
     */
    protected  $version ;
    /**
     * @var Log
     */
    protected  $log ;
    /** @var Utilities $utilities */
    protected  $utilities ;
    /**
     *
     * @param \Freemius $freemius Object for freemius.
     */
    public function __construct( $freemius )
    {
        $this->plugin_name = 'fullworks-anti-spam';
        $this->version = FULLWORKS_ANTI_SPAM_PLUGIN_VERSION;
        $this->freemius = $freemius;
        $this->utilities = new Utilities();
        $this->log = new Log( $this->utilities );
    }
    
    /**
     *
     */
    public function run()
    {
        $this->set_options_data();
        $this->set_locale();
        $this->settings_pages();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_core_hooks();
    }
    
    /**
     *
     */
    private function set_options_data()
    {
        // set up options & cron - do it here rather than activator to cover multi sites
        $options = get_option( 'fullworks-anti-spam' );
        if ( false === $options ) {
            add_option( 'fullworks-anti-spam', Admin_Settings::option_defaults( 'fullworks-anti-spam' ) );
        }
        if ( !wp_next_scheduled( 'fullworks_anti_spam_daily_admin' ) ) {
            wp_schedule_event( time() - 30, 'daily', 'fullworks_anti_spam_daily_admin' );
        }
    }
    
    /**
     *
     */
    private function set_locale()
    {
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
    }
    
    /**
     *
     */
    private function settings_pages()
    {
        $settings = new Admin_Settings(
            $this->get_plugin_name(),
            $this->get_version(),
            $this->freemius,
            $this->log,
            $this->utilities
        );
        add_action( 'admin_menu', array( $settings, 'settings_setup' ) );
        add_action( 'init', array( $settings, 'plugin_action_links' ) );
    }
    
    /**
     * @return string
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }
    
    /**
     * @return string
     */
    public function get_version()
    {
        return $this->version;
    }
    
    /**
     *
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Admin(
            $this->get_plugin_name(),
            $this->get_version(),
            $this->utilities,
            $this->freemius
        );
        add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
        // Cron events
        $purge = new Purge( $this->get_plugin_name(), $this->get_version() );
        add_action( 'fullworks_anti_spam_daily_admin', array( $purge, 'daily' ) );
    }
    
    /**
     *
     */
    private function define_public_hooks()
    {
        $plugin_public = new FrontEnd( $this->get_plugin_name(), $this->get_version(), $this->utilities );
        add_action( 'wp_enqueue_scripts', array( $plugin_public, 'enqueue_scripts' ) );
        add_action( 'login_footer', array( $plugin_public, 'enqueue_scripts' ) );
        // anti spam
    }
    
    /**
     *
     */
    private function define_core_hooks()
    {
        $spam_options = get_option( 'fullworks-anti-spam' );
        $process_spam_checks = new Process_Spam_Checks(
            $this->log,
            $spam_options,
            $this->utilities,
            $this->freemius
        );
        add_action( 'init', array( $process_spam_checks, 'init' ), 0 );
    }
    
    /**
     *
     */
    public function load_plugin_textdomain()
    {
        load_plugin_textdomain( 'fullworks-anti-spam', false, FULLWORKS_ANTI_SPAM_PLUGIN_DIR . 'languages/' );
    }

}