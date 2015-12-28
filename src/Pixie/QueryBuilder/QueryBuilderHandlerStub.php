<?php
namespace Pixie\QueryBuilder;

use PDO;
use Pixie\Connection;
use Pixie\Exception;

abstract class QueryBuilderHandlerStub
{
    /**
     * Set the fetch mode
     *
     * @param $mode
     *
     * @return QueryBuilderHandler
     */
    public static function setFetchMode($mode)
    {
    }

    /**
     * Fetch query results as object of specified type
     *
     * @param $className
     * @param array $constructorArgs
     *
     * @return QueryBuilderHandler
     */
    public static function asObject($className, $constructorArgs = array())
    {
    }

    /**
     * @param null|\Pixie\Connection $connection
     *
     * @return QueryBuilderHandler
     */
    public static function newQuery(Connection $connection = null)
    {
    }

    /**
     * @param       $sql
     * @param array $bindings
     *
     * @return QueryBuilderHandler
     */
    public static function query($sql, $bindings = array())
    {
    }

    /**
     * @param       $sql
     * @param array $bindings
     *
     * @return array PDOStatement and execution time as float
     */
    public static function statement($sql, $bindings = array())
    {
    }

    /**
     * Get all rows
     *
     * @return \stdClass|null
     */
    public static function get()
    {
    }

    /**
     * Get first row
     *
     * @return \stdClass|null
     */
    public static function first()
    {
    }

    /**
     * @param        $value
     * @param string $fieldName
     *
     * @return null|\stdClass
     */
    public static function findAll($fieldName, $value)
    {
    }

    /**
     * @param        $value
     * @param string $fieldName
     *
     * @return null|\stdClass
     */
    public static function find($value, $fieldName = 'id')
    {
    }

    /**
     * Get count of rows
     *
     * @return int
     */
    public static function count()
    {
    }

    /**
     * @param string $type
     * @param array $dataToBePassed
     *
     * @return mixed
     * @throws Exception
     */
    public static function getQuery($type = 'select', $dataToBePassed = array())
    {
    }

    /**
     * @param QueryBuilderHandler $queryBuilder
     * @param null $alias
     *
     * @return Raw
     */
    public static function subQuery(QueryBuilderHandler $queryBuilder, $alias = null)
    {
    }

    /**
     * @param $data
     *
     * @return array|string
     */
    public static function insert($data)
    {
    }

    /**
     * @param $data
     *
     * @return array|string
     */
    public static function insertIgnore($data)
    {
    }

    /**
     * @param $data
     *
     * @return array|string
     */
    public static function replace($data)
    {
    }

    /**
     * @param $data
     *
     * @return QueryBuilderHandler
     */
    public static function update($data)
    {
    }

    /**
     * @param $data
     *
     * @return array|string
     */
    public static function updateOrInsert($data)
    {
    }

    /**
     * @param $data
     *
     * @return QueryBuilderHandler
     */
    public static function onDuplicateKeyUpdate($data)
    {
    }

    /**
     *
     */
    public static function delete()
    {
    }

    /**
     * @param $tables
     *
     * @return QueryBuilderHandler
     */
    public static function table($tables)
    {
    }

    /**
     * @param $tables
     *
     * @return QueryBuilderHandler
     */
    public static function from($tables)
    {
    }

    /**
     * @param $fields
     *
     * @return QueryBuilderHandler
     */
    public static function select($fields)
    {
    }

    /**
     * @param $fields
     *
     * @return QueryBuilderHandler
     */
    public static function selectDistinct($fields)
    {
    }

    /**
     * @param $field
     *
     * @return QueryBuilderHandler
     */
    public static function groupBy($field)
    {
    }

    /**
     * @param        $fields
     * @param string $defaultDirection
     *
     * @return QueryBuilderHandler
     */
    public static function orderBy($fields, $defaultDirection = 'ASC')
    {
    }

    /**
     * @param $limit
     *
     * @return QueryBuilderHandler
     */
    public static function limit($limit)
    {
    }

    /**
     * @param $offset
     *
     * @return QueryBuilderHandler
     */
    public static function offset($offset)
    {
    }

    /**
     * @param        $key
     * @param        $operator
     * @param        $value
     * @param string $joiner
     *
     * @return QueryBuilderHandler
     */
    public static function having($key, $operator, $value, $joiner = 'AND')
    {
    }

    /**
     * @param        $key
     * @param        $operator
     * @param        $value
     *
     * @return QueryBuilderHandler
     */
    public static function orHaving($key, $operator, $value)
    {
    }

    /**
     * @param $key
     * @param $operator
     * @param $value
     *
     * @return QueryBuilderHandler
     */
    public static function where($key, $operator = null, $value = null)
    {
    }

    /**
     * @param $key
     * @param $operator
     * @param $value
     *
     * @return QueryBuilderHandler
     */
    public static function orWhere($key, $operator = null, $value = null)
    {
    }

    /**
     * @param $key
     * @param $operator
     * @param $value
     *
     * @return QueryBuilderHandler
     */
    public static function whereNot($key, $operator = null, $value = null)
    {
    }

    /**
     * @param $key
     * @param $operator
     * @param $value
     *
     * @return QueryBuilderHandler
     */
    public static function orWhereNot($key, $operator = null, $value = null)
    {
    }

    /**
     * @param       $key
     * @param array $values
     *
     * @return QueryBuilderHandler
     */
    public static function whereIn($key, array $values)
    {
    }

    /**
     * @param       $key
     * @param array $values
     *
     * @return QueryBuilderHandler
     */
    public static function whereNotIn($key, array $values)
    {
    }

    /**
     * @param       $key
     * @param array $values
     *
     * @return QueryBuilderHandler
     */
    public static function orWhereIn($key, array $values)
    {
    }

    /**
     * @param       $key
     * @param array $values
     *
     * @return QueryBuilderHandler
     */
    public static function orWhereNotIn($key, array $values)
    {
    }

    /**
     * @param $key
     * @param $valueFrom
     * @param $valueTo
     *
     * @return QueryBuilderHandler
     */
    public static function whereBetween($key, $valueFrom, $valueTo)
    {
    }

    /**
     * @param $key
     * @param $valueFrom
     * @param $valueTo
     *
     * @return QueryBuilderHandler
     */
    public static function orWhereBetween($key, $valueFrom, $valueTo)
    {
    }

    /**
     * @param $key
     *
     * @return QueryBuilderHandler
     */
    public static function whereNull($key)
    {
    }

    /**
     * @param $key
     *
     * @return QueryBuilderHandler
     */
    public static function whereNotNull($key)
    {
    }

    /**
     * @param $key
     *
     * @return QueryBuilderHandler
     */
    public static function orWhereNull($key)
    {
    }

    /**
     * @param $key
     *
     * @return QueryBuilderHandler
     */
    public static function orWhereNotNull($key)
    {
    }

    /**
     * @param        $table
     * @param        $key
     * @param        $operator
     * @param        $value
     * @param string $type
     *
     * @return QueryBuilderHandler
     */
    public static function join($table, $key, $operator = null, $value = null, $type = 'inner')
    {
    }

    /**
     * @param      $table
     * @param      $key
     * @param null $operator
     * @param null $value
     *
     * @return QueryBuilderHandler
     */
    public static function leftJoin($table, $key, $operator = null, $value = null)
    {
    }

    /**
     * @param      $table
     * @param      $key
     * @param null $operator
     * @param null $value
     *
     * @return QueryBuilderHandler
     */
    public static function rightJoin($table, $key, $operator = null, $value = null)
    {
    }

    /**
     * @param      $table
     * @param      $key
     * @param null $operator
     * @param null $value
     *
     * @return QueryBuilderHandler
     */
    public static function innerJoin($table, $key, $operator = null, $value = null)
    {
    }

    /**
     * Add a raw query
     *
     * @param $value
     * @param $bindings
     *
     * @return mixed
     */
    public static function raw($value, $bindings = array())
    {
    }

    /**
     * Return PDO instance
     *
     * @return PDO
     */
    public static function pdo()
    {
    }

    /**
     * @param Connection $connection
     *
     * @return QueryBuilderHandler
     */
    public static function setConnection(Connection $connection)
    {
    }

    /**
     * @return Connection
     */
    public static function getConnection()
    {
    }

    /**
     * Add table prefix (if given) on given string.
     *
     * @param      $values
     * @param bool $tableFieldMix If we have mixes of field and table names with a "."
     *
     * @return array|mixed
     */
    public static function addTablePrefix($values, $tableFieldMix = true)
    {
    }

    /**
     * @param $event
     * @param $table
     *
     * @return callable|null
     */
    public static function getEvent($event, $table = ':any')
    {
    }

    /**
     * @param          $event
     * @param string $table
     * @param callable|\Closure $action
     *
     */
    public static function registerEvent($event, $table, \Closure $action)
    {
    }

    /**
     * @param          $event
     * @param string $table
     *
     * @return void
     */
    public static function removeEvent($event, $table = ':any')
    {
    }

    /**
     * @param      $event
     *
     * @return mixed
     */
    public static function fireEvents($event)
    {
    }

    /**
     * @return array
     */
    public static function getStatements()
    {
    }
}
