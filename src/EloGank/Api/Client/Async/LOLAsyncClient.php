<?php

namespace EloGank\Api\Client\Async;

use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Logger\LoggerFactory;
use Predis\Client;
use Psr\Log\LoggerInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class LOLAsyncClient implements LOLClientInterface
{
    /**
     * @var string
     */
    protected $pidPath;

    /**
     * @var int
     */
    protected $clientId;

    /**
     * @var \ZMQSocket
     */
    protected $con;

    /**
     * @var Client
     */
    protected $redis;

    /**
     * @var LoggerInterface
     */
    protected $logger;


    /**
     * @param int $accountKey
     * @param int $clientId
     */
    public function __construct($accountKey, $clientId)
    {
        $rootFolder = __DIR__ . '/../../../../..';

        $this->pidPath  = $rootFolder . '/' . ConfigurationLoader::get('cache.path') . '/client_' . $clientId . '.pid';
        $this->clientId = $clientId;
        $this->con      = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_PUSH);
        $this->redis    = new Client('tcp://127.0.0.1:6379');
        $this->logger   = LoggerFactory::create();

        // Create process
        popen(sprintf('php %s/console elogank:client:create %d %d > /dev/null 2>&1 & echo $! > %s', $rootFolder, $accountKey, $clientId, $this->pidPath), 'r');
    }

    public function authenticate()
    {
        $this->con->connect('tcp://127.0.0.1:5555');

        $this->send('authenticate');
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        $message = $this->redis->brpop('elogank.api.client.authentication', 20);
        if (isset($message[0])) {
            return json_decode($message[1], true)['is_authenticated'];
        }

        return false;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->clientId;
    }

    public function getRegion()
    {
        // TODO: Implement getRegion() method.
    }

    public function getError()
    {
        // TODO: Implement getError() method.
    }

    /**
     * Kill the client pid
     */
    public function kill()
    {
        $pid = file_get_contents($this->pidPath);
        unlink($this->pidPath);

        if (posix_kill((int) $pid, SIGTERM)) {
            $this->logger->debug('Client #' . $this->clientId . ' has been killed');
        }
        else {
            $this->logger->error('Cannot kill the client #' . $this->clientId . ', please kill this client manually');
        }
    }

    /**
     * @param string $commandName
     * @param array  $parameters
     */
    protected function send($commandName, array $parameters = array())
    {
        $this->con->send(json_encode([
            'command'    => $commandName,
            'parameters' => $parameters
        ]));
    }

}