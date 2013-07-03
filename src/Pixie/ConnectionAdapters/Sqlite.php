<?php namespace Pixie\ConnectionAdapters;

class Sqlite extends ConnectionAdapter
{

    public function connect($config)
    {
        $connectionString = 'sqlite:' . $config['database'];
        return $this->container->build('\PDO', array($connectionString, null, null));
    }
}