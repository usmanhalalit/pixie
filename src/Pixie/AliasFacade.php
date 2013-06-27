<?php namespace Pixie;

/**
 * This class gives the ability to access non-static methods statically
 *
 * Class AliasFacade
 *
 * @package Caliber\Database
 */
class AliasFacade {
    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        if(!Container::has('QueryBuilder')) {
            Container::singleton('QueryBuilder', '\\Pixie\\QueryBuilder\\QueryBuilderHandler');
        }
        $queryBuilder = Container::build('QueryBuilder');
        return call_user_func_array(array($queryBuilder, $method), $args);
    }
}