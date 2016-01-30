<?php

namespace Ganzal\Lulz\Pinger\Assets;

/**
 * Библиотека статусов хоста.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
class HostStatuses
{

    /**
     * Не определенный но и не ошибочный статус.
     */
    const UNKNOWN = 0;

    /**
     * Хост создан и активирован.
     */
    const CREATED_ENABLED = 11;

    /**
     * Хост создан и деактивирован.
     */
    const CREATED_DISABLED = -12;

    /**
     * Хост активирован, ожидает начала проверок.
     */
    const ENABLED_WAITING = 21;

    /**
     * Хост деактивирован.
     */
    const DISABLED = -22;

    /**
     * Сервис запущен, хост ожидане начала проверок.
     */
    const SERVICE_STARTED = 31;

    /**
     * Сервис остановлен, хост условно отключен.
     */
    const SERVICE_STOPPED = -32;

    /**
     * Хост удален.
     */
    const DELETED = -127;

    /**
     * Не удалось рарзеришть доменное имя хоста в IP-адрес.
     */
    const NXDOMAIN = -41;

    /**
     * Хост недоступен при проверке Ping-ом.
     */
    const UNPINGABLE = -51;

    /**
     * Хост доступен при проверке Ping-ом.
     */
    const PINGABLE = 61;


    /**
     * Получение текстового описания статуса для списка хостов.
     * 
     * @param int $status
     * @return string
     * @throws \Exception
     */
    public static function statusText ($status)
    {
        switch ($status)
        {
            case static::UNKNOWN:
                return 'UNK';

            case static::CREATED_ENABLED:
            case static::ENABLED_WAITING:
            case static::SERVICE_STARTED:
                return 'QUE';

            case static::UNPINGABLE:
                return 'FAIL';

            case static::SERVICE_STOPPED:
                return 'STOP';

            case static::PINGABLE:
                return 'OK';

            case static::CREATED_DISABLED:
            case static::DISABLED:
                return 'PAU';

            case static::DELETED:
                return '';

            case static::NXDOMAIN:
                return 'NX';

            default:
                throw new \Exception("Unexpected status value ({$status})");
        }

    } // public static function statusText ($status)

}

// class HostStatuses

# EOF