<?php

namespace EloGank\Api\Manager;

use EloGank\Api\Client\LOLClient;
use EloGank\Api\Region\Exception\RegionNotFoundException;
use EloGank\Api\Region\RegionInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ApiManager
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $loggerHandlers;

    /**
     * @var array
     */
    protected $configs = array();

    /**
     * @var
     */
    protected $clients;

    /**
     * @var int
     */
    protected $clientId = 1;


    /**
     * @param array $loggerHandlers
     */
    public function __construct(array $loggerHandlers = array())
    {
        // Default configs
        $this->setConfigs(\Spyc::YAMLLoad($this->getConfigFile()));

        $this->loggerHandlers = $loggerHandlers;
        $this->logger = new Logger('ApiManager', $this->getLoggerHandlers());
    }

    public function connect()
    {
        foreach ($this->configs['client']['accounts'] as $region => $account) {
            $clientId = $this->getClientId();
            $client = new LOLClient($clientId, $this->createRegion($region), $account['username'], $account['password'], $this->configs['client']['version'], $this->configs['client']['locale'], $this->getLoggerHandlers());
            $client->connect();

            $this->clients[] = $client;
            $this->logger->info('Client #' . $clientId . ' (' . $region . ') is connected');
        }
    }

    /**
     * @param $regionUniqueName
     *
     * @return RegionInterface
     *
     * @throws \EloGank\Api\Region\Exception\RegionNotFoundException
     */
    protected function createRegion($regionUniqueName)
    {
        if (!isset($this->configs['region']['servers'][$regionUniqueName])) {
            throw new RegionNotFoundException('The region with unique name "' . $regionUniqueName . '" is not found');
        }

        $region = $this->configs['region']['servers'][$regionUniqueName];
        $class = $this->configs['region']['class'];

        return new $class($regionUniqueName, $region['name'], $region['server'], $region['loginQueue']);
    }

    protected function getClientId()
    {
        return $this->clientId++;
    }

    /**
     * @param array $configs
     */
    public function setConfigs(array $configs)
    {
        $this->configs = array_merge_recursive($this->configs, $configs);
    }

    protected function getLoggerHandlers()
    {
        return array_merge(array(
            new RotatingFileHandler(__DIR__ . '/../../../../' . $this->configs['log']['path'], constant('Monolog\Logger::' . strtoupper($this->configs['log']['verbosity'])))
        ), $this->loggerHandlers);
    }

    /**
     * @return string
     */
    protected function getConfigFile()
    {
        return __DIR__ . '/../../../../config/config.yml';
    }
}