<?php namespace Pixie;

use Pixie\QueryBuilder\QueryBuilderHandler;

/**
 * This class gives the ability to access non-static methods statically
 *
 * Class AliasFacade
 *
 * @package Caliber\Database
 */
class AliasFacade
{

    protected static $queryBuilderInstance;

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        if (!static::$queryBuilderInstance) {
            static::$queryBuilderInstance = new QueryBuilderHandler();
        }

        return call_user_func_array(array(static::$queryBuilderInstance, $method), $args);
    }
}