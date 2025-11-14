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

use ActionScheduler_Store;
use Fullworks_Anti_Spam\Anti_Spam_Api;
use Fullworks_Anti_Spam\Core\Forms_Registrations;
use Fullworks_Anti_Spam\Core\Utilities;
class Admin_Settings extends Admin_Pages {
    protected $settings_page;

    protected $settings_page_id = 'settings_page_fullworks-anti-spam-settings';

    protected $option_group = 'fullworks-anti-spam';

    /** @var Utilities $utilities */
    protected $utilities;

    protected $options;

    protected $show_upgrade_notice = false;

    private $titles;

    /** @var Anti_Spam_API $api */
    private $api;

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
        $utilities,
        $api
    ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->freemius = $freemius;
        $this->utilities = $utilities;
        $this->api = $api;
        $this->options = get_option( 'fullworks-anti-spam' );
        parent::__construct();
    }

    /**
     * Get settings title with lazy initialization to avoid early translation loading
     *
     * @return string
     */
    protected function get_settings_title() {
        if ( null === $this->settings_title ) {
            $this->settings_title = '<img src="' . dirname( plugin_dir_url( __FILE__ ) ) . '/admin/images/brand/light-anti-spam-75h.svg" class="logo" alt="Fullworks Logo"/><div class="text">' . __( 'Anti Spam Settings', 'fullworks-anti-spam' ) . '</div>';
        }
        return $this->settings_title;
    }

    /**
     * Override parent settings_page to add upgrade notice and initialize settings_title
     */
    public function settings_page() {
        // Initialize settings title before rendering
        $this->settings_title = $this->get_settings_title();
        // Check if we should render the upgrade notice
        $show_upgrade_notice = false;
        if ( !$this->freemius->can_use_premium_code() ) {
            $user_id = get_current_user_id();
            $dismissed = get_user_meta( $user_id, 'fwas_upgrade_notice_dismissed', true );
            if ( !$dismissed ) {
                $show_upgrade_notice = true;
            }
        }
        // Store flag for use in render method
        $this->show_upgrade_notice = $show_upgrade_notice;
        /* global vars */
        global $hook_suffix;
        if ( $this->settings_page_id === $hook_suffix ) {
            /* enable add_meta_boxes function in this page. */
            do_action( $this->settings_page_id . '_settings_page_boxes', $hook_suffix );
            ?>

            <div class="wrap fs-page">

                <h2 class="brand"><?php 
            echo wp_kses_post( $this->settings_title );
            ?></h2>

                <div class="fs-settings-meta-box-wrap">
					<?php 
            $this->display_tabs();
            ?>

					<?php 
            $this->render_upgrade_notice();
            ?>

                    <form id="fs-smb-form" method="post" action="options.php">

						<?php 
            settings_fields( $this->option_group );
            // options group
            ?>
						<?php 
            wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
            ?>
						<?php 
            wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
            ?>


                        <div id="poststuff">

                            <div id="post-body"
                                 class="metabox-holder columns-<?php 
            echo ( 1 == get_current_screen()->get_columns() ? '1' : '2' );
            ?>">

                                <div id="postbox-container-1" class="postbox-container">

									<?php 
            do_meta_boxes( $hook_suffix, 'side', null );
            ?>
                                    <!-- #side-sortables -->

                                </div><!-- #postbox-container-1 -->

                                <div id="postbox-container-2" class="postbox-container">

									<?php 
            $this->do_promo_box();
            ?>


									<?php 
            do_meta_boxes( $hook_suffix, 'normal', null );
            ?>
                                    <!-- #normal-sortables -->

									<?php 
            do_meta_boxes( $hook_suffix, 'advanced', null );
            ?>
                                    <!-- #advanced-sortables -->

                                </div><!-- #postbox-container-2 -->

                            </div><!-- #post-body -->

                            <br class="clear">

                        </div><!-- #poststuff -->

                    </form>

                </div><!-- .fs-settings-meta-box-wrap -->

            </div><!-- .wrap -->
			<?php 
        }
    }

    /**
     * Render upgrade notice with protection summary (compact version)
     */
    public function render_upgrade_notice() {
        if ( empty( $this->show_upgrade_notice ) ) {
            return;
        }
        // Get installed forms count
        $installed_forms = Forms_Registrations::get_registered_forms();
        $total_installed = count( $installed_forms );
        // Includes comments
        // Count protected forms (comments + forms with protection_level = 1)
        $free_protected_count = 1;
        // Comments always protected
        foreach ( $installed_forms as $form_key => $form_data ) {
            if ( $form_key === 'comments' ) {
                continue;
            }
            if ( isset( $form_data['protection_level'] ) && $form_data['protection_level'] === 1 ) {
                $free_protected_count++;
            }
        }
        $has_unprotected = $free_protected_count < $total_installed;
        ?>
		<div class="fwas-upgrade-notice">
			<button type="button" class="notice-dismiss"></button>
			<h3><?php 
        esc_html_e( 'Unlock Full Protection', 'fullworks-anti-spam' );
        ?></h3>

			<?php 
        if ( $has_unprotected ) {
            ?>
				<p class="fwas-protection-summary">
					<?php 
            /* translators: %1$d: number of protected forms, %2$d: total forms */
            printf( esc_html__( '%1$d of %2$d systems fully protected', 'fullworks-anti-spam' ), (int) $free_protected_count, (int) $total_installed );
            ?>
					<strong><?php 
            esc_html_e( ' — Missing: AI spam detection & IP blocklist', 'fullworks-anti-spam' );
            ?></strong>
				</p>
			<?php 
        }
        ?>

			<p class="fwas-upgrade-benefits">
				<?php 
        esc_html_e( 'Stop manual spammers with AI • Block 10M+ spam IPs • Protect all forms • Email reports', 'fullworks-anti-spam' );
        ?>
			</p>

			<a href="<?php 
        echo esc_url( $this->freemius->get_trial_url() );
        ?>" class="fwas-trial-cta">
				<?php 
        esc_html_e( 'Start 7-Day FREE Trial', 'fullworks-anti-spam' );
        ?>
			</a>
			<span class="fwas-trial-details">
				<?php 
        esc_html_e( 'No credit card • Cancel anytime', 'fullworks-anti-spam' );
        ?>
			</span>
		</div>
		<?php 
    }

    public static function option_defaults( $option ) {
        /** @var \Freemius $fwantispam_fs Freemius global object. */
        global $fwas_fs;
        switch ( $option ) {
            case 'fullworks-anti-spam':
                $res = array(
                    'comments'              => 1,
                    'days'                  => 30,
                    'freemius_state_set'    => false,
                    'show_dashboard_widget' => 1,
                );
                if ( !$fwas_fs->is_anonymous() && !$fwas_fs->is_plan_or_trial( 'gdpr', true ) ) {
                    $res['sendspam'] = 1;
                } else {
                    $res['sendspam'] = 0;
                }
                return $res;
            default:
                return false;
        }
    }

    /**
     * Get titles array with lazy initialization.
     * This ensures translations are only loaded when needed (after init hook).
     *
     * @return array
     */
    private function get_titles() {
        if ( null !== $this->titles ) {
            return $this->titles;
        }
        $this->titles = array(
            'Bot Comments' => array(
                'title' => esc_html__( 'Comments', 'fullworks-anti-spam' ),
                'tip'   => esc_html__( 'Kill automated (bot) spam without Captcha or other annoying user quizes', 'fullworks-anti-spam' ),
            ),
        );
        $this->titles['Keep Spam'] = array(
            'title' => esc_html__( 'Keep Spam', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Select the number of days to keep spam, when spam is logged. Select 0 to discard immediately', 'fullworks-anti-spam' ),
        );
        $this->titles['Email Logs'] = array(
            'title' => esc_html__( 'Email Logs', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Select the number of days to keep email logs. Select 0 to discard immediately', 'fullworks-anti-spam' ),
        );
        $this->titles['Spam Email'] = array(
            'title' => esc_html__( 'Send spam email to:', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Your contact form will still send you some spam emails marked as spam in the subject and headers, it is recommended
					to set a separate email to avoid clutter, leave blank and it will default to your form settings', 'fullworks-anti-spam' ),
        );
        $this->titles['Bot Forms'] = array(
            'title' => esc_html__( 'Forms', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Forms types protected, without Captcha, Gravity Forms, Contact Form 7, WPForms, WP Registration and WooCommerce Registrations', 'fullworks-anti-spam' ),
        );
        $this->titles['BL Comments'] = array(
            'title' => esc_html__( 'Comments', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Refuse comments from IP addresses that are on an IP blocklist', 'fullworks-anti-spam' ),
        );
        $this->titles['BL Forms'] = array(
            'title' => esc_html__( 'Forms', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Reject form submissions from IPs with a bad reputation, Gravity Forms, Contact Form 7, WPForms, WP Registration and WooCommerce Registrations', 'fullworks-anti-spam' ),
        );
        $this->titles['Upgrade'] = array(
            'title' => esc_html__( 'Upgrade', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'By upgrading you will benefit from machine learning and AI to protect against human manually input spam', 'fullworks-anti-spam' ),
        );
        $this->titles['Spam Stats Free'] = array(
            'title' => esc_html__( 'Stats', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Upgraded version will automatically email spam statistics', 'fullworks-anti-spam' ),
        );
        $this->titles['Spam Score'] = array(
            'title' => esc_html__( 'Statistical Analysis', 'fullworks-anti-spam' ) . '&nbsp;<sup>(1)</sup>',
            'tip'   => esc_html__( 'Set the desired spam probability filter percentage, default is 55 percent, a lower percentage will exclude more messages and may include genuine messages, a higher percentage will allow more messages through so may let through more spam', 'fullworks-anti-spam' ),
        );
        $this->titles['AI'] = array(
            'title' => esc_html__( 'Artificial Intelligence', 'fullworks-anti-spam' ) . '&nbsp;<sup>(2)</sup>',
            'tip'   => esc_html__( 'Using natural language AI learning server will provide a probability score as to whether user input is likely to be spam, please note as this uses third party AI servers, the message submission may be delayed by as much as 3 seconds while the service responds, if this concerns you set this to zero to not use.', 'fullworks-anti-spam' ),
        );
        $this->titles['Strategy'] = array(
            'title' => esc_html__( 'Strategy', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Decide if you want both machine methods to indicate spam to mark as spam- conservative -or just one method - aggressive. Normally you would choose \'aggressive\' but if for instance your site is about adult content or sells SEO so valid messages may seem spammy you may want to try \'conservative\'', 'fullworks-anti-spam' ),
        );
        $this->titles['Human Comments'] = array(
            'title' => esc_html__( 'Comments', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Use machine learning technology to filter out comment spam from human beings', 'fullworks-anti-spam' ),
        );
        $this->titles['Human Forms'] = array(
            'title' => esc_html__( 'Forms', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Forms types protected, without Captcha, Gravity Forms, Contact Form 7, WPForms, Fusion Forms, QEM, QCF, WP Registration and WooCommerce Registrations', 'fullworks-anti-spam' ),
        );
        $this->titles['Single Words'] = array(
            'title' => esc_html__( 'Single Words', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Single words, often random in text areas, can be a sign of spam, check this option to always decide one word answers are always spam', 'fullworks-anti-spam' ),
        );
        $this->titles['Send Spam'] = array(
            'title' => esc_html__( 'Spam transmission', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Disabling transmission ensures complete privacy for website visitor messages and comments which enables easy compliance with privacy laws such as GDPR and UK GDPR without relying on Data Processing Agreements (DPA) and
			 Standard Contractual Clauses (SCC) if you want to enable transmission to Fullworks and would like a DPA and SCC please contact support. Disabling will mean that AI can not be used and remote Statistical Analysis will not happen resulting in potentially
			 lower correct classification of human spam. Bot and IP Blocklist and Local Statistical Analysis spam detection remains the same.', 'fullworks-anti-spam' ),
        );
        $this->titles['Spam Email'] = array(
            'title' => esc_html__( 'Spam to Email', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Some forms we can\'t stop email being sent even when we detect spam so to handle this you can set up a different email address so you can direct to spam folders. Leaving blank will send the email to whatever is set up in the forms with a modified subject lime.', 'fullworks-anti-spam' ),
        );
        $this->titles['Dashboard Widget'] = array(
            'title' => esc_html__( 'Dashboard Widget', 'fullworks-anti-spam' ),
            'tip'   => esc_html__( 'Display spam protection status and statistics on the WordPress dashboard. Shows protection coverage for installed form systems and recent spam blocking activity.', 'fullworks-anti-spam' ),
        );
        return $this->titles;
    }

    public function plugin_action_links() {
        add_filter( 'plugin_action_links_' . FULLWORKS_ANTI_SPAM_PLUGIN_BASENAME, array($this, 'add_plugin_action_links') );
    }

    public function add_plugin_action_links( $links ) {
        $links = array_merge( array('<a href="' . esc_url( admin_url( 'options-general.php?page=fullworks-anti-spam-settings' ) ) . '">' . __( 'Settings', 'fullworks-anti-spam' ) . '</a>'), $links );
        return $links;
    }

    public function register_settings() {
        /* Register our setting. */
        register_setting( 
            $this->option_group,
            /* Option Group */
            'fullworks-anti-spam',
            /* Option Name */
            array($this, 'sanitize_spam')
         );
        Utilities::get_instance()->register_settings_page_tab(
            __( 'Settings', 'fullworks-anti-spam' ),
            'settings',
            admin_url( 'admin.php?page=fullworks-anti-spam-settings' ),
            0
        );
        Utilities::get_instance()->register_settings_page_tab(
            __( 'Documentation', 'fullworks-anti-spam' ),
            'settings',
            'https://fullworksplugins.com/docs/anti-spam-by-fullworks/',
            5
        );
        /* Add settings menu page */
        $this->settings_page = add_submenu_page(
            'fullworks-anti-spam-settings',
            esc_html__( 'Settings', 'fullworks-anti-spam' ),
            /* Page Title */
            esc_html__( 'Settings', 'fullworks-anti-spam' ),
            /* Menu Title */
            'manage_options',
            /* Capability */
            'fullworks-anti-spam-settings',
            /* Page Slug */
            array($this, 'settings_page')
        );
        register_setting( 
            $this->option_group,
            /* Option Group */
            "{$this->option_group}-reset",
            /* Option Name */
            array($this, 'reset_sanitize')
         );
    }

    public function reset_sanitize( $settings ) {
        // Detect multiple sanitizing passes.
        // Accomodates bug: https://core.trac.wordpress.org/ticket/21989
        check_admin_referer( 'fwas_submit_meta_box', '_fwas_submit_meta_box_nonce' );
        if ( !empty( $settings ) ) {
            add_settings_error(
                $this->option_group,
                'reset',
                esc_html__( 'Settings reset to defaults.', 'fullworks-anti-spam' ),
                'updated'
            );
            /* Delete Option */
            $this->delete_options();
        }
        return $settings;
    }

    public function delete_options() {
        delete_transient( 'fullworks-anti-spam-utility-data' );
        update_option( 'fullworks-anti-spam', self::option_defaults( 'fullworks-anti-spam' ) );
    }

    public function add_meta_boxes() {
        $this->add_meta_box(
            'botspam',
            /* Meta Box ID */
            esc_html__( 'Bot Spam Protection', 'fullworks-anti-spam' ),
            /* Title */
            array($this, 'meta_box_bot_spam'),
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
            esc_html__( 'IP Blocklist Checking', 'fullworks-anti-spam' ),
            /* Title */
            array($this, 'meta_box_blocklist_spam'),
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
            esc_html__( 'Stats', 'fullworks-anti-spam' ),
            /* Title */
            array($this, 'meta_box_stats'),
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
            esc_html__( 'Human Spam Protection', 'fullworks-anti-spam' ),
            /* Title */
            array($this, 'meta_box_human_spam'),
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
        $this->add_send_spam_box();
        $this->add_meta_box(
            'housekeeping',
            /* Meta Box ID */
            esc_html__( 'Administration', 'fullworks-anti-spam' ),
            /* Title */
            array($this, 'meta_box_spam_admin'),
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

    private function add_send_spam_box() {
        $this->add_meta_box(
            'sendspam',
            /* Meta Box ID */
            esc_html__( 'Site visitor privacy [GDPR safe]', 'fullworks-anti-spam' ),
            /* Title */
            array($this, 'meta_box_send_spam'),
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
    ) {
        add_meta_box(
            $id,
            $title,
            $callback,
            $screen,
            $context,
            $priority,
            $callback_args
        );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification not required.
        if ( !isset( $_GET['settings-updated'] ) ) {
            if ( $closed ) {
                add_filter( "postbox_classes_{$screen}_{$id}", function ( $classes ) {
                    array_push( $classes, 'closed' );
                    return $classes;
                } );
            }
        }
    }

    public function sanitize_spam( $settings ) {
        $options = get_option( 'fullworks-anti-spam' );
        if ( isset( $_REQUEST['fullworks-anti-spam-reset'] ) ) {
            check_admin_referer( 'fwas_submit_meta_box', '_fwas_submit_meta_box_nonce' );
            return $settings;
        }
        if ( !isset( $settings['comments'] ) ) {
            $settings['comments'] = 0;
        }
        if ( !isset( $settings['sendspam'] ) ) {
            $settings['sendspam'] = 0;
        }
        if ( !isset( $settings['show_dashboard_widget'] ) ) {
            $settings['show_dashboard_widget'] = 0;
        }
        return $settings;
    }

    public function meta_box_stats() {
        $data = $this->utilities->get_spam_stats();
        if ( count( $data ) < 2 ) {
            echo '<p>' . esc_html__( 'No spam stopped yet, be patient it will come soon enough', 'fullworks-anti-spam' ) . '</p>';
            return;
        }
        ?>
        <p><?php 
        esc_html_e( 'Spam stopped in the last 30 days', 'fullworks-anti-spam' );
        ?></p>
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
            echo esc_html( $value['count'] ) . ' - ' . esc_html( $value['source'] ) . '[' . esc_html( $value['type'] ) . '] ';
            if ( !empty( $value['link'] ) ) {
                echo '<a href="' . esc_url( admin_url( sprintf( $value['link'], 1 ) ) ) . '">' . esc_html__( 'Review', 'fullworks-anti-spam' ) . '</a>';
            }
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

    public function meta_box_diagnostics() {
        $enabled = ( isset( $this->options['diagnostics_enabled'] ) ? $this->options['diagnostics_enabled'] : 0 );
        ?>
        <p>
            <label>
                <input type="checkbox"
                       name="fullworks-anti-spam[diagnostics_enabled]"
                       value="1"
                       <?php 
        checked( 1, $enabled );
        ?>>
				<?php 
        esc_html_e( 'Enable', 'fullworks-anti-spam' );
        ?>
            </label>
        </p>
		<?php 
        if ( $enabled ) {
            ?>
            <p>
                <a href="<?php 
            echo esc_url( admin_url( 'admin.php?page=fullworks-anti-spam-diagnostics' ) );
            ?>">
					<?php 
            esc_html_e( 'View Diagnostics', 'fullworks-anti-spam' );
            ?> →
                </a>
            </p>
		<?php 
        }
        ?>
		<?php 
    }

    public function meta_box_bot_spam() {
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
                                                                      id="fullworks-anti-spam[comments]" value="1"
							<?php 
        checked( '1', $this->options['comments'] );
        ?>>
						<?php 
        esc_html_e( 'Enable bot spam protection for comments', 'fullworks-anti-spam' );
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
                    <label for="fullworks-anti-spam[forms]"><input type="checkbox" name="fullworks-anti-spam[forms]"
                                                                   id="fullworks-anti-spam[forms]" value="1"
							<?php 
        checked( '1', $opt );
        echo esc_attr( $disabled );
        ?>
                        >
						<?php 
        // Build message showing current protection status
        if ( $this->freemius->is__premium_only() && $this->freemius->can_use_premium_code() ) {
            // Premium users: Simple enable message
            $msg = esc_html__( 'Enable bot spam protection for forms input', 'fullworks-anti-spam' );
        } else {
            // Free users: Show which forms have bot protection and upgrade option
            // Get forms with free bot protection dynamically (protection_level = 1)
            $forms_with_free_bot_protection = Forms_Registrations::get_installed_form_names_by_protection_level( 1 );
            if ( !empty( $forms_with_free_bot_protection ) ) {
                $msg = sprintf( 
                    /* translators: %s: comma-separated list of form names */
                    esc_html__( 'Bot protection enabled for %s.', 'fullworks-anti-spam' ),
                    implode( ', ', $forms_with_free_bot_protection )
                 );
                $msg .= ' <a href="' . esc_url( $this->freemius->get_trial_url() ) . '">' . esc_html__( 'Activate the FREE trial', 'fullworks-anti-spam' ) . '</a> ';
                $msg .= esc_html__( 'for full protection (human spam + IP blocklist) on all forms', 'fullworks-anti-spam' );
            } else {
                $msg = '<a href="' . esc_url( $this->freemius->get_trial_url() ) . '">' . esc_html__( 'Activate the FREE trial', 'fullworks-anti-spam' ) . '</a> ';
                $msg .= esc_html__( 'to enable bot spam protection for forms input', 'fullworks-anti-spam' );
            }
        }
        echo wp_kses_post( $msg );
        ?>
                    </label>
                </td>
				<?php 
        $this->display_tip( 'Bot Forms' );
        ?>
            </tr>
            </tbody>
        </table>
		<?php 
    }

    private function display_th( $title ) {
        ?>
        <th scope="row">
			<?php 
        $titles = $this->get_titles();
        echo wp_kses_post( $titles[$title]['title'] );
        ?>
        </th>
		<?php 
    }

    private function display_tip( $title ) {
        ?>
        <td class="help-tip__td">
			<?php 
        $titles = $this->get_titles();
        echo ( isset( $titles[$title]['tip'] ) ? '<div class="help-tip"><p>' . esc_html( $titles[$title]['tip'] ) . '</p></div>' : '' );
        ?>
        </td>
		<?php 
    }

    public function meta_box_blocklist_spam() {
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
        echo esc_attr( $disabled );
        ?>
                        >
						<?php 
        $msg = '<a  href="' . esc_url( $this->freemius->get_trial_url() ) . '">' . esc_html__( 'Activate the FREE trial', 'fullworks-anti-spam' ) . '</a> ' . esc_html__( 'to remove more spam by IP blocklist checking for comments', 'fullworks-anti-spam' );
        echo wp_kses_post( $msg );
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
        echo esc_attr( $disabled );
        ?>
                        >
						<?php 
        $msg = '<a  href="' . esc_url( $this->freemius->get_trial_url() ) . '">' . esc_html__( 'Activate the FREE trial', 'fullworks-anti-spam' ) . '</a> ' . esc_html__( 'to enable IP blocklist checking for forms input', 'fullworks-anti-spam' );
        echo wp_kses_post( $msg );
        ?>
                    </label>
                </td>
				<?php 
        $this->display_tip( 'Bot Forms' );
        ?>
            </tr>
            </tbody>
        </table>
		<?php 
    }

    public function meta_box_human_spam() {
        $disabled = 'disabled';
        $opt_f = 0;
        $opt_c = 0;
        $opt_level = 0;
        $opt_ai = 0;
        $opt_strategy = 'aggressive';
        $opt_single_word_spam = 0;
        ?>
        <table class="form-table">
            <tbody>
            <tr>
				<?php 
        $this->display_th( 'Human Comments' );
        ?>
                <td>
                    <label for="fullworks-anti-spam[hcomments]"><input type="checkbox"
                                                                       name="fullworks-anti-spam[hcomments]"
                                                                       id="fullworks-anti-spam[hcomments]"
                                                                       value="1"
							<?php 
        checked( '1', $opt_c );
        echo esc_attr( $disabled );
        ?>>
						<?php 
        $msg = '<a  href="' . esc_url( $this->freemius->get_trial_url() ) . '">' . esc_html__( 'Activate the FREE trial', 'fullworks-anti-spam' ) . '</a> ' . esc_html__( 'to protect comments from humans', 'fullworks-anti-spam' );
        echo wp_kses_post( $msg );
        ?>
                    </label>
                </td>
				<?php 
        $this->display_tip( 'Human Comments' );
        ?>
            </tr>
            <tr>
				<?php 
        $this->display_th( 'Human Forms' );
        ?>
                <td>
                    <label for="fullworks-anti-spam[hforms]"><input type="checkbox"
                                                                    name="fullworks-anti-spam[hforms]"
                                                                    id="fullworks-anti-spam[hforms]"
                                                                    value="1"
							<?php 
        checked( '1', $opt_f );
        echo esc_attr( $disabled );
        ?>>
						<?php 
        $msg = '<a  href="' . esc_url( $this->freemius->get_trial_url() ) . '">' . esc_html__( 'Activate the FREE trial', 'fullworks-anti-spam' ) . '</a> ' . esc_html__( 'to protect forms from humans', 'fullworks-anti-spam' );
        echo wp_kses_post( $msg );
        ?>
                    </label>
                </td>
				<?php 
        $this->display_tip( 'Human Forms' );
        ?>
            </tr>

            <tr id="fwas-spam-score">
				<?php 
        $this->display_th( 'Spam Score' );
        ?>
                <td>
                    <label for="fullworks-anti-spam[level]"><input type="number"
                                                                   name="fullworks-anti-spam[level]"
                                                                   id="fullworks-anti-spam[level]"
                                                                   class="small-text"
                                                                   value="<?php 
        echo (int) $opt_level;
        ?>"
                                                                   min="0"
                                                                   max="99"
							<?php 
        echo esc_attr( $disabled );
        ?>
                        >
						<?php 
        $msg = '<a  href="' . esc_url( $this->freemius->get_trial_url() ) . '">' . esc_html__( 'Activate the FREE trial', 'fullworks-anti-spam' ) . '</a> ' . esc_html__( 'to use probability server', 'fullworks-anti-spam' );
        echo wp_kses_post( $msg );
        ?>
                    </label>
                </td>
				<?php 
        $this->display_tip( 'Spam Score' );
        ?>
            </tr>
			<?php 
        if ( !$this->freemius->is_plan_or_trial( 'gdpr', true ) || 0 === $this->options['sendspam'] ) {
            ?>
                <tr id="fwas-ai">
					<?php 
            $this->display_th( 'AI' );
            ?>
                    <td>
                        <label for="fullworks-anti-spam[ai]"><input type="number"
                                                                    name="fullworks-anti-spam[ai]"
                                                                    id="fullworks-anti-spam[ai]"
                                                                    class="small-text"
                                                                    value="<?php 
            echo (int) $opt_ai;
            ?>"
                                                                    min="0"
                                                                    max="99"
								<?php 
            echo esc_attr( $disabled );
            ?>>
							<?php 
            $msg = '<a  href="' . esc_url( $this->freemius->get_trial_url() ) . '">' . esc_html__( 'Activate the FREE trial', 'fullworks-anti-spam' ) . '</a> ' . esc_html__( 'to use AI server', 'fullworks-anti-spam' );
            echo wp_kses_post( $msg );
            ?>
                        </label>
                    </td>
					<?php 
            $this->display_tip( 'AI' );
            ?>
                </tr>
                <tr id="fwas-settings-strategy">
					<?php 
            $this->display_th( 'Strategy' );
            ?>
                    <td>
                        <label for="fullworks-anti-spam[strategy]"><input type="radio"
                                                                          name="fullworks-anti-spam[strategy]"
                                                                          id="fullworks-anti-spam[strategy]"
                                                                          value="conservative"
								<?php 
            checked( 'conservative', $opt_strategy );
            echo esc_attr( $disabled );
            ?>>
							<?php 
            $msg = '<a  href="' . esc_url( $this->freemius->get_trial_url() ) . '">' . esc_html__( 'Activate the FREE trial', 'fullworks-anti-spam' ) . '</a> ' . esc_html__( 'to use AI server', 'fullworks-anti-spam' );
            echo wp_kses_post( $msg );
            ?>
                            <br><label for="fullworks-anti-spam[strategy]"><input type="radio"
                                                                                  name="fullworks-anti-spam[strategy]"
                                                                                  id="fullworks-anti-spam[strategy]"
                                                                                  value="aggressive"
									<?php 
            checked( 'aggressive', $opt_strategy );
            echo esc_attr( $disabled );
            ?>>
								<?php 
            $msg = '<a  href="' . esc_url( $this->freemius->get_trial_url() ) . '">' . esc_html__( 'Activate the FREE trial', 'fullworks-anti-spam' ) . '</a> ' . esc_html__( 'to select machine strategies', 'fullworks-anti-spam' );
            echo wp_kses_post( $msg );
            ?>
                            </label>
                    </td>
					<?php 
            $this->display_tip( 'Strategy' );
            ?>
                </tr>
				<?php 
        }
        $this->display_th( 'Single Words' );
        ?>
            <td>
                <label for="fullworks-anti-spam[single_word_spam]"><input type="checkbox"
                                                                          name="fullworks-anti-spam[single_word_spam]"
                                                                          id="fullworks-anti-spam[single_word_spam]"
                                                                          value="1"
						<?php 
        checked( '1', $opt_single_word_spam );
        echo esc_attr( $disabled );
        ?>>
					<?php 
        $msg = '<a  href="' . esc_url( $this->freemius->get_trial_url() ) . '">' . esc_html__( 'Activate the FREE trial', 'fullworks-anti-spam' ) . '</a> ' . esc_html__( 'to decide single words are spam answers', 'fullworks-anti-spam' );
        echo wp_kses_post( $msg );
        ?>
                </label>
            </td>
			<?php 
        $this->display_tip( 'Single Words' );
        ?>
            </tr>
            </tbody>
        </table>
		<?php 
    }

    public function meta_box_send_spam() {
        $sendspam_value = ( isset( $this->options['sendspam'] ) ? $this->options['sendspam'] : 0 );
        ?>
        <table class="form-table">
            <tbody>
            <tr>
				<?php 
        $this->display_th( 'Send Spam' );
        ?>
                <td>
                    <label for="fullworks-anti-spam[sendspam]"><input type="checkbox"
                                                                      name="fullworks-anti-spam[sendspam]"
                                                                      id="fullworks-anti-spam[sendspam]"
                                                                      value="1"
							<?php 
        checked( '1', $sendspam_value );
        ?>><?php 
        esc_html_e( 'Allow transmission of visitor messages to Fullworks for spam classification by AI and Machine Learning Analysis', 'fullworks-anti-spam' );
        ?>
                    </label>
                </td>
				<?php 
        $this->display_tip( 'Send Spam' );
        ?>
            </tr>
            </tbody>
        </table>
		<?php 
    }

    public function meta_box_spam_admin() {
        ?>
        <table class="form-table">
        <tbody>
		<?php 
        do_action( 'fwas_seetings_admin_row' );
        ?>
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
        echo (int) $this->options['days'];
        ?>"
                                                              min="0">
					<?php 
        esc_html_e( 'Days', 'fullworks-anti-spam' );
        ?></label>
            </td>
			<?php 
        $this->display_tip( 'Keep Spam' );
        ?>
        </tr>
		<?php 
        ?>
        <tr>
			<?php 
        $this->display_th( 'Dashboard Widget' );
        ?>
                <td>
                    <label for="fullworks-anti-spam[show_dashboard_widget]"><input type="checkbox"
                                                                                    name="fullworks-anti-spam[show_dashboard_widget]"
                                                                                    id="fullworks-anti-spam[show_dashboard_widget]"
                                                                                    value="1"
						<?php 
        checked( '1', $this->options['show_dashboard_widget'] );
        ?>>
						<?php 
        esc_html_e( 'Show spam stats in dashboard', 'fullworks-anti-spam' );
        ?>
                    </label>
                </td>
				<?php 
        $this->display_tip( 'Dashboard Widget' );
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
                        <a href="<?php 
            echo esc_url( $this->freemius->get_trial_url() );
            ?>">
							<?php 
            esc_html_e( 'Activate the FREE trial', 'fullworks-anti-spam' );
            ?>
                        </a>
						<?php 
            esc_html_e( 'for spam statistics auto reporting by email', 'fullworks-anti-spam' );
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

}
