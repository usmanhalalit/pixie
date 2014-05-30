<?php namespace Pixie\QueryBuilder;

use Pixie\Connection;
use Pixie\Exception;

class QueryBuilderHandler
{

    /**
     * @var \Viocon\Container
     */
    protected $container;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $statements = array();

    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var null|\PDOStatement
     */
    protected $pdoStatement = null;

    /**
     * @var null|string
     */
    protected $tablePrefix = null;

    /**
     * @var \Pixie\QueryBuilder\Adapters\BaseAdapter
     */
    protected $adapterInstance;

    /**
     * @param null|\Pixie\Connection $connection
     *
     * @throws \Pixie\Exception
     */
    public function __construct(Connection $connection = null)
    {
        if (is_null($connection)) {
            if (!$connection = Connection::getStoredConnection()) {
                throw new Exception('No database connection found.', 1);
            }
        }

        $this->connection = $connection;
        $this->container = $this->connection->getContainer();
        $this->pdo = $this->connection->getPdoInstance();
        $this->adapter = $this->connection->getAdapter();
        $this->adapterConfig = $this->connection->getAdapterConfig();

        if (isset($this->adapterConfig['prefix'])) {
            $this->tablePrefix = $this->adapterConfig['prefix'];
        }

        // Query builder adapter instance
        $this->adapterInstance = $this->container->build('\\Pixie\\QueryBuilder\\Adapters\\' . ucfirst($this->adapter), array($this->connection));

        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @param null|\Pixie\Connection $connection
     *
     * @return static
     */
    public function newQuery(Connection $connection = null)
    {
        if (is_null($connection)) {
            $connection = $this->connection;
        }

        return new static($connection);
    }

    /**
     * @param       $sql
     * @param array $bindings
     *
     * @return $this
     */
    public function query($sql, $bindings = array())
    {
        $pdoStatement = $this->pdo->prepare($sql);
        $pdoStatement->execute($bindings);
        $this->pdoStatement = $pdoStatement;
        return $this;
    }

    /**
     * Get all rows
     *
     * @return \stdClass|null
     */
    public function get()
    {
        $this->fireEvents('before-select');
        $this->preparePdoStatement();
        $result = $this->pdoStatement->fetchAll(\PDO::FETCH_CLASS);
        $this->pdoStatement = null;
        $this->fireEvents('after-select', $result);
        return $result;
    }

    /**
     * Get first row
     *
     * @return \stdClass|null
     */
    public function first()
    {
        $this->limit(1);
        $result = $this->get();
        return empty($result) ? null : $result[0];
    }

    /**
     * @param        $value
     * @param string $fieldName
     *
     * @return null|\stdClass
     */
    public function findAll($fieldName, $value)
    {
        $this->where($fieldName, '=', $value);
        return $this->get();
    }

    /**
     * @param        $value
     * @param string $fieldName
     *
     * @return null|\stdClass
     */
    public function find($value, $fieldName = 'id')
    {
        $this->where($fieldName, '=', $value);
        return $this->first();
    }

    /**
     * Get count of rows
     *
     * @return int
     */
    public function count()
    {
        return $this->aggregate('count');
    }

    /**
     * @param $type
     *
     * @return int
     */
    protected function aggregate($type)
    {
        // Get the current selects
        $mainSelects = isset($this->statements['selects']) ? $this->statements['selects'] : null;
        // Replace select with a scalar value like `count`
        $this->statements['selects'] = array($this->raw($type . '(*) as field'));
        $row = $this->get();

        // Set the select as it was
        if ($mainSelects) {
            $this->statements['selects'] = $mainSelects;
        } else {
            unset($this->statements['selects']);
        }

        return isset($row[0]->field) ? (int) $row[0]->field : 0;
    }

    /**
     * @param string $type
     * @param array  $dataToBePassed
     *
     * @return mixed
     * @throws Exception
     */
    public function getQuery($type = 'select', $dataToBePassed = array())
    {
        $allowedTypes = array('select', 'insert', 'delete', 'update', 'criteriaonly');
        if (!in_array(strtolower($type), $allowedTypes)) {
            throw new Exception($type . ' is not a known type.', 2);
        }

        $queryArr = $this->adapterInstance->$type($this->statements, $dataToBePassed);

        return $this->container->build(
            '\\Pixie\\QueryBuilder\\QueryObject',
            array($queryArr['sql'], $queryArr['bindings'], $this->pdo)
        );
    }

    /**
     * @param QueryBuilderHandler $queryBuilder
     * @param null                $alias
     *
     * @return Raw
     */
    public function subQuery(QueryBuilderHandler $queryBuilder, $alias = null)
    {
        $sql = '(' . $queryBuilder->getQuery()->getRawSql() . ')';
        if ($alias) {
            $sql = $sql . ' as ' . $alias;
        }

        return $queryBuilder->raw($sql);
    }

    /**
     * @param $data
     *
     * @return array|string
     */
    public function insert($data)
    {
        $this->fireEvents('before-insert');
        // If first value is not an array
        // Its not a batch insert
        if (!is_array(current($data))) {
            $queryObject = $this->getQuery('insert', $data);
            $this->query($queryObject->getSql(), $queryObject->getBindings());

            $return = $this->pdo->lastInsertId();
        } else {
            // Its a batch insert
            $return = array();
            foreach ($data as $subData) {
                $queryObject = $this->getQuery('insert', $subData);
                $this->query($queryObject->getSql(), $queryObject->getBindings());
                $return[] = $this->pdo->lastInsertId();
            }
        }

        $this->fireEvents('after-insert', $return);
        return $return;
    }

    /**
     * @param $data
     *
     * @return void
     */
    public function update($data)
    {
        $this->fireEvents('before-update');
        $queryObject = $this->getQuery('update', $data);
        $this->query($queryObject->getSql(), $queryObject->getBindings());
        $this->fireEvents('after-update', $queryObject);
    }

    /**
     *
     */
    public function delete()
    {
        $this->fireEvents('before-delete');
        $queryObject = $this->getQuery('delete');
        $this->query($queryObject->getSql(), $queryObject->getBindings());
        $this->fireEvents('after-delete', $queryObject);
    }

    /**
     * @param $tables
     *
     * @return static
     */
    public function table($tables)
    {
        $instance = new static($this->connection);
        $tables = $this->addTablePrefix($tables, false);
        $instance->addStatement('tables', $tables);
        return $instance;
    }

    /**
     * @param $tables
     *
     * @return $this
     */
    public function from($tables)
    {
        $tables = $this->addTablePrefix($tables, false);
        $this->addStatement('tables', $tables);
        return $this;
    }

    /**
     * @param $fields
     *
     * @return $this
     */
    public function select($fields)
    {
        $fields = $this->addTablePrefix($fields);
        $this->addStatement('selects', $fields);
        return $this;
    }

    /**
     * @param $field
     *
     * @return $this
     */
    public function groupBy($field)
    {
        $field = $this->addTablePrefix($field);
        $this->addStatement('groupBys', $field);
        return $this;
    }

    /**
     * @param        $field
     * @param string $type
     *
     * @return $this
     */
    public function orderBy($field, $type = 'ASC')
    {
        $field = $this->addTablePrefix($field);
        $this->statements['orderBys'][] = compact('field', 'type');
        return $this;
    }

    /**
     * @param $limit
     *
     * @return $this
     */
    public function limit($limit)
    {
        $this->statements['limit'] = $limit;
        return $this;
    }

    /**
     * @param $offset
     *
     * @return $this
     */
    public function offset($offset)
    {
        $this->statements['offset'] = $offset;
        return $this;
    }

    /**
     * @param        $key
     * @param        $operator
     * @param        $value
     * @param string $joiner
     *
     * @return $this
     */
    public function having($key, $operator, $value, $joiner = 'AND')
    {
        $key = $this->addTablePrefix($key);
        $this->statements['havings'][] = compact('key', 'operator', 'value', 'joiner');
        return $this;
    }

    /**
     * @param        $key
     * @param        $operator
     * @param        $value
     *
     * @return $this
     */
    public function orHaving($key, $operator, $value)
    {
        return $this->having($key, $operator, $value, 'OR');
    }

    /**
     * @param $key
     * @param $operator
     * @param $value
     *
     * @return $this
     */
    public function where($key, $operator = null, $value = null)
    {
        // If two params are given then assume operator is =
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->_where($key, $operator, $value);
    }

    /**
     * @param $key
     * @param $operator
     * @param $value
     *
     * @return $this
     */
    public function orWhere($key, $operator = null, $value = null)
    {
        // If two params are given then assume operator is =
        if (func_num_args() == 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->_where($key, $operator, $value, 'OR');
    }

    /**
     * @param       $key
     * @param array $values
     *
     * @return $this
     */
    public function whereIn($key, array $values)
    {
        return $this->_where($key, 'IN', $values, 'AND');
    }

    /**
     * @param       $key
     * @param array $values
     *
     * @return $this
     */
    public function whereNotIn($key, array $values)
    {
        return $this->_where($key, 'NOT IN', $values, 'AND');
    }

    /**
     * @param       $key
     * @param array $values
     *
     * @return $this
     */
    public function orWhereIn($key, array $values)
    {
        return $this->_where($key, 'IN', $values, 'OR');
    }

    /**
     * @param       $key
     * @param array $values
     *
     * @return $this
     */
    public function orWhereNotIn($key, array $values)
    {
        return $this->_where($key, 'NOT IN', $values, 'OR');
    }

    /**
     * @param        $table
     * @param        $key
     * @param        $operator
     * @param        $value
     * @param string $type
     *
     * @return $this
     */
    public function join($table, $key, $operator = null, $value = null, $type = 'inner')
    {
        if (!$key instanceof \Closure) {
            $key = function($joinBuilder) use ($key, $operator, $value) {
                $joinBuilder->on($key, $operator, $value);
            };
        }

        // Build a new JoinBuilder class, keep it by reference so any changes made
        // in the closure should reflect here
        $joinBuilder = $this->container->build('\\Pixie\\QueryBuilder\\JoinBuilder', array($this->connection));
        $joinBuilder = & $joinBuilder;
        // Call the closure with our new joinBuilder object
        $key($joinBuilder);
        $table = $this->addTablePrefix($table, false);
        // Get the criteria only query from the joinBuilder object
        $this->statements['joins'][] = compact('type', 'table', 'joinBuilder');

        return $this;
    }

    /**
     * @param      $table
     * @param      $key
     * @param null $operator
     * @param null $value
     *
     * @return $this
     */
    public function leftJoin($table, $key, $operator = null, $value = null)
    {
        return $this->join($table, $key, $operator, $value, 'left');
    }

    /**
     * @param      $table
     * @param      $key
     * @param null $operator
     * @param null $value
     *
     * @return $this
     */
    public function rightJoin($table, $key, $operator = null, $value = null)
    {
        return $this->join($table, $key, $operator, $value, 'right');
    }

    /**
     * @param      $table
     * @param      $key
     * @param null $operator
     * @param null $value
     *
     * @return $this
     */
    public function innerJoin($table, $key, $operator = null, $value = null)
    {
        return $this->join($table, $key, $operator, $value, 'inner');
    }

    /**
     * Add a raw query
     *
     * @param $value
     *
     * @return mixed
     */
    public function raw($value)
    {
        return $this->container->build('\\Pixie\\QueryBuilder\\Raw', array($value));
    }

    /**
     * Return PDO instance
     *
     * @return \PDO
     */
    public function pdo()
    {
        return $this->pdo;
    }

    /**
     * @param Connection $connection
     *
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    protected function preparePdoStatement(){
        // If user has not passed any statement (its a raw query)
        if (is_null($this->pdoStatement)) {
            $queryObject = $this->getQuery('select');
            $this->query($queryObject->getSql(), $queryObject->getBindings());
        }
    }

    /**
     * @param        $key
     * @param        $operator
     * @param        $value
     * @param string $joiner
     *
     * @return $this
     */
    protected function _where($key, $operator = null, $value = null, $joiner = 'AND')
    {
        $key = $this->addTablePrefix($key);
        $this->statements['wheres'][] = compact('key', 'operator', 'value', 'joiner');
        return $this;
    }

    /**
     * Add table prefix (if given) on given string.
     *
     * @param      $values
     * @param bool $tableFieldMix If we have mixes of field and table names with a "."
     *
     * @return array|mixed
     */
    public function addTablePrefix($values, $tableFieldMix = true)
    {
        if (is_null($this->tablePrefix)) {
            return $values;
        }

        // $value will be an array and we will add prefix to all table names

        // If supplied value is not an array then make it one
        $single = false;
        if (!is_array($values)) {
            $values = array($values);
            // We had single value, so should return a single value
            $single = true;
        }

        $return = array();

        foreach ($values as $key => $value) {
            // Its a raw query, just add it to our return array and continue next
            if ($value instanceof Raw || $value instanceof \Closure) {
                $return[$key] = $value;
                continue;
            }

            // If our value has mix of field and table names with a ".", like my_table.field
            if ($tableFieldMix) {
                // If we have a . then we really have a table name, else we have only field
                $return[$key] = strstr($value, '.') ? $this->tablePrefix . $value : $value;
            } else {
                // Method call says, we have just table, force adding prefix
                $return[$key] = $this->tablePrefix . $value;
            }


        }

        // If we had single value then we should return a single value (end value of the array)
        return $single ? end($return) : $return;
    }

    /**
     * @param $key
     * @param $value
     */
    protected function addStatement($key, $value)
    {
        if (!is_array($value)) {
            $value = array($value);
        }

        if (!array_key_exists($key, $this->statements)) {
            $this->statements[$key] = $value;
        } else {
            $this->statements[$key] = array_merge($this->statements[$key], $value);
        }
    }

    /**
     * @param $event
     * @param $table
     *
     * @return callable|null
     */
    public function getEvent($event, $table = ':any')
    {
        return $this->connection->getEventHandler()->getEvent($event, $table);
    }

    /**
     * @param          $event
     * @param string   $table
     * @param callable $action
     *
     * @return void
     */
    public function registerEvent($event, $table = ':any', \Closure $action)
    {
        if ($table != ':any') {
            $table = $this->addTablePrefix($table, false);
        }
        return $this->connection->getEventHandler()->registerEvent($event, $table, $action);
    }

    /**
     * @param          $event
     * @param string   $table
     *
     * @return void
     */
    public function removeEvent($event, $table = ':any')
    {
        if ($table != ':any') {
            $table = $this->addTablePrefix($table, false);
        }

        return $this->connection->getEventHandler()->removeEvent($event, $table);
    }

    /**
     * @param      $event
     * @param null $dataToBePassed
     */
    public function fireEvents($event, $dataToBePassed = null) {
        return $this->connection->getEventHandler()->fireEvents($this, $event, $dataToBePassed);
    }

    /**
     * @return array
     */
    public function getStatements()
    {
        return $this->statements;
    }
}