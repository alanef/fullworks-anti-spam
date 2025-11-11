<?php
/**
 * Class Admin_Table_Allow_Deny
 *
 * Class used to create and display the Allow / Deny List table in the admin area.
 */

namespace Fullworks_Anti_Spam\Admin;

use Fullworks_Anti_Spam\Admin\Admin_Tables;
use Fullworks_Anti_Spam\Core\Forms_Registrations;
use Fullworks_Anti_Spam\Core\Utilities;

class Admin_Table_Allow_Deny extends Admin_Tables {

	protected $show_upgrade_notice = false;

	public function __construct( $plugin_name, $version, $freemius ) {
		// add admin ajax handler
		add_action( 'wp_ajax_fwantispam_ajax_handler', array( $this, 'ajax_handler' ) );
		parent::__construct( $plugin_name, $version, $freemius );
	}


	/**
	 * @return void
	 */
	public function add_table_page() {
		Utilities::get_instance()->register_settings_page_tab(
			esc_html__( 'Allow / Deny List', 'fullworks-anti-spam' ),
			'settings',
			admin_url( 'admin.php?page=fullworks-anti-spam-settings-allow-deny-settings' ),
			3
		);
		$this->hook = add_submenu_page(
			'fullworks-anti-spam-settings',
			esc_html__( 'Allow / Deny List', 'fullworks-anti-spam' ),
			esc_html__( 'Allow / Deny List', 'fullworks-anti-spam' ),
			'manage_options',
			'fullworks-anti-spam-settings-allow-deny-settings',
			array( $this, 'list_page' )
		);

		add_action( "load-{$this->hook}", array( $this, 'screen_option' ) );
	}

	public function screen_option() {

		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Rules', 'fullworks-anti-spam' ),
			'default' => 25,
			'option'  => 'rules_per_page',
		);

		add_screen_option( $option, $args );

		$this->table_obj = new List_Table_Allow_Deny();
	}

	public function list_page() {
		// Check if we should render the upgrade notice
		if ( ! $this->freemius->can_use_premium_code() ) {
			$user_id = get_current_user_id();
			$dismissed = get_user_meta( $user_id, 'fwas_upgrade_notice_dismissed', true );
			if ( ! $dismissed ) {
				$this->show_upgrade_notice = true;
			}
		}

		$add_action         = '<a  class="alignright button-primary" href="#" id="fwas-add-rule">' . esc_html__( 'Add New', 'fullworks-anti-spam' ) . '</a>';
		$this->page_heading = '<img src="' . dirname( plugin_dir_url( __FILE__ ) ) . '/admin/images/brand/light-anti-spam-75h.svg" class="logo" alt="Fullworks Logo"/><div class="text">' . __( 'Allow / Deny', 'fullworks-anti-spam' ) . $add_action . '</div>';
		?>
        <div id="fwas_ad_import_csv_dialog" style="display:none;">
            <h2><?php esc_html_e('Upload CSV file','fullworks-anti-spam');?></h2>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="fwas_ad_csv_import">
                <input type="file" name="csv_file" accept=".csv, .txt">
                <input type="submit" value="Import">
				<?php wp_nonce_field('fwas_ad_csv_import_nonce', '_fwas_ad_wpnonce_csv_import'); ?>
            </form>
        </div>
        <div class="wrap fs-page  fwas-allow-deny">
            <h2 class="brand"><?php echo wp_kses_post( $this->page_heading ); ?></h2>
			<?php $this->display_tabs(); ?>
			<?php $this->render_upgrade_notice(); ?>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post">
								<?php
								$this->table_obj->prepare_items();
								$this->table_obj->views();
								$this->table_obj->search_box( 'search', 'search_id' );
								$this->table_obj->display();
								?>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>
		<?php
	}

	public function ajax_handler() {
		// check nonce
		check_ajax_referer( 'fwantispam_ajax_nonce', 'nonce' );
		// check user is admin
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You are not allowed to do that' );
		}
		// check action
		if ( ! isset( $_POST['action'] ) ) {
			wp_die( 'No action specified' );
		}
		// sanitize the input  type, value, allow_deny  ( type is email  or string ( regex ) or IP ( inc subnet) )
		// check type is valid
		// check value is valid
		// check allow_deny is valid
		// insert into table or update if not empty ID
		// return success or error and refresh page
		//
		$type       = sanitize_text_field( wp_unslash( isset( $_POST['type'] ) ? $_POST['type'] : '' ) );
		$value      = trim( sanitize_text_field( wp_unslash( isset( $_POST['value'] ) ? $_POST['value'] : '' ) ) );
		$allow_deny = sanitize_text_field( wp_unslash( isset( $_POST['allow_deny'] ) ? $_POST['allow_deny'] : '' ) );
		$notes      = trim( sanitize_text_field( wp_unslash( isset( $_POST['notes'] ) ? $_POST['notes'] : '' ) ) );
		// check type is valid
		if ( ! in_array( $type, array( 'email', 'string', 'IP' ) ) ) {
			wp_send_json_error( 'Invalid type' );
		}
		// check value is valid
		if ( 'email' == $type ) {
			if ( ! is_email( $value ) ) {
				if ( ! is_email( $value ) ) {
					// check if it is a regular expression containing an email
					if ( false === @preg_match_all( $value, '' ) ) {
						wp_send_json_error( esc_html__( 'Invalid email or email pattern', 'fullworks-anti-spam' ) );
					}
				}
			}
		} elseif ( 'string' == $type ) {
			if ( false === @preg_match( $value, '' ) ) {
				// Not a proper regex pattern, consider as a string and enclose in slashes
				$value = '/' . $value . '/im';
			}
			if ( false === @preg_match( $value, '' ) ) {
				wp_send_json_error( esc_html__( 'Invalid string or regular expression', 'fullworks-anti-spam' ) );
			}
		} elseif ( 'IP' == $type ) {
			// check IP is valid
			if ( false === Utilities::get_instance()->validate_ip_or_subnet( $value ) ) {
				wp_send_json_error( esc_html__( 'Invalid IP or IP Subnet', 'fullworks-anti-spam' ) );
			}
		}
		// check allow_deny is valid
		if ( ! in_array( $allow_deny, array( 'allow', 'deny' ) ) ) {
			wp_send_json_error( 'Invalid allow_deny' );
		}
		global $wpdb;
		$table_name = $wpdb->prefix . 'fwantispam_allow_deny';
		if ( empty( $_POST['ID'] ) ) {
			// insert into table
			$result = $wpdb->insert(
				$table_name,
				array(
					'allow_deny' => $allow_deny,
					'type'       => $type,
					'value'      => $value,
                    'notes'      => $notes,
				)
			);
			if ( false === $result ) {
				wp_send_json_error( esc_html__( 'Error inserting allow / deny data', 'fullworks-anti-spam' ), 500 );
			}
		} else {
			// update table
			$result = $wpdb->update(
				$table_name,
				array(
					'allow_deny' => $allow_deny,
					'type'       => $type,
					'value'      => $value,
					'notes'      => $notes,
				),
				array(
					'ID' => (int) $_POST['ID'],
				)
			);
			if ( false === $result ) {
				wp_send_json_error( esc_html__( 'Error updating allow / deny data', 'fullworks-anti-spam' ), 500 );
			}
		}
		wp_send_json_success( esc_html__( 'Record Saved', 'fullworks-anti-spam' ) );
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
		$total_installed = count( $installed_forms ); // Includes comments

		// Count protected forms (comments + forms with protection_level = 1)
		$free_protected_count = 1; // Comments always protected
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
			<h3><?php esc_html_e( 'Unlock Full Protection', 'fullworks-anti-spam' ); ?></h3>

			<?php if ( $has_unprotected ) : ?>
				<p class="fwas-protection-summary">
					<?php
					/* translators: %1$d: number of protected forms, %2$d: total forms */
					printf( esc_html__( '%1$d of %2$d systems fully protected', 'fullworks-anti-spam' ), (int) $free_protected_count, (int) $total_installed );
					?>
					<strong><?php esc_html_e( ' — Missing: AI spam detection & IP blocklist', 'fullworks-anti-spam' ); ?></strong>
				</p>
			<?php endif; ?>

			<p class="fwas-upgrade-benefits">
				<?php esc_html_e( 'Stop manual spammers with AI • Block 10M+ spam IPs • Protect all forms • Email reports', 'fullworks-anti-spam' ); ?>
			</p>

			<a href="<?php echo esc_url( $this->freemius->get_trial_url() ); ?>" class="fwas-trial-cta">
				<?php esc_html_e( 'Start 7-Day FREE Trial', 'fullworks-anti-spam' ); ?>
			</a>
			<span class="fwas-trial-details">
				<?php esc_html_e( 'No credit card • Cancel anytime', 'fullworks-anti-spam' ); ?>
			</span>
		</div>
		<?php
	}

}