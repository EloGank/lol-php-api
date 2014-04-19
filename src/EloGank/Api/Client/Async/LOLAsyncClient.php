<?php

namespace EloGank\Api\Client\Async;

use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Logger\LoggerFactory;
use EloGank\Api\Model\Region\RegionInterface;
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
     * @var LoggerInterface
     */
    protected $logger;


    /**
     * @param Client          $redis
     * @param int             $accountKey
     * @param int             $clientId
     * @param RegionInterface $region
     * @param int             $port
     */
    public function __construct(Client $redis, $accountKey, $clientId, RegionInterface $region, $port)
    {
        $rootFolder = __DIR__ . '/../../../../..';

        $this->pidPath  = $rootFolder . '/' . ConfigurationLoader::get('cache.path') . '/clientpids/client_' . $clientId . '.pid';
        $this->redis    = $redis;
        $this->clientId = $clientId;
        $this->region   = $region;
        $this->port     = $port;

        $this->con      = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_PUSH);
        $this->logger   = LoggerFactory::create();

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
     * @return bool
     */
    public function isAuthenticated()
    {
        $message = $this->redis->brpop('elogank.api.client.authentication', 20);
        if (isset($message[0])) {
            return json_decode($message[1], true)['is_authenticated'];
        }

        $this->error = 'Asynchronous client did not respond';

        return false;
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
     * Kill the client pid
     */
    public function kill()
    {
        // Client doesn't exist
        if (!is_file($this->pidPath)) {
            return;
        }

        $pid = (int) file_get_contents($this->pidPath);
        unlink($this->pidPath);

        if (posix_kill($pid, SIGKILL)) {
            $this->logger->debug('Client #' . $this->clientId . ' (pid: #' . $pid . ') has been killed');
        }
        else {
            $this->logger->error('Cannot kill the client #' . $this->clientId . ' (pid: #' . $pid . '), please kill this client manually');
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