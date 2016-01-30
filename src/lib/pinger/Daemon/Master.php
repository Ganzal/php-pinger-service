<?php

declare(ticks = 1);
namespace Ganzal\Lulz\Pinger\Daemon;

use Ganzal\Lulz\Pinger\Assets\Exceptions;
use Ganzal\Lulz\Pinger\Assets\Config;
use Ganzal\Lulz\Pinger\Assets\DB;
use Ganzal\Lulz\Pinger\Assets\DBQueries;
use Ganzal\Lulz\Pinger\Assets\HostStatuses;
use Ganzal\Lulz\Pinger\Assets\PID;
use Ganzal\Lulz\Pinger\Assets\RKS;
use \com\ganzal\repo\bernd\TwinLog;

/**
 * Класс Мастер-процесса сервиса Pinger.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
class Master
{

    /**
     * Микровремя входа в Мастер-процесс.
     * 
     * @var float
     * @access protected
     * @static
     */
    protected static $start_utime = 0;

    /**
     * Счетчик форков за время жизни Мастер-процесса.
     * 
     * @var int
     * @access protected
     * @static
     */
    protected static $fork_num = 0;

    /**
     * Массив идентификаторов процессорв Воркеров.
     * 
     * @var array
     * @access protected
     * @static
     */
    protected static $jobs = array();

    /**
     * Признак продолжения исполнения Мастер-цикла.
     * 
     * @var boolean
     * @access protected
     * @static
     */
    protected static $master_loop = false;

    /**
     * Массив слотов для Воркеров.
     * 
     * @var array
     * @access protected
     * @static
     */
    protected static $pid_slots = array();

    /**
     * Массив сигналов.
     * 
     * @var array
     * @access protected
     * @static
     */
    protected static $sig_queue = array();


    /**
     * Корневой метод мастера.
     * 
     * @return int
     * @access public
     * @static
     */
    public static function main ()
    {
        DEBUG && print("Master::main(): begin\n");

        static::$start_utime = microtime(true);

        try
        {
            cli_set_process_title('PingerService/Master/WarmUp');

            // открытие PID-файла
            PID::Lock();

            // регистрация обработчика сигналов
            static::initSignalHandlers();

            // предварительная загрузка классов
            static::preloadClasses();

            // сброс статистики хостов
            static::resetHostsStatuses(HostStatuses::SERVICE_STARTED);

            // включение Мастер-цикла
            static::$master_loop = true;

            // выполнение Мастер-цикла
            static::masterLoop();

            cli_set_process_title('PingerService/Master/Shutdown');

            // сброс статистики хостов
            static::resetHostsStatuses(HostStatuses::SERVICE_STOPPED);

            // закрытие PID-файла
            PID::Unlock();

            // успешный выход
            DEBUG && printf("Master::main(): exit with status 0\n  uptime %0.2fsecs, %d forks\n\n",
                            microtime(true) - static::$start_utime,
                            static::$fork_num);
            return 0;
        }
        catch (\Exception $e)
        {
            var_dump($e);
        }

        DEBUG && printf("Master::main(): exit with status 1\n  uptime %0.2fsecs, %d forks\n\n",
                        microtime(true) - static::$start_utime,
                        static::$fork_num);

        return 1;

    }

// public static function main ()


    /**
     * Основной метод Мастера - Мастер-цикл.
     * 
     * @access protected
     * @static
     */
    protected static function masterLoop ()
    {
        DEBUG && print("Master::masterLoop(): begin\n");

        cli_set_process_title('PingerService/Master/Loop');

        while (static::$master_loop)
        {
            $sec = date('s');

            DEBUG && printf("Master::masterLoop(): switch(%s)\n", $sec);

            switch ($sec)
            {
                case 55:
                    DEBUG && print("Master::masterLoop(): case 55 begin\n");

                    RKS::open();

                    static::populateNslookupQueue();
                    static::launchWorkers('Nslookup');

                    DEBUG && print("Master::masterLoop(): case 55 end\n\n");

                    break;

                case 0:
                case 10:
                case 20:
                case 30:
                case 40:
                case 50:
                    DEBUG && print("Master::masterLoop(): case /10 begin\n");

                    RKS::open();

                    static::populatePingQueues();
                    static::launchWorkers('Ping');

                    DEBUG && print("Master::masterLoop(): case /10 end\n\n");

                    break;
            }

            DEBUG && print("Master::masterLoop(): sleep\n");

            time_sleep_until(ceil(microtime(true)));
        }

        DEBUG && print("Master::masterLoop(): end\n\n");

    }

// protected static function masterLoop ()


    /**
     * Заполнение очереди процедуры <code>Nslookup</code>.
     * 
     * @access protected
     * @static
     */
    protected static function populateNslookupQueue ()
    {
        DEBUG && print("Master::populateNslookupQueue(): begin\n");
        DEBUG && print("Master::populateNslookupQueue(): Redis->sUnionStore('queue:nslookup', 'hosts:enabled')\n");

        RKS::getInstance()->sUnionStore('queue:nslookup', 'hosts:enabled');

        DEBUG && print("Master::populateNslookupQueue(): end\n\n");

    }

// protected static function populateNslookupQueue ()


    /**
     * Заполнение очередей процедуры <code>Ping</code>.
     * 
     * @access protected
     * @static
     */
    protected static function populatePingQueues ()
    {
        DEBUG && print("Master::populatePingQueues(): begin\n");

        $redis = RKS::getInstance();
        $hosts = $redis->sMembers('hosts:enabled');

        DEBUG && printf("Master::populatePingQueues(): Redis->sMembers('host:enabled')\n%s\n",
                        var_export($hosts, true));


        foreach ($hosts as $label)
        {
            DEBUG && printf("Master::populatePingQueues(): begin label '%s'\n",
                            $label);

            $data = $redis->hGetAll('hosts:data:' . $label);

            DEBUG && printf("Master::populatePingQueues(): Redis->hGetAll('host:data:%s')\n%s\n",
                            $label, var_export($data, true));

            if (!$data || empty($data['addr']))
            {
                DEBUG && printf("Master::populatePingQueues(): host '%s' not ready\n",
                                $label);
                continue;
            }

            switch ($data['state'] & 0x3)
            {
                case 0: $queue = 'failed';
                case 2: $queue = 'prefailed';
                case 3: default: $queue = 'ok';
            }

            DEBUG && printf("Master::populatePingQueues(): Redis->sAdd('queue:ping:%s', '%s')\n",
                            $queue, $label);

            $redis->sAdd('queue:ping:' . $queue, $label);

            DEBUG && printf("Master::populatePingQueues(): end label '%s'\n",
                            $label);
        }

        DEBUG && print("Master::populatePingQueues(): end\n\n");

    }

// protected static function populatePingQueues ()


    /**
     * Сброс состояний и статусов хостов в кэше Redis и СУБД.
     * 
     * @param int $status
     * @access protected
     * @static
     */
    protected static function resetHostsStatuses ($status)
    {
        DEBUG && printf("Master::resetHostsStatuses(%d): begin\n", $status);
        
        DB::open();
        
        $redis = RKS::getInstance();
        $hosts = $redis->sMembers('hosts:enabled');

        DEBUG && printf("Master::resetHostsStatuses(): Redis->sMembers('host:enabled')\n%s\n",
                        var_export($hosts, true));

        foreach ($hosts as $label)
        {
            DEBUG && printf("Master::resetHostsStatuses(): begin label '%s'\n",
                            $label);

            $data = $redis->hGetAll('hosts:data:' . $label);

            DEBUG && printf("Master::resetHostsStatuses(): Redis->hGetAll('host:data:%s')\n%s\n",
                            $label, var_export($data, true));

            if (!$data || empty($data['addr']))
            {
                DEBUG && printf("Master::resetHostsStatuses(): host '%s' not ready\n",
                                $label);
                continue;
            }

            $data_out = ['state' => 0,
                'status' => $status,
            ];
            
            DEBUG && printf("Master::resetHostsStatuses(): Redis->hMset('host:data:%s', %s)\n",
                            $label, var_export($data_out, true));

            $redis->hMset('hosts:data:' . $label, $data_out);
            
            DBQueries::dataPush($data['id'], $status, 0);
            
            DEBUG && printf("Master::resetHostsStatuses(): end label '%s'\n",
                            $label);
        }
        
        DB::close();
        
        DEBUG && print("Master::resetHostsStatuses(): end\n\n");

    }

// protected static function resetHostsStatuses ()


    /**
     * Заполнение слотов, выделяемых Воркерам.
     * 
     * @access protected
     * @static
     */
    protected static function recalcSlots ()
    {
        DEBUG && print("Master::recalcSlots(): begin\n");

        if (Config::$fork_threads)
        {
            DEBUG && printf("Master::recalcSlots(): preconfigured size = %d\n",
                            Config::$fork_threads);

            if (count(static::$pid_slots) != Config::$fork_threads)
            {
                static::$pid_slots = range(1, Config::$fork_threads);
            }
        }
        else
        {
            $cnt = min(32, count(RKS::getInstance()->sMembers('hosts:enabled')));

            DEBUG && printf("Master::recalcSlots(): dynamic size = %d\n", $cnt);

            static::$pid_slots = range(1, $cnt);

            RKS::close();
        }

        DEBUG && print("Master::recalcSlots(): end\n\n");

    }

// protected static function recalcSlots ()


    /**
     * Предварительная загрузка классов.
     * 
     * @access protected
     * @static
     */
    protected static function preloadClasses ()
    {
        DEBUG && print("Master::preloadClasses(): begin\n");
        
        $classPrefix = preg_replace("~\\\\Daemon$~", '', __NAMESPACE__);
        
        $classList = [
            'Assets\DB',
            'Assets\DBQueries',
            'Assets\Host',
            'Assets\HostStatuses',
            'Assets\RKS',
            'Assets\SystemNslookupWrapper',
            'Assets\SystemPingWrapper',
            'Assets\WorkerBaseTrait',
            
            'Daemon\NslookupWorker',
            'Daemon\PingWorker',
            
        ];
        
        foreach ($classList as $classSuffix)
        {
            $className = $classPrefix . '\\' . $classSuffix;
            
            DEBUG && printf("Master::preloadClasses(): loading '%s'\n",
                            $className);

            class_exists($className);
        }
        
        DEBUG && print("Master::preloadClasses(): end\n\n");

    }

// protected static function preloadClasses ()


    /**
     * Запуск Воркеров выбранного типа.
     * 
     * @param string $type
     * @throws \Ganzal\Lulz\Pinger\Assets\Exceptions\Worker_Fork_Fail_Exception
     */
    protected static function launchWorkers ($type)
    {
        DEBUG && printf("Master::launchWorkers(%s)/master: begin\n", $type);

        // обновление слотов Воркеров
        static::recalcSlots();

        // полное квалифицированное имя запускаемого метода Воркера
        $forkCallback = __NAMESPACE__ . '\\' . $type . 'Worker::main';

        DEBUG && printf("Master::launchWorkers()/master: forkCallback = %s\n",
                        $forkCallback);

        // запуск Воркеров во все доступные слоты
        while ($slot = array_shift(static::$pid_slots))
        {
            ++static::$fork_num;

            DEBUG && printf("Master::launchWorkers()/master: slot #%d for fork #%d\n",
                            $slot, static::$fork_num);


            // попытка запуска дочернего процесса
            $pid = @pcntl_fork();

            if ($pid == -1)
            {
                // ошибка ветвления
                throw new Exceptions\Worker_Fork_Fail_Exception;
            }
            elseif ($pid)
            {
                //
                // рутины родительского процесса
                //
                DEBUG && printf("Master::launchWorkers()/master: forked with PID %d to slot #%d\n",
                                $pid, $slot);

                // регистрация дочернего процесса в списоке активных заданий
                static::$jobs[$pid] = $slot;

                DEBUG && printf("Master::launchWorkers()/master: jobs\n%s\n",
                                var_export(static::$jobs, true));

                // продолжаем цикл Мастера
                DEBUG && print("Master::launchWorkers()/master: fill next slot\n");

                continue;
            }

            //
            // рутины дочернего процесса
            //
            
            cli_set_process_title('PingerService/' . $type . 'Worker');

            DEBUG && printf("Master::launchWorkers()/worker/%d: begin\n",
                            static::$fork_num);

            // закрытие стандартных дескрипторов на случай, если
            // Мастер запускался напрямую, а не через SBINDIR/pinger.
            if ('resource' == gettype(STDIN))
            {
                DEBUG && printf("Master::launchWorkers()/worker/%d: @fclose(STDIN);\n",
                                static::$fork_num);
                @fclose(STDIN);
            }

            if ('resource' == gettype(STDOUT))
            {
                DEBUG && printf("Master::launchWorkers()/worker/%d: @fclose(STDOUT);\n",
                                static::$fork_num);
                @fclose(STDOUT);
            }

            if ('resource' == gettype(STDERR))
            {
                DEBUG && printf("Master::launchWorkers()/worker/%d: @fclose(STDERR);\n",
                                static::$fork_num);
                @fclose(STDERR);
            }

            // перемещение журнала
            DEBUG && printf("Master::launchWorkers()/worker/%d: TwinLog::rename(PINGER_LOGFILE, '%sWorker', %d)\n",
                            static::$fork_num, $type, $slot);

            TwinLog::rename(PINGER_LOGDIR, $type . 'Worker', $slot);

            call_user_func($forkCallback);

            DEBUG && printf("Master::launchWorkers()/worker/%d: end\n\n",
                            static::$fork_num);

            TwinLog::kill(true);

            // хорошо завершились
            exit(0);
        } // while (static::$master_loop)

        DEBUG && print("Master::launchWorkers()/master: end\n\n");

    }

// protected static function launchWorkers ($type)


    /**
     * Инициализация перехвата сигналов мастером.
     * 
     * @return void
     * @access protected
     * @static
     */
    protected static function initSignalHandlers ()
    {
        DEBUG && print("Master::initSignalHandlers(): begin\n");

        /**
         * Сигналы, которые невозможно перехватить:
         *
         * pcntl_signal(SIGKILL, SIG_DFL);
         * pcntl_signal(SIGSTOP, SIG_DFL);
         * 
         */
        /**
         * Следующие сигналы игнорируются:
         */
        // ошибка PIPE-а - пока без обработки
        pcntl_signal(SIGPIPE, SIG_IGN);

        // пользовательский сигнал №1 - пока без обработки
        pcntl_signal(SIGUSR1, SIG_IGN);

        // пользовательский сигнал №2 - пока без обработки
        pcntl_signal(SIGUSR2, SIG_IGN);

        // продолжение выполнения процесса - надо бы обработать, но не сейчас
        pcntl_signal(SIGCONT, SIG_IGN);

        // остановка терминала
        pcntl_signal(SIGTSTP, SIG_IGN);

        // чтение с терминала
        pcntl_signal(SIGTTIN, SIG_IGN);

        // вывод с терминала
        pcntl_signal(SIGTTOU, SIG_IGN);

        // URGENT - срочные сообщения в сокетах
        pcntl_signal(SIGURG, SIG_IGN);

        // будильник на виртуальном таймере
        pcntl_signal(SIGVTALRM, SIG_IGN);

        // таймер в профайлере
        pcntl_signal(SIGPROF, SIG_IGN);

        // смена размера терминала - tput cols, tput lines, ncurses - интригует, но не сейчас
        pcntl_signal(SIGWINCH, SIG_IGN);

        // пулл ввода-вывода и бла-бла-бла
        pcntl_signal(SIGPOLL, SIG_IGN);

        // синоним POLL
        pcntl_signal(SIGIO, SIG_IGN);

        // обратить внинмание на источник питания - пора сохранить данные? мы всё теряем
        pcntl_signal(SIGPWR, SIG_IGN);

        // тут POSIX и сами запутались
        pcntl_signal(SIGSYS, SIG_IGN);

        // синоним SYS
        pcntl_signal(SIGBABY, SIG_IGN);

        /**
         * На эти сигналы ответит система:
         */
        // SEGMENTATION VIOLATION - утекать плохо
        pcntl_signal(SIGSEGV, SIG_DFL);

        // ILLEGAL INSTRUCTION - все должно быть легально
        pcntl_signal(SIGILL, SIG_DFL);

        // ловушек не используем
        pcntl_signal(SIGTRAP, SIG_DFL);

        // IO TRAP - не наше
        pcntl_signal(SIGIOT, SIG_DFL);

        // система контролирует обращения к памяти
        pcntl_signal(SIGBUS, SIG_DFL);

        // FLOATING POINT EXCEPTION
        pcntl_signal(SIGFPE, SIG_DFL);

        // STACK FAULT
        pcntl_signal(SIGSTKFLT, SIG_DFL);

        // лимит процессорного времени
        pcntl_signal(SIGXCPU, SIG_DFL);

        // лимит размера файла - пока никак, но для журналов сгодится
        pcntl_signal(SIGXFSZ, SIG_DFL);

        /**
         * А эти сигналы обработаем мы:
         */
        // завершение потомка
        pcntl_signal(SIGCHLD, "static::masterSignalHandler");

        // синоним CHLD
        pcntl_signal(SIGCLD, "static::masterSignalHandler");

        // по будильнику мы прерываем ожидание ради похорон зомби
        pcntl_signal(SIGALRM, "static::masterSignalHandler");

        // HANG UP - потеря терминала. многие трактуют как рестарт, мы - как стандартное завершение
        pcntl_signal(SIGHUP, "static::masterSignalHandler");

        // INTERRUPT - прерывание выполнения. наше стандартное завершение
        pcntl_signal(SIGINT, "static::masterSignalHandler");

        // и еще один вариант стандартного завершения
        pcntl_signal(SIGQUIT, "static::masterSignalHandler");

        // и еще
        pcntl_signal(SIGTERM, "static::masterSignalHandler");

        // преждевременное прерывание. ПОДУМАЕМ, а пока - стандартное завершение
        pcntl_signal(SIGABRT, "static::masterSignalHandler");

        // заводим будильник
        pcntl_alarm(10);

        DEBUG && print("Master::initSignalHandlers(): end\n\n");

    }

// protected static function initSignalHandlers ()


    /**
     * Обработка сигналов Мастером.
     * 
     * @param int $signo Код сигнала
     * @param mixed $pid Идентификатор процесса для обработки
     * @param mixed $status Статус завершения процесса
     * @throws \Ganzal\Lulz\Pinger\Assets\Exceptions\Master_Bad_Signal_Exception
     * @return void
     * @access protected
     * @static
     */
    protected static function masterSignalHandler ($signo, $pid = null, $status = null)
    {
        DEBUG && printf("Master::masterSignalHandler(%s, %s, %s): begin\n",
                        var_export($signo, true), var_export($pid, true),
                        var_export($status, true)
        );

        switch ($signo)
        {
            case SIGHUP:
            case SIGINT:
            case SIGQUIT:
            case SIGTERM:
            case SIGABRT:
                DEBUG && print ("Master::masterSignalHandler(): master_loop = false\n");

                static::$master_loop = false;
                pcntl_signal_dispatch();
                break;

            case SIGALRM:
            case SIGCHLD:
            case SIGCLD:

                // Пид не указан - сигнал от системы. Уточняем кто умер.
                if (!$pid)
                {
                    $pid = pcntl_waitpid(-1, $status, WNOHANG);
                }

                // Дожидаемся окончания очереди умерших.
                while ($pid > 0)
                {
                    if ($pid && isset(static::$jobs[$pid]))
                    {
                        DEBUG && print("Master::masterSignalHandler(): ((**))\n");

                        $exitCode = pcntl_wexitstatus($status);
                        if ($exitCode != 0)
                        {
                            DEBUG && printf("Master::masterSignalHandler(): %d exited with status %d\n",
                                            $pid, $exitCode);
                        }
                        // этот демон не требует возврата слотов в пул
                        //array_push(static::$pid_slots, static::$jobs[$pid]);
                        // удаление завершенного процесса из списка
                        unset(static::$jobs[$pid]);
                    }
                    elseif ($pid)
                    {
                        DEBUG && print ("Master::masterSignalHandler(): (())\n");

                        // пид указан, но не в нашем списке детишек!
                        // запишем в очередь сигналов, вдруг чо
                        DEBUG && printf("Master::masterSignalHandler(): adding %d to the signal queue \n",
                                        $pid);
                        static::$sig_queue[$pid] = $status;
                    }

                    $pid = pcntl_waitpid(-1, $status, WNOHANG);
                }

                pcntl_alarm(10);
                break;

            default:
                throw new Exceptions\Master_Bad_Signal_Exception($signo);
        } // switch($signo)

        DEBUG && print("Master::masterSignalHandler(): end\n\n");

    }

// protected static function masterSignalHandler ($signo, $pid = null, $status = null)

}

// class Master

# EOF