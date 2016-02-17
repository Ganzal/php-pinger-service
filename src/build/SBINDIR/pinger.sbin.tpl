#!@php@
<?php
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
 * @version 0.1.0
 */

################################################################################

require_once '@base@@libdir@/pinger/bootstrap.php';
Ganzal\Lulz\Pinger\PingerService::main($argv);

################################################################################

# EOF