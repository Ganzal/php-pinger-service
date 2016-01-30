<?php

declare(ticks = 1);
namespace Ganzal\Lulz\Pinger\Daemon;

use Ganzal\Lulz\Pinger\Assets\Config;
use Ganzal\Lulz\Pinger\Assets\DB;
use Ganzal\Lulz\Pinger\Assets\DBQueries;
use Ganzal\Lulz\Pinger\Assets\HostStatuses;
use Ganzal\Lulz\Pinger\Assets\SystemPingWrapper;
use Ganzal\Lulz\Pinger\Assets\RKS;
use Ganzal\Lulz\Pinger\Assets\WorkerBaseTrait;

/**
 * Класс процесса проверки доступности хостов.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
class PingWorker
{

    use WorkerBaseTrait;


    /**
     * Главный метод воркера Ping.
     * 
     * Выплняет в цикле пинг хостов и запись в кэш Redis.
     * 
     * @access public
     * @static
     */
    public static function main ()
    {
        DEBUG && print("PingWorker::main(): begin\n");

        // регистрация обработчика сигналов
        static::initSignalHandlers();

        // подключение к СУБД и кэшу Redis - оба используются
        DB::open();
        RKS::open();

        // объявление массива очередей
        $queue_lists = ['ok',
            'prefailed',
            'failed'];

        // итерация массива очередей
        foreach ($queue_lists as $queue_list)
        {
            DEBUG && printf("PingWorker::main(): begin queue '%s'\n",
                            $queue_list);

            // итерация очереди
            while (static::$worker_loop)
            {
                // получение следующей записи из очереди
                $label = static::fetch($queue_list);

                // записей нет - выход из цикла очереди
                if (!$label)
                {
                    DEBUG && printf("NslookupWorker::main(): break loop '%s'\n",
                                    $queue_list);
                    break;
                }

                // выполнение процедур для лейбла
                static::exec($label);
            }

            DEBUG && printf("PingWorker::main(): end queue '%s'\n", $queue_list);
        }
        
        DB::close();
        RKS::close();
        
        DEBUG && print("PingWorker::main(): end\n\n");

    }

// public static function main ()


    /**
     * Получение следующей записи в очереди <code>queue:ping:$queue_list</code>.
     * 
     * @return string
     * @access public
     * @static
     */
    public static function fetch ($queue_list)
    {
        DEBUG && printf("PingWorker::fetch(%s): begin\n", $queue_list);

        $label = RKS::getInstance()->sPop('queue:ping:' . $queue_list);

        DEBUG && printf("PingWorker::fetch(): Redis->sPop('queue:ping:%s') = '%s'\n\n",
                        $queue_list, $label);

        return $label;

    }

// public static function fetch ($queue_list)


    /**
     * Выполнение <code>ping</code> для лейбла.
     * 
     * @param string $label
     * @return void
     * @access public
     * @static
     */
    public static function exec ($label)
    {
        DEBUG && printf("PingWorker::exec(%s): begin\n", $label);

        $redis = RKS::getInstance();

        // получение свединей о лейбле из кэша Redis
        $data = $redis->hGetAll('hosts:data:' . $label);

        DEBUG && printf("PingWorker::exec(): Redis->hGetAll('host:data:%s')\n%s\n",
                        $label, var_export($data, true));


        $status = HostStatuses::UNKNOWN;
        if (!$data || HostStatuses::NXDOMAIN == $data['status'])
        {
            DEBUG && printf("PingWorker::exec(): host '%s' not ready\n", $label);

            if ($data && isset($data['id']) && isset($data['status']))
            {
                DBQueries::dataPush($data['id'], HostStatuses::NXDOMAIN, 0);
            }

            return;
        }

        // получение статуса хоста
        if (0 != $data['addr'])
        {
            if (!$data['state'])
            {
                $data['state'] = 0;
            }

            switch ($data['state'] & 0x3)
            {
                case 0: $timeout = Config::$ping_timeout_fail;
                case 2: $timeout = Config::$ping_timeout_prefail;
                case 3: default: $timeout = Config::$ping_timeout_ok;
            }

            $success = SystemPingWrapper::exec($data['addr'], $timeout);
            DEBUG && printf("PingWorker::exec(): success = %s\n",
                            var_export($success, true));

            $status = ($success ? HostStatuses::PINGABLE : HostStatuses::UNPINGABLE);
        }

        DEBUG && printf("PingWorker::exec(): status = %s\n",
                        var_export($status, true));

        // вычисление состояния хоста
        switch ($status)
        {
            case HostStatuses::PINGABLE:
                $state = 1;
                break;

            case HostStatuses::UNPINGABLE:
            default:
                $state = 0;
        }

        DEBUG && printf("PingWorker::exec(): state = %s\n",
                        var_export($state, true));

        $data_out = [
            'state' => 0xFF & (($data['state'] << 1) | $state),
            'status' => $status,
        ];

        DEBUG && printf("PingWorker::exec(): Redis->hMset('host:data:%s', %s)\n",
                        $label, var_export($data_out, true));

        // запись статистики в кэш Redis
        $redis->hMSet('hosts:data:' . $label, $data_out);

        // запись статистики в БД
        DBQueries::dataPush($data['id'], $data_out['status'], $data_out['state']);

        DEBUG && print("PingWorker::exec(): end\n\n");

    }

// public static function exec ($label)

}

// class PingWorker

# EOF