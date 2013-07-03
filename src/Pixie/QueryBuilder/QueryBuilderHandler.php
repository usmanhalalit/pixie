<?php namespace Pixie\QueryBuilder;

use Pixie\Connection;
use Pixie\Container;

class QueryBuilderHandler
{

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

    public function __construct($connection = null)
    {
        if (is_null($connection)) {
            if (!Container::has('DatabaseConnection')) {
                throw new \Exception('No database connection found.');
            }

            $connection = Container::build('DatabaseConnection');
        }

        $this->connection = $connection;

        $this->pdo = $this->connection->getPdoInstance();
        $this->adapter = $this->connection->getAdapter();
        $this->adapterConfig = $this->connection->getAdapterConfig();

        if (isset($this->adapterConfig['prefix'])) {
            $this->tablePrefix = $this->adapterConfig['prefix'];
        }

        // Query builder adapter instance
        $this->adapterInstance = Container::build('\\Pixie\\QueryBuilder\\Adapters\\' . ucfirst($this->adapter));

        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function newQuery($connection = null)
    {
        if (is_null($connection)) {
            $connection = $this->connection;
        }

        return new static($connection);
    }

    public function query($sql, $bindings = array())
    {
        $query = $this->pdo->prepare($sql);
        $query->execute($bindings);
        $this->queryObject = $query;
        return $query;
    }

    protected function makeCollection(\PDOStatement $queryObject)
    {
        return $queryObject->fetchAll(\PDO::FETCH_CLASS);
    }

    public function get()
    {
        if (is_null($this->queryObject)) {
            $queryArr = $this->adapterInstance->select($this->statements);
            var_dump($queryArr);
            $this->query($queryArr['sql'], $queryArr['bindings']);
        }

        return $this->makeCollection($this->queryObject);
    }

    public function getQuery($type = 'select', $dataToBePassed= array())
    {
        $allowedTypes = array('select', 'insert', 'delete', 'update');
        if (!in_array(strtolower($type), $allowedTypes)) {
            throw new \Exception($type . ' is not a known type.');
        }

        $queryArr = $this->adapterInstance->$type($this->statements, $dataToBePassed);
        return Container::build(
            '\\Pixie\\QueryBuilder\\QueryObject',
            array($queryArr['sql'], $queryArr['bindings'], $this->pdo)
        );
    }

    public function subQuery(QueryBuilderHandler $queryBuilder, $alias = null)
    {
        $sql = '(' . $queryBuilder->getQuery()->getRawSql() . ')';
        if ($alias) {
            $sql = $sql . ' as ' . $alias;
        }

        return $queryBuilder->raw($sql);
    }

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

    public function update($data)
    {
        $queryArr = $this->adapterInstance->update($this->statements, $data);
        var_dump($queryArr);
        $this->query($queryArr['sql'], $queryArr['bindings']);
    }

    public function delete()
    {
        $queryArr = $this->adapterInstance->delete($this->statements);
        var_dump($queryArr);
        $this->query($queryArr['sql'], $queryArr['bindings']);
    }

    public function table($tables)
    {
        $instance = new static($this->connection);
        $tables = $this->addTablePrefix($tables, false);
        $instance->addStatement('froms', $tables);
        return $instance;
    }

    public function from($tables)
    {
        $tables = $this->addTablePrefix($tables, false);
        $this->addStatement('froms', $tables);
        return $this;
    }

    public function select($fields)
    {
        $fields = $this->addTablePrefix($fields);
        $this->addStatement('selects', $fields);
        return $this;
    }

    public function groupBy($field)
    {
        $field = $this->addTablePrefix($field);
        $this->addStatement('groupBys', $field);
        return $this;
    }

    public function orderBy($field, $type = 'ASC')
    {
        $field = $this->addTablePrefix($field);
        $this->statements['orderBys'][] = compact('field', 'type');
        return $this;
    }

    public function limit($limit)
    {
        $this->statements['limit'] = $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->statements['offset'] = $offset;
        return $this;
    }

    public function having($key, $operator, $value, $joiner = 'AND')
    {
        $key = $this->addTablePrefix($key);
        $this->statements['havings'][] = compact('key', 'operator', 'value', 'joiner');
        return $this;
    }

    public function where($key, $operator, $value)
    {
        return $this->_where($key, $operator, $value);
    }

    protected function _where($key, $operator, $value, $joiner = 'AND')
    {
        $key = $this->addTablePrefix($key);
        $this->statements['wheres'][] = compact('key', 'operator', 'value', 'joiner');
        return $this;
    }

    public function orWhere($key, $operator, $value)
    {
        return $this->_where($key, $operator, $value, 'OR');
    }

    public function whereIn($key, array $values)
    {
        return $this->_where($key, 'IN', $values, 'AND');
    }

    public function whereNotIn($key, array $values)
    {
        return $this->_where($key, 'NOT IN', $values, 'AND');
    }

    public function orWhereIn($key, array $values)
    {
        return $this->where($key, 'IN', $values, 'OR');
    }

    public function orWhereNotIn($key, array $values)
    {
        return $this->where($key, 'NOT IN', $values, 'OR');
    }

    public function join($table, $key, $operator, $value, $type = 'inner', $joiner = 'AND')
    {
        $table = $this->addTablePrefix($table, false);
        $key = $this->addTablePrefix($key);
        $value = $this->addTablePrefix($value);

        $joinStatement = array(compact('key', 'operator', 'value', 'joiner'));
        if (isset($this->statements['joins'][$type][$table])) {
            $this->statements['joins'][$type][$table] = array_merge(
                $joinStatement,
                $this->statements['joins'][$type][$table]
            );
        } else {
            $this->statements['joins'][$type][$table] = $joinStatement;
        }

        return $this;
    }

    public function getStatements()
    {
        return $this->statements;
    }

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

    public function raw($value)
    {
        return Container::build('\\Pixie\\QueryBuilder\\Raw', array($value));
    }

    /**
     *
     * @param      $values
     * @param bool $tableFieldMix If we have mixes of field and table names with a "."
     *
     * @return array|mixed
     */
    protected function addTablePrefix($values, $tableFieldMix = true)
    {
        if (!$this->tablePrefix) {
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
            if ($value instanceof Raw) {
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
     * return PDO instance
     *
     * @return \PDO
     */
    public function pdo()
    {
        return $this->pdo;
    }
}