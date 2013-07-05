<?php namespace Pixie\ConnectionAdapters;

abstract class ConnectionAdapter
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

    /**
     * @param $config
     *
     * @return \PDO
     */
    abstract public function connect($config);
}