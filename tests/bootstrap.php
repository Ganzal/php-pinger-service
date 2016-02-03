<?php
/**
 * Файл первичной загрузки юнит-тестов сервиса Pinger.
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

if (defined('PHPUNIT_NETBEANS_TRICK') && PHPUNIT_NETBEANS_TRICK)
{
    define('PINGER_CONF', __DIR__ . '/../nbproject/private/pinger-conf.php');
    require_once __DIR__ . '/../src/lib/pinger/bootstrap.php';
}
else
{
    if (!file_exists(__DIR__ . '/../build/bootstrap.php'))
    {
        die('Build project first.');
    }


    require __DIR__ . '/../build/bootstrap.php';
}

################################################################################

# EOF