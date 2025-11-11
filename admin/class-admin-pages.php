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

namespace Fullworks_Anti_Spam\Admin;

use Fullworks_Anti_Spam\Core\Forms_Registrations;
use Fullworks_Anti_Spam\Core\Utilities;


/**
 * Class Settings
 * @package Fullworks_Anti_Spam\Admin
 */
class Admin_Pages {

	protected $settings_page;  // toplevel appearance etc  followed by slug

	// for the block report
	protected $settings_page_id = 'toplevel_page_fullworks-anti-spam-settings';

	protected $settings_title;

	protected $plugin_name;
	protected $version;
	protected $freemius;

	public function __construct() {
	}

	public static function set_screen( $status, $option, $value ) {
		return $value;
	}

	public function settings_setup() {
		// @TODO called multiple times - thing maybe singleton

		$title = esc_html__( 'Anti Spam', 'fullworks-anti-spam' );

		/* Add settings menu page */
		add_submenu_page(
			'options-general.php',
			$title,
			$title,
			'manage_options',
			'fullworks-anti-spam-settings',
			array( $this, 'settings_page' ),
			10
		);

		$this->register_settings();

	}

	public function register_settings() {
		// overide in extended class
	}

	public function hooks() {
		/* Vars */
		$page_hook_id = $this->settings_page_id;

		/* Do stuff in settings page, such as adding scripts, etc. */
		if ( ! empty( $this->settings_page ) ) {
			/* Load the JavaScript needed for the settings screen. */
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			/* Set number of column available. */
			add_filter( 'screen_layout_columns', array( $this, 'screen_layout_column' ), 10, 2 );
			add_action( $this->settings_page_id . '_settings_page_boxes', array( $this, 'add_required_meta_boxes' ) );
		}
	}

	public function enqueue_scripts( $hook_suffix ) {
		$page_hook_id = $this->settings_page_id;
		if ( $hook_suffix === $page_hook_id ) {
			wp_enqueue_script( 'common' );
			wp_enqueue_script( 'wp-lists' );
			wp_enqueue_script( 'postbox' );
			$confirm_message = esc_html__( 'Are you sure want to do this?', 'fullworks-anti-spam' );
			$inline_script   = "jQuery(document).ready(function ($) {
    $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
    postboxes.add_postbox_toggles('" . esc_html( $page_hook_id ) . "');
    $('#fx-smb-form').submit(function() {
        $('#publishing-action .spinner').css('visibility', 'visible');
    });
    $('#delete-action *').on('click', function() {
        return confirm('" . esc_html( $confirm_message ) . "');
    });
});";
			wp_add_inline_script( 'common', $inline_script );
		}
	}

	public function screen_layout_column( $columns, $screen ) {
		$page_hook_id = $this->settings_page_id;
		if ( $screen === $page_hook_id ) {
			$columns[ $page_hook_id ] = 2;
		}

		return $columns;
	}

	public function settings_page() {

		/* global vars */
		global $hook_suffix;
		if ( $this->settings_page_id === $hook_suffix ) {

			/* enable add_meta_boxes function in this page. */
			do_action( $this->settings_page_id . '_settings_page_boxes', $hook_suffix );
			?>

            <div class="wrap fs-page">

                <h2 class="brand"><?php echo wp_kses_post( $this->settings_title ); ?></h2>

                <div class="fs-settings-meta-box-wrap">
					<?php $this->display_tabs(); ?>

                    <form id="fs-smb-form" method="post" action="options.php">

						<?php settings_fields( $this->option_group ); // options group
						?>
						<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
						<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>


                        <div id="poststuff">

                            <div id="post-body"
                                 class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

                                <div id="postbox-container-1" class="postbox-container">

									<?php do_meta_boxes( $hook_suffix, 'side', null ); ?>
                                    <!-- #side-sortables -->

                                </div><!-- #postbox-container-1 -->

                                <div id="postbox-container-2" class="postbox-container">

									<?php $this->do_promo_box(); ?>


									<?php do_meta_boxes( $hook_suffix, 'normal', null ); ?>
                                    <!-- #normal-sortables -->

									<?php do_meta_boxes( $hook_suffix, 'advanced', null ); ?>
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

	public function display_tabs() {
		Utilities::get_instance()->display_tabs();
	}


	protected function do_promo_box() {
		if ( ! $this->freemius->can_use_premium_code() ) {
			?>
            <div class="postbox"><a href="<?php echo esc_url( $this->freemius->get_upgrade_url() ); ?>"><img
                            class="admin-image" src="<?php
					echo esc_url( dirname( plugin_dir_url( __FILE__ ) ) . '/admin/images/upsell_banner.svg' )
					?>" alt="<?php
					esc_html_e( 'Upgrade', 'fullworks-anti-spam' );
					?>"/></a>
                <div class="inside">
                    <table class="form-table">
                        <tbody>

						<?php
						// Comments - always show if enabled
						if ( Utilities::get_instance()->is_comments_open() ) {
							?>
                            <tr>
                            <th><?php esc_html_e( 'WP Comments', 'fullworks-anti-spam' ); ?></th>
                            <td>✓ <?php esc_html_e( 'Bot protection active', 'fullworks-anti-spam' ); ?> - <a href="<?php echo esc_url( $this->freemius->get_trial_url() ); ?>"><?php esc_html_e( 'Upgrade for full protection', 'fullworks-anti-spam' ); ?></a></td>
                            </tr><?php
						}

						// Get all registered forms dynamically
						$registered_forms = Forms_Registrations::get_registered_forms();
						$free_bot_protection_keys = Forms_Registrations::get_form_keys_by_protection_level( 1 );

						// Display registered (installed) forms
						foreach ( $registered_forms as $form_key => $form_data ) {
							// Skip comments - we handle them separately above
							if ( $form_key === 'comments' ) {
								continue;
							}
							$protection_level = isset( $form_data['protection_level'] ) ? $form_data['protection_level'] : 0;
							$has_free_bot_protection = ( $protection_level === 1 );
							?>
                            <tr>
                            <th><?php echo esc_html( $form_data['name'] ); ?></th>
                            <td>
								<?php if ( $has_free_bot_protection ) : ?>
									✓ <?php esc_html_e( 'Bot protection active', 'fullworks-anti-spam' ); ?> - <a href="<?php echo esc_url( $this->freemius->get_trial_url() ); ?>"><?php esc_html_e( 'Upgrade for full protection', 'fullworks-anti-spam' ); ?></a>
								<?php else : ?>
									<a href="<?php echo esc_url( $this->freemius->get_trial_url() ); ?>"><?php esc_html_e( 'Upgrade to PRO', 'fullworks-anti-spam' ); ?></a> <?php esc_html_e( 'for spam protection', 'fullworks-anti-spam' ); ?>
								<?php endif; ?>
                            </td>
                            </tr><?php
						}

						?>
                        <tr>
                        <th></th>
                        <td>
                            <div style="float:right"><a style="font-weight:bold; font-size: 130%"
                                                        class="button-primary orange"
                                                        href="<?php echo esc_url( $this->freemius->get_trial_url() ); ?>"><?php esc_html_e( 'Start my FREE trial of PRO', 'fullworks-anti-spam' ); ?></a>
                            </div>
                        </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
			<?php
		}


	}

	public function add_required_meta_boxes() {
		global $hook_suffix;

		if ( $this->settings_page_id === $hook_suffix ) {

			$this->add_meta_boxes();

			add_meta_box(
				'submitdiv',               /* Meta Box ID */
				__( 'Save Options', 'fullworks-anti-spam' ),            /* Title */
				array( $this, 'submit_meta_box' ),  /* Function Callback */
				$this->settings_page_id,                /* Screen: Our Settings Page */
				'side',                    /* Context */
				'high'                     /* Priority */
			);
		}
	}

	public function add_meta_boxes() {
		// in extended class
	}

	public function submit_meta_box() {

		?>
        <div id="submitpost" class="submitbox">

            <div id="major-publishing-actions">
				<?php wp_nonce_field( 'fwas_submit_meta_box', '_fwas_submit_meta_box_nonce' ); ?>

                <div id="delete-action">
                    <input type="submit" name="<?php echo esc_attr( "{$this->option_group}-reset" ); ?>"
                           id="<?php echo esc_attr( "{$this->option_group}-reset" ); ?>"
                           class="button"
                           value="<?php esc_html_e( 'Reset Settings', 'fullworks-anti-spam' ); ?>">
                </div><!-- #delete-action -->

                <div id="publishing-action">
                    <span class="spinner"></span>
					<?php submit_button( esc_html__( 'Save', 'fullworks-anti-spam' ), 'primary', 'submit', false ); ?>
                </div>

                <div class="clear"></div>

            </div><!-- #major-publishing-actions -->

        </div><!-- #submitpost -->

		<?php
	}

	public function reset_sanitize( $settings ) {
		// for extended class to manage
		return $settings;
	}

	public function delete_options() {
		// for extended class to manage
	}
}
