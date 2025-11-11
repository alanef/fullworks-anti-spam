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
 * Created
 * User: alan
 * Date: 25/08/17
 * Time: 09:12
 */
namespace Fullworks_Anti_Spam\Control;

use Gamajo_Template_Loader;
require_once FULLWORKS_ANTI_SPAM_PLUGIN_DIR . '/vendor/gamajo/template-loader/class-gamajo-template-loader.php';
/**
 * Template loader
 *
 * Only need to specify class properties here.
 *
 */
class Template_Loader extends Gamajo_Template_Loader {
    public static $html_output = '';

    /**
     * Prefix for filter names.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $filter_prefix = 'fullworks-anti-spam';

    /**
     * Directory name where custom templates for this plugin should be found in the theme.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $theme_template_directory = 'fullworks-anti-spam';

    /**
     * Reference to the root directory path of this plugin.
     *
     * Can either be a defined constant, or a relative reference from where the subclass lives.
     *
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $plugin_directory = FULLWORKS_ANTI_SPAM_PLUGIN_DIR;

    /**
     * Directory name where templates are found in this plugin.
     *
     * Can either be a defined constant, or a relative reference from where the subclass lives.
     *
     * e.g. 'templates' or 'includes/templates', etc.
     *
     * @since 1.1.0
     *
     * @var string
     */
    protected $plugin_template_directory = 'templates';

    public function __construct() {
        /**
         * @var \Freemius $fwantispam_fs Object for freemius.
         */
        add_filter( 'fullworks-anti-spam_template_paths', function ( $file_paths ) {
            if ( isset( $file_paths[1] ) ) {
                $file_paths[2] = trailingslashit( $file_paths[1] ) . 'parts';
                $file_paths[3] = trailingslashit( $file_paths[1] ) . 'loops';
            }
            $file_paths[11] = trailingslashit( $file_paths[10] ) . 'parts';
            $file_paths[12] = trailingslashit( $file_paths[10] ) . 'loops';
            $file_paths[20] = trailingslashit( dirname( FULLWORKS_ANTI_SPAM_PLUGINS_TOP_DIR ) ) . 'widget-for-eventbrite-api';
            $file_paths[21] = trailingslashit( dirname( FULLWORKS_ANTI_SPAM_PLUGINS_TOP_DIR ) ) . 'widget-for-eventbrite-api/parts';
            $file_paths[22] = trailingslashit( dirname( FULLWORKS_ANTI_SPAM_PLUGINS_TOP_DIR ) ) . 'widget-for-eventbrite-api/loops';
            global $fwas_fs;
            $file_paths[] = FULLWORKS_ANTI_SPAM_PLUGIN_DIR . 'templates__free';
            $file_paths[] = FULLWORKS_ANTI_SPAM_PLUGIN_DIR . 'templates__free/parts';
            $file_paths[] = FULLWORKS_ANTI_SPAM_PLUGIN_DIR . 'templates__free/loops';
            ksort( $file_paths );
            return $file_paths;
        }, 0 );
    }

    public function set_output( $html ) {
        self::$html_output .= $html;
    }

    public function get_output() {
        return self::$html_output;
    }

}
