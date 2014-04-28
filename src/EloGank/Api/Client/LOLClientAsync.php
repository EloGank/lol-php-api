<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Client;

use EloGank\Api\Client\Exception\RequestTimeoutException;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Model\Region\RegionInterface;
use Predis\Client;
use Psr\Log\LoggerInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class LOLClientAsync implements LOLClientInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $pidPath;

    /**
     * @var int
     */
    protected $clientId;

    /**
     * @var RegionInterface
     */
    protected $region;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $error;

    /**
     * @var \ZMQSocket
     */
    protected $con;

    /**
     * @var Client
     */
    protected $redis;

    /**
     * @var int
     */
    protected $lastCall = 0;

    /**
     * @var array
     */
    protected static $callbacks = [];


    /**
     * @param LoggerInterface $logger
     * @param Client          $redis
     * @param int             $accountKey
     * @param int             $clientId
     * @param RegionInterface $region
     * @param int             $port
     */
    public function __construct(LoggerInterface $logger, Client $redis, $accountKey, $clientId, RegionInterface $region, $port)
    {
        $rootFolder     = __DIR__ . '/../../../..';

        $this->logger   = $logger;
        $this->pidPath  = $rootFolder . '/' . ConfigurationLoader::get('cache.path') . '/clientpids/client_' . $clientId . '.pid';
        $this->redis    = $redis;
        $this->clientId = $clientId;
        $this->region   = $region;
        $this->port     = $port;

        $this->con      = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_PUSH);

        // Create process
        popen(sprintf('php %s/console elogank:client:create %d %d > /dev/null 2>&1 & echo $! > %s', $rootFolder, $accountKey, $clientId, $this->pidPath), 'r');
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $this->con->connect('tcp://127.0.0.1:' . $this->port);

        $this->send('authenticate', array(), $this->clientId . '.authenticate');
    }

    /**
     * {@inheritdoc}
     */
    public function invoke($destination, $operation, $parameters = array(), \Closure $callback = null, $packetClass = 'flex.messaging.messages.RemotingMessage', $headers = array(), $body = array())
    {
        $invokeId = $this->send('syncInvoke', [
            $destination,
            $operation,
            $parameters
        ]);

        if (null !== $callback) {
            self::$callbacks[$invokeId] = $callback;
        }

        return $invokeId;
    }

    /**
     * {@inheritdoc}
     */
    public function getResults($invokeId, $timeout)
    {
        $message = $this->redis->brpop($this->getKey('client.commands.' . $invokeId), $timeout);
        if (null == $message) {
            throw new RequestTimeoutException('Request timeout, LoL servers might be overloaded or the asynchronous client crashed');
        }

        // Callback process
        if (isset(self::$callbacks[$invokeId])) {
            $callback = self::$callbacks[$invokeId];
            unset(self::$callbacks[$invokeId]);

            return $callback(unserialize($message[1]));
        }

        return unserialize($message[1]);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated()
    {
        $message = $this->redis->rpop($this->getKey('client.commands.' . $this->clientId . '.authenticate'));
        if (null == $message) {
            return null;
        }

        return unserialize($message);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->clientId;
    }

    /**
     * {@inheritdoc}
     */
    public function getRegion()
    {
        return $this->region->getUniqueName();
    }

    /**
     * {@inheritdoc}
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable()
    {
        return $this->lastCall <= microtime(true);
    }

    /**
     * {@inheritdoc}
     */
    public function setIsOverloaded()
    {
        $this->lastCall += (int) ConfigurationLoader::get('client.request.overload.wait');
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function getKey($key)
    {
        return ConfigurationLoader::get('client.async.redis.key') . '.' . $key;
    }

    /**
     * @param string          $commandName
     * @param array           $parameters
     * @param int|string|null $invokeId
     *
     * @return int|string
     */
    protected function send($commandName, array $parameters = array(), $invokeId = null)
    {
        if (null == $invokeId) {
            $invokeId = $this->redis->incr($this->getKey('invokeId'));
        }

        $nextAvailableTime = (float) ConfigurationLoader::get('client.request.overload.available');
        $this->lastCall = microtime(true) + $nextAvailableTime;
        $this->con->send(json_encode([
            'invokeId'   => $invokeId,
            'command'    => $commandName,
            'parameters' => $parameters
        ]));

        return $invokeId;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return sprintf('async #%d (%s)', $this->clientId, $this->getRegion());
    }
}