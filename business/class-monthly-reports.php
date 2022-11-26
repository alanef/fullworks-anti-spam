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

namespace Fullworks_Anti_Spam\Business;

use Fullworks_Anti_Spam\Control\Template_Loader;
use Fullworks_Anti_Spam\Core\Utilities;

/**
 *
 */
class Monthly_Reports {
	/** @var Utilities $utilities */
	protected $utilities;
	/** @var \Freemius $freemius Object for freemius. */
	protected $freemius;

	/**
	 * @param $utilities
	 * @param $freemius
	 */
	public function __construct( $utilities, $freemius ) {
		$this->utilities = $utilities;
		$this->freemius  = $freemius;
	}

	/**
	 *
	 */
	public function monthly_spam_stats() {
		$options = get_option( 'fullworks-anti-spam' );
		if ( ! $options['statsemail'] ) {
			return;
		}
		$locale          = get_locale();
		$template_loader = new Template_Loader();
		// get counts  SELECT eventcode, count(*) from wp_fwantispam_log where eventcode > 999 and eventcode < 2000  group by eventcode ;
		$template_loader->set_template_data(
			array(
				'logo'     => 'https://d23dbzwf6sdefr.cloudfront.net/light-anti-spam-75h.png',
				'plugin'   => 'Anti Spam',
				'website'  => get_bloginfo( 'name' ) . ' ' . get_bloginfo( 'url' ),
				'date'     => strftime( '%e %B %Y' ),
				'rows'     => $this->utilities->get_spam_stats(),
				'settings' => admin_url( 'options-general.php?page=fullworks-anti-spam-settings' ),
			)
		);
		ob_start();
		$template = $template_loader->get_template_part( 'monthly-spam-report-' . $locale );
		if ( false === $template ) {
			$template = $template_loader->get_template_part( 'monthly-spam-report-en_GB' );
		}
		$message = ob_get_clean();
		if ( false === $template ) {
			return;
		}
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
		$res = wp_mail( $options['email'], esc_html__( 'Your Monthly Anti-Spam Review', 'fullworks-anti-spam' ), $message );
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
	}

	/**
	 * @return string
	 */
	public function set_html_content_type() {
		return 'text/html';
	}


}