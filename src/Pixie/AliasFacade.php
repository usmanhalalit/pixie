<?php namespace Pixie;

use Pixie\QueryBuilder\QueryBuilderHandler;

/**
 * This class gives the ability to access non-static methods statically
 *
 * Class AliasFacade
 *
 * @package Pixie
 */
class AliasFacade
{

    /**
     * @var QueryBuilderHandler
     */
    protected static $queryBuilderInstances;

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        $alias = get_called_class();
        if (!array_key_exists($alias, static::$queryBuilderInstances)) {
            static::$queryBuilderInstances[$alias] = new QueryBuilderHandler();
        }

        // Call the non-static method from the class instance
        return call_user_func_array(array(static::$queryBuilderInstances[$alias], $method), $args);
    }

    /**
     * @param QueryBuilderHandler $queryBuilderInstance
     */
    public static function setQueryBuilderInstance($alias, $queryBuilderInstance)
    {
        static::$queryBuilderInstances[$alias] = $queryBuilderInstance;
    }
}
