<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Client\Factory;

use EloGank\Api\Client\LOLClient;
use EloGank\Api\Client\LOLClientAsync;
use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Component\Configuration\Exception\ConfigurationKeyNotFoundException;
use EloGank\Api\Model\Region\Exception\RegionNotFoundException;
use EloGank\Api\Model\Region\RegionInterface;
use Predis\Client;
use Psr\Log\LoggerInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ClientFactory
{
    /**
     * @param LoggerInterface $logger
     * @param Client          $redis
     * @param int             $accountKey
     * @param string          $clientId
     * @param bool            $forceSynchronous
     *
     * @return LOLClientInterface
     *
     * @throws \RuntimeException
     */
    public static function create(LoggerInterface $logger, Client $redis, $accountKey, $clientId, $forceSynchronous = false)
    {
        $configs = ConfigurationLoader::get('client.accounts')[$accountKey];
        $port = (int) ConfigurationLoader::get('client.async.startPort');
        $port += $clientId - 1;

        // Custom client port
        if (isset($configs['async']['port'])) {
            $port = $configs['async']['port'];
        }

        // Async process
        if (!$forceSynchronous && true === ConfigurationLoader::get('client.async.enabled')) {
            return new LOLClientAsync(
                $logger,
                $redis,
                $accountKey,
                $clientId,
                self::createRegion($configs['region']),
                $port
            );
        }

        // Sync process
        return new LOLClient(
            $logger,
            $redis,
            $clientId,
            self::createRegion($configs['region']),
            $configs['username'],
            $configs['password'],
            ConfigurationLoader::get('client.version'),
            'en_US',
            $port
        );
    }

    /**
     * @param string $regionUniqueName
     *
     * @return RegionInterface
     *
     * @throws RegionNotFoundException
     */
    private static function createRegion($regionUniqueName)
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
}