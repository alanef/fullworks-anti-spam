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
 * Class Freemius_Config
 * @package Fullworks_Anti_Spam\Control
 */
class Freemius_Config
{
    /**
     * @return \Freemius
     * @throws \Freemius_Exception
     */
    public function init()
    {
        /** @var \Freemius $fwantispam_fs Freemius global object. */
        global  $fwantispam_fs ;
        
        if ( !isset( $fwantispam_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_5065_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_5065_MULTISITE', true );
            }
            // Include Freemius SDK.
            require_once FULLWORKS_ANTI_SPAM_PLUGIN_DIR . '/vendor/freemius/wordpress-sdk/start.php';
            $fwantispam_fs = fs_dynamic_init( array(
                'id'             => '5065',
                'slug'           => 'fullworks-anti-spam',
                'premium_slug'   => 'fullworks-anti-spam-pro',
                'type'           => 'plugin',
                'public_key'     => 'pk_742878bf26f007c206731eb58e390',
                'is_premium'     => false,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                'days'               => 14,
                'is_require_payment' => true,
            ),
                'navigation'     => 'tabs',
                'menu'           => array(
                'slug'    => 'fullworks-anti-spam-settings',
                'contact' => false,
                'support' => true,
                'parent'  => array(
                'slug' => 'options-general.php',
            ),
            ),
                'is_live'        => true,
            ) );
        }
        
        return $fwantispam_fs;
    }

}