<?php namespace Pixie\QueryBuilder;

class NestedCriteria extends QueryBuilderHandler
{
    /**
     * @param mixed $key
     * @param null|mixed $operator
     * @param null|mixed $value
     * @param string $joiner
     *
     * @return $this
     */
    protected function whereHandler($key, $operator = null, $value = null, $joiner = 'AND')
    {
        $key = $this->addTablePrefix($key);
        $this->statements['criteria'][] = compact('key', 'operator', 'value', 'joiner');
        return $this;
    }
}
