<?php namespace Pixie\ConnectionAdapters;

abstract class BaseAdapter implements ConnectionInterface
{
    /**
     * @var \Viocon\Container
     */
    protected $container;

    /**
     * @param \Viocon\Container $container
     */
    public function __construct(\Viocon\Container $container)
    {
        $this->container = $container;
    }
}