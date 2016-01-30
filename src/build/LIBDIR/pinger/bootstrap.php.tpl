<?php
/**
 * Файл первичной загрузки процессов сервиса Pinger.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */

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

DEBUG && printf("Bootstrap file: %s\nGenerated: %s\n\n", __FILE__, date('Y-m-d H:i:s', filemtime(__FILE__)));

################################################################################

if (!defined('PINGER_PREFIX'))
{
    define('PINGER_PREFIX', '@base@@prefix@');
}

if (!defined('PINGER_EPREFIX'))
{
    define('PINGER_EPREFIX', '@base@@eprefix@');
}

if (!defined('PINGER_BINDIR'))
{
    define('PINGER_BINDIR', '@base@@bindir@');
}

if (!defined('PINGER_LIBDIR'))
{
    define('PINGER_LIBDIR', '@base@@libdir@');
}

if (!defined('PINGER_SBINDIR'))
{
    define('PINGER_SBINDIR', '@base@@sbindir@');
}

if (!defined('PINGER_SYSCONFDIR'))
{
    define('PINGER_SYSCONFDIR', '@base@@sysconfdir@');
}

if (!defined('PINGER_CONF'))
{
    define('PINGER_CONF', '@base@@conf@');
}

if (!defined('PINGER_PIDFILE'))
{
    define('PINGER_PIDFILE', '@base@@pidfile@');
}

if (!defined('PINGER_LOGDIR'))
{
    define('PINGER_LOGDIR', '@base@@logdir@');
}

if (!defined('PINGER_PHP'))
{
    define('PINGER_PHP', '@php@');
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