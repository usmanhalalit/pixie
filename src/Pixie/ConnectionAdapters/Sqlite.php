<?php namespace Pixie\ConnectionAdapters;

use PDO;


class Sqlite extends ConnectionAdapter
{

    public function connect($config)
    {
        return new PDO('sqlite:' . $config['database'], null, null);
    }
}