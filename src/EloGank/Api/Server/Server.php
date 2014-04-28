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
use EloGank\Api\Component\Exception\ArrayException;
use EloGank\Api\Component\Logging\LoggerFactory;
use EloGank\Api\Manager\ApiManager;
use EloGank\Api\Server\Exception\MalformedClientInputException;
use EloGank\Api\Server\Exception\UnknownFormatException;
use EloGank\Api\Server\Formatter\ClientFormatterInterface;
use EloGank\Api\Server\Formatter\JsonClientFormatter;
use EloGank\Api\Server\Formatter\NativeClientFormatter;
use EloGank\Api\Server\Formatter\XmlClientFormatter;
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
     * @var ClientFormatterInterface[]
     */
    protected $formatters;


    /**
     * @param ApiManager $apiManager
     */
    public function __construct(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
        $this->logger     = LoggerFactory::create();

        $this->formatters = [
            'native' => new NativeClientFormatter(),
            'json'   => new JsonClientFormatter(),
            'xml'    => new XmlClientFormatter()
        ];
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

            // On receive data
            $conn->on('data', function ($rawData) use ($conn) {
                $this->logger->debug(sprintf('Client sent: %s', $rawData));

                $data = json_decode($rawData, true);
                $format = null;

                if (isset($data['format'])) {
                    $format = $data['format'];
                }

                try {
                    if (!$this->isValidInput($data)) {
                        throw new MalformedClientInputException('The input sent to the server is maformed');
                    }

                    $response = $this->apiManager->getRouter()->process($this->apiManager, $data);

                    $conn->write($this->format($response, $format));
                }
                catch (ArrayException $e) {
                    $this->logger->error('Client [' . $conn->getRemoteAddress() . ']: ' . $e->getMessage());

                    $conn->write($this->format($e->toArray(), $format));
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
     * @param array  $results
     * @param string $format
     *
     * @return mixed
     *
     * @throws UnknownFormatException
     */
    protected function format(array $results, $format = null)
    {
        if (null == $format) {
            $format = ConfigurationLoader::get('server.format');
        }
        else {
            if (!isset($this->formatters[$format])) {
                throw new UnknownFormatException('Unknown format for "' . $format . '". Did you mean : "' . join(', ', array_keys($this->formatters)) . '" ?');
            }
        }

        return $this->formatters[$format]->format($results);
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function isValidInput(array $data)
    {
        if (!isset($data['region']) || !isset($data['route']) || !isset($data['parameters'])) {
            return false;
        }

        return true;
    }
} 