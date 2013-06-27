<?php namespace Pixie\QueryBuilder\Adapters;

use Pixie\QueryBuilder\Raw;

abstract class BaseAdapter {

    /**
     * Array concat method, like implode.
     * But it does wrap sanitizer and trims last glue
     *
     * @param array $pieces
     * @param       $glue
     * @param bool  $wrapSanitizer
     *
     * @return string
     */
    protected function arrayStr(array $pieces, $glue, $wrapSanitizer = true)
    {
        $str = '';
        foreach ($pieces as $piece) {
            if ($wrapSanitizer) {
                $piece = $this->wrapSanitizer($piece);
            }

            $str .= $piece . $glue;
        }

        return trim($str, $glue);
    }

    /**
     * Join different part of queries with a space.
     *
     * @param array $pieces
     *
     * @return string
     */
    protected function concatQuery(array $pieces)
    {
        $str = '';
        foreach ($pieces as $piece) {
            $str = trim($str) . ' ' . trim($piece);
        }
        return trim($str);
    }

    protected function buildCriteria($statements, $bindValues = true)
    {
        $criteria = '';
        $bindings = array();
        foreach ($statements as $statement) {

            $key = $this->wrapSanitizer($statement['key']);

            if(! is_array($value = $statement['value'])) {
                if (! $bindValues) {
                    // We are not binding values, lets sanitize then
                    $value = $this->wrapSanitizer($value);
                    $criteria .= $statement['joiner'] . ' ' . $key . ' ' . $statement['operator']  . ' ' . $value . ' ';
                } else {
                    $valuePlaceholder = '?';
                    $bindings[] = $value;
                    $criteria .= $statement['joiner'] . ' ' . $key . ' ' . $statement['operator']  . ' ' . $valuePlaceholder . ' ';
                }
            } else {
                // where_in like query
                $valuePlaceholder = '';
                foreach ($statement['value'] as $subValue) {
                    $valuePlaceholder .= '?, ';
                    $bindings[] = $subValue;
                }

                $valuePlaceholder = trim($valuePlaceholder, ', ');
                $criteria .= $statement['joiner'] . ' ' . $key . ' ' . $statement['operator']  . ' (' . $valuePlaceholder . ')';
            }
        }

        $criteria = trim($criteria);
        $criteria = trim($criteria, 'AND');
        $criteria = trim($criteria, 'OR');
        $criteria = trim($criteria);

        return array($criteria, $bindings);
    }

    /**
     * Wrap values with adapter's sanitizer like, `
     *
     * @param $value
     *
     * @return string
     */
    protected function wrapSanitizer($value)
    {
        // Its a raw query, just cast as string, object has __toString()
        if ($value instanceof Raw) {
            return (string) $value;
        }

        // Separate our table and fields which are joined with a ".",
        // like my_table.id
        $valueArr = explode('.', $value, 2);

        foreach ($valueArr as $key => $subValue) {
            // Don't wrap if we have *, which is not a usual field
            $valueArr[$key] = trim($subValue) == '*' ? $subValue : $this->sanitizer . $subValue . $this->sanitizer;
        }

        // Join these back with "." and return
        return implode('.', $valueArr);
    }


    public function select($statements)
    {
        // From
        $froms = $this->arrayStr($statements['froms'], ', ');
        // Select
        $selects = $this->arrayStr($statements['selects'], ', ');


        // Wheres
        list($whereCriteria, $whereBindings) = $this->buildChildCriteria($statements, 'wheres', 'WHERE');
        // Group bys
        $groupBys = '';
        if(isset($statements['groupBys']) && $groupBys = $this->arrayStr($statements['groupBys'], ', ')) {
            $groupBys = 'GROUP BY ' . $groupBys;
        }

        // Order bys
        $orderBys = '';
        if(isset($statements['orderBys']) && is_array($statements['orderBys'])) {
            foreach ($statements['orderBys'] as $orderBy) {
                $orderBys .= $this->wrapSanitizer($orderBy['field']) . ' ' . $orderBy['type'] . ',';
            }

            if($orderBys = trim($orderBys, ',')) {
                $orderBys = 'ORDER BY ' . $orderBys;
            }
        }

        // Limit and offset
        $limit = isset($statements['limit']) ? 'LIMIT ' . $statements['limit'] : '';
        $offset = isset($statements['offset']) ? 'OFFSET ' . $statements['offset'] : '';

        // Having
        list($havingCriteria, $havingBindings) = $this->buildChildCriteria($statements, 'havings', 'HAVING');

        // Joins
        list($innerJoinCriteria, $innerJoinBindings) = $this->buildJoin($statements, 'inner');
        list($outerJoinCriteria, $outerJoinBindings) = $this->buildJoin($statements, 'outer');
        list($leftJoinCriteria, $leftJoinBindings) = $this->buildJoin($statements, 'left');
        list($rightJoinCriteria, $rightJoinBindings) = $this->buildJoin($statements, 'right');

        $sqlArray = array(
            'SELECT' , $selects , 'FROM' , $froms,
            $innerJoinCriteria , $outerJoinCriteria, $leftJoinCriteria, $rightJoinCriteria,
            $whereCriteria , $groupBys ,$havingCriteria, $orderBys , $limit, $offset
        );

        $sql = $this->concatQuery($sqlArray);

        $bindings = array_merge($innerJoinBindings, $outerJoinBindings, $leftJoinBindings, $rightJoinBindings, $whereBindings, $havingBindings);
        return compact('sql', 'bindings');
    }

    protected function buildChildCriteria($statements, $key, $type, $bindValues = true)
    {
        $criteria = '';
        $bindings = array();

        if(isset($statements[$key])) {
            // Get the generic/adapter agnostic criteria string from parent
            list($criteria, $bindings) = $this->buildCriteria($statements[$key], $bindValues);

            if($criteria) {
                $criteria = $type . ' ' . $criteria;
            }
        }

        return array($criteria, $bindings);
    }

    protected function buildJoin($statements, $type)
    {
        $joinCriteria = '';
        $joinBindings = array();

        if(isset($statements['joins'][$type])) {
            foreach ($statements['joins'][$type] as $table => $criteriaArr) {
                $criteria = $this->buildChildCriteria($statements['joins'][$type], $table, ' ON', false);
                // TODO: Check if .= is really needed
                $joinCriteria .= $criteria[0];
                $joinBindings = array_merge($criteria[1], $joinBindings);

                if($joinCriteria) {
                    $joinCriteria = strtoupper($type) . ' JOIN ' . $this->wrapSanitizer($table) . ' ' . $joinCriteria;
                }
            }
        }
        return array($joinCriteria, $joinBindings);
    }

    public function insert($statements, array $data)
    {
        if (!isset($statements['froms'])) {
            throw new \Exception('No table specified');
        }elseif(count($data) < 1) {
            throw new \Exception('No data given.');
        }

        $table = end($statements['froms']);

        $bindings  = $keys = $values = array();

        foreach ($data as $key => $value) {
            $keys[] = $key;
            $values[] = '?';
            $bindings[] = $value;
        }

        $sqlArray = array(
            'INSERT INTO',
            $table,
            '(' . $this->arrayStr($keys, ',') . ')',
            'VALUES',
            '(' . $this->arrayStr($values, ',', false) . ')',
        );

        $sql = $this->concatQuery($sqlArray, ' ', false);

        return compact('sql', 'bindings');
    }

    public function update($statements, array $data)
    {
        if (!isset($statements['froms'])) {
            throw new \Exception('No table specified');
        }elseif(count($data) < 1) {
            throw new \Exception('No data given.');
        }

        $table = end($statements['froms']);

        $bindings  = $keys = $values = array();
        $updateStatement = '';

        foreach ($data as $key => $value) {
            $updateStatement .= $this->wrapSanitizer($key) . '=?,';
            $bindings[] = $value;
        }

        $updateStatement = trim($updateStatement, ',');

        // Wheres
        list($whereCriteria, $whereBindings) = $this->buildChildCriteria($statements, 'wheres', 'WHERE');

        $sqlArray = array(
            'UPDATE',
            $table,
            'SET ' . $updateStatement,
            $whereCriteria,
        );

        $sql = $this->concatQuery($sqlArray, ' ', false);

        $bindings = array_merge($bindings, $whereBindings);
        return compact('sql', 'bindings');
    }

    public function delete($statements)
    {
        if (!isset($statements['froms'])) {
            throw new \Exception('No table specified');
        }

        $table = end($statements['froms']);

        // Wheres
        list($whereCriteria, $whereBindings) = $this->buildChildCriteria($statements, 'wheres', 'WHERE');

        $sqlArray = array('DELETE from', $table, $whereCriteria);
        $sql = $this->concatQuery($sqlArray, ' ', false);
        $bindings = $whereBindings;

        return compact('sql', 'bindings');
    }
}