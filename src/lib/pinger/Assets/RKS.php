<?php

namespace Ganzal\Lulz\Pinger\Assets;

/**
 * Микро-обёртка вокруг Redis.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
class RKS
{

    /**
     * Объект подключения к Redis.
     * 
     * @var \Redis
     * @access protected
     * @static
     */
    protected static $redis = null;


    /**
     * Подключение к Redis.
     * 
     * @return void
     * @access public
     * @static
     */
    public static function open ()
    {
        static::$redis = new \Redis();
        static::$redis->connect(Config::$redis_host, Config::$redis_port,
                Config::$redis_wait);
        static::$redis->setOption(\Redis::OPT_PREFIX,
                !empty(Config::$redis_bank) ? Config::$redis_bank . ':' : '');

    }

// public static function open ()


    /**
     * Отключение от Redis.
     * 
     * @return void
     * @access public
     * @static
     */
    public static function close ()
    {
        if (static::$redis)
        {
            static::$redis->close();
            static::$redis = null;
        }

    }

// public static function close ()


    /**
     * Возврат объекта подключения к Redis.
     * 
     * @return \Redis
     * @access public
     * @static
     */
    public static function getInstance ()
    {
        if (!static::$redis)
        {
            static::open();
        }

        return static::$redis;

    }

// public static function getInstance ()

}

// class RKS

# EOF