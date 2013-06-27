<?php namespace Pixie;

class Connection
{

    /**
     * @var string
     */
    protected $adapter;

    /**
     * @var array
     */
    protected $adapterConfig;

    /**
     * @var \PDO
     */
    protected $pdoInstance;

    /**
     * @param                 $adapter
     * @param array           $adapterConfig
     * @param bool            $alias
     *
     * @return \Pixie\Connection
     */
    public function __construct($adapter, array $adapterConfig, $alias = false)
    {
        // Launch the container
        if (!class_exists('\\Pixie\\Container')) {
            new \Viocon\Container('Pixie\\Container');
        }


        $this->setAdapter($adapter)->setAdapterConfig($adapterConfig)->connect();

        if ($alias) {
            class_alias('Pixie\\AliasFacade', $alias);
        }
    }

    /**
     * @return ConnectionAdapters\ConnectionAdapter
     */
    private function connect()
    {
        // Build a database connection if we don't have one connected

        $adapter = '\\Pixie\\ConnectionAdapters\\' . ucfirst(strtolower($this->adapter));
        $adapterInstance = Container::build($adapter);

        $pdo = $adapterInstance->connect($this->adapterConfig);
        $this->setPdoInstance($pdo);

        // Preserve the first database connection state with a singleton
        if (!Container::has('DatabaseConnection')) {
            $connection = $this;
            Container::singleton(
                'DatabaseConnection',
                function () use ($connection) {
                    return $connection;
                }
            );
        }
    }

    public function setPdoInstance(\PDO $pdo)
    {
        $this->pdoInstance = $pdo;
        return $this;
    }

    public function getPdoInstance()
    {
        return $this->pdoInstance;
    }

    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    public function getAdapter()
    {
        return $this->adapter;
    }

    public function setAdapterConfig(array $adapterConfig)
    {
        $this->adapterConfig = $adapterConfig;
        return $this;
    }

    public function getAdapterConfig()
    {
        return $this->adapterConfig;
    }
}