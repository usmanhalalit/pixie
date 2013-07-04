<?php namespace Pixie\QueryBuilder;


class Raw
{

    /**
     * @var string
     */
    protected $value;

    public function __construct($value)
    {
        $this->value = (string)$value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }

}