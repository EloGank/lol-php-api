<?php

namespace EloGank\Api\Client\Async;

use EloGank\Api\Client\LOLClient;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Logger\LoggerFactory;
use Predis\Client;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ClientWorker
{
    /**
     * @var LOLClient
     */
    protected $client;

    /**
     * @var Client
     */
    protected $redis;


    /**
     * @param LOLClient $client
     * @param Client    $redis
     */
    public function __construct(LOLClient $client, $redis)
    {
        $this->client = $client;
        $this->redis  = $redis;
    }

    /**
     *
     */
    public function worker()
    {
        $context = new \ZMQContext();
        $server = new \ZMQSocket($context, \ZMQ::SOCKET_PULL);
        $server->bind('tcp://127.0.0.1:' . (ConfigurationLoader::get('client.async.startPort') + $this->client->getId() - 1));

        $logger = LoggerFactory::create('Client #' . $this->client->getId(), true);
        $logger->debug('Client worker #' . $this->client->getId() . ' is ready');

        while (true) {
            $request = $server->recv();

            $logger->info($request);

            $this->client->authenticate();
            $this->redis->rpush('elogank.api.client.authentication', json_encode([
                'is_authenticated' => $this->client->isAuthenticated()
            ]));
        }
    }
}