<?php namespace Pixie\QueryBuilder;


class NestedCriteria extends QueryBuilderHandler
{
    protected function _where($key, $operator = null, $value = null, $joiner = 'AND')
    {
        $key = $this->addTablePrefix($key);
        $this->statements['criteria'][] = compact('key', 'operator', 'value', 'joiner');
        return $this;
    }
}