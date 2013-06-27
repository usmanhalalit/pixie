<?php namespace Pixie\ConnectionAdapters;

use PDO;


class Pgsql extends ConnectionAdapter
{
    public function connect($config)
    {
        $connectionString = "pgsql:host={$config['host']};dbname={$config['database']}";

        if (isset($config['port'])) {
            $connectionString .= ";port={$config['port']}";
        }

        $connection = new PDO($connectionString, $config['username'], $config['password']);

        if (isset($config['charset'])) {
            $connection->prepare("SET NAMES '{$config['charset']}'")->execute();
        }

        if (isset($config['schema'])) {
            $connection->prepare("SET search_path TO '{$config['schema']}'")->execute();
        }

        return $connection;
    }
}