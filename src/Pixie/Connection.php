<?php namespace Pixie;

use Viocon\Container;

class Connection
{

    /**
     * @var Container
     */
    protected $container;

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
     * @var Connection
     */
    protected static $storedConnection;

    /**
     * @param               $adapter
     * @param array         $adapterConfig
     * @param null|string   $alias
     * @param Container     $container
     */
    public function __construct($adapter, array $adapterConfig, $alias = null, Container $container = null)
    {
        $container = $container ? : new Container();

        $this->container = $container;

        $this->setAdapter($adapter)->setAdapterConfig($adapterConfig)->connect();

        if ($alias) {
            class_alias('Pixie\\AliasFacade', $alias);
        }
    }


    /**
     * Create the connection adapter
     */
    private function connect()
    {
        // Build a database connection if we don't have one connected

        $adapter = '\\Pixie\\ConnectionAdapters\\' . ucfirst(strtolower($this->adapter));

        $adapterInstance = $this->container->build($adapter, array($this->container));

        $pdo = $adapterInstance->connect($this->adapterConfig);
        $this->setPdoInstance($pdo);

        // Preserve the first database connection with a static property
        if (!static::$storedConnection) {
            static::$storedConnection = $this;
        }
    }

    /**
     * @param \PDO $pdo
     *
     * @return $this
     */
    public function setPdoInstance(\PDO $pdo)
    {
        $this->pdoInstance = $pdo;
        return $this;
    }

    /**
     * @return \PDO
     */
    public function getPdoInstance()
    {
        return $this->pdoInstance;
    }

    /**
     * @param $adapter
     *
     * @return $this
     */
    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * @return string
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param array $adapterConfig
     *
     * @return $this
     */
    public function setAdapterConfig(array $adapterConfig)
    {
        $this->adapterConfig = $adapterConfig;
        return $this;
    }

    /**
     * @return array
     */
    public function getAdapterConfig()
    {
        return $this->adapterConfig;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return Connection
     */
    public static function getStoredConnection()
    {
        return static::$storedConnection;
    }
}