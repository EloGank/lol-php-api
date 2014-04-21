<?php

namespace EloGank\Api\Client\Async;

use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Model\Region\RegionInterface;
use Predis\Client;
use Psr\Log\LoggerInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class LOLAsyncClient implements LOLClientInterface
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
        $rootFolder     = __DIR__ . '/../../../../..';

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
     * Connect the client to the worker and authenticate the client
     */
    public function authenticate()
    {
        $this->con->connect('tcp://127.0.0.1:' . $this->port);

        $this->send('authenticate', array(), 'authenticate');
    }

    /**
     * @param $destination
     * @param $operation
     * @param array $parameters
     * @param callable $callback
     * @param string $packetClass
     * @param array $headers
     * @param array $body
     *
     * @return int|string
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
     * @param int $invokeId
     * @param int $timeout
     *
     * @return array
     */
    public function getResults($invokeId, $timeout = 10)
    {
        $message = $this->redis->brpop($this->getKey('client.commands.' . $invokeId), $timeout);
        if (null == $message) {
            // TODO handle exception when timeout is reached

            return null;
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
     * @return null|bool
     */
    public function isAuthenticated()
    {
        $message = $this->redis->rpop($this->getKey('client.commands.authenticate'));
        if (null == $message) {
            return null;
        }

        return unserialize($message);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->clientId;
    }

    /**
     * @return RegionInterface
     */
    public function getRegion()
    {
        return $this->region->getUniqueName();
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->lastCall <= microtime(true);
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

        $this->lastCall = microtime(true) + 0.03;
        $this->con->send(json_encode([
            'invokeId'   => $invokeId,
            'command'    => $commandName,
            'parameters' => $parameters
        ]));

        return $invokeId;
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
     * @return string
     */
    public function __toString()
    {
        return sprintf('async #%d (%s)', $this->clientId, $this->getRegion());
    }
}