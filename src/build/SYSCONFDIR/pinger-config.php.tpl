<?php
/**
 * Конфигурация сервиса Pinger. Конфигурируемые значения.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 * @see Config
 */
return [
    /**
     * Пользователь-владелец Мастер-процесса.
     * 
     * @var string
     */
    'exec_user'  => '@conf_exec_user@',
    
    /**
     * Группа-владелец Мастер-процесса.
     * 
     * @var string
     */
    'exec_group' => '@conf_exec_group@',
    
    
    /**
     * Лимит дочерних процессов Воркеров.
     * 
     * 0 - отключает лимит, Мастер ориентируется на
     *  кол-во хостов в списке <code>hosts:enabled</code> лимитируя
     *  себя 32 одновременными потоками.
     * 
     * @var int
     */
    'fork_threads' => @conf_fork_threads@,
    
    
    /**
     * Сервер разрешения доменных имён.
     * 
     * @var string
     */
    'nslookup_server' => '@conf_nslookup_server@',
    
    
    /**
     * Хост СУБД MySQL.
     * 
     * @var string|null
     */
    'mysql_host' => '@conf_mysql_host@',
    
    /**
     * Порт СУБД MySQL.
     * 
     * @var int
     */
    'mysql_port' => @conf_mysql_port@,
    
    /**
     * Сокет СУБД MySQL.
     * 
     * @var string
     */
    'mysql_sock' => '@conf_mysql_sock@',
    
    /**
     * Кодировка соединения с СУБД MySQL.
     * 
     * @var string
     */
    'mysql_cset' => '@conf_mysql_cset@',
    
    /**
     * Пользователь СУБД MySQL.
     * 
     * @var string
     */
    'mysql_user' => '@conf_mysql_user@',
    
    /**
     * Пароль пользователя СУБД MySQL.
     * 
     * @var string
     */
    'mysql_pass' => '@conf_mysql_pass@',
    
    /**
     * Имя базы сервиса Pinger в СУБД MySQL.
     * 
     * @var string
     */
    'mysql_base' => '@conf_mysql_base@',
    
    
    /**
     * Таймаут пинга хоста в очереди <code>queue:ping:ok</code>.
     * 
     * @var int
     */
    'ping_timeout_ok'      => @conf_ping_timeout_ok@,
    
    /**
     * Таймаут пинга хоста в очереди <code>queue:ping:prefailed</code>.
     * 
     * @var int
     */
    'ping_timeout_prefail' => @conf_ping_timeout_prefail@,
    
    /**
     * Таймаут пинга хоста в очереди <code>queue:ping:failed</code>.
     * 
     * @var int
     */
    'ping_timeout_fail'    => @conf_ping_timeout_fail@,
    
    /**
     * Шаблон команды <code>ping</code>.
     * 
     * @var string
     */
    'ping_command' => '@conf_ping_command@',
    
    
    /**
     * Хост сервера Redis.
     * 
     * @var string
     */
    'redis_host' => '@conf_redis_host@',
    
    /**
     * Порт сервера Redis.
     * 
     * @var int
     */
    'redis_port' => @conf_redis_port@,
    
    /**
     * Таймаут ожидания подключения к серверу Redis.
     * 
     * @var float
     */
    'redis_wait' => @conf_redis_wait@,
    
    /**
     * Перфикс ключей в кэше Redis.
     * 
     * @var string
     */
    'redis_bank' => '@conf_redis_bank@',
    
];

# EOF