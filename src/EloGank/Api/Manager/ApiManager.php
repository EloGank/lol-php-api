<?php

namespace EloGank\Api\Manager;

use EloGank\Api\Client\Factory\ClientFactory;
use EloGank\Api\Client\LOLClient;
use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Component\Routing\Router;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Logger\LoggerFactory;
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

        // TODO check if all async clients has been deleted

        if (true === ConfigurationLoader::get('client.async.enabled')) {
            $this->catchSignals();
        }
    }

    /**
     * Catch signals before the API server is killed and kill all the asynchronous clients
     */
    protected function catchSignals()
    {
        $killClients = function () {
            $this->logger->info('Killing all clients...');

            foreach ($this->clients as $client) {
                $client->kill();
            }

            exit(0);
        };

        declare(ticks = 1);
        pcntl_signal(SIGTERM, $killClients);
        pcntl_signal(SIGINT, $killClients);
    }

    /**
     * Create client instances & auth
     *
     * @return bool True if one or more clients are connected, false otherwise
     */
    public function connect()
    {
        $tmpClients = [];
        foreach (ConfigurationLoader::get('client.accounts') as $accountKey => $account) {
            $client = ClientFactory::create($accountKey, $this->getClientId());
            $client->authenticate();

            $tmpClients[] = $client;
        }

        /** @var LOLClientInterface $client */
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