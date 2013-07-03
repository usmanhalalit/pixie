<?php namespace Pixie\ConnectionAdapters;

abstract class ConnectionAdapter {

    protected $container;

    public function __construct(\Viocon\Container $container)
    {
        $this->container = $container;
    }

    abstract public function connect($config);
}