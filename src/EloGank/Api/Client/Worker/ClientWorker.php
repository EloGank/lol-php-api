<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Client\Worker;

use EloGank\Api\Client\Exception\ClientNotReadyException;
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
     * @var int
     */
    protected $expire;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var int
     */
    protected $defaultPort;


    /**
     * @param LoggerInterface    $logger
     * @param LOLClientInterface $client
     * @param Client             $redis
     *
     * @throws \Exception
     */
    public function __construct(LoggerInterface $logger, LOLClientInterface $client, $redis)
    {
        $this->logger = $logger;
        $this->client = $client;
        $this->redis  = $redis;

        // Init configuration to handle exception and log them
        try {
            $this->expire = (int) ConfigurationLoader::get('client.response.expire');
            if ($this->expire < (int) ConfigurationLoader::get('client.request.timeout')) {
                $this->expire = (int) ConfigurationLoader::get('client.request.timeout');
            }

            $this->key = ConfigurationLoader::get('client.async.redis.key');
            $this->defaultPort = ConfigurationLoader::get('client.async.startPort');
        }
        catch (\Exception $e) {
            $this->logger->critical($e->getMessage());

            throw $e;
        }
    }

    /**
     * Start the worker and wait for requests
     */
    public function listen()
    {
        $context = new \ZMQContext();
        $server = new \ZMQSocket($context, \ZMQ::SOCKET_PULL);
        $server->bind('tcp://127.0.0.1:' . ($this->defaultPort + $this->client->getId() - 1));

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

            try {
                // Call the right method in the client and push to redis the result
                $result = call_user_func_array(array($this->client, $request['command']), $request['parameters']);
            }
            catch (ClientNotReadyException $e) {
                $this->logger->warning('Client worker ' . $this->client . ' received a request (#' . $request['invokeId'] . ') whereas the client is not ready. This is normal in case of client reconnection process. Ignoring.');

                continue;
            }

            $key = $this->key . '.client.commands.' . $request['invokeId'];

            $this->redis->rpush($key, serialize($result));
            $this->redis->expire($key, $this->expire);
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