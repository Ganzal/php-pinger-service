<?php

namespace Ganzal\Lulz\Pinger\Assets;

/**
 * Обёртка вокруг системной утилиты <code>nslookup</code>.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
class SystemNslookupWrapper
{
    /**
     * Шаблон команды разрешения доменного имени в IP-адрес.
     */
    const NSLOOKUP_COMMAND = "nslookup %1\$s %2\$s | awk '/Address/&&!/#/' | awk -F ': ' '{print$2}'";
    
    /**
     * Отладочная копия выполненной команды.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $debug_nslookup_command;
    
    /**
     * Отладочная копия результата выполненнения команды.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $debug_nslookup_output;
    
    
    /**
     * Разрешение доменного имени в IP-адрес.
     * 
     * @param string $fqdn
     * @return string|boolean
     * @access public
     * @static
     */
    public static function exec ($fqdn)
    {
        static::$debug_nslookup_command = '';
        static::$debug_nslookup_output = '';
        
        DEBUG && printf("SystemNslookupWrapper::exec(%s): begin\n", $fqdn);
        
        // небольшая ретушь FQDN
        $fqdn = rtrim($fqdn, '.') . '.';
        DEBUG && printf("SystemNslookupWrapper::exec(): fqdn = '%s'\n", $fqdn);
        
        // подготовка команды
        $nslookup_command = sprintf(static::NSLOOKUP_COMMAND, $fqdn, Config::$nslookup_server);
        DEBUG && printf("SystemNslookupWrapper::exec(): nslookup_command = '%s'\n", $nslookup_command);
        
        $nslookup_output = exec($nslookup_command);
        DEBUG && printf("SystemNslookupWrapper::exec(): nslookup_output = '%s'\n", $nslookup_output);
        
        static::$debug_nslookup_command = $nslookup_command;
        static::$debug_nslookup_output = $nslookup_output;
        
        $addr = !empty($nslookup_output) ? $nslookup_output : false;
        
        DEBUG && printf("SystemNslookupWrapper::exec(): end ~ %s\n",
                        var_export($addr, true));

        return $addr;

    } // public static function exec ($fqdn)

} // class SystemNslookupWrapper

# EOF