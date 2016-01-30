<?php

namespace Ganzal\Lulz\Pinger\Assets;

/**
 * Класс конфигурации сервиса Pinger.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
class Config
{

    /**
     * Пользователь-владелец Мастер-процесса.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $exec_user = 'nobody';

    /**
     * Группа-владелец Мастер-процесса.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $exec_group = 'nogroup';

    /**
     * Лимит дочерних процессов Воркеров.
     * 
     * 0 - отключает лимит, Мастер ориентируется на
     *  кол-во хостов в списке <code>hosts:enabled</code> лимитируя
     *  себя 32 одновременными потоками.
     * 
     * @var int
     * @access public
     * @static
     */
    public static $fork_threads = 0;

    /**
     * Сервер разрешения доменных имён.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $nslookup_server = '';

    /**
     * Хост СУБД MySQL.
     * 
     * @var string|null
     * @access public
     * @static
     */
    public static $mysql_host = '127.0.0.1';

    /**
     * Порт СУБД MySQL.
     * 
     * @var int
     * @access public
     * @static
     */
    public static $mysql_port = 3306;

    /**
     * Сокет СУБД MySQL.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $mysql_sock = '/var/run/mysqld/mysqld.sock';

    /**
     * Кодировка соединения с СУБД MySQL.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $mysql_cset = 'utf8';

    /**
     * Пользователь СУБД MySQL.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $mysql_user = 'root';

    /**
     * Пароль пользователя СУБД MySQL.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $mysql_pass = '';

    /**
     * Имя базы сервиса Pinger в СУБД MySQL.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $mysql_base = 'pinger';

    /**
     * Таймаут пинга хоста в очереди <code>queue:ping:ok</code>.
     * 
     * @var int
     * @access public
     * @static
     */
    public static $ping_timeout_ok = 1;

    /**
     * Таймаут пинга хоста в очереди <code>queue:ping:prefailed</code>.
     * 
     * @var int
     * @access public
     * @static
     */
    public static $ping_timeout_prefail = 5;

    /**
     * Таймаут пинга хоста в очереди <code>queue:ping:failed</code>.
     * 
     * @var int
     * @access public
     * @static
     */
    public static $ping_timeout_fail = 1;

    /**
     * Шаблон команды <code>ping</code>.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $ping_command = '/usr/bin/env ping -c1 -W%1$d -n -q %2$s';

    /**
     * Хост сервера Redis.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $redis_host = '127.0.0.1';

    /**
     * Порт сервера Redis.
     * 
     * @var int
     * @access public
     * @static
     */
    public static $redis_port = 6379;

    /**
     * Таймаут ожидания подключения к серверу Redis.
     * 
     * @var float
     * @access public
     * @static
     */
    public static $redis_wait = 1.0;

    /**
     * Перфикс ключей в кэше Redis.
     * 
     * @var string
     * @access public
     * @static
     */
    public static $redis_bank = 'pinger';


    /**
     * Чтение конфигурации.
     * 
     * Обязательно читается файл значений по умолчанию.
     * Опционально читается системынй файл настроек.
     * 
     * @return void
     * @access public
     * @static
     */
    public static function configure ()
    {
        $config = require __DIR__ . '/default-config.php';

        if (file_exists(PINGER_CONF))
        {
            $config = array_merge($config, require PINGER_CONF);
        }

        foreach ($config as $k => $v)
        {
            static::${$k} = $v;
        }

    }

// public static function configure ()

}

// class Config

# EOF