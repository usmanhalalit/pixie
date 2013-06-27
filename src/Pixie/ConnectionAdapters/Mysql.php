<?php namespace Pixie\ConnectionAdapters;

use PDO;


class Mysql extends ConnectionAdapter
{
    public function connect($config)
    {
        $connectionString = "mysql:host={$config['host']};dbname={$config['database']}";

        if (isset($config['port'])) {
            $connectionString .= ";port={$config['port']}";
        }

        if (isset($config['unix_socket'])) {
            $connectionString .= ";unix_socket={$config['unix_socket']}";
        }

        $connection = new PDO($connectionString, $config['username'], $config['password']);

        if (isset($config['charset'])) {
            $connection->prepare("SET NAMES '{$config['charset']}'")->execute();
        }

        return $connection;
    }
}