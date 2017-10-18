<?php namespace Pixie\QueryBuilder\Adapters;

use Pixie\Connection;

class Mysql extends BaseAdapter
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->sanitizer = '`';
    }
}
