<?php

declare(ticks = 1);
namespace Ganzal\Lulz\Pinger\Daemon;

use \Ganzal\Lulz\Pinger\Assets\DB;
use \Ganzal\Lulz\Pinger\Assets\DBQueries;
use \Ganzal\Lulz\Pinger\Assets\HostStatuses;
use \Ganzal\Lulz\Pinger\Assets\RKS;
use \Ganzal\Lulz\Pinger\Assets\SystemNslookupWrapper;
use \Ganzal\Lulz\Pinger\Assets\WorkerBaseTrait;

/**
 * Класс процесса разрешения доменных имён хостов.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
class NslookupWorker
{

    use WorkerBaseTrait;


    /**
     * Главный метод Воркера Nslookup.
     * 
     * Выплняет в цикле разрешение имён и запись в кэш Redis.
     * 
     * @access public
     * @static
     */
    public static function main ()
    {
        DEBUG && print("NslookupWorker::main(): begin\n");

        // регистрация обработчика сигналов
        static::initSignalHandlers();

        RKS::open();

        // итерация очереди
        while (static::$worker_loop)
        {
            // получение следующей записи из очереди
            $label = static::fetch();

            // записей нет - выход
            if (!$label)
            {
                DEBUG && print("NslookupWorker::main(): break loop\n");
                break;
            }

            // выполнение процедур для лейбла
            static::exec($label);
        }

        RKS::close();

        DEBUG && print("NslookupWorker::main(): end\n\n");

    }

// public static function main ()


    /**
     * Получение следующей записи из очереди <code>queue:nslookup</code>.
     * 
     * @return string
     * @access public
     * @static
     */
    public static function fetch ()
    {
        DEBUG && print("NslookupWorker::fetch(): begin\n");

        $label = RKS::getInstance()->sPop('queue:nslookup');

        DEBUG && printf("NslookupWorker::fetch(): Redis->sPop('queue:nslookup') = '%s'\n\n",
                        $label);

        return $label;

    }

// public static function fetch ()


    /**
     * Выполнение <code>nslookup</code> для лейбла.
     * 
     * @param string $label
     * @return void
     * @access public
     * @static
     */
    public static function exec ($label)
    {
        DEBUG && printf("NslookupWorker::exec(%s): begin\n", $label);

        $redis = RKS::getInstance();

        // получение свединей о лейбле из кэша Redis
        $data = $redis->hGetAll('hosts:data:' . $label);

        DEBUG && printf("NslookupWorker::exec(): Redis->hGetAll('host:data:%s')\n%s\n",
                        $label, var_export($data, true));

        if (!$data)
        {
            DEBUG && printf("NslookupWorker::exec(): host '%s' not ready\n",
                            $label);
            return;
        }

        // поиск адреса
        $addr = SystemNslookupWrapper::exec($data['fqdn']);
        DEBUG && printf("NslookupWorker::exec(): addr = %s\n",
                        var_export($addr, true));

        $data_out = [
            'addr' => $addr,
            'status' => $addr ? (int) $data['status'] : HostStatuses::NXDOMAIN,
        ];

        if (
                (HostStatuses::NXDOMAIN == $data_out['status'] && HostStatuses::NXDOMAIN != $data['status']) ||
                (HostStatuses::NXDOMAIN != $data_out['status'] && HostStatuses::NXDOMAIN == $data['status'])
        )
        {
            DB::open();

            DBQueries::dataPush($data['id'], $data_out['status'], 0);

            DB::close();
        }

        DEBUG && printf("NslookupWorker::exec(): Redis->hMset('host:data:%s', %s)\n",
                        $label, var_export($data_out, true));

        // запись сведений в кэш Redis
        $redis->hMset('hosts:data:' . $label, $data_out);

        DEBUG && print("NslookupWorker::exec(): end\n\n");

    }

// public static function exec ($label)

}

// class NslookupWorker

# EOF