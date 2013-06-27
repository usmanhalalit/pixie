<?php namespace Pixie\QueryBuilder;


class Raw {

    protected $value;

    public function __construct($value)
    {
        $this->value = (string) $value;
    }

    public function __toString()
    {
        return (string) $this->value;
    }

}