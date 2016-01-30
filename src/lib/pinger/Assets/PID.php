<?php

namespace Ganzal\Lulz\Pinger\Assets;

/**
 * Вспомогательный класс работы с PID-файлом.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
class PID
{

    /**
     * Ресурс PID-файла, откртого функцией <code>fopen()</code>.
     * 
     * @var null|resource
     * @access protected
     * @static
     */
    protected static $handle;

    /**
     * Прочитанный из PID-файла идентификатор процесса.
     * 
     * @var int
     * @access public
     * @static
     */
    public static $pid = 0;


    /**
     * Проверка PID-файла перед запуском Мастер-процесса.
     * 
     * @throws Exceptions\PID_Open_Fail_Exception
     * @throws Exceptions\PID_Read_Fail_Exception
     * @throws Exceptions\PID_Lock_Fail_Exception
     * @throws Exceptions\PID_Unlink_Fail_Exception
     * @return void
     * @access public
     * @static
     */
    public static function PreLock ()
    {

        // поиск файла - не должно быть
        if (file_exists(PINGER_PIDFILE))
        {
            // файл существует
            // попытка открыть для чтения
            // ожидается успешное открытие как признак мусорного файла
            static::$handle = @fopen(PINGER_PIDFILE, 'rb');
            if (!static::$handle)
            {
                throw new Exceptions\PID_Open_Fail_Exception();
            }

            // успешно открыт для чтения
            // попытка заблокировать
            // ожидается успешная блокировка как признак мусорного файла
            $l = @flock(static::$handle, LOCK_EX | LOCK_NB);
            if (!$l)
            {
                // блокировка не удалась
                // возможно существует запущенный процесс
                // попытка прочитать идентификатор процесса
                @flock(static::$handle, LOCK_UN);

                $pid = @fread(static::$handle, 8);
                if (false === $pid)
                {
                    // попытка чтения не удалась
                    throw new Exceptions\PID_Read_Fail_Exception();
                }

                // процесс существует
                static::$pid = (int) $pid;

                throw new Exceptions\PID_Lock_Fail_Exception();
            }

            // успешно открыт для чтения и заблокирован
            // признается мусором и удаляется

            @flock(static::$handle, LOCK_UN);
            @fclose(static::$handle);
            $u = @unlink(static::FILENAME);

            if (!$u)
            {
                // попытка удаления не удалась
                throw new Exceptions\PID_Unlink_Fail_Exception();
            }
        }

    }

// public static function PreLock ()


    /**
     * Блокировка PID-файла Мастер-процессом.
     * 
     * @throws Exceptions\PID_Open_Fail_Exception
     * @throws Exceptions\PID_Lock_Fail_Exception
     * @throws Exceptions\PID_Truncate_Fail_Exception
     * @throws Exceptions\PID_Write_Fail_Exception
     * @return void
     * @access public
     * @static
     */
    public static function Lock ()
    {
        DEBUG && print("PID::Lock(): begin\n");
        // получение идентификатор Мастер-процесса
        $pid = posix_getpid();

        DEBUG && printf("PID::Lock(): PID = %d\n", $pid);

        // попытка открытия PID-файла для записи
        static::$handle = @fopen(PINGER_PIDFILE, 'wb');
        if (false === static::$handle)
        {
            throw new Exceptions\PID_Open_Fail_Exception();
        }

        DEBUG && print ("PID::Lock(): Handle OK\n");

        // попытка блокировки PID-файла
        if (false === flock(static::$handle, LOCK_EX))
        {
            throw new Exceptions\PID_Lock_Fail_Exception();
        }

        DEBUG && print ("PID::Lock(): Lock OK\n");

        // попытка урезания PID-файла
        if (false === ftruncate(static::$handle, 0))
        {
            throw new Exceptions\PID_Truncate_Fail_Exception();
        }

        DEBUG && print ("PID::Lock(): Truncate OK\n");

        // попытка записи идентификатора процесса в PID-файл
        if (false === fwrite(static::$handle, $pid))
        {
            throw new Exceptions\PID_Write_Fail_Exception();
        }

        // успешное завершение
        DEBUG && print ("PID::Lock(): Write OK\n");
        DEBUG && print ("PID::Lock(): end\n\n");

    }

// public static function Lock ()


    /**
     * Снятие блокировки с PID-файла Мастер-процессом.
     * 
     * @throws Exceptions\PID_Truncate_Fail_Exception
     * @throws Exceptions\PID_Unlink_Fail_Exception
     * @access public
     * @static
     */
    public static function Unlock ()
    {
        DEBUG && print ("PID::Unlock(): begin\n");

        if (false === ftruncate(static::$handle, 0))
        {
            throw new Exceptions\PID_Truncate_Fail_Exception();
        }

        DEBUG && print ("PID::Unlock(): Truncate OK\n");

        flock(static::$handle, LOCK_UN);
        fclose(static::$handle);

        $u = unlink(PINGER_PIDFILE);

        if (!$u)
        {
            throw new Exceptions\PID_Unlink_Fail_Exception();
        }

        DEBUG && print ("PID::Unlock(): Unlink OK\n");
        DEBUG && print ("PID::Unlock(): end\n\n");

    }

// public static function Unlock ()


    /**
     * Чтение идентификатора процесса из PID-файла.
     * 
     * @return int
     * @throws Exceptions\PID_Non_Existent_Exception
     * @throws Exceptions\PID_Open_Fail_Exception
     * @throws Exceptions\PID_Lock_Success_Exception
     * @throws Exceptions\PID_Read_Fail_Exception
     * @access public
     * @static
     */
    public static function Read ()
    {
        clearstatcache(false, PINGER_PIDFILE);

        // поиск файла
        // ожидается что файл существует
        if (!file_exists(PINGER_PIDFILE))
        {
            throw new Exceptions\PID_Non_Existent_Exception();
        }

        // файл существует
        // попытка открыть для чтения
        // ожидается успешное открытие
        $f = @fopen(PINGER_PIDFILE, 'rb+');
        if (!$f)
        {
            throw new Exceptions\PID_Open_Fail_Exception();
        }

        // успешно открыт для чтения
        // попытка заблокировать
        // ожидается неудачная блокировка как признак работающего процесса
        $b = @flock($f, LOCK_EX | LOCK_NB);
        if ($b)
        {
            @flock($f, LOCK_UN);
            throw new Exceptions\PID_Lock_Success_Exception();
        }

        // не удалось заблокировать
        // попытка чтения
        // ожидается успешное чтение
        $pid = fread($f, 8);
        if (false === $pid)
        {
            throw new Exceptions\PID_Read_Fail_Exception();
        }

        // возврат идентификатор процесса
        return (int) $pid;

    } // public static function Read ()

} // class PID

# EOF