<?php

namespace EloGank\Api\Manager;

use EloGank\Api\Client\Factory\ClientFactory;
use EloGank\Api\Client\LOLClient;
use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Component\Routing\Router;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Logger\LoggerFactory;
use Predis\Client;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
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
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Client
     */
    protected $redis;


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
        $this->clients = [];

        $this->loop = Factory::create();

        // Catch signals
        $this->loop->addPeriodicTimer(1, function () {
            pcntl_signal_dispatch();
        });

        // Clients logging
        if (true === ConfigurationLoader::get('client.async.enabled')) {
            $this->loop->addPeriodicTimer(0.5, function () {
                LoggerFactory::subscribe();
            });
        }

        $this->router = new Router();
        $this->router->init();

        $this->redis = new Client(sprintf('tcp://%s:%d', ConfigurationLoader::get('client.async.redis.host'), ConfigurationLoader::get('client.async.redis.port')));

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
            if (isset($this->clients[0])) {
                $this->logger->info('Killing all clients...');

                foreach ($this->clients as $client) {
                    $client->kill();
                }
            }

            die;
        };

        pcntl_signal(SIGINT, $killClients);
        pcntl_signal(SIGTERM, $killClients);
    }

    /**
     * Create client instances & auth
     *
     * @return bool True if one or more clients are connected, false otherwise
     */
    public function connect()
    {
        $this->logger->info('Starting clients...');

        $tmpClients = [];
        foreach (ConfigurationLoader::get('client.accounts') as $accountKey => $account) {
            $client = ClientFactory::create($this->redis, $accountKey, $this->getClientId());
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
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
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