<?php

namespace EloGank\Api\Manager;

use EloGank\Api\Client\LOLClient;
use EloGank\Api\Client\Thread\ClientAuthThread;
use EloGank\Api\Configuration\ConfigurationLoader;
use EloGank\Api\Configuration\Exception\ConfigurationKeyNotFoundException;
use EloGank\Api\Logger\LoggerFactory;
use EloGank\Api\Region\Exception\RegionNotFoundException;
use EloGank\Api\Region\RegionInterface;
use Psr\Log\LoggerInterface;
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
     * @var
     */
    protected $clients;

    /**
     * @var int
     */
    protected $clientId = 1;


    /**
     *
     */
    public function __construct()
    {
        $this->logger = LoggerFactory::create('ApiManager');
    }

    /**
     *
     */
    public function connect()
    {
        $threads = [];
        foreach (ConfigurationLoader::get('client.accounts') as $account) {
            $clientId = $this->getClientId();
            $thread = new ClientAuthThread(new LOLClient(
                $clientId,
                $this->createRegion(
                    $account['region']),
                    $account['username'],
                    $account['password'],
                    ConfigurationLoader::get('client.version'),
                    ConfigurationLoader::get('client.locale')
                )
            );

            $thread->start(PTHREADS_INHERIT_NONE);

            $threads[] = $thread;
        }

        $this->clients = [];
        $threadsLength = count($threads);

        while (count($this->clients) < $threadsLength) {
            /** @var ClientAuthThread $thread */
            foreach ($threads as $thread) {
                if ($thread->join()) {
                    $client = $thread->getClient();
                    $this->clients[] = $client;

                    if ($thread->isSuccess()) {
                        $this->logger->info('Client #' . $client->getClientId() . ' (' . $client->getRegion() . ') is connected');
                    }
                    else {
                        $this->logger->error('Client #' . $client->getClientId() . ' (' . $client->getRegion() . ') cannot be connected.');
                    }
                }
            }
        }
    }

    /**
     * @param $regionUniqueName
     *
     * @return RegionInterface
     *
     * @throws \EloGank\Api\Region\Exception\RegionNotFoundException
     */
    protected function createRegion($regionUniqueName)
    {
        try {
            $region = ConfigurationLoader::get('region.servers.' . $regionUniqueName);
        }
        catch (ConfigurationKeyNotFoundException $e) {
            throw new RegionNotFoundException('The region with unique name "' . $regionUniqueName . '" is not found');
        }

        $class = ConfigurationLoader::get('region.class');

        return new $class($regionUniqueName, $region['name'], $region['server'], $region['loginQueue']);
    }

    /**
     * @return int
     */
    protected function getClientId()
    {
        return $this->clientId++;
    }
}