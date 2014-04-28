<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Manager;

use EloGank\Api\Client\Factory\ClientFactory;
use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Component\Routing\Router;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Component\Logging\LoggerFactory;
use EloGank\Api\Model\Region\Exception\RegionNotFoundException;
use Predis\Client;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use Symfony\Component\Yaml\Parser;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ApiManager
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var LOLClientInterface[]
     */
    protected $clients = [];

    /**
     * @var int
     */
    protected $clientId = 1;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Client
     */
    protected $redis;


    /**
     *
     */
    public function __construct()
    {
        $this->logger = LoggerFactory::create();
    }

    /**
     * Init the API components
     */
    public function init()
    {
        $this->loop = Factory::create();

        // Catch signals
        $this->loop->addPeriodicTimer(1, function () {
            pcntl_signal_dispatch();
        });

        // Clients logging
        if (true === ConfigurationLoader::get('client.async.enabled')) {
            $this->loop->addPeriodicTimer(0.5, function () {
                LoggerFactory::subscribe();
            });
        }

        // Init router
        $this->router = new Router();
        $this->router->init();

        // Init redis
        $this->redis = new Client(sprintf('tcp://%s:%d', ConfigurationLoader::get('client.async.redis.host'), ConfigurationLoader::get('client.async.redis.port')));

        // Async processes
        if (true === ConfigurationLoader::get('client.async.enabled')) {
            $this->clean(true);

            $this->catchSignals();
        }
        else {
            $this->logger->warning('You use the slow mode (synchronous), you can use the fast mode (asynchronous) by setting the configuration "client.async.enabled" to "true"');
        }
    }

    /**
     * Create client instances & auth
     *
     * @return bool True if one or more clients are connected, false otherwise
     */
    public function connect()
    {
        $this->logger->info('Starting clients...');

        $tmpClients = [];
        $accounts = ConfigurationLoader::get('client.accounts');
        foreach ($accounts as $accountKey => $account) {
            $client = ClientFactory::create($this->logger, $this->redis, $accountKey, $this->getNextClientId());
            $client->authenticate();

            $tmpClients[] = $client;
        }

        $nbClients = count($tmpClients);
        $isAsync = true === ConfigurationLoader::get('client.async.enabled');
        $i = 0;

        /** @var LOLClientInterface $client */
        while ($i < $nbClients) {
            $deleteClients = [];
            foreach ($tmpClients as $j => $client) {
                $isAuthenticated = $client->isAuthenticated();
                if (null !== $isAuthenticated) {
                    if (true === $isAuthenticated) {
                        $this->clients[$client->getRegion()][] = $client;
                        $this->logger->info('Client ' . $client . ' is connected');
                    }
                    else {
                        if ($isAsync) {
                            $this->cleanAsyncClients(false, $client);
                        }
                    }

                    $i++;
                    $deleteClients[] = $j;
                }
            }

            foreach ($deleteClients as $deleteClientId) {
                unset($tmpClients[$deleteClientId]);
            }

            if ($isAsync) {
                pcntl_signal_dispatch();
                LoggerFactory::subscribe();
                sleep(1);
            }
        }

        // No connected client, abort
        $clientCount = count($this->clients);
        if (0 == $clientCount) {
            return false;
        }

        $totalClientCount = count($accounts);
        $message = sprintf('%d/%d client%s successfully connected', $clientCount, $totalClientCount, $clientCount > 1 ? 's' : '');
        if ($clientCount < $totalClientCount) {
            $this->logger->alert('Only ' . $message);
        }
        else {
            $this->logger->info($message);
        }

        return true;
    }

    /**
     * Catch signals before the API server is killed and kill all the asynchronous clients
     */
    protected function catchSignals()
    {
        $killClients = function () {
            $this->clean();

            exit(0);
        };

        pcntl_signal(SIGINT, $killClients);
        pcntl_signal(SIGTERM, $killClients);
    }

    /**
     * @param bool $throwException
     */
    public function clean($throwException = false)
    {
        $this->cleanAsyncClients($throwException);

        if (ConfigurationLoader::get('client.async.enabled')) {
            $this->clearCache();
        }
    }

    /**
     * Delete all keys from redis
     */
    protected function clearCache()
    {
        $this->logger->info('Clearing cache...');

        $keys = $this->redis->keys('elogank.api.*');
        foreach ($keys as $key) {
            $this->redis->del($key);
        }
    }

    /**
     * Cleaning all asynchronous client processes registed by cache files
     *
     * @param bool                    $throwException
     * @param null|LOLClientInterface $client
     *
     * @throws \RuntimeException
     */
    protected function cleanAsyncClients($throwException = false, $client = null)
    {
        $this->logger->info('Cleaning cached async clients...');

        $cachePath = __DIR__ . '/../../../../' . ConfigurationLoader::get('cache.path') . '/' . 'clientpids';
        if (!is_dir($cachePath)) {
            if (!mkdir($cachePath, 0777, true)) {
                throw new \RuntimeException('Cannot write in the cache folder');
            }
        }

        if (null != $client) {
            $path = $cachePath . '/client_' . $client->getId() . '.pid';

            if (!is_file($path)) {
                return;
            }

            $this->killClient($path, $throwException, $client);
        }
        else {
            $iterator = new \DirectoryIterator($cachePath);
            foreach ($iterator as $pidFile) {
                if ($pidFile->isDir()) {
                    continue;
                }

                $this->killClient($pidFile->getRealPath(), $throwException);
            }
        }
    }

    /**
     * @param string                  $path
     * @param bool                    $throwException
     * @param null|LOLClientInterface $client
     *
     * @throws \RuntimeException
     */
    protected function killClient($path, $throwException, $client = null)
    {
        $pid = (int) file_get_contents($path);

        // Test if process is still running
        $output = [];
        exec('ps ' . $pid, $output);

        if (!isset($output[1])) {
            if (null != $client) {
                $this->logger->debug('Client ' . $client . ' (pid: #' . $pid . ') not running, deleting cache pid file');
            }
            else {
                $this->logger->debug('Process #' . $pid . ' not running, deleting cache pid file');
            }

            unlink($path);

            return;
        }

        // Kill
        if (posix_kill($pid, SIGKILL)) {
            if (null != $client) {
                $this->logger->debug('Client ' . $client . ' (pid: #' . $pid . ') has been killed');
            }
            else {
                $this->logger->debug('Process #' . $pid . ' has been killed');
            }

            unlink($path);
        }
        else {
            if ($throwException) {
                throw new \RuntimeException('Cannot kill the process #' . $pid . ', please kill this process manually');
            }

            $this->logger->critical('Cannot kill the process #' . $pid . ', please kill this process manually');
        }
    }

    /**
     * @return int
     */
    protected function getNextClientId()
    {
        return $this->clientId++;
    }

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * The RTMP LoL API will temporary ban you if you call too many times a service<br />
     * To avoid this limitation, you must wait before making a new request
     *
     * @param string $regionUniqueName
     *
     * @return LOLClientInterface
     *
     * @throws RegionNotFoundException When there is not client with the selected region unique name
     */
    public function getClient($regionUniqueName)
    {
        if (!isset($this->clients[$regionUniqueName])) {
            throw new RegionNotFoundException('There is no registered client with the region "' . $regionUniqueName . '"');
        }

        $nextAvailableTime = (float) ConfigurationLoader::get('client.request.overload.available');
        $nextAvailableTime /= 2;

        while (true) {
            foreach ($this->clients[$regionUniqueName] as $client) {
                if ($client->isAvailable()) {
                    return $client;
                }
            }

            sleep($nextAvailableTime);
        }
    }
}