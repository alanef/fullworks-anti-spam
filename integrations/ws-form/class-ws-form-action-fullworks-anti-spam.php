<?php

namespace Fullworks_Anti_Spam\Integrations\WS_Form;

use \WS_Form_Action;
use \WS_Form_Common;

class WS_Form_Action_Fullworks_Anti_Spam extends WS_Form_Action {

	public $id = 'fullworksantispam';
	public $pro_required = false;
	public $label;
	public $label_action;
	public $events;
	public $multiple = false;
	public $configured = true;
	public $priority = 0;
	public $can_repost = false;
	public $form_add = false;

	public function __construct() {

		// Set label
		$this->label = 'Fullworks Anti Spam';

		// Events
		$this->events = array( 'save', 'submit' );

		// Add to spam tab in form settings sidebar
		add_filter( 'wsf_config_settings_form_admin', array( $this, 'config_settings_form_admin' ), 10, 1 );

		// Register config filters
		add_filter( 'wsf_config_meta_keys', array( $this, 'config_meta_keys' ), 10, 2 );

		// Register as action
		add_filter( 'wsf_actions_post_save', array( $this, 'actions_post_add' ), 10, 3 );
		add_filter( 'wsf_actions_post_submit', array( $this, 'actions_post_add' ), 10, 3 );


		// Register action
		parent::register( $this );
	}

	public function register_ws_form() {

	}

	public function actions_post_add( $actions, $form, $submit ) {
		// Set label
		$this->label = __( 'Fullworks Anti Spam', 'fullworks-anti-spam' );

		// Set label for actions pull down
		$this->label_action = __( 'Spam Check with Fullworks Anti Spam', 'fullworks-anti-spam' );


		if (
			! self::plugin_installed() ||
			! self::form_enabled( $form->id ) ||
			! self::enabled( $form )
		) {

			return $actions;
		}


		// Prepend this action so it runs first
		$actions[] = array(

			'id'        => $this->id,
			'meta'      => array(),
			'events'    => array(
				'0' => 'save',
				'1' => 'submit',
			),
			'label'     => $this->label_action,
			'priority'  => $this->priority,
			'row_index' => 0,
		);

		return $actions;
	}

	public function enabled( $form ) {

		$enabled = WS_Form_Common::get_object_meta_value( $form, 'action_' . $this->id . '_enabled' );

		return ( $enabled === 'on' );
	}

	public function plugin_installed() {
		global $fwas_fs;

		return function_exists( 'Fullworks_Anti_Spam\run_fullworks_anti_spam' ) && $fwas_fs->can_use_premium_code();
	}

	public function form_enabled() {
		// redundant
		return true;
	}


	public function post( $form, &$submit, $config ) {

		// Get configuration
		$field_email       = WS_Form_Common::get_object_meta_value( $form, 'action_' . $this->id . '_field_email' );
		$field_mapping     = WS_Form_Common::get_object_meta_value( $form, 'action_' . $this->id . '_field_mapping' );
		$spam_level_reject = WS_Form_Common::get_object_meta_value( $form, 'action_' . $this->id . '_spam_level_reject', '' );
		$admin_no_run      = WS_Form_Common::get_object_meta_value( $form, 'action_' . $this->id . '_admin_no_run', 'on' );

		// Checks
		if ( ! self::enabled( $form ) ) {
			return true;
		}

		if ( $admin_no_run && WS_Form_Common::can_user( 'manage_options_wsform' ) ) {
			return true;
		}

		// Build email
		if (
			( $field_email != '' ) &&
			isset( $submit->meta ) &&
			isset( $submit->meta[ WS_FORM_FIELD_PREFIX . $field_email ] ) &&
			isset( $submit->meta[ WS_FORM_FIELD_PREFIX . $field_email ]['value'] )
		) {

			$email_address = $submit->meta[ WS_FORM_FIELD_PREFIX . $field_email ]['value'];
			if ( ! filter_var( $email_address, FILTER_VALIDATE_EMAIL ) ) {
				$email_address = '';
			}
		}

		// Build message
		$comment_content_array = array();
		foreach ( $field_mapping as $field_map ) {

			$field_id     = $field_map->ws_form_field;
			$submit_value = parent::get_submit_value( $submit, WS_FORM_FIELD_PREFIX . $field_id, false );
			if ( $submit_value !== false ) {

				$comment_content_array[] = $submit_value;
			}
		}
		if ( count( $comment_content_array ) > 0 ) {

			$message = implode( "\n", $comment_content_array );
		}

		// do spam check here
		$anti_spam  = new \Fullworks_Anti_Spam\Anti_Spam_Api();
		$is_spam    = $anti_spam->is_spam( false, WS_FORM_NAME, $email_address, $message );
		$spam_level = $anti_spam->spam_level * 100;
		if ( false === $is_spam ) {
			parent::success( __( 'Submitted form content to Fullworks Anti Spam (Not spam).', 'fullworks-anti-spam' ) );
		}

		// Set spam level on submit record
		if ( is_null( parent::$spam_level ) || ( parent::$spam_level < $spam_level ) ) {
			parent::$spam_level = $spam_level;
		}

		// Check spam level (Return halt if submission should be rejected)
		$spam_level_reject = absint( $spam_level_reject );
		if ( $spam_level_reject > 0 ) {

			if ( $spam_level >= $spam_level_reject ) {
				parent::error( __( 'Spam detected', 'fullworks-anti-spam' ) );

				return 'halt';
			}
		}

		return $spam_level;
	}

	// Add meta keys to spam tab in form settings
	public function config_settings_form_admin( $config_settings_form_admin ) {

		if ( self::plugin_installed() && self::form_enabled() ) {

			$fieldset = array(

				'meta_keys' => array(
					'action_' . $this->id . '_intro',
					'action_' . $this->id . '_enabled',
					'action_' . $this->id . '_field_email',
					'action_' . $this->id . '_field_mapping',
					'action_' . $this->id . '_spam_level_reject',
					'action_' . $this->id . '_admin_no_run',
				),
			);

		} else {

			$fieldset = array(

				'meta_keys' => array( 'action_' . $this->id . '_intro', 'action_' . $this->id . '_not_enabled' ),
			);
		}

		// Inject after first element
		$config_settings_form_admin['sidebars']['form']['meta']['fieldsets']['spam']['fieldsets'] = WS_Form_Common::array_inject_element( $config_settings_form_admin['sidebars']['form']['meta']['fieldsets']['spam']['fieldsets'], $fieldset, 0 );

		return $config_settings_form_admin;
	}

	// Meta keys for this action
	public function config_meta_keys( $meta_keys = array(), $form_id = 0 ) {

		// Build instructions
		$instructions_array = array();

		if ( ! self::plugin_installed() ) {

			$instructions_array[] = '<li>' . sprintf(
				// translators: placeholder is a url
					__( 'Install and activate the <a href="%s" target="_blank">Fullworks Anti Spam Pro plugin</a>.', 'fullworks-anti-spam' ), 'https://fullworksplugins.com/products/anti-spam/' ) . '</li>';

		} else {

			$instructions_array[] = sprintf( '<li class="wsf-disabled">%s</li>', __( 'Install and activate the Fullworks Anti Spam Pro plugin.', 'fullworks-anti-spam' ) );
		}


		$instructions_array[] = sprintf( '<li class="wsf-disabled">%s</li>', __( 'Enable protection on this form.', 'fullworks-anti-spam' ) );


		$instructions = sprintf( '<p>%s</p><ol>%s</ol>', __( 'To enable Fullworks Anti Spam on this form:', 'fullworks-anti-spam' ), implode( '', $instructions_array ) );

		// Build config_meta_keys
		$config_meta_keys = array(

			// Intro HTML block
			'action_' . $this->id . '_intro'             => array(

				'type' => 'html',
				'html' => sprintf( '<a href="https://fullworksplugins.com/products/anti-spam" target="_blank"><img src="%s/admin/images/brand/dark-anti-spam-75h.svg" alt="Fullworks Anti Spam" title="Fullworks Anti Spam" /></a><div class="wsf-helper">%s</div>', FULLWORKS_ANTI_SPAM_PLUGIN_URL, sprintf( '%s <a href="%s" target="_blank">%s</a>', __( 'Use Fullworks Anti Spam to filter out form submissions that contain spam.', 'fullworks-anti-spam' ), 'https://fullworksplugins.com/docs/anti-spam-by-fullworks/form-protection/wsform/', __( 'Learn more', 'fullworks-anti-spam' ) ) ),
			),

			// Not enable HTML block
			'action_' . $this->id . '_not_enabled'       => array(

				'type' => 'html',
				'html' => $instructions,
			),

			// Enabled
			'action_' . $this->id . '_enabled'           => array(

				'label'   => __( 'Enabled', 'fullworks-anti-spam' ),
				'type'    => 'checkbox',
				'default' => '',
			),

			// Email field
			'action_' . $this->id . '_field_email'       => array(

				'label'              => __( 'Email Field', 'fullworks-anti-spam' ),
				'type'               => 'select',
				'options'            => 'fields',
				'options_blank'      => __( 'Select...', 'fullworks-anti-spam' ),
				'fields_filter_type' => array( 'email' ),
				'help'               => __( 'Select which field contains the email address of the person submitting the form.', 'fullworks-anti-spam' ),
				'condition'          => array(

					array(

						'logic'      => '==',
						'meta_key'   => 'action_' . $this->id . '_enabled',
						'meta_value' => 'on',
					),
				),
			),

			// Field mapping
			'action_' . $this->id . '_field_mapping'     => array(

				'label'            => __( 'Fields To Check For Spam', 'fullworks-anti-spam' ),
				'type'             => 'repeater',
				'help'             => sprintf(

				/* translators: %s = WS Form */
					__( 'Select which %s fields Fullworks Anti Spam should check for spam.', 'fullworks-anti-spam' ),

					WS_FORM_NAME_GENERIC
				),
				'meta_keys'        => array(

					'ws_form_field_edit',
				),
				'meta_keys_unique' => array(

					'ws_form_field_edit',
				),
				'condition'        => array(

					array(

						'logic'      => '==',
						'meta_key'   => 'action_' . $this->id . '_enabled',
						'meta_value' => 'on',
					),
				),
			),

			// List ID
			'action_' . $this->id . '_spam_level_reject' => array(

				'label'     => __( 'Settings', 'fullworks-anti-spam' ),
				'type'      => 'select',
				'help'      => __( 'Reject submission if spam level meets this criteria.', 'fullworks-anti-spam' ),
				'options'   => array(

					array( 'value' => '', 'text' => __( 'Use Spam Threshold', 'fullworks-anti-spam' ) ),
					array( 'value' => '75', 'text' => __( 'Reject Suspected Spam', 'fullworks-anti-spam' ) ),
					array( 'value' => '100', 'text' => __( 'Reject Blatant Spam', 'fullworks-anti-spam' ) ),
				),
				'condition' => array(

					array(

						'logic'      => '==',
						'meta_key'   => 'action_' . $this->id . '_enabled',
						'meta_value' => 'on',
					),
				),
			),

			// Administrator
			'action_' . $this->id . '_admin_no_run'      => array(

				'label'     => __( 'Bypass If Administrator', 'fullworks-anti-spam' ),
				'type'      => 'checkbox',
				'help'      => __( 'If checked, this action will not run if you are signed in as an administrator.', 'fullworks-anti-spam' ),
				'default'   => 'on',
				'condition' => array(

					array(

						'logic'      => '==',
						'meta_key'   => 'action_' . $this->id . '_enabled',
						'meta_value' => 'on',
					),
				),
			),
		);

		// Merge
		$meta_keys = array_merge( $meta_keys, $config_meta_keys );

		return $meta_keys;
	}
}

