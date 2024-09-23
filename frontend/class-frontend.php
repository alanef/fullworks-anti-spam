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
 * The public-facing functionality of the plugin.
 *
 */

namespace Fullworks_Anti_Spam\FrontEnd;

use Fullworks_Anti_Spam\Core\Forms_Registrations;
use Fullworks_Anti_Spam\Core\Utilities;


use DateTime;

/**
 * Class FrontEnd
 * @package Fullworks_Anti_Spam\FrontEnd
 */
class FrontEnd {

	/**
	 * @var
	 */
	private $plugin_name;

	/**
	 * @var
	 */
	private $version;


	/** @var Utilities $utilities */
	protected $utilities;


	/**
	 * FrontEnd constructor.
	 *
	 * @param $plugin_name
	 * @param $version
	 * @param $log
	 * @param $utilities
	 */
	public function __construct( $plugin_name, $version, $utilities ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->utilities   = $utilities;
	}

	/**
	 * Enqueues the necessary scripts for the frontend.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		/** @var \Freemius $fwantispam_fs Freemius global object. */
		global $fwantispam_fs;
		wp_enqueue_script( $this->plugin_name . '-front-logged-out', plugin_dir_url( __FILE__ ) . 'js/frontend.js', array( 'jquery' ), $this->version . '.' . $this->utilities->get_random_version(), false );
		$forms_registrations = new Forms_Registrations();
		$registered_forms    = $forms_registrations->get_registered_forms();
		$fas_forms_selectors = implode( ', ', array_column( $registered_forms, 'selectors' ) );
		wp_localize_script(
			$this->plugin_name . '-front-logged-out',
			'FullworksAntiSpamFELO',
			array(
				'form_selectors' => $fas_forms_selectors,
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	public function get_keys() {
		/* ajax function  fwas_get_keys as action to return as json */
		wp_send_json( array(
			'name'           => $this->utilities->get_uid( 'fullworks_anti_spam_key_name', 12 ),
			'value'          => $this->utilities->get_uid( 'fullworks_anti_spam_key_value', 64 ),
		) );

	}

}
