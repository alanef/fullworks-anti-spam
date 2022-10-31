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
 * The admin-specific functionality of the plugin.
 *
 *
 */

namespace Fullworks_Anti_Spam\Admin;

use Fullworks_Anti_Spam\Core\Utilities;


/**
 * Class Admin
 * @package Fullworks_Anti_Spam\Admin
 */
class Admin {

	/** @var Utilities $utilities */
	protected $utilities;
	/** @var \Freemius $freemius Object for freemius. */
	protected $freemius;
	/**
	 * The ID of this plugin.
	 *
	 */
	private $plugin_name;
	/**
	 * The version of this plugin.
	 *
	 */
	private $version;

	/**
	 * Admin constructor.
	 *
	 * @param $plugin_name
	 * @param $version
	 * @param $utilities
	 */
	public function __construct( $plugin_name, $version, $utilities, $freemius ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->utilities   = $utilities;
		$this->freemius    = $freemius;
	}


	/**
	 * Register the stylesheets for the admin area.
	 *
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), $this->version, 'all' );
	}
}




