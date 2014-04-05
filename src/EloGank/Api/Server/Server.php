<?php

namespace EloGank\Api\Server;

use EloGank\Api\Configuration\ConfigurationLoader;
use EloGank\Api\Logger\LoggerFactory;
use EloGank\Api\Manager\ApiManager;
use EloGank\Api\Server\Exception\MalformedClientInputException;
use EloGank\Api\Server\Exception\ServerException;
use Psr\Log\LoggerInterface;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
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
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var \React\Socket\Server
     */
    protected $socket;


    /**
     * @param ApiManager $apiManager
     */
    public function __construct(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
        $this->logger = LoggerFactory::create('Server');
    }

    /**
     *
     */
    public function listen()
    {
        // Init API
        $this->apiManager->init();
        //$this->apiManager->connect();

        // Init server
        $this->loop   = Factory::create();
        $this->socket = new SocketServer($this->loop);

        // TODO dev part
        $this->socket->on('connection', function ($conn) {
            /** @var Connection $conn */
            $this->logger->debug(sprintf('Client [%s] is connected to server', $conn->getRemoteAddress()));

            $conn->on('data', function ($rawData) use ($conn) {
                $this->logger->debug(sprintf('Client sent: %s', $rawData));
                $data = json_decode($rawData, true);

                try {
                    if (!$this->isValidInput($data)) {
                        throw new MalformedClientInputException('The input sent to the server is maformed');
                    }

                    $response = $this->apiManager->getRouter()->process($data);
                    var_dump($response);
                }
                catch (ServerException $e) {
                    $this->logger->error($e->getMessage());

                    $conn->write($e->toJson());
                }
                finally {
                    $conn->close();
                }

                $conn->getBuffer()->on('full-drain', function () use ($conn) {
                    $conn->close();
                });
            });
        });

        $port = ConfigurationLoader::get('server.port');
        $bind = ConfigurationLoader::get('server.bind');

        $this->logger->info(sprintf('Listening on %s:%d', $bind == '0.0.0.0' ? '*' : $bind, $port));
        $this->socket->listen($port, $bind);

        $this->loop->run();
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    private function isValidInput(array $data)
    {
        if (!isset($data['route']) || !isset($data['parameters'])) {
            return false;
        }

        return true;
    }
} 