<?php namespace Pixie\ConnectionAdapters;

abstract class ConnectionAdapter implements ConnectionInterface
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