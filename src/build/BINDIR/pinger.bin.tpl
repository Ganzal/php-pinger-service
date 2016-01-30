#!@php@
<?php
/**
 * Консольный интерфейс к Pinger.
 * 
 * <p>Допускает запуск от пользователя, состоящего в группе "pinger".</p>
 * 
 * <p>Команды:
 * <ul>
 *  <li><code>add LABEL FQDN</code></li>
 *  <li><code>remove LABEL</code></li>
 *  <li><code>enable LABEL</code></li>
 *  <li><code>disable LABEL</code></li>
 *  <li><code>list</code></li>
 *  <li><code>help</code></li>
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
Ganzal\Lulz\Pinger\PingerConsole::main($argv);

################################################################################

# EOF