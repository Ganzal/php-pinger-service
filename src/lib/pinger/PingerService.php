<?php

namespace Ganzal\Lulz\Pinger;

use \com\ganzal\repo\bernd\TwinLog;

/**
 * Системный сервис Pinger.
 * 
 * <p>Запускается от имени суперпользователя, переключаясь 
 *  к пользователю:группе "pinger:pinger"</p>
 * 
 * <p>Команды:<ul>
 *  <ul><code>start</code></li>
 *  <ul><code>stop</code></li>
 *  <ul><code>status</code></li>
 *  <ul><code>restart</code></li>
 *  <ul><code>reload</code></li>
 *  <ul><code>help</code></li>
 * </ul></p>
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1-dev
 */
class PingerService
{


    /**
     * Вход в управление сервисом Pinger.
     * 
     * @param array $argv Копия массива <code>$argv</code> с аргументами вызова скрипта.
     * @access public
     * @static
     */
    public static function main ($argv)
    {
        if ('cli' !== PHP_SAPI)
        {
            echo "CLI only!\n";
            exit(1);
        }
        
        if (0 !== posix_geteuid())
        {
            echo "Error: Pinger Service must be launched as root!\n";
            exit(1);
        }

        $argv += array_fill(0, 4, null);

        switch ($argv[1])
        {
            case 'start':
                static::cmdStart();
                break;

            case 'stop':
                static::cmdStop();
                break;

            case 'restart':
                static::cmdRestart();
                break;

            case 'reload':
                static::cmdReload();
                break;

            case 'status':
                static::cmdStatus();
                break;

            case 'help':
            default:
                static::cmdHelp($argv[1], $argv[2]);
                break;
        }

        exit(0);

    }

// public static function main ($argv)


    /**
     * Запуск сервиса.
     * 
     * @access protected
     * @static
     */
    protected static function cmdStart ()
    {
        echo "Starting Pinger service... ";

        try
        {
            // проверка PID-файла
            Assets\PID::PreLock();

            // попытка запуска Мастер-процесса
            $pid = @pcntl_fork();

            if ($pid == -1)
            {
                // ошибка ветвления
                throw new Assets\Exceptions\Master_Fork_Fail_Exception();
            }
            elseif ($pid)
            {
                //
                // рутины родительского процесса - сценария SBINDIR/pinger
                //
                printf(" [OK]\nLooks like Master forked with PID=%d\n", $pid);
                exit(0);
            }
            else
            {
                //
                // рутины дочернего процесса - Мастер-процесса
                //
                
                /* @todo Реализовать переключение UID:GID процесса. */
//                posix_setuid(50000);
//                posix_setgid(50000);
                // открытие журналов
                TwinLog::init(PINGER_LOGDIR, 'Master');

                // закрытие дескрипторов
                @fclose(STDIN);
                @fclose(STDOUT);
                @fclose(STDERR);

                // вход в Мастер-процесс
                Daemon\Master::main();

                // закрытие журналов
                TwinLog::kill();

                // успешный выход
                // exit(0) не используется для возможности перезапуска
                // с применением этого метода
            }
        }
        catch (Assets\Exceptions\PID_Open_Fail_Exception $e)
        {
            echo " [FAIL]\n PID-file exists but unreadable\n";
            exit(1);
        }
        catch (Assets\Exceptions\PID_Lock_Fail_Exception $e)
        {
            printf("\n Daemon already running with PID=%d", Assets\PID::$pid);
            exit(1);
        }
        catch (Assets\Exceptions\PID_Read_Fail_Exception $e)
        {
            echo " [FAIL]\n PID-file exists and locked\n";
            echo " PID-file reading failed!\n";
            exit(1);
        }
        catch (Assets\Exceptions\PID_Unlink_Fail_Exception $e)
        {
            echo " [FAIL]\n PID-file exists but failed to unlink\n";
            exit(1);
        }
        catch (Assets\Exceptions\Master_Fork_Fail_Exception $e)
        {
            echo " [FAIL]\n fork() failed\n";
            exit(1);
        }
        catch (\Exception $e)
        {
            echo " [FAIL]\n Unexpected exception:\n";
            var_export($e->getMessage());
            var_export($e->getTraceAsString());
            exit(1);
        }

    }

// protected static function cmdStart ()


    /**
     * Остановка сервиса.
     * 
     * @access protected
     * @static
     */
    protected static function cmdStop ()
    {
        echo "Stopping Pinger service: ";

        try
        {
            // попытка чтения PID-файла
            $pid = Assets\PID::Read();

            // отправка сигнала Мастер-процессу
            $kill = posix_kill($pid, SIGQUIT);

            if (false === $kill)
            {
                throw new Assets\Exceptions\PID_Kill_Fail_Exception();
            }

            // ожидание завершения Мастер-процесса
            echo 'PID ', $pid, ' ';

            $i = 0;
            while (true)
            {
                $i++;
                echo '.';
                sleep(1);

                clearstatcache(false, PINGER_PIDFILE);
                if (!file_exists(PINGER_PIDFILE))
                {
                    break;
                }

                if (15 == $i)
                {
                    echo "timeout ;-(\n";
                    exit(2);
                }
            }

            // успешный выход
            // exit(0) не используется для возможности перезапуска
            // с применением этого метода
            echo " [OK]\n";
        }
        catch (Assets\Exceptions\PID_Non_Existent_Exception $e)
        {
            echo " [FAIL]\n PID-file not exists\n";
            exit(1);
        }
        catch (Assets\Exceptions\PID_Open_Fail_Exception $e)
        {
            echo " [FAIL]\n PID-file exists but unreadable\n";
            exit(1);
        }
        catch (Assets\Exceptions\PID_Lock_Success_Exception $e)
        {
            echo " [FAIL]\n PID-file exists but not locked\nDaemon tragically died\n";
            exit(1);
        }
        catch (Assets\Exceptions\PID_Read_Fail_Exception $e)
        {
            echo " [FAIL]\n PID-file reading failed!\n";
            exit(1);
        }
        catch (Assets\Exceptions\PID_Kill_Fail_Exception $e)
        {
            echo " [FAIL]\n Error sending SIGKILL to daemon process!\n";
            exit(1);
        }
        catch (\Exception $e)
        {
            echo " [FAIL]\n Unexpected exception:\n";
            var_export($e->getMessage());
            var_export($e->getTraceAsString());
            exit(1);
        }

    }

// protected static function cmdStop ()


    /**
     * Перезапуск сервиса.
     * 
     * @access protected
     * @static
     */
    protected static function cmdRestart ()
    {
        static::cmdStop();
        static::cmdStart();

    }

// protected static function cmdRestart ()


    /**
     * Перечитка конфигурации.
     * 
     * @access protected
     * @static
     */
    protected static function cmdReload ()
    {
        echo "Not implemented yet.\n";

    }

// protected static function cmdReload ()


    /**
     * Вывод текущего состояния сервиса.
     * 
     * @access protected
     * @static
     */
    protected static function cmdStatus ()
    {
        try
        {
            $pid = Assets\PID::Read();

            echo "Running with PID={$pid}\n";
            exit(0);
        }
        catch (Assets\Exceptions\PID_Non_Existent_Exception $e)
        {
            echo "Currently not running\n";
            exit(0);
        }
        catch (Assets\Exceptions\PID_Open_Fail_Exception $e)
        {
            echo "PID-file exists but unreadable\n";
            exit(1);
        }
        catch (Assets\Exceptions\PID_Lock_Success_Exception $e)
        {
            echo "PID-file exists but not locked\nDaemon tragically died\n";
            exit(1);
        }
        catch (Assets\Exceptions\PID_Read_Fail_Exception $e)
        {
            echo "PID-file reading failed!\n";
            exit(1);
        }
        catch (\Exception $e)
        {
            var_export($e->getMessage());
        }

    }

// protected static function cmdStatus ()


    /**
     * Вывод справочной информации.
     * 
     * @param mixed $arg1
     * @access protected
     * @static
     */
    protected static function cmdHelp ($arg1 = null)
    {
        fwrite(STDERR,
                <<<PINGER_HELP
Usage: service pinger {start|stop|restart|reload|status|help}
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

// protected static function cmdHelp ($arg1 = null)

}

// class PingerService

# EOF