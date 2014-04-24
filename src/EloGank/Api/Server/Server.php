<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Server;

use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Component\Logging\LoggerFactory;
use EloGank\Api\Manager\ApiManager;
use EloGank\Api\Server\Exception\MalformedClientInputException;
use EloGank\Api\Server\Exception\ServerException;
use Psr\Log\LoggerInterface;
use React\Socket\Connection;
use React\Socket\Server as SocketServer;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class Server
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ApiManager
     */
    protected $apiManager;


    /**
     * @param ApiManager $apiManager
     */
    public function __construct(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
        $this->logger     = LoggerFactory::create();
    }

    /**
     * Start & init the API
     */
    public function listen()
    {
        // Init API
        $this->apiManager->init();
        if (!$this->apiManager->connect()) {
            throw new \RuntimeException('There is no ready client, aborted');
        }

        // Init server
        $loop   = $this->apiManager->getLoop();
        $socket = new SocketServer($loop);

        $socket->on('connection', function ($conn) {
            /** @var Connection $conn */
            $this->logger->debug(sprintf('Client [%s] is connected to server', $conn->getRemoteAddress()));

            $conn->getBuffer()->on('full-drain', function () use ($conn) {
                $conn->close();
            });

            $conn->on('data', function ($rawData) use ($conn) {
                $this->logger->debug(sprintf('Client sent: %s', $rawData));
                $data = json_decode($rawData, true);

                try {
                    if (!$this->isValidInput($data)) {
                        throw new MalformedClientInputException('The input sent to the server is maformed');
                    }

                    $response = $this->apiManager->getRouter()->process($this->apiManager, $data);

                    $conn->write($response);

                    // TODO do some action here, like write the response to the client
                }
                catch (ServerException $e) {
                    $this->logger->error($e->getMessage());

                    $conn->write($e->toJson());
                }
            });
        });

        $port = ConfigurationLoader::get('server.port');
        $bind = ConfigurationLoader::get('server.bind');

        $this->logger->info(sprintf('Listening on %s:%d', $bind == '0.0.0.0' ? '*' : $bind, $port));
        $socket->listen($port, $bind);

        $loop->run();
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function isValidInput(array $data)
    {
        if (!isset($data['route']) || !isset($data['parameters'])) {
            return false;
        }

        return true;
    }
} 