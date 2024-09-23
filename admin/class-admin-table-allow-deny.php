<?php
/**
 * Class Admin_Table_Allow_Deny
 *
 * Class used to create and display the Allow / Deny List table in the admin area.
 */

namespace Fullworks_Anti_Spam\Admin;

use Fullworks_Anti_Spam\Admin\Admin_Tables;
use Fullworks_Anti_Spam\Core\Utilities;

class Admin_Table_Allow_Deny extends Admin_Tables {

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
			esc_html__( 'Allow / Deny List', 'Allow / Deny List' ),
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

}