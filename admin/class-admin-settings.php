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
 * Created
 * User: alan
 * Date: 04/04/18
 * Time: 13:45
 */
namespace Fullworks_Anti_Spam\Admin;

use  Fullworks_Anti_Spam\Control\Log ;
use  Fullworks_Anti_Spam\Core\Utilities ;
class Admin_Settings extends Admin_Pages
{
    protected  $settings_page ;
    protected  $settings_page_id = 'settings_page_fullworks-anti-spam-settings' ;
    protected  $option_group = 'fullworks-anti-spam' ;
    /** @var Log $log */
    protected  $log ;
    /** @var Utilities $utilities */
    protected  $utilities ;
    protected  $options ;
    private  $titles ;
    /**
     * Settings constructor.
     *
     * @param string $plugin_name
     * @param string $version plugin version.
     * @param \Freemius $freemius Freemius SDK.
     */
    public function __construct(
        $plugin_name,
        $version,
        $freemius,
        $log,
        $utilities
    )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->freemius = $freemius;
        $this->log = $log;
        $this->utilities = $utilities;
        $this->titles = array(
            'Bot Comments' => array(
            'title' => __( 'Comments', 'fullworks-anti-spam' ),
            'tip'   => sprintf( __( 'Kill automated (bot) spam without Captcha or other annoying user quizes', 'fullworks-anti-spam' ) ),
        ),
        );
        $this->titles['Keep Spam'] = [
            'title' => __( 'Keep Spam', 'fullworks-anti-spam' ),
            'tip'   => sprintf( __( 'Select the number of days to keep spam, when spam is logged. Select 0 to discard immediately', 'fullworks-anti-spam' ) ),
        ];
        $this->titles['Bot Forms'] = [
            'title' => __( 'Forms', 'fullworks-anti-spam' ),
            'tip'   => sprintf( __( 'Forms types protected, without Captcha, Gravity Forms, Contact Form 7, WPForms, WP Registration and WooCommerce Registrations', 'fullworks-anti-spam' ) ),
        ];
        $this->titles['BL Comments'] = [
            'title' => __( 'Comments', 'fullworks-anti-spam' ),
            'tip'   => sprintf( __( 'Refuse comments from IP addresses that are on a blacklist', 'fullworks-anti-spam' ) ),
        ];
        $this->titles['BL Forms'] = [
            'title' => __( 'Forms', 'fullworks-anti-spam' ),
            'tip'   => sprintf( __( 'Reject form submissions from IPs with a bad reputation, Gravity Forms, Contact Form 7, WPForms, WP Registration and WooCommerce Registrations', 'fullworks-anti-spam' ) ),
        ];
        $this->titles['Upgrade'] = [
            'title' => __( 'Upgrade', 'fullworks-anti-spam' ),
            'tip'   => sprintf( __( 'By upgrading you will benefit from machine learning logic to protect against human manually input spam', 'fullworks-anti-spam' ) ),
        ];
        $this->titles['Spam Stats Free'] = [
            'title' => __( 'Stats', 'fullworks-anti-spam' ),
            'tip'   => sprintf( __( 'Upgraded version will automatically email you spam statistics', 'fullworks-anti-spam' ) ),
        ];
        $this->options = get_option( 'fullworks-anti-spam' );
        $this->options = array_merge( self::option_defaults( 'fullworks-anti-spam' ), $this->options );
        $this->settings_title = '<img src="' . dirname( plugin_dir_url( __FILE__ ) ) . '/admin/images/brand/light-anti-spam-75h.png" class="logo" alt="Fullworks Logo"/><div class="text">' . __( 'Anti Spam Settings', 'fullworks-anti-spam' ) . '</div>';
        parent::__construct();
    }
    
    public function plugin_action_links()
    {
        add_filter( 'plugin_action_links_' . FULLWORKS_ANTI_SPAM_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
    }
    
    public function add_plugin_action_links( $links )
    {
        $links = array_merge( array( '<a href="' . esc_url( admin_url( 'options-general.php?page=fullworks-anti-spam-settings' ) ) . '">' . __( 'Settings' ) . '</a>' ), $links );
        return $links;
    }
    
    public function register_settings()
    {
        /* Register our setting. */
        register_setting(
            $this->option_group,
            /* Option Group */
            'fullworks-anti-spam',
            /* Option Name */
            array( $this, 'sanitize_spam' )
        );
        Utilities::get_instance()->register_settings_page_tab(
            __( 'Settings', 'fullworks-anti-spam' ),
            'settings',
            admin_url( 'admin.php?page=fullworks-anti-spam-settings' ),
            0
        );
        /* Add settings menu page */
        $this->settings_page = add_submenu_page(
            'fullworks-anti-spam-settings',
            'Settings',
            /* Page Title */
            'Settings',
            /* Menu Title */
            'manage_options',
            /* Capability */
            'fullworks-anti-spam-settings',
            /* Page Slug */
            array( $this, 'settings_page' )
        );
        register_setting(
            $this->option_group,
            /* Option Group */
            "{$this->option_group}-reset",
            /* Option Name */
            array( $this, 'reset_sanitize' )
        );
    }
    
    public function reset_sanitize( $settings )
    {
        // Detect multiple sanitizing passes.
        // Accomodates bug: https://core.trac.wordpress.org/ticket/21989
        
        if ( !empty($settings) ) {
            add_settings_error(
                $this->option_group,
                '',
                'Settings reset to defaults.',
                'updated'
            );
            /* Delete Option */
            $this->delete_options();
        }
        
        return $settings;
    }
    
    public function delete_options()
    {
        delete_transient( 'fullworks-anti-spam-utility-data' );
        update_option( 'fullworks-anti-spam', self::option_defaults( 'fullworks-anti-spam' ) );
    }
    
    public static function option_defaults( $option )
    {
        global  $fwantispam_fs ;
        switch ( $option ) {
            case 'fullworks-anti-spam':
                $res = array(
                    'comments' => 1,
                    'days'     => 30,
                );
                return $res;
            default:
                return false;
        }
    }
    
    public function add_meta_boxes()
    {
        $this->add_meta_box(
            'botspam',
            /* Meta Box ID */
            __( 'Bot Spam Protection', 'fullworks-anti-spam' ),
            /* Title */
            array( $this, 'meta_box_bot_spam' ),
            /* Function Callback */
            $this->settings_page_id,
            /* Screen: Our Settings Page */
            'normal',
            /* Context */
            'default',
            /* Priority */
            null,
            false
        );
        $this->add_meta_box(
            'blspam',
            /* Meta Box ID */
            __( 'Blacklist Checking', 'fullworks-anti-spam' ),
            /* Title */
            array( $this, 'meta_box_blacklist_spam' ),
            /* Function Callback */
            $this->settings_page_id,
            /* Screen: Our Settings Page */
            'normal',
            /* Context */
            'default',
            /* Priority */
            null,
            false
        );
        $this->add_meta_box(
            'stats',
            /* Meta Box ID */
            __( 'Stats', 'fullworks-anti-spam' ),
            /* Title */
            array( $this, 'meta_box_stats' ),
            /* Function Callback */
            $this->settings_page_id,
            /* Screen: Our Settings Page */
            'side',
            /* Context */
            'default',
            null,
            false
        );
        $this->add_meta_box(
            'humanspam',
            /* Meta Box ID */
            __( 'Human Spam Protection', 'fullworks-anti-spam' ),
            /* Title */
            array( $this, 'meta_box_human_spam' ),
            /* Function Callback */
            $this->settings_page_id,
            /* Screen: Our Settings Page */
            'normal',
            /* Context */
            'default',
            /* Priority */
            null,
            false
        );
        $this->add_meta_box(
            'housekeeping',
            /* Meta Box ID */
            __( 'Adminstration', 'fullworks-anti-spam' ),
            /* Title */
            array( $this, 'meta_box_spam_admin' ),
            /* Function Callback */
            $this->settings_page_id,
            /* Screen: Our Settings Page */
            'normal',
            /* Context */
            'default',
            /* Priority */
            null,
            false
        );
    }
    
    private function add_meta_box(
        $id,
        $title,
        $callback,
        $screen = null,
        $context = 'advanced',
        $priority = 'default',
        $callback_args = null,
        $closed = true
    )
    {
        add_meta_box(
            $id,
            $title,
            $callback,
            $screen,
            $context,
            $priority,
            $callback_args
        );
        if ( !isset( $_GET['settings-updated'] ) ) {
            if ( $closed ) {
                add_filter( "postbox_classes_{$screen}_{$id}", function ( $classes ) {
                    array_push( $classes, 'closed' );
                    return $classes;
                } );
            }
        }
    }
    
    public function sanitize_spam( $settings )
    {
        if ( isset( $_REQUEST['fullworks-anti-spam-reset'] ) ) {
            return $settings;
        }
        if ( !isset( $settings['comments'] ) ) {
            $settings['comments'] = 0;
        }
        return $settings;
    }
    
    public function meta_box_stats()
    {
        $data = $this->utilities->get_spam_stats();
        
        if ( count( $data ) < 2 ) {
            _e( '<p>No spam stopped yet, be patient it will come soon enough</p>', 'fullworks-anti-spam' );
            return;
        }
        
        ?>
        <p>Spam stopped in the last 30 days</p>
        <table class="form-table">
            <tbody>
			<?php 
        foreach ( $data as $value ) {
            if ( $value === end( $data ) ) {
                break;
            }
            ?>

                <tr>
                    <td>
						<?php 
            printf(
                '%3$s - %1$s [%2$s] %4$s',
                $value['source'],
                $value['type'],
                $value['count'],
                $value['link']
            );
            ?>
                    </td>
                </tr>
				<?php 
        }
        ?>
            </tbody>
        </table>
		<?php 
    }
    
    public function meta_box_bot_spam()
    {
        ?>
        <table class="form-table">
            <tbody>
            <tr>
				<?php 
        $this->display_th( 'Bot Comments' );
        ?>
                <td>
                    <label for="fullworks-anti-spam[comments]"><input type="checkbox"
                                                                      name="fullworks-anti-spam[comments]"
                                                                      id="fullworks-anti-spam[comments]"
                                                                      value="1"
							<?php 
        checked( '1', $this->options['comments'] );
        ?>>
						<?php 
        _e( 'Enable bot spam protection for comments', 'fullworks-anti-spam' );
        ?></label>
                </td>
				<?php 
        $this->display_tip( 'Bot Comments' );
        ?>
            </tr>
			<?php 
        $disabled = 'disabled';
        $opt = 0;
        ?>
            <tr>
				<?php 
        $this->display_th( 'Bot Forms' );
        ?>
                <td>
                    <label for="fullworks-anti-spam[forms]"><input type="checkbox"
                                                                   name="fullworks-anti-spam[forms]"
                                                                   id="fullworks-anti-spam[forms]"
                                                                   value="1"
							<?php 
        checked( '1', $opt );
        echo  $disabled ;
        ?>>
						<?php 
        $msg = sprintf( __( '<a  href="%s">Activate FREE trial</a> to enable bot spam protection for forms input', 'fullworks-anti-spam' ), $this->freemius->get_trial_url() );
        echo  $msg ;
        ?>
                    </label>
                </td>
				<?php 
        $this->display_tip( 'Bot Forms' );
        ?>
            </tr>
			<?php 
        ?>

            </tbody>
        </table>
		<?php 
    }
    
    public function meta_box_blacklist_spam()
    {
        $disabled = 'disabled';
        $opt_f = 0;
        $opt_c = 0;
        ?>
        <table class="form-table">
            <tbody>
            <tr>
				<?php 
        $this->display_th( 'BL Comments' );
        ?>
                <td>
                    <label for="fullworks-anti-spam[bl-comments]"><input type="checkbox"
                                                                         name="fullworks-anti-spam[bl-comments]"
                                                                         id="fullworks-anti-spam[bl-comments]"
                                                                         value="1"
							<?php 
        checked( '1', $opt_c );
        echo  $disabled ;
        ?>>
						<?php 
        $msg = sprintf( __( '<a  href="%s">Activate the FREE trial</a> to remove more spam by blacklist checking for comments', 'fullworks-anti-spam' ), $this->freemius->get_trial_url() );
        echo  $msg ;
        ?>
                    </label>
                </td>
				<?php 
        $this->display_tip( 'BL Comments' );
        ?>
            </tr>

            <tr>
				<?php 
        $this->display_th( 'BL Forms' );
        ?>
                <td>
                    <label for="fullworks-anti-spam[bl-forms]"><input type="checkbox"
                                                                      name="fullworks-anti-spam[bl-forms]"
                                                                      id="fullworks-anti-spam[bl-forms]"
                                                                      value="1"
							<?php 
        checked( '1', $opt_f );
        echo  $disabled ;
        ?>>
						<?php 
        $msg = sprintf( __( '<a  href="%s">Activate FREE trial</a> to enable blacklist checking for forms input', 'fullworks-anti-spam' ), $this->freemius->get_trial_url() );
        echo  $msg ;
        ?>
                    </label>
                </td>
				<?php 
        $this->display_tip( 'Bot Forms' );
        ?>
            </tr>
			<?php 
        ?>

            </tbody>
        </table>
		<?php 
    }
    
    public function meta_box_human_spam()
    {
        
        if ( !$this->freemius->can_use_premium_code() ) {
            ?>
            <table class="form-table">
                <tbody>
                <tr>
					<?php 
            $this->display_th( 'Upgrade' );
            ?>
                    <td>
						<?php 
            printf( __( '<a href="%s">Activate FREE trial</a> to protect against manually input \'human\' spam', 'fullworks-anti-spam' ), $this->freemius->get_trial_url() );
            ?>
                    </td>
					<?php 
            $this->display_tip( 'Upgrade' );
            ?>
                </tr>
                </tbody>
            </table>
			<?php 
        }
    
    }
    
    public function meta_box_spam_admin()
    {
        ?>
        <table class="form-table">
            <tbody>
            <tr>
				<?php 
        $this->display_th( 'Keep Spam' );
        ?>
                <td>
                    <label for="fullworks-anti-spam[days]"><input type="number"
                                                                  name="fullworks-anti-spam[days]"
                                                                  id="fullworks-anti-spam[days]"
                                                                  class="small-text"
                                                                  value="<?php 
        echo  $this->options['days'] ;
        ?>"
                                                                  min="0">
						<?php 
        _e( 'Days', 'fullworks-anti-spam' );
        ?></label>
                </td>
				<?php 
        $this->display_tip( 'Keep Spam' );
        ?>
            </tr>
			<?php 
        
        if ( !$this->freemius->can_use_premium_code() ) {
            ?>
                <tr>
					<?php 
            $this->display_th( 'Spam Stats Free' );
            ?>
                    <td>
						<?php 
            printf( __( '<a href="%s">Upgrade</a> for spam statistics auto reporting by email', 'fullworks-anti-spam' ), $this->freemius->get_upgrade_url() );
            ?>
                    </td>
					<?php 
            $this->display_tip( 'Spam Stats Free' );
            ?>
                </tr>
				<?php 
        }
        
        ?>

            </tbody>
        </table>
		<?php 
    }
    
    private function display_th( $title )
    {
        ?>
        <th scope="row"><?php 
        echo  $this->titles[$title]['title'] ;
        ?>
        </th>
		<?php 
    }
    
    private function display_tip( $title )
    {
        ?>
        <td><?php 
        echo  ( isset( $this->titles[$title]['tip'] ) ? '<div class="help-tip"><p>' . $this->titles[$title]['tip'] . '</p></div>' : '' ) ;
        ?>
        </td>
		<?php 
    }

}