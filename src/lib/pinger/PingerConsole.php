<?php

namespace Ganzal\Lulz\Pinger;

use Ganzal\Lulz\Pinger\Assets\DB;
use Ganzal\Lulz\Pinger\Assets\DBQueries;
use Ganzal\Lulz\Pinger\Assets\HostStatuses;
use Ganzal\Lulz\Pinger\Assets\RKS;

/**
 * Консольный интерфейс к Pinger.
 * 
 * <p>Допускает запуск от пользователя, состоящего в группе "pinger".</p>
 * 
 * <p>Команды:
 * <ul>
 *  <li><code>add LABEL FQDN</code></li>
 *  <li><code>remove LABEL</code></li>
 *  <li><code>enable LABEL</code></li>
 *  <li><code>disable LABEL</code></li>
 *  <li><code>list</code></li>
 *  <li><code>help</code></li>
 * </ul></p>
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
class PingerConsole
{

    /**
     * Формат заголовка списка хостов.
     */
    const LIST_TITLE = "%s %7s | %-16s | %-25s | %-7s %s\n";

    /**
     * Формат тела списка хостов.
     */
    const LIST_BODY = "%s %5d %1s | %-16s | %-25s | %s%-13s %3s \n";

    /**
     * Цвет статуса <code>OK</code>.
     */
    const LIST_ANSI_OK = "\033[92m";

    /**
     * Цвет статуса <code>UNKNOWN</code>.
     */
    const LIST_ANSI_UNKNOWN = "\033[93m";

    /**
     * Цвет статуса <code>PREFAIL</code>.
     */
    const LIST_ANSI_PREFAIL = "\033[93;41m";

    /**
     * Цвет статуса <code>PREOK</code>.
     */
    const LIST_ANSI_PREOK = "\033[93;42m";

    /**
     * Цвет статуса <code>PAUSED</code>.
     */
    const LIST_ANSI_PAUSED = "\033[90m";

    /**
     * Цвет статуса <code>FAIL</code>.
     */
    const LIST_ANSI_FAIL = "\033[91m";

    /**
     * Цвет заголовка списка.
     */
    const LIST_ANSI_TITLE = "\033[1;97m";

    /**
     * Сброс стиля оформления.
     */
    const LIST_ANSI_RESET = "\033[0m";


    /**
     * Вход в консоль Pinger.
     * 
     * @param array $argv Копия массива <code>$argv</code> с аргументами вызова скрипта.
     * @return void
     * @access protected
     * @static
     */
    public static function main ($argv)
    {
        if ('cli' !== PHP_SAPI)
        {
            die("CLI only!");
        }
        
        if (!count(array_intersect(posix_getgroups(), [0, posix_getgrnam('pinger')['gid']])))
        {
            echo "Error: Pinger Console must be launched as root or from group named 'pinger'!\n";
            exit(1);
        }
        
        $argv += array_fill(0, 4, null);

        switch ($argv[1])
        {
            case 'list':
                static::cmdList();
                break;

            case 'add':
                static::cmdAdd($argv[2], $argv[3]);
                break;

            case 'rem':
                static::cmdRem($argv[2]);
                break;

            case 'enable':
                static::cmdEnable($argv[2]);
                break;

            case 'disable':
                static::cmdDisable($argv[2]);
                break;

            case 'help':
            default:
                static::cmdHelp($argv[1]);
                break;
        }

    }

// public static function main ($argv)


    /**
     * Вывод списока хостов.
     * 
     * @access public
     * @static
     */
    public static function cmdList ()
    {
        DB::open();
        $res = DBQueries::hostsList();

        $redis = RKS::getInstance();

        printf(static::LIST_TITLE, static::LIST_ANSI_TITLE, 'ID / On', 'Label',
                'FQDN', 'Status', static::LIST_ANSI_RESET);

        /* @var $host \Ganzal\Lulz\Pinger\Assets\Host */
        while ($host = $res->fetch_object('\\Ganzal\\Lulz\\Pinger\\Assets\\Host'))
        {
            $data = $redis->hGetAll('hosts:data:' . $host->host_label);
            $data['row_ansi'] = '';
            if (empty($data) || '' == $data['status'])
            {
                $data['status'] = 0;
            }

            $data['list_status'] = HostStatuses::statusText($data['status']);
            $data['list_state'] = isset($data['state']) ? (int) $data['state'] : 0;

            switch ($data['list_status'])
            {
                case 'NX':
                    $data['list_status_ansi'] = static::LIST_ANSI_FAIL;
                    $data['list_state'] = '';
                    break;

                case 'STOP':
                case 'PAU':
                case 'QUE':
                    $data['list_status_ansi'] = static::LIST_ANSI_PAUSED;
                    $data['list_state'] = '';
                    break;

                case 'UNK':
                    $data['list_status_ansi'] = static::LIST_ANSI_UNKNOWN;
                    $data['list_state'] = '';
                    break;

                case 'OK':
                case 'FAIL':
                    $data['list_state'] = base_convert($data['state'], 10, 8);

                    switch ($data['state'] & 0x3)
                    {
                        case 0:
                            $data['list_status'] = 'FAIL';
                            $data['list_status_ansi'] = static::LIST_ANSI_FAIL;
                            break;

                        case 3:
                            $data['list_status'] = 'OK';
                            $data['list_status_ansi'] = static::LIST_ANSI_OK;
                            break;

                        case 1:
                            $data['list_status'] = 'PRE';
                            $data['list_status_ansi'] = static::LIST_ANSI_PREOK;
                            break;

                        case 2:
                            $data['list_status'] = 'PRE';
                            $data['list_status_ansi'] = static::LIST_ANSI_PREFAIL;
                            break;

                        default:
                            $data['list_status'] = $data['state'];
                            $data['list_status_ansi'] = '';
                            break;
                    }
                    break;
            }

            printf(
                    static::LIST_BODY, $data['row_ansi'], $host->host_id,
                    $host->host_enabled ? 'Y' : '-', $host->host_label,
                    $host->host_fqdn, static::LIST_ANSI_RESET,
                    $data['list_status_ansi'] . $data['list_status'] . static::LIST_ANSI_RESET,
                    $data['list_state']
            );
        }

        exit(0);

    }

// public static function cmdList ()


    /**
     * Добавление хоста в базу Пингера.
     * 
     * @param string $label Лейбл добавляемого хоста.
     * @param string $fqdn FQDN добавляемого хоста.
     * @access public
     * @static
     */
    public static function cmdAdd ($label, $fqdn)
    {
        // первичная проверка аргументов
        $argsOk = true;

        if (!preg_match('~^[a-z0-9-]{3,48}$~', $label))
        {
            fwrite(STDERR,
                    "Label contains invalid characters. Only [a-z0-9-]{3,48} allowed.\n");
            $argsOk = false;
        }

        if (!preg_match('~^[a-z0-9-\.]{3,224}$~', $fqdn))
        {
            fwrite(STDERR,
                    "FQDN contains invalid characters. Only [a-z0-9-\.]{3,224} allowed.\n");
            $argsOk = false;
        }

        if (!$argsOk)
        {
            exit(1);
        }

        // подключение к СУБД
        DB::open();

        // проверка на занятость лейбла и хоста
        $res = DBQueries::hostUniqueness($label, $fqdn);

        $check = $res->fetch_object();

        if ($check->label)
        {
            fwrite(STDERR, "Host w/label '{$label}' is already exists.\n");
        }

        if ($check->fqdn)
        {
            fwrite(STDERR, "Host w/FQDN '{$fqdn}' is already exists.\n");
        }

        if ($check->label || $check->fqdn)
        {
            exit(1);
        }

        // добавление хоста в БД
        DBQueries::hostInsert($label, $fqdn);

        $id = DB::id();

        // добавление пустой записи в таблицу данных
        DBQueries::dataPush($id, HostStatuses::CREATED_ENABLED, 0);

        // добавление хоста в кэш Redis
        $redis = RKS::getInstance();

        $redis->hMset('hosts:data:' . $label,
                [
            'id' => $id,
            'label' => $label,
            'fqdn' => $fqdn,
            'addr' => false,
            'status' => false,
            'state' => false,
        ]);

        $redis->sAdd("hosts:enabled", $label);

        exit(0);

    }

// public static function cmdAdd ($label, $fqdn)


    /**
     * Удаление хоста из базы Pinger.
     * 
     * На самом деле хост не удаляется а лишь получает уникальный суффикс в полях
     * лейбла и FQDN что позволяет сохранить связь с таблицей <code>`data`</code>.
     * 
     * @param string $label Лейбл удаляемого хоста.
     * @access public
     * @static
     */
    public static function cmdRem ($label)
    {
        // первичная проверка аргументов
        if (!preg_match('~^[a-z0-9-]{3,48}$~', $label))
        {
            fwrite(STDERR,
                    "Label contains invalid characters. Only [a-z0-9-]{3,48} allowed.\n");
            exit(1);
        }

        // подключение к СУБД
        DB::open();

        // поиск лейбла в базе хостов
        $res = DBQueries::hostByLabel($label);
        if (0 == $res->num_rows)
        {
            fwrite(STDERR, spinrtf("Host with label '%s' was not found.\n", $label));
            exit(1);
        }

        if (1 < $res->num_rows)
        {
            fwrite(STDERR,
                    sprintf("Fatal: Expected 1 row, has %d\n", $res->num_rows));
            exit(1);
        }

        /* @var $host \Ganzal\Lulz\Pinger\Assets\Host */
        $host = $res->fetch_object('\\Ganzal\\Lulz\\Pinger\\Assets\\Host');

        // удаление упоминаний хоста из кэша Redis
        $redis = RKS::getInstance();
        $redis->delete('hosts:data:' . $label);
        $redis->sRemove("hosts:enabled", $label);
        $redis->sRemove("queue:ping:ok", $label);
        $redis->sRemove("queue:ping:prefailed", $label);
        $redis->sRemove("queue:ping:failed", $label);
        $redis->sRemove("queue:nslookup", $label);

        // "удаление" хоста из БД
        DBQueries::hostDelete($host->host_id);

        // "закрытие" собранных данных хоста в БД
        DBQueries::dataPush($host->host_id, HostStatuses::DELETED, 0);

        exit(0);

    }

// public static function cmdRem ($label)


    /**
     * Активация хоста.
     * 
     * @param string $label Лейбл активируемого хоста.
     * @access public
     * @static
     */
    public static function cmdEnable ($label)
    {
        // первичная проверка аргументов
        if (!preg_match('~^[a-z0-9-]{3,48}$~', $label))
        {
            fwrite(STDERR,
                    "Label contains invalid characters. Only [a-z0-9-]{3,48} allowed.\n");
            exit(1);
        }

        // подключение к СУБД
        DB::open();

        // поиск лейбла в базе хостов
        $res = DBQueries::hostByLabel($label);
        if (0 == $res->num_rows)
        {
            fwrite(STDERR, spinrtf("Host with label '%s' was not found.\n", $label));
            exit(1);
        }

        if (1 < $res->num_rows)
        {
            fwrite(STDERR,
                    sprintf("Fatal: Expected 1 row, has %d\n", $res->num_rows));
            exit(1);
        }

        /* @var $host \Ganzal\Lulz\Pinger\Assets\Host */
        $host = $res->fetch_object('\\Ganzal\\Lulz\\Pinger\\Assets\\Host');

        if (1 == $host->host_enabled)
        {
            fwrite(STDERR, "Host is already enabled.\n");
        }
        else
        {
            // обновление статуса в таблице хостов
            $res = DBQueries::hostXXable($host->host_id, 1);

            // отсечка о возобновлении сбора данных
            DBQueries::dataPush($host->host_id, HostStatuses::ENABLED_WAITING, 0);

            // обновление статуса в кэше Redis.
            // достаточно добавить в список hosts:enabled.
            // в пулы queue:* хост добавляется Мастер-процессом.

            $redis = RKS::getInstance();
            $redis->sAdd("hosts:enabled", $label);
            $redis->hMset('hosts:data:' . $label,
                    ['state' => '',
                'status' => '']);

            fwrite(STDERR, "Host successfully enabled.\n");
        }

        exit(0);

    }

// public static function cmdEnable ($label)


    /**
     * Деактивация хоста.
     * 
     * @param string $label Лейбл деактивируемого хоста.
     * @access public
     * @static
     */
    public static function cmdDisable ($label)
    {
        // первичная проверка аргументов
        if (!preg_match('~^[a-z0-9-]{3,48}$~', $label))
        {
            fwrite(STDERR,
                    "Label contains invalid characters. Only [a-z0-9-]{3,48} allowed.\n");
            exit(1);
        }

        // подключение к СУБД
        DB::open();

        // поиск лейбла в базе хостов
        $res = DBQueries::hostByLabel($label);
        if (0 == $res->num_rows)
        {
            fwrite(STDERR, spinrtf("Host with label '%s' was not found.\n", $label));
            exit(1);
        }

        if (1 < $res->num_rows)
        {
            fwrite(STDERR,
                    sprintf("Fatal: Expected 1 row, has %d\n", $res->num_rows));
            exit(1);
        }

        /* @var $host \Ganzal\Lulz\Pinger\Assets\Host */
        $host = $res->fetch_object('\\Ganzal\\Lulz\\Pinger\\Assets\\Host');

        if (0 == $host->host_enabled)
        {
            fwrite(STDERR, "Host is already disabled.\n");
        }
        else
        {
            // обновление статуса в таблице хостов
            $res = DBQueries::hostXXable($host->host_id, 0);

            // отсечка о прекращении сбора данных
            DBQueries::dataPush($host->host_id, HostStatuses::DISABLED, 0);

            /* @var $redis \Redis */
            $redis = RKS::getInstance();

            // обновление статуса в кэше Redis.
            // удаление лейбла из списка hosts:enabled отключает будущие пинги.
            // удаление из всех пулов queue:* так как не известно где он может быть.

            $redis->sRemove("hosts:enabled", $label);
            $redis->sRemove("queue:ping:ok", $label);
            $redis->sRemove("queue:ping:prefailed", $label);
            $redis->sRemove("queue:ping:failed", $label);
            $redis->sRemove("queue:nslookup", $label);
            $redis->hMset('hosts:data:' . $label,
                    ['state' => '',
                'status' => '']);

            fwrite(STDERR, "Host successfully disabled.\n");
        }

        exit(0);

    }

// public static function cmdDisable ($label)


    /**
     * Вывод справочной информации.
     * 
     * @param mixed $arg1 Значение второго аргумента вызова скрипта <code>$argv[1]</code>
     * @access public
     * @static
     */
    public static function cmdHelp ($arg1 = null)
    {
        fwrite(STDERR,
                <<<PINGER_HELP
Usage: pingerc {add|rem|enable|disable|list|help}

add LABEL FQDN
rem LABEL
enable LABEL
disable LABEL
list
help
PINGER_HELP
        );

        if (isset($arg1) && 'help' != $arg1)
        {
            fwrite(STDERR, sprintf("\nInvalid command '%s'\n", $arg1));
            exit(1);
        }

        if (!isset($arg1))
        {
            fwrite(STDERR, "\nNo command specified.\n");
            exit(1);
        }

        exit(0);

    }

// public static function cmdHelp ($arg1 = null)

}

// class PingerConsole

# EOF