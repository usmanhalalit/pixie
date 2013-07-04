<?php namespace Pixie;

/**
 * A service provider for the Caliber Framework
 *
 * Class CaliberServiceProvider
 *
 * @package Pixie
 */
class CaliberServiceProvider
{

    public function __construct($app, $serviceName = 'database', $configServiceName = 'config')
    {
        $app->set(
            $serviceName,
            function () use ($configServiceName, $app) {
                if (!$app->has($configServiceName)) {
                    throw new \Exception('No config service found under ' . $configServiceName . '.');
                }

                $config = $app->build($configServiceName);

                // Default adapter, like mysql
                $adapter = $config->getDatabase('default');
                // Adapter config
                $adapterConfig = $config->getDatabase('connections.' . $adapter);

                $app->build('\\Pixie\\Connection', array($adapter, $adapterConfig, false));
                return $app->build('\\Pixie\\QueryBuilder\\QueryBuilderHandler');
            }
        );
    }

}