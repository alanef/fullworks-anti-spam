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

use FS_Admin_Menu_Manager;
use FS_Permission_Manager;
/**
 * Class Freemius_Config
 * @package Fullworks_Anti_Spam\Control
 */
class Freemius_Config {
    /**
     * @return \Freemius
     * @throws \Freemius_Exception
     */
    public function init() {
        /** @var \Freemius $fwantispam_fs Freemius global object. */
        global $fwas_fs;
        if ( !isset( $fwas_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_5065_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_5065_MULTISITE', true );
            }
            // Include Freemius SDK.
            require_once FULLWORKS_ANTI_SPAM_PLUGIN_DIR . '/vendor/freemius/wordpress-sdk/start.php';
            $fwas_fs = fs_dynamic_init( array(
                'id'              => '5065',
                'slug'            => 'fullworks-anti-spam',
                'premium_slug'    => 'fullworks-anti-spam-pro',
                'type'            => 'plugin',
                'public_key'      => 'pk_742878bf26f007c206731eb58e390',
                'is_premium'      => false,
                'premium_suffix'  => 'Pro',
                'has_addons'      => false,
                'has_paid_plans'  => true,
                'navigation'      => 'tabs',
                'trial'           => array(
                    'days'               => 14,
                    'is_require_payment' => false,
                ),
                'has_affiliation' => 'selected',
                'menu'            => array(
                    'slug'    => 'fullworks-anti-spam-settings',
                    'contact' => false,
                    'support' => true,
                    'parent'  => array(
                        'slug' => 'options-general.php',
                    ),
                ),
                'anonymous_mode'  => $this->is_anonymous(),
                'is_live'         => true,
            ) );
        }
        $fwas_fs->add_filter( 'plugin_icon', function () {
            return FULLWORKS_ANTI_SPAM_PLUGIN_DIR . 'admin/images/brand/icon-256x256.svg';
        } );
        $fwas_fs->add_filter( 
            /**
             * @type string $id
             * @type bool $default
             * @type string $icon -class
             * @type bool $optional
             * @type string $label
             * @type string $tooltip
             * @type string $desc
             */
            'permission_list',
            function ( $permissions ) use($fwas_fs) {
                $permissions['fullworks'] = array(
                    'id'         => 'fullworks',
                    'optional'   => false,
                    'default'    => true,
                    'icon-class' => 'dashicons dashicons-cloud-upload',
                    'label'      => esc_html__( 'Notify Spam to Fullworks', 'fullworks-anti-spam' ),
                    'desc'       => esc_html__( 'Allow spam messages to be sent to Fullworks for the purpose of improving spam detection, if you want to opt out of this specific option you can do on the settings page', 'fullworks-anti-spam' ),
                    'priority'   => 21,
                    'tooltip'    => esc_html__( 'When you manually mark a comment or other item as spam or not spam the data will be send to Fullworks for the purpose of improving spam detection', 'fullworks-anti-spam' ),
                );
                return $permissions;
            }
         );
        return $fwas_fs;
    }

    private function is_anonymous() {
        return defined( 'FWAS_ANON' ) && FWAS_ANON;
    }

}
