<?php

namespace com\ganzal\repo\bernd;

/**
 * Чуть более функциональное журналирование чем через UNIX-утилиту <code>tee</code>.
 * 
 * @author Sergey D. Ivanov <me@dev.ganzal.com>
 * 
 * @package com/ganzal/repo/bernd
 * 
 * @version 15.01.30
 * @since 15.01.30  Убраны лишние символы в сообщении открытия журнала ошибок.
 * @since 15.01.30  Появление в com\ganzal\repo\bernd\TwinLog.php
 *      на базе кода twinlog версии 15.01.30
 *      из /com/xtotra/lib/class.twinlog.php проекта com.xtotra.headquarters.
 * @since 15.01.30  Исправлена опечатка в методе stdout().
 * @since 14.09.09  Регистрация в /com/xtotra/lib/class.twinlog.php
 */
class TwinLog
{

    /**
     * Ресурс журнала ошибок.
     * 
     * @type null|resource
     * @access protected
     * @static
     */
    protected static $err_fh = null;

    /**
     * Имя журнала ошибок.
     * 
     * @type string
     * @access protected
     * @static
     */
    protected static $err_fn;

    /**
     * Микровремя инициализации класса.
     * 
     * @type float
     * @access protected
     * @static
     */
    protected static $init_utime = 0.0;

    /**
     * Ресурс журнала вывода.
     * 
     * @type null|resource
     * @access protected
     * @static
     */
    protected static $log_fh = null;

    /**
     * Имя журнала вывода.
     * 
     * @type string
     * @access protected
     * @static
     */
    protected static $log_fn = null;


    /**
     * Инициализация TwinLog.
     * Открывает файлы журналов, устанавливает обработчики ошибок и буффера вывода.
     * 
     * @param string $logdir Путь к каталогу журналов.
     * @param string $prefix Префикс журналов.
     * @param string $suffix [OPTIONAL] Дополнительный суффикс журналов.
     * @return void
     * @access public
     * @static
     */
    public static function init ($logdir, $prefix, $suffix = false)
    {
        // регистрируем обработчик буффера вывода
        // двойка нужна для моментального вызова callback-метода при
        // достижении буффером длины двух и более байт
        ob_start(array(
            __CLASS__,
            'stdout'), 2);
        // 
        #ob_implicit_flush();
        // регистрируем обработчик ошибок
        set_error_handler(array(
            __CLASS__,
            'stderr'));

        static::rename($logdir, $prefix, $suffix);

    }

// public static function init ($logdir, $prefix, $suffix=false)


    /**
     * 
     * @param string $logdir Путь к каталогу журналов.
     * @param string $prefix Префикс журналов.
     * @param boolean|string $suffix [OPTIONAL] Дополнительный суффикс журналов.
     * @param boolean $purge [OPTIONAL] Удаление пустых журналов.
     * @return void
     * @access public
     * @static
     */
    public static function rename ($logdir, $prefix, $suffix = false, $purge = false)
    {
        if (static::$log_fh || static::$err_fh)
        {
//            $msg = sprintf("[%13s] TwinLog renaming! Have a nice day!\n"
//                    , static::event_offset()
//            );
//
//            static::stderr_fwrite($msg);
//            static::stdout_fwrite($msg);

            fclose(static::$err_fh);
            fclose(static::$log_fh);
            
            if ($purge)
            {
                if (!filesize(static::$err_fn))
                {
                    @unlink(static::$err_fn);
                }
                
                if (!filesize(static::$log_fn))
                {
                    @unlink(static::$log_fn);
                }
            }
            
        }
        
        // сбрасываем микровремя инициализации
        static::$init_utime = microtime(true);
        // разбиваем на секунды и микросекунды
        list($sec, $usec) = explode('.', static::$init_utime);

        // прочищаем суффикс
        $suffix = trim($suffix);
        if (!strlen($suffix))
        {
            $suffix = false;
        }

        // генерируем префикс журналов
        $logs_prefix = sprintf("%s/%s-%s%s"
                , $logdir
                , $prefix
                , date('Y-md-Hi-s', $sec)
                , ($suffix ? '-' . $suffix : '')
        );

        // открываем журналы
        static::$err_fn = $logs_prefix . '.err';
        static::$log_fn = $logs_prefix . '.log';

        static::$err_fh = fopen(static::$err_fn, 'ab');
        static::$log_fh = fopen(static::$log_fn, 'ab');

        // готовим дату запуска
//        $init_udate = sprintf("%s.%d", date('Y-m-d H:i:s', $sec), $usec);

        // записываем заголовок в журнале ошибок.
//        fwrite(static::$err_fh,
//                sprintf("\nError log opened by %s%s at %s\n"
//                        , $prefix
//                        ,
//                        ($suffix ? '-' . $suffix : '')
//                        , $init_udate
//        ));

        // записываем заголовок в журнале вывода.
//        fwrite(static::$log_fh,
//                sprintf("\nMessage log opened by %s%s at %s\n"
//                        , $prefix
//                        ,
//                        ($suffix ? '+' . $suffix : '')
//                        , $init_udate
//        ));

        // готовим сообщение о готовности TwinLog
//        $msg = sprintf("[%13s] TwinLog ready!\n"
//                , static::event_offset()
//        );

        // записываем сообщение о готовности в журнал ошибок...
//        static::stderr_fwrite($msg);
        // и журнал вывода.
//        static::stdout_fwrite($msg);

    }

// public static function rename ($logdir, $prefix, $suffix = false)


    /**
     * Возвращает отступ события относительно времени инициализации TwinLog.
     * 
     * @return float Разница между текущим значением <code>microtime(true)</code> и <code>static:$init_utime</code>.
     * @access public
     * @static
     */
    public static function event_offset ()
    {
        return sprintf("%06.6f", microtime(true) - static::$init_utime);

    }

// public static function event_offset ()


    /**
     * Обработчик буффера вывода.
     * Callback-метод для <code>ob_start()</code>.
     * 
     * @param string $string Данные, поступившие в буффер вывода.
     * @return string Пустая строка при подавлении вывода или повтор данных если доступен <code>STDOUT</code>
     *                  или доступен буффер вывода более низкого уровня <code>ob_get_level()</code>.
     * @access public
     * @static
     */
    public static function stdout ($string)
    {
        static::stdout_fwrite($string);

        return ('resource' == gettype(STDOUT) || 1 < ob_get_level()) ? $string : '';

    }

// public static function stdout ($string)


    /**
     * Обработчик ошибок.
     * Callback-метод для <code>set_error_handler()</code>.
     * 
     * @param int $errno Номер ошибки.
     * @param string $errstr Строка ошибки.
     * @param string $errfile Файл-инициатор ошибки.
     * @param int $errline Строка-инициатор ошибки.
     * @return void
     * @access public
     * @static
     */
    public static function stderr ($errno, $errstr, $errfile, $errline)
    {
        switch ($errno)
        {
            case E_WARNING: $e_type = 'E_WARNING';
                break;
            case E_NOTICE: $e_type = 'E_NOTICE';
                break;
            case E_USER_ERROR: $e_type = 'E_USER_ERROR';
                break;
            case E_USER_WARNING: $e_type = 'E_USER_WARNING';
                break;
            case E_USER_NOTICE: $e_type = 'E_USER_NOTICE';
                break;

            case E_RECOVERABLE_ERROR: $e_type = 'E_RECOVERABLE_ERROR';
                break;
            case E_DEPRECATED: $e_type = 'E_DEPRECATED';
                break;
            case E_USER_DEPRECATED: $e_type = 'E_USER_DEPRECATED';
                break;

            case E_ALL: $e_type = 'E_ALL';
                break;
            default: $e_type = 'E_UNKNOWN #' . $errno;
                break;
        }

        $msg = sprintf("[%13s] %s: %s at %s:%d\n"
                , static::event_offset()
                , $e_type
                , $errstr
                , $errfile
                , $errline
        );

        // запись в журнал ошибок
        static::stderr_fwrite($msg);

        // вывод в поток для перехвата буффером вывода
        echo $msg;

    }

// public static function stderr ($errno, $errstr, $errfile, $errline)


    /**
     * Записывает сообщение в журнал вывода.
     * 
     * @param string $string Строка сообщения.
     * @return void
     * @access private
     * @static
     */
    private static function stdout_fwrite ($string)
    {
        fwrite(static::$log_fh, $string);

    }

// private static function stdout_fwrite ($string)


    /**
     * Записывает сообщение в журнал ошибок.
     * 
     * @param string $string Строка сообщения.
     * @return void
     * @access private
     * @static
     */
    private static function stderr_fwrite ($string)
    {
        fwrite(static::$err_fh, $string);

    }

// private static function stderr_fwrite ($string)


    /**
     * Завершает сеанс TwinLog.
     * 
     * @param boolean $purge [OPTIONAL] Удаление пустых журналов.
     * @return void
     * @access public
     * @static
     */
    public static function kill ($purge = false)
    {
//        $msg = sprintf("[%13s] TwinLog killed! Have a nice day!\n"
//                , static::event_offset()
//        );
//
//        static::stderr_fwrite($msg);
//        static::stdout_fwrite($msg);

        fclose(static::$err_fh);
        fclose(static::$log_fh);
        

        if ($purge)
        {
            if (!filesize(static::$err_fn))
            {
                @unlink(static::$err_fn);
            }

            if (!filesize(static::$log_fn))
            {
                @unlink(static::$log_fn);
            }
        }
        
        restore_error_handler();
        ob_end_clean();

    }

// public static function kill ()

}

// class TwinLog, namespace com\ganzal\repo\bernd

# EOF