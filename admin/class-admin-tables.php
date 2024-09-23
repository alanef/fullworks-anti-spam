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

use Fullworks_Anti_Spam\Core\Utilities;

/**
 * Class Settings
 * @package Fullworks_Security\Admin
 */
class Admin_Tables {

	/**
	 * Protected
	 *
	 * @var \WP_List_Table $table_obj WP Tables.
	 */
	public $table_obj;
	protected $hook;
	protected $page_heading;
	private $plugin_name;
	private $version;
	/*
	 * @var \Freemius $freemius
     */
    private $freemius;


	/**
	 * Settings constructor.
	 *
	 * @param string $plugin_name
	 * @param string $version plugin version.
	 * @param \Freemius $freemius Freemius SDK.
	 */
	public function __construct( $plugin_name, $version, $freemius ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->freemius    = $freemius;


	}


	public static function set_screen( $status, $option, $value ) {
		return $value;
	}


	public function add_table_page() {

	}

	public function screen_option() {

	}

	public function list_page() {
		?>
        <div class="wrap fs-page">
            <h2><?php echo esc_html( $this->page_heading ) ?></h2>
			<?php $this->display_tabs(); ?>

            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post">
								<?php
								$this->table_obj->prepare_items();
								$this->table_obj->views();
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

	public function display_tabs() {
		Utilities::get_instance()->display_tabs();
	}
}
