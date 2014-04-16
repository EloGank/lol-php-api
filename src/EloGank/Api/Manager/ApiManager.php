<?php

namespace EloGank\Api\Manager;

use EloGank\Api\Client\Factory\ClientFactory;
use EloGank\Api\Client\LOLClient;
use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Component\Routing\Router;
use EloGank\Api\Configuration\ConfigurationLoader;
use EloGank\Api\Configuration\Exception\ConfigurationKeyNotFoundException;
use EloGank\Api\Logger\LoggerFactory;
use EloGank\Api\Model\Region\Exception\RegionNotFoundException;
use EloGank\Api\Model\Region\RegionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Parser;

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
     * @var LOLClientInterface[]
     */
    protected $clients;

    /**
     * @var int
     */
    protected $clientId = 1;

    /**
     * @var Router
     */
    protected $router;


    /**
     *
     */
    public function __construct()
    {
        $this->logger = LoggerFactory::create();
    }

    /**
     * Init the API components
     */
    public function init()
    {
        $this->router = new Router();
        $this->router->init();

        $this->clients = [];
    }

    /**
     * Create client instances & auth
     *
     * @return bool True if one or more clients are connected, false otherwise
     */
    public function connect()
    {
        $version = ConfigurationLoader::get('client.version');
        $locale  = ConfigurationLoader::get('client.locale');

        $tmpClients = [];
        foreach (ConfigurationLoader::get('client.accounts') as $key => $account) {
            $client = ClientFactory::create(
                $key,
                $this->getClientId(),
                $this->createRegion($account['region']),
                $account['username'],
                $account['password'],
                $version,
                $locale
            );

            $client->authenticate();
            $tmpClients[] = $client;
        }

        foreach ($tmpClients as $client) {
            if ($client->isAuthenticated()) {
                $this->clients[] = $client;

                $this->logger->info('Client #' . $client->getId() . ' (' . $client->getRegion() . ') is connected');
            }
            else {
                $this->logger->error('Client #' . $client->getId() . ' (' . $client->getRegion() . ') cannot be connected : ' . $client->getError());
            }
        }

        if (!isset($this->clients[0])) {
            return false;
        }

        return true;
    }

    /**
     * @param $regionUniqueName
     *
     * @return RegionInterface
     *
     * @throws \EloGank\Api\Model\Region\Exception\RegionNotFoundException
     */
    protected function createRegion($regionUniqueName)
    {
        try {
            $region = ConfigurationLoader::get('region.servers.' . $regionUniqueName);
        }
        catch (ConfigurationKeyNotFoundException $e) {
            throw new RegionNotFoundException('The region with unique name "' . $regionUniqueName . '" is not found');
        }

        $class = ConfigurationLoader::get('region.class');

        return new $class($regionUniqueName, $region['name'], $region['server'], $region['loginQueue']);
    }

    /**
     * @return int
     */
    protected function getClientId()
    {
        return $this->clientId++;
    }

    /**
     * @return \EloGank\Api\Component\Routing\Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @return LOLClient
     */
    public function getClient()
    {
        // TODO do the anti flood selection here

        return $this->clients[0];
    }
}