<?php

namespace Ganzal\Lulz\Pinger\Assets;

/**
 * Библиотека запросов к СУБД MySQL.
 * 
 * @package Pinger
 * @subpackage Service
 * 
 * @author      Sergey D. Ivanov <me@dev.ganzal.com>
 * @copyright   Copyright (c) 2016, Sergey D. Ivanov
 * 
 * @version 0.1.0
 */
class DBQueries
{

    /**
     * Шаблон запросов, выполняемых методом <code>dataPush</code>.
     */
    const DATA_PUSH = <<<SQL
INSERT INTO `data`
    (`host_id`, `streak_id`, `ping_status`, `ping_state`)
VALUES
    (%d, %d, %d, %d)
SQL;


    /**
     * Выборка последней строки хоста из таблицы данных.
     * 
     * @param int $host_id
     * @return \mysqli_result|boolean
     * @access public
     * @static
     */
    public static function dataFetchLast ($host_id)
    {
        return DB::query(
                        sprintf("SELECT * FROM `data`
WHERE `host_id` = %d
ORDER BY `ping_datetime`
DESC LIMIT 1", $host_id));

    }

// public static function dataFetchLast ($host_id)


    /**
     * Вставка записи в таблицу данных.
     * 
     * @param int $host_id
     * @param int $ping_status
     * @param int $ping_state
     * @access public
     * @static
     */
    public static function dataPush ($host_id, $ping_status, $ping_state)
    {
        $res = static::dataFetchLast($host_id);

        if (!$res->num_rows)
        {
            $reset_sql = sprintf(static::DATA_PUSH, $host_id, 0, 0, 0);

            DEBUG && printf("DBQueries::dataPush()/reset: sql = %s\n",
                            $reset_sql);

            DB::query($reset_sql);
            $data = [
                'streak_id' => 0,
                'ping_status' => 0,
            ];
        }
        else
        {
            $data = $res->fetch_assoc();
        }

        if ($data['ping_status'] != $ping_status)
        {
            $data['streak_id'] ++;
        }

        $sql = sprintf(static::DATA_PUSH, $host_id, $data['streak_id'],
                $ping_status, $ping_state);
        DEBUG && printf("DBQueries::dataPush()/main: sql = %s\n", $sql);

        DB::query($sql);

    }

// public static function dataPush ($host_id, $ping_status, $ping_state)


    /**
     * Выборка списка хостов.
     * 
     * @return \mysqli_result|boolean
     * @access public
     * @static
     */
    public static function hostsList ()
    {
        return DB::query("SELECT * FROM `hosts` ORDER BY `host_label`");

    }

// public static function hostsList ()


    /**
     * Выборка хоста по лейблу.
     * 
     * @param string $host_label
     * @return \mysqli_result|boolean
     * @access public
     * @static
     */
    public static function hostByLabel ($host_label)
    {
        return DB::query(sprintf("SELECT * FROM `hosts`
WHERE `host_label` = '%s'", DB::escape($host_label)));

    }

// public static function hostByLabel ($host_label)


    /**
     * Псевдо-удаление хоста.
     * 
     * @param int $host_id
     * @return \mysqli_result|boolean
     * @access public
     * @static
     */
    public static function hostDelete ($host_id)
    {
        return DB::query(sprintf("UPDATE `hosts` SET
    `host_label` = CONCAT(`host_label`, '+d', UNIX_TIMESTAMP(),
    `host_fqdn` = CONCAT(`host_label`, '+d', UNIX_TIMESTAMP(),
    `host_enabled` = 0
WHERE `host_id` = %d", $host_id));

    }

// public static function hostDelete ($host_id)


    /**
     * Вставка хоста.
     * 
     * @param string $host_label
     * @param string $host_fqdn
     * @return \mysqli_result|boolean
     * @access public
     * @static
     */
    public static function hostInsert ($host_label, $host_fqdn)
    {
        return DB::query(sprintf("INSERT INTO `hosts`
    (`host_label`, `host_fqdn`, `host_enabled`)
VALUES
    ('%s','%s', 1)", DB::escape($host_label), DB::escape($host_fqdn)));

    }

// public static function hostInsert ($host_label, $host_fqdn)


    /**
     * Проверка уникальности лейбла и FQDN хоста.
     * 
     * @param string $host_label
     * @param string $host_fqdn
     * @return \mysqli_result|boolean
     * @access public
     * @static
     */
    public static function hostUniqueness ($host_label, $host_fqdn)
    {
        return DB::query(sprintf("SELECT SUM(`label`) `label`, SUM(`fqdn`) `fqdn` FROM (
    SELECT 1 `label`, 0 `fqdn` FROM `hosts` WHERE `host_label` = '%s'
        UNION
    SELECT 0 `label`, 1 `fqdn` FROM `hosts` WHERE `host_fqdn` = '%s'
) sub", DB::escape($host_label), DB::escape($host_fqdn)));

    }

// public static function hostUniqueness ($host_label, $host_fqdn)


    /**
     * Установка поля включенности хоста.
     * 
     * @param int $host_id
     * @param int $host_enabled
     * @return \mysqli_result|boolean
     * @access public
     * @static
     */
    public static function hostXXable ($host_id, $host_enabled)
    {
        return DB::query(sprintf("UPDATE `hosts`
SET `host_enabled` = %d
WHERE `host_id` = %d", $host_enabled, $host_id));

    }

// public static function hostXXable ($host_id, $host_enabled)

}

// class DBQueries

# EOF