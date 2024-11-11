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

namespace Fullworks_Anti_Spam\Control;

/**
 * Class Deactivator
 * @package Fullworks_Anti_Spam\Control
 */
class Deactivator {

	/**
	 *
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'fwas_admin_daily' );
		wp_clear_scheduled_hook( 'fullworks_anti_spam_monthly_reports' );
		wp_clear_scheduled_hook( 'fullworks_anti_spam_alerts' );
		wp_clear_scheduled_hook( 'fullworks_anti_spam_daily_training' );
	}
}
