<?php

namespace Ganzal\Lulz\Pinger\Assets;

/**
 * Представление хоста, прочитанного из БД.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
class Host
{
    /**
     * Идентификатор хоста.
     * 
     * @var int
     * @access public
     */
    public $host_id;
    
    /**
     * Лейбл хоста.
     * 
     * @var string
     * @access public
     */
    public $host_label;
    
    /**
     * FQDN хоста.
     * 
     * @var string
     * @access public
     */
    public $host_fqdn;
    
    /**
     * Признак активности хоста.
     * 
     * @var int
     * @access public
     */
    public $host_enabled;
    
} // class Host

# EOF