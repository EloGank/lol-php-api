<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Client\Async;

use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use Predis\Client;
use Psr\Log\LoggerInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ClientWorker
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var LOLClientInterface
     */
    protected $client;

    /**
     * @var Client
     */
    protected $redis;


    /**
     * @param LoggerInterface    $logger
     * @param LOLClientInterface $client
     * @param Client             $redis
     */
    public function __construct(LoggerInterface $logger, LOLClientInterface $client, $redis)
    {
        $this->logger = $logger;
        $this->client = $client;
        $this->redis  = $redis;
    }

    /**
     * Start the worker and wait for requests
     */
    public function listen()
    {
        $context = new \ZMQContext();
        $server = new \ZMQSocket($context, \ZMQ::SOCKET_PULL);
        $server->bind('tcp://127.0.0.1:' . (ConfigurationLoader::get('client.async.startPort') + $this->client->getId() - 1));

        $this->logger->info('Client worker ' . $this->client . ' is ready');

        while (true) {
            $request = $server->recv();
            $this->logger->debug('Client worker ' . $this->client . ' receiving request : ' . $request);

            // Check if the input is valid, ignore if wrong
            $request = json_decode($request, true);
            if (!$this->isValidInput($request)) {
                $this->logger->error('Client worker ' . $this->client . ' received an invalid input');

                continue;
            }

            // Call the right method in the client and push to redis the result
            $result = call_user_func_array(array($this->client, $request['command']), $request['parameters']);
            $key = ConfigurationLoader::get('client.async.redis.key') . '.client.commands.' . $request['invokeId'];

            $this->redis->rpush($key, serialize($result));
            $this->redis->expire($key, 60); // TODO config
        }
    }

    /**
     * @param array $input
     *
     * @return bool
     */
    protected function isValidInput(array $input)
    {
        if (!isset($input['invokeId']) || !isset($input['command']) || !isset($input['parameters'])) {
            return false;
        }

        return true;
    }
}