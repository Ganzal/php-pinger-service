<?php

namespace Ganzal\Lulz\Pinger\Assets;

/**
 * Микро-обёртка вокруг mysqli.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
class DB
{

    /**
     * Объект подключения к СУБД.
     * 
     * @var \mysqli
     * @access protected
     * @static
     */
    protected static $rdbmsconn = null;


    /**
     * Подключение к СУБД методом <code>mysqli::__construct()</code>.
     * 
     * @return void
     * @access public
     * @static
     */
    public static function open ()
    {
        if (static::$rdbmsconn)
        {
            return;
        }

        $driver = new \mysqli_driver();
        $driver->report_mode = \MYSQLI_REPORT_ERROR | \MYSQLI_REPORT_STRICT;

        static::$rdbmsconn = new \mysqli(
                Config::$mysql_host, Config::$mysql_user, Config::$mysql_pass,
                Config::$mysql_base, Config::$mysql_port, Config::$mysql_sock
        );
        static::$rdbmsconn->set_charset(Config::$mysql_cset);

    }

// public static function open ()


    /**
     * Выполнение запроса к СУБД методом <code>mysqli::query()</code>.
     * 
     * @param string $query
     * @return \mysqli_result|boolean
     * @access public
     * @static
     */
    public static function query ($query)
    {
        !static::$rdbmsconn && static::open();
        return static::$rdbmsconn->query($query);

    }

// public static function query ($query)


    /**
     * Закрытие подключения к СУБД вызовом <code>mysqli::close()</code>.
     * 
     * @return boolean
     * @access public
     * @static
     */
    public static function close ()
    {
        $retval = static::$rdbmsconn->close();
        static::$rdbmsconn = null;

        return $retval;

    }

// public static function close ()


    /**
     * Экранирование строки вызовом <code>mysqli::real_escape_string()</code>.
     * 
     * @param string $escapestr
     * @return string
     * @access public
     * @static
     */
    public static function escape ($escapestr)
    {
        !static::$rdbmsconn && static::open();
        return static::$rdbmsconn->real_escape_string($escapestr);

    }

// public static function escape ($escapestr)


    /**
     * Возврат последнего вставленного идентификатора строки.
     * 
     * @return int
     * @access public
     * @static
     */
    public static function id ()
    {
        !static::$rdbmsconn && static::open();
        return static::$rdbmsconn->insert_id;

    }

// public static function id ()


    /**
     * Возврат объекта подключения к СУБД.
     * 
     * @return \mysqli
     * @access public
     * @static
     */
    public static function share ()
    {
        return static::$rdbmsconn;

    }

// public static function share ()

}

// class DB

# EOF