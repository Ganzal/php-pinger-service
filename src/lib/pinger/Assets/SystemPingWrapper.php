<?php

namespace Ganzal\Lulz\Pinger\Assets;

/**
 * Обёртка вокруг системной утилиты <code>ping</code>.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
class SystemPingWrapper
{

    /**
     * Отладочная копия выполненной команды.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $debug_ping_command;

    /**
     * Отладочная копия результата выполненнения команды.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $debug_ping_output;


    /**
     * Пинг IP-адреса.
     * 
     * @param string $fqdn
     * @return string|boolean
     * @access public
     * @static
     */
    public static function exec ($addr, $timeout = null)
    {
        static::$debug_ping_command = '';
        static::$debug_ping_output = '';

        DEBUG && printf("SystemPingWrapper::exec(%s, %s): begin\n", $addr,
                        var_dump($timeout, true));

        if (!filter_var($addr, FILTER_VALIDATE_IP))
        {
            DEBUG && print("SystemPingWrapper::exec(): invalid ADDR value ~ false\n");
            return false;
        }

        if (null === $timeout)
        {
            $timeout = Config::$ping_timeout_ok;
        }

        $ping_command = sprintf(
                Config::$ping_command, $timeout, $addr
        );

        DEBUG && printf("SystemPingWrapper::exec(): ping_command = '%s'\n",
                        $ping_command);

        $ping_output = exec($ping_command);
        DEBUG && printf("SystemPingWrapper::exec(): ping_output = '%s'\n", $ping_output);

        static::$debug_ping_command = $ping_command;
        static::$debug_ping_output = $ping_output;

        $retval = !empty($ping_output);

        DEBUG && printf("SystemPingWrapper::exec(): end ~ %s\n",
                        var_export($retval, true));

        return $retval;

    }

// public static function exec ($addr, $timeout = null)

}

// class SystemPingWrapper

# EOF