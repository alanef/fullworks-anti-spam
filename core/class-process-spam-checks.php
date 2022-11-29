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
namespace Fullworks_Anti_Spam\Core;

use  Fullworks_Anti_Spam\Data\Log ;
use  GFAPI ;
use  WP_Error ;
use  WP_Http ;
/**
 * Class Process_Spam_Checks
 * @package Fullworks_Anti_Spam\Control
 */
class Process_Spam_Checks
{
    /** @var Log $log */
    protected  $log ;
    /** @var Utilities $utilities */
    protected  $utilities ;
    /**
     * @var $options array
     */
    protected  $options ;
    /** @var \Freemius $freemius Object for freemius. */
    protected  $freemius ;
    /**
     * Process_Spam_Checks constructor.
     *
     * @param $log
     * @param $options
     * @param $utilities
     * @param $freemius
     */
    public function __construct(
        $log,
        $options,
        $utilities,
        $freemius
    )
    {
        $this->log = $log;
        $this->options = $options;
        $this->utilities = $utilities;
        $this->freemius = $freemius;
    }
    
    /**
     *
     */
    public function init()
    {
        $this->set_hooks();
    }
    
    /**
     *
     */
    private function set_hooks()
    {
    }
    
    /**
     * @return bool
     */
    private function is_valid()
    {
        if ( !empty($_POST[$this->utilities->get_uid( 'fullworks_anti_spam_key_name', 12 )]) && $_POST[$this->utilities->get_uid( 'fullworks_anti_spam_key_name', 12 )] === $this->utilities->get_uid( 'fullworks_anti_spam_key_value', 64 ) ) {
            return true;
        }
        return false;
    }
    
    /**
     * @param $status
     * @param $commentdata
     *
     * @return string|WP_Error
     */
    public function pre_comment_approved( $status, $commentdata )
    {
        if ( 'comment' === $commentdata['comment_type'] && $this->options['comments'] ) {
            
            if ( !$this->is_valid() ) {
                $this->log->run( 'Spam Comment (Bot)', 1410 );
                return 'spam';
            }
        
        }
        return $status;
    }

}