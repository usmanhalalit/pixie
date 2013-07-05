<?php namespace Pixie\QueryBuilder;

use Pixie\Connection;

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
     * @var null|QueryObject
     */
    protected $queryObject = null;

    /**
     * @var null|string
     */
    protected $tablePrefix = null;

    /**
     * @var \Pixie\QueryBuilder\Adapters\BaseAdapter
     */
    protected $adapterInstance;

    /**
     * @param null $connection
     *
     * @throws \Exception
     */
    public function __construct($connection = null)
    {
        if (is_null($connection)) {
            if (!$connection = Connection::getStoredConnection()) {
                throw new \Exception('No database connection found.');
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
     * @param null $connection
     *
     * @return static
     */
    public function newQuery($connection = null)
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
     * @return \PDOStatement
     */
    public function query($sql, $bindings = array())
    {
        $query = $this->pdo->prepare($sql);
        $query->execute($bindings);
        $this->queryObject = $query;
        return $query;
    }

    /**
     * @return array
     */
    public function get()
    {
        if (is_null($this->queryObject)) {
            $queryArr = $this->adapterInstance->select($this->statements);
            var_dump($queryArr);
            $this->query($queryArr['sql'], $queryArr['bindings']);
        }

        return $this->makeCollection($this->queryObject);
    }

    /**
     * @param string $type
     * @param array  $dataToBePassed
     *
     * @return mixed
     * @throws \Exception
     */
    public function getQuery($type = 'select', $dataToBePassed = array())
    {
        $allowedTypes = array('select', 'insert', 'delete', 'update', 'criteriaonly');
        if (!in_array(strtolower($type), $allowedTypes)) {
            throw new \Exception($type . ' is not a known type.');
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
     * @return mixed
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
        // If first value is not an array
        // Its not a batch insert
        if (!is_array(current($data))) {
            $queryArr = $this->adapterInstance->insert($this->statements, $data);
            var_dump($queryArr);
            $this->query($queryArr['sql'], $queryArr['bindings']);

            return $this->pdo->lastInsertId();
        } else {
            // Its a batch insert
            $return = array();
            foreach ($data as $subData) {
                $queryArr = $this->adapterInstance->insert($this->statements, $subData);
                var_dump($queryArr);
                $this->query($queryArr['sql'], $queryArr['bindings']);
                $return[] = $this->pdo->lastInsertId();
            }

            return $return;
        }
    }

    /**
     * @param $data
     */
    public function update($data)
    {
        $queryArr = $this->adapterInstance->update($this->statements, $data);
        var_dump($queryArr);
        $this->query($queryArr['sql'], $queryArr['bindings']);
    }

    /**
     *
     */
    public function delete()
    {
        $queryArr = $this->adapterInstance->delete($this->statements);
        var_dump($queryArr);
        $this->query($queryArr['sql'], $queryArr['bindings']);
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
     * @param $key
     * @param $operator
     * @param $value
     *
     * @return $this
     */
    public function where($key, $operator = null, $value = null)
    {
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
        return $this->where($key, 'IN', $values, 'OR');
    }

    /**
     * @param       $key
     * @param array $values
     *
     * @return $this
     */
    public function orWhereNotIn($key, array $values)
    {
        return $this->where($key, 'NOT IN', $values, 'OR');
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
        $joinBuilder = & $this->container->build('\\Pixie\\QueryBuilder\\JoinBuilder', array($this->connection));
        // Call the closure with our new joinBuilder object
        $key($joinBuilder);
        $table = $this->addTablePrefix($table, false);
        // Get the criteria only query from the joinBuilder object
        $this->statements['joins'][$type] = compact('table', 'joinBuilder');

        return $this;
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
    protected function addTablePrefix($values, $tableFieldMix = true)
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
     * @param \PDOStatement $queryObject
     *
     * @return array
     */
    protected function makeCollection(\PDOStatement $queryObject)
    {
        return $queryObject->fetchAll(\PDO::FETCH_CLASS);
    }
}