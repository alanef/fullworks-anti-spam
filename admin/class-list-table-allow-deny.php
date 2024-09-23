<?php

/**
 * Class List_Table_Allow_Deny
 *
 * @package Fullworks_Anti_Spam\Admin
 */

namespace Fullworks_Anti_Spam\Admin;

use WP_List_Table;

class List_Table_Allow_Deny extends WP_List_Table {

	const TABLE = 'fwantispam_allow_deny';

	public function __construct() {

		parent::__construct(
			array(
				'singular' => 'allow_deny', //singular name of the listed records
				'plural'   => 'allow_denys', //plural name of the listed records
				'ajax'     => false, //should this table support ajax?
			)
		);
	}


	public function no_items() {
		esc_html_e( 'No current items', 'fullworks-anti-spam' );
	}

	protected static function view_record_count( $type ): ?string {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TABLE;
		if ( 'all' === $type ) {
			$sql = "SELECT COUNT(*) FROM {$table_name}";
		} else {
			$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} WHERE allow_deny = %s", $type );
		}

		return $wpdb->get_var( $sql );
	}

	protected function extra_tablenav( $which ) {
		if ( 'top' == $which || 'bottom' == $which ) {
			?>
            <div class="alignleft actions bulkactions">
				<?php $all = self::view_record_count( 'all' );
				if ( $all > 0 ) { ?>
                    <a href="<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_nonce_url is safe
					echo wp_nonce_url( admin_url( 'admin-post.php?action=fwas_ad_csv_export' ), 'fwas_ad_csv_export_nonce', '_fwas_ad_wpnonce_csv_export' ); ?>"
                       class="button button"><?php esc_html_e( 'Export to CSV', 'fullworks-anti-spam' ) ?></a>
				<?php } ?>
                <a href="#TB_inline?width=99&height=99&inlineId=fwas_ad_import_csv_dialog"
                   class="thickbox button"><?php esc_html_e( 'Import from CSV', 'fullworks-anti-spam' ) ?></a>
            </div>
			<?php
		}
	}

	protected function get_views(): array {
		$all   = self::view_record_count( 'all' );
		$allow = self::view_record_count( 'allow' );
		$deny  = self::view_record_count( 'deny' );

		$all_class   = 'current';
		$allow_class = '';
		$deny_class  = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce not needed here
		$view = sanitize_text_field( wp_unslash( $_GET['view'] ?? '' ) );
		if ( ! empty( $view ) ) {
			// set the class based on the type
			switch ( $view ) {
				case 'allow':
					$allow_class = 'current';
					$all_class   = '';
					$deny_class  = '';
					break;
				case 'deny':
					$deny_class  = 'current';
					$all_class   = '';
					$allow_class = '';
					break;
			}
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce not needed here
		$page = esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['page'] ?? '' ) ) );

		return array(
			'all'   => sprintf( '<a href="?page=%1$s" class="%2$s">%3$s</a><span class="count">(%4$d)</span>', esc_attr( $page ), $all_class, __( 'All', 'fullworks-anti-spam' ), $all ),
			'allow' => sprintf( '<a href="?page=%1$s&view=allow" class="%2$s">%3$s</a><span class="count">(%4$d)</span>', esc_attr( $page ), $allow_class, __( 'Allowed', 'fullworks-anti-spam' ), $allow ),
			'deny'  => sprintf( '<a href="?page=%1$s&view=deny" class="%2$s">%3$s</a><span class="count">(%4$d)</span>', esc_attr( $page ), $deny_class, __( 'Denied', 'fullworks-anti-spam' ), $deny ),
		);
	}

	public function get_columns() {
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'allow_deny' => esc_html__( 'Allow / Deny', 'fullworks-anti-spam' ),
			'type'       => esc_html__( 'Type', 'fullworks-anti-spam' ),
			'value'      => esc_html__( 'Value', 'fullworks-anti-spam' ),
			'notes'      => esc_html__( 'Notes', 'fullworks-anti-spam' ),
		);

		return $columns;
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'allow_deny' => array( 'allow_deny', true ),
			'type'       => array( 'type', true ),
			'value'      => array( 'value', true ),

		);

		return $sortable_columns;
	}

	public function column_type( $item ) {
		if ( 'IP' === $item['type'] ) {
			return '<div class="IP select-item" data-select="IP">' . esc_html__( 'IP', 'fullworks-anti-spam' ) . '</div>';
		} elseif ( 'email' === $item['type'] ) {
			return '<div class="email select-item" data-select="email">' . esc_html__( 'Email', 'fullworks-anti-spam' ) . '</div>';
		} elseif ( 'string' === $item['type'] ) {
			return '<div class="string select-item" data-select="string">' . esc_html__( 'Expression', 'fullworks-anti-spam' ) . '</div>';
		}

		return '???' . $item['type'];
	}

	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => esc_html__( 'Delete', 'fullworks-anti-spam' ),
		);

		return $actions;
	}

	public function column_allow_deny( $item ) {

		// create a nonce
		$delete_nonce = wp_create_nonce( 'fwas_delete_rest_rules' );

		if ( 'allow' === $item['allow_deny'] ) {
			$title = '<div class="allow select-item" data-select="allow">' . esc_html__( 'Allow', 'fullworks-anti-spam' ) . '</div>';
		} elseif ( 'deny' === $item['allow_deny'] ) {
			$title = '<div class="deny select-item" data-select="deny">' . esc_html__( 'Deny', 'fullworks-anti-spam' ) . '</div>';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce not needed here
		$page = esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['page'] ?? '' ) ) );

		$actions = array(
			'edit'   => '<a href="#">' . __( 'Edit', 'fullworks-anti-spam' ) . '</a>',
			'delete' => sprintf( '<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">' . esc_html__( 'Delete', 'fullworks-anti-spam' ) . '</a>', esc_attr( $page ), 'delete', absint( $item['ID'] ), $delete_nonce ),
		);

		return $title . $this->row_actions( $actions );
	}

	public function prepare_items() {
		global $wpdb;

		/** Process bulk action */
		$this->process_bulk_action();

		$table_name   = $wpdb->prefix . self::TABLE;
		$per_page     = $this->get_items_per_page( 'rules_per_page', 25 );
		$current_page = $this->get_pagenum();

		$sortable_fields    = array(
			'allow_deny',
			'type',
			'value',
		);
		$sortable_direction = array(
			'ASC',
			'DESC',
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce not needed here
		$order = strtoupper( ( isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'DESC' ) );
		if ( ! in_array( $order, $sortable_direction ) ) {
			$order = 'DESC';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce not needed here
		$orderby = ( isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'ID' );
		if ( ! in_array( $orderby, $sortable_fields ) ) {
			$orderby = 'ID';
		}
		$order_clause = $orderby . ' ' . $order;

		$search_token = sanitize_text_field( wp_unslash( $_REQUEST['s'] ?? '' ) );
		$view         = sanitize_text_field( wp_unslash( $_REQUEST['view'] ?? '' ) );

		$search_clause = '';
		if ( ! empty( $search_token ) ) {
			check_admin_referer( 'bulk-allow_denys' );
			$search_token  = '%' . $wpdb->esc_like( $search_token ) . '%';
			$search_clause = $wpdb->prepare( ' AND value LIKE %s', $search_token );
		}

		$where_clause = '';

		if ( ! empty( $view ) ) {
			$where_clause = $wpdb->prepare( ' WHERE allow_deny = %s', $view );
			if ( ! empty( $search_clause ) ) {
				$where_clause .= $search_clause;
			}
		} elseif ( ! empty( $search_clause ) ) {
			$where_clause = str_replace( ' AND', ' WHERE', $search_clause );
		}

		$this->items = $wpdb->get_results(
			$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- using $table_name as a variable is acceptable here for pre 6.1 compatability, the $where_clause is prepared and $order_clause is safe as it is validated against arrays first then sanitised with sanitize_sql_orderby https://developer.wordpress.org/reference/functions/sanitize_sql_orderby/
				"SELECT * FROM $table_name " . $where_clause . " ORDER BY " . sanitize_sql_orderby( $order_clause ) . " LIMIT %d OFFSET %d",
				(int) $per_page,
				( (int) $current_page - 1 ) * (int) $per_page
			),
			ARRAY_A
		);

		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name   $where_clause" );
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
			case 'type':
			case 'value':
				return $item['value'];
			case 'allow_deny':
			case 'notes':
				return $item['notes'] ?? '';
			default:
				//	return print_r( $item, true );
		}
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['ID']
		);
	}

	public function process_bulk_action() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'fwantispam_allow_deny';

		if ( 'delete' === $this->current_action() ) {
			check_admin_referer( 'fwas_delete_rest_rules' );
			if ( ! isset( $_GET['id'] ) ) {
				die();
			}
			$wpdb->delete( $table_name, array( 'id' => absint( $_GET['id'] ) ) );
		}
		if ( ( isset( $_POST['action'] ) && isset( $_POST['bulk-delete'] ) && 'bulk-delete' === $_POST['action'] ) || ( isset( $_POST['action2'] ) && 'bulk-delete' === $_POST['action2'] ) ) {
			check_admin_referer( 'bulk-allow_denys' );
			// sanitize each element of the array $_POST['bulk-delete'] as integer
			$delete_ids = array_map( 'absint', $_POST['bulk-delete'] );
			// delete
			foreach ( $delete_ids as $id ) {
				$wpdb->delete( $table_name, array( 'id' => $id ) );
			}
		}
	}
}
