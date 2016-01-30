<?php

declare(ticks = 1);
namespace Ganzal\Lulz\Pinger\Assets;

use Ganzal\Lulz\Pinger\Assets\Exceptions;

/**
 * Базовый код классов Воркер-процессов сервиса Pinger.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
trait WorkerBaseTrait
{

    /**
     * Признак продолжения исполнения Воркер-цикла.
     * 
     * @var boolean
     * @access protected
     * @static
     */
    protected static $worker_loop = true;


    /**
     * Регистрация обработчика сигналов Воркера.
     * 
     * @return void
     * @access protected
     * @static
     */
    protected static function initSignalHandlers ()
    {
        DEBUG && print("***Worker::initSignalHandlers(): begin\n");

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
         * Переопределение ловушек Мастера:
         */
        // завершение потомка - у воркера их нет
        pcntl_signal(SIGCHLD, SIG_IGN);

        // синоним CHLD
        pcntl_signal(SIGCLD, SIG_IGN);

        // по будильнику прерывается ожидание, ради похорон зомби.
        // позднее будет использоваться для обработки сигналов от Мастера
        // или внешнего мира. а сейчас - игнорируется
        pcntl_signal(SIGALRM, SIG_IGN);

        /**
         * Следующие сигналы обрабатываются Воркером:
         */
        // HANG UP - потеря терминала. многие трактуют как рестарт, здесь - как стандартное завершение
        pcntl_signal(SIGHUP, "static::workerSignalHandler");

        // INTERRUPT - прерывание выполнения. стандартное завершение
        pcntl_signal(SIGINT, "static::workerSignalHandler");

        // и еще один вариант стандартного завершения
        pcntl_signal(SIGQUIT, "static::workerSignalHandler");

        // и еще
        pcntl_signal(SIGTERM, "static::workerSignalHandler");

        // преждевременное прерывание. ПОДУМАЕМ, а пока - стандартное завершение
        pcntl_signal(SIGABRT, "static::workerSignalHandler");

        DEBUG && print("***Worker::initSignalHandlers(): end\n\n");

    }

// protected static function initSignalHandlers ()


    /**
     * Обработка сигналов Воркером.
     * 
     * @param int $signo Код сигнала
     * @param mixed $pid Идентификатор процесса для обработки
     * @param mixed $status Статус завершения процесса
     * @throws \Exception
     * @return void
     * @access protected
     * @static
     */
    protected static function workerSignalHandler ($signo, $pid = null, $status = null)
    {
        DEBUG && printf("***Worker::workerSignalHandler(%s, %s, %s): begin\n",
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
                DEBUG && print ("***Worker::workerSignalHandler(): worker_loop = false\n");

                static::$worker_loop = false;
                pcntl_signal_dispatch();
                break;

            default:
                throw new Exceptions\Worker_Bad_Signal_Exception($signo);
        } // switch ($signo)

    }

// protected static function workerSignalHandler ($signo, $pid = null, $status = null)

}

// trait WorkerBaseTrait, namespace Ganzal\Lulz\Pinger\Assets

# EOF