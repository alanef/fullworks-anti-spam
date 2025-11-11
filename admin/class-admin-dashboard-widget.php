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

use Fullworks_Anti_Spam\Anti_Spam_Api;
use Fullworks_Anti_Spam\Core\Forms_Registrations;
use Fullworks_Anti_Spam\Core\Utilities;
/**
 * Class Admin_Dashboard_Widget
 * @package Fullworks_Anti_Spam\Admin
 */
class Admin_Dashboard_Widget {
    /** @var \Freemius $freemius Object for freemius. */
    protected $freemius;

    /** @var Utilities $utilities */
    protected $utilities;

    /** @var Anti_Spam_Api $api */
    protected $api;

    /** @var array $options */
    protected $options;

    /**
     * Admin_Dashboard_Widget constructor.
     *
     * @param \Freemius $freemius
     * @param Utilities $utilities
     * @param Anti_Spam_Api $api
     */
    public function __construct( $freemius, $utilities, $api ) {
        $this->freemius = $freemius;
        $this->utilities = $utilities;
        $this->api = $api;
        $this->options = get_option( 'fullworks-anti-spam' );
    }

    /**
     * Register the dashboard widget
     */
    public function register_widget() {
        // Check if widget should be displayed
        if ( empty( $this->options['show_dashboard_widget'] ) ) {
            return;
        }
        // Check user capability
        if ( !current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_add_dashboard_widget( 'fullworks_anti_spam_dashboard_widget', esc_html__( 'Anti-Spam Protection Status', 'fullworks-anti-spam' ), array($this, 'render_widget') );
    }

    /**
     * Render the dashboard widget
     */
    public function render_widget() {
        ?>
		<div class="fwas-dashboard-widget">
			<?php 
        $this->render_protection_coverage();
        ?>
			<?php 
        $this->render_spam_statistics();
        ?>
			<?php 
        $this->render_quick_actions();
        ?>
		</div>
		<?php 
    }

    /**
     * Render protection coverage indicator
     */
    private function render_protection_coverage() {
        $installed_forms = $this->get_installed_forms();
        $protected_forms = $this->get_protected_forms( $installed_forms );
        // Always count comments as protected (free version protects from bots)
        $total_installed = count( $installed_forms ) + 1;
        // +1 for comments
        $total_protected = count( $protected_forms ) + 1;
        // +1 for comments (always protected)
        // Calculate coverage percentage
        $coverage_percent = ( $total_installed > 0 ? $total_protected / $total_installed * 100 : 100 );
        // Determine status color
        if ( $coverage_percent === 100 ) {
            $status_class = 'fwas-status-good';
            $status_icon = '✓';
        } elseif ( $coverage_percent >= 50 ) {
            $status_class = 'fwas-status-warning';
            $status_icon = '⚠';
        } else {
            $status_class = 'fwas-status-error';
            $status_icon = '✗';
        }
        ?>
		<div class="fwas-coverage-section">
			<h3 class="fwas-section-title">
				<span class="fwas-status-icon <?php 
        echo esc_attr( $status_class );
        ?>">
					<?php 
        echo esc_html( $status_icon );
        ?>
				</span>
				<?php 
        esc_html_e( 'Protection Coverage', 'fullworks-anti-spam' );
        ?>
			</h3>

			<div class="fwas-coverage-summary">
				<strong>
					<?php 
        /* translators: %1$d: number of protected items, %2$d: total number of items */
        printf( esc_html__( '%1$d of %2$d systems protected', 'fullworks-anti-spam' ), (int) $total_protected, (int) $total_installed );
        ?>
				</strong>
			</div>

			<ul class="fwas-forms-list">
				<!-- Comments always protected -->
				<li>
					<span class="fwas-protected">✓ <?php 
        esc_html_e( 'Comments', 'fullworks-anti-spam' );
        ?></span>
				</li>

				<?php 
        foreach ( $installed_forms as $form_name => $form_data ) {
            $protection_level = ( isset( $form_data['protection_level'] ) ? $form_data['protection_level'] : 0 );
            $has_free_bot_protection = $protection_level === 1;
            $is_premium = $this->freemius->can_use_premium_code__premium_only();
            ?>
					<li>
						<?php 
            if ( in_array( $form_name, $protected_forms, true ) ) {
                ?>
							<?php 
                if ( !$is_premium && $has_free_bot_protection ) {
                    ?>
								<span class="fwas-protected">
									✓ <?php 
                    echo esc_html( $form_data['name'] );
                    ?>
									- <em><?php 
                    esc_html_e( 'bot protection', 'fullworks-anti-spam' );
                    ?></em>
								</span>
								<a href="<?php 
                    echo esc_url( $this->freemius->get_upgrade_url() );
                    ?>" style="font-size:11px;">
									(<?php 
                    esc_html_e( 'upgrade for full protection', 'fullworks-anti-spam' );
                    ?>)
								</a>
							<?php 
                } else {
                    ?>
								<span class="fwas-protected">✓ <?php 
                    echo esc_html( $form_data['name'] );
                    ?></span>
							<?php 
                }
                ?>
						<?php 
            } else {
                ?>
							<span class="fwas-unprotected">
								<?php 
                echo esc_html( $form_data['name'] );
                ?>
								- <em><?php 
                esc_html_e( 'unprotected', 'fullworks-anti-spam' );
                ?></em>
							</span>
						<?php 
            }
            ?>
					</li>
				<?php 
        }
        ?>
			</ul>
		</div>
		<?php 
    }

    /**
     * Render spam statistics
     */
    private function render_spam_statistics() {
        ?>
		<div class="fwas-stats-section">
			<h3 class="fwas-section-title"><?php 
        esc_html_e( 'Spam Statistics', 'fullworks-anti-spam' );
        ?></h3>

			<?php 
        // Check if user can use premium code (paying or in trial)
        if ( $this->freemius->can_use_premium_code() ) {
            $this->render_premium_stats();
        } else {
            $this->render_free_stats();
        }
        ?>
		</div>
		<?php 
    }

    /**
     * Render premium statistics (detailed breakdown)
     */
    private function render_premium_stats() {
    }

    /**
     * Render free version statistics (basic summary)
     */
    private function render_free_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fwantispam_log';
        // Get comment spam count only (BOT protection)
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE eventcode_str LIKE 'comments,BOT'" );
        // Ensure count is at least 0
        $count = ( $count ? (int) $count : 0 );
        ?>
		<div class="fwas-free-stats">
			<p class="fwas-stats-summary">
				<strong class="fwas-stats-number"><?php 
        echo (int) $count;
        ?></strong>
				<span class="fwas-stats-label"><?php 
        esc_html_e( 'spam comments blocked (last 30 days)', 'fullworks-anti-spam' );
        ?></span>
			</p>

			<?php 
        if ( $this->has_unprotected_forms() ) {
            ?>
				<p class="fwas-upgrade-prompt">
					<a href="<?php 
            echo esc_url( $this->freemius->get_trial_url() );
            ?>" class="button button-primary">
						<?php 
            esc_html_e( 'Start FREE PRO Trial', 'fullworks-anti-spam' );
            ?>
					</a>
					<br>
					<small><?php 
            esc_html_e( 'Protect forms and see detailed statistics', 'fullworks-anti-spam' );
            ?></small>
				</p>
			<?php 
        }
        ?>
		</div>
		<?php 
    }

    /**
     * Render quick action links
     */
    private function render_quick_actions() {
        ?>
		<div class="fwas-actions-section">
			<p class="fwas-actions-links">
				<a href="<?php 
        echo esc_url( admin_url( 'options-general.php?page=fullworks-anti-spam-settings' ) );
        ?>">
					<?php 
        esc_html_e( 'Configure Protection', 'fullworks-anti-spam' );
        ?>
				</a>
				<?php 
        if ( $this->has_spam_to_review() ) {
            ?>
					| <a href="<?php 
            echo esc_url( admin_url( 'edit-comments.php?comment_status=spam' ) );
            ?>">
						<?php 
            esc_html_e( 'Review Spam', 'fullworks-anti-spam' );
            ?>
					</a>
				<?php 
        }
        ?>
			</p>
		</div>
		<?php 
    }

    /**
     * Get list of installed form systems
     * Now uses the dynamic Forms_Registrations system
     *
     * @return array
     */
    private function get_installed_forms() {
        // Get all registered forms (which are only registered if installed)
        $registered_forms = Forms_Registrations::get_registered_forms();
        // Skip comments as we handle it separately in the widget
        unset($registered_forms['comments']);
        return $registered_forms;
    }

    /**
     * Get list of protected form systems
     *
     * @param array $installed_forms
     * @return array
     */
    private function get_protected_forms( $installed_forms ) {
        $protected = array();
        // Get forms with free bot protection dynamically (protection_level = 1)
        $forms_with_free_bot_protection = Forms_Registrations::get_form_keys_by_protection_level( 1 );
        foreach ( $installed_forms as $form_key => $form_data ) {
            // Free users: Forms with protection_level = 1 have bot protection
            if ( in_array( $form_key, $forms_with_free_bot_protection, true ) ) {
                $protected[] = $form_key;
                continue;
            }
        }
        return $protected;
    }

    /**
     * Check if there are unprotected forms
     *
     * @return bool
     */
    private function has_unprotected_forms() {
        $installed_forms = $this->get_installed_forms();
        $protected_forms = $this->get_protected_forms( $installed_forms );
        return count( $installed_forms ) > count( $protected_forms );
    }

    /**
     * Check if there is spam to review
     *
     * @return bool
     */
    private function has_spam_to_review() {
        $spam_count = wp_count_comments();
        return !empty( $spam_count->spam );
    }

}
