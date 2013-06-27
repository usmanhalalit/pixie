<?php namespace Pixie\ConnectionAdapters;


abstract class ConnectionAdapter {

    abstract public function connect($config);
}