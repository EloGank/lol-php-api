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
     * Authenticate the client
     */
    public function authenticate()
    {
        $this->con->connect('tcp://127.0.0.1:' . $this->port);

        $this->send('authenticate');
    }

    /**
     * @param $destination
     * @param $operation
     * @param array|string $parameters
     * @param string $packetClass
     * @param array $headers
     * @param array $body
     *
     * @return $this
     */
    public function invoke($destination, $operation, $parameters = array(), $packetClass = 'flex.messaging.messages.RemotingMessage', $headers = array(), $body = array())
    {
        $this->send('syncInvoke', [
            $destination,
            $operation,
            $parameters
        ]);

        return $this;
    }

    /**
     * @param int $timeout
     *
     * @return array
     */
    public function getResults($timeout = 10)
    {
        $message = $this->redis->brpop('elogank.api.clients.' . $this->clientId . '.syncInvoke', $timeout);

        return unserialize($message[1]);
    }

    /**
     * @return null|bool
     */
    public function isAuthenticated()
    {
        $message = $this->redis->rpop('elogank.api.clients.' . $this->clientId . '.authenticate');
        if (null == $message) {
            return null;
        }

        return unserialize($message)['result'];
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
     * @param string $commandName
     * @param array  $parameters
     */
    protected function send($commandName, array $parameters = array())
    {
        $this->lastCall = microtime(true) + 0.03;
        $this->con->send(json_encode([
            'command'    => $commandName,
            'parameters' => $parameters
        ]));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('async #%d (%s)', $this->clientId, $this->getRegion());
    }
}