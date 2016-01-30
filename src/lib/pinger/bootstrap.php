<?php

/**
 * Файл первичной загрузки процессов сервиса Pinger.
 * Версия для запуска юнит-тестов в IDE NetBeans.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */

$prefix = __DIR__ . '/../..';

################################################################################

if (!defined('DEBUG_AUTOLOADER'))
{
    define('DEBUG_AUTOLOADER',
        isset($_SERVER['DEBUG_AUTOLOADER']) && !!$_SERVER['DEBUG_AUTOLOADER']
    );
}

if (!defined('DEBUG'))
{
    define('DEBUG',
        isset($_SERVER['DEBUG']) && !!$_SERVER['DEBUG']
    );
}

################################################################################

DEBUG && printf("Bootstrap file: %s\nUpdated: %s\n\n", __FILE__, date('Y-m-d H:i:s', filemtime(__FILE__)));

################################################################################

if (!defined('PINGER_PREFIX'))
{
    define('PINGER_PREFIX', $prefix);
}

if (!defined('PINGER_EPREFIX'))
{
    define('PINGER_EPREFIX', $prefix);
}

if (!defined('PINGER_BINDIR'))
{
    define('PINGER_BINDIR', PINGER_EPREFIX . '/bin');
}

if (!defined('PINGER_LIBDIR'))
{
    define('PINGER_LIBDIR', PINGER_EPREFIX .'/lib');
}

if (!defined('PINGER_SBINDIR'))
{
    define('PINGER_SBINDIR', PINGER_EPREFIX . '/sbin');
}

if (!defined('PINGER_SYSCONFDIR'))
{
    define('PINGER_SYSCONFDIR', PINGER_EPREFIX . '/etc');
}

if (!defined('PINGER_CONF'))
{
    define('PINGER_CONF', PINGER_SYSCONFDIR . '/pinger-config.php');
}

if (!defined('PINGER_PIDFILE'))
{
    define('PINGER_PIDFILE', '/run/pinger.pid');
}

if (!defined('PINGER_LOGDIR'))
{
    define('PINGER_LOGDIR', '/var/log/pinger');
}

if (!defined('PINGER_PHP'))
{
    define('PINGER_PHP', '/usr/bin/php');
}

if ( !defined('PINGER_AUTOLOAD'))
{
    spl_autoload_register(function ($class) {
        (DEBUG || DEBUG_AUTOLOADER) && printf("Autoloader #1: call(%s)\n", $class);
        
        $real_class = preg_replace('~^Ganzal/Lulz/Pinger/~', '', str_replace('\\', '/', $class));
        $try_file = PINGER_LIBDIR . '/pinger/' . $real_class . '.php';
        
        (DEBUG || DEBUG_AUTOLOADER) && printf("Autoloader #1: real_class = %s\n", $real_class);
        (DEBUG || DEBUG_AUTOLOADER) && printf("Autoloader #1: try_path = %s\n", $try_file);
        
        if (file_exists($try_file))
        {
            (DEBUG || DEBUG_AUTOLOADER) && printf("Autoloader #1: file exists\n");
            
            include $try_file;
            return true;
        }
        
        (DEBUG || DEBUG_AUTOLOADER) && printf("Autoloader #1: file not exists\n");
        
        return false;
    });
    
    spl_autoload_register(function ($class) {
        (DEBUG || DEBUG_AUTOLOADER) && printf("Autoloader #2: call(%s)\n", $class);

        $real_class = preg_replace('~^com/ganzal/repo/bernd/~', '', str_replace('\\', '/', $class));
        $try_file = PINGER_LIBDIR . '/pinger/contrib/' . $real_class . '.php';
        
        (DEBUG || DEBUG_AUTOLOADER) && printf("Autoloader #2: real_class = %s\n", $real_class);
        (DEBUG || DEBUG_AUTOLOADER) && printf("Autoloader #2: try_path = %s\n", $try_file);
        
        if (file_exists($try_file))
        {
            (DEBUG || DEBUG_AUTOLOADER) && printf("Autoloader #2: file exists\n");
            
            include $try_file;
            return true;
        }
        
        (DEBUG || DEBUG_AUTOLOADER) && printf("Autoloader #2: file not exists\n");
        
        return false;
    });
    
    define('PINGER_AUTOLOAD', true);
}

################################################################################

Ganzal\Lulz\Pinger\Assets\Config::configure();

################################################################################

# EOF