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

namespace Fullworks_Anti_Spam\Data;

use Fullworks_Anti_Spam\Core\Utilities;

/**
 * Class Log
 * @package Fullworks_Anti_Spam\Control
 */
class Log {
	/**
	 * @var
	 */
	protected $event;
	/**
	 * @var
	 */
	protected $eventcode;
	/**
	 * @var
	 */
	protected $ip;
	/**
	 * @var
	 */
	protected $severity;
	/** @var Utilities $utilities */
	protected $utilities;

	/**
	 * Log constructor.
	 *
	 * @param $utilities
	 */
	public function __construct( $utilities ) {
		$this->utilities = $utilities;
	}

	/**
	 * @param $event
	 * @param null $eventcode
	 */
	public function run( $event, $eventcode = null ) {
		$this->event     = $event;
		$this->eventcode = $eventcode;
		$this->ip        = $this->utilities->get_ip( $this->ip );
		$this->local_log();
	}

	/**
	 *
	 */
	private function local_log() {
		global $wpdb;
		$server = array();
		if ( is_array( $_SERVER ) ) {
			foreach ( $_SERVER as $key => $server_element ) {
				$server[ $key ] = sanitize_text_field( $server_element );
			}
		} else {
			$server[] = sanitize_text_field( $_SERVER );
		}
		$table_name = $wpdb->prefix . 'fwantispam_log';

		$result = $wpdb->query( $wpdb->prepare( "INSERT INTO $table_name 
( IP, server, event, eventcode_str ) 
values (INET6_ATON(%s), %s,%s,%s)"
			, $this->ip, maybe_serialize( $server ), $this->event, $this->eventcode ) );
	}
}