<?php

namespace EloGank\Api\Client\Async;

use EloGank\Api\Client\LOLClient;
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
     * @var LOLClient
     */
    protected $client;

    /**
     * @var Client
     */
    protected $redis;


    /**
     * @param LoggerInterface $logger
     * @param LOLClient       $client
     * @param Client          $redis
     */
    public function __construct(LoggerInterface $logger, LOLClient $client, $redis)
    {
        $this->logger = $logger;
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

        $this->logger->info('Client worker ' . $this->client . ' is ready');

        while (true) {
            $request = $server->recv();
            $this->logger->debug('Client worker ' . $this->client . ' receiving request : ' . $request);

            $request = json_decode($request, true);
            if (!$this->isValidInput($request)) {
                continue;
            }

            $result = call_user_func_array(array($this->client, $request['command']), $request['parameters']);
            $this->redis->rpush('elogank.api.clients.' . $this->client->getId() . '.' . $request['command'], serialize([
                'result' => $result
            ]));
        }
    }

    /**
     * @param array $input
     *
     * @return bool
     */
    protected function isValidInput(array $input)
    {
        if (!isset($input['command']) || !isset($input['parameters'])) {
            return false;
        }

        return true;
    }
}