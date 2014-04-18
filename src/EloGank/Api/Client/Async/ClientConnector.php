<?php

namespace EloGank\Api\Client\Async;

use EloGank\Api\Client\LOLClient;
use EloGank\Api\Logger\LoggerFactory;
use Predis\Client;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ClientConnector
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
     */
    public function __construct(LOLClient $client)
    {
        $this->client = $client;
        $this->redis = new Client('tcp://127.0.0.1:6379');
    }

    /**
     *
     */
    public function worker()
    {
        $context = new \ZMQContext();
        $server = new \ZMQSocket($context, \ZMQ::SOCKET_PULL);
        $server->bind('tcp://127.0.0.1:5555');

        $logger = LoggerFactory::create('Client #' . $this->client->getId());

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