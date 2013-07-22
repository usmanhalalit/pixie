<?php namespace Pixie\ConnectionAdapters;

interface ConnectionInterface
{
    /**
     * @param $config
     *
     * @return \PDO
     */
    public function connect($config);
}