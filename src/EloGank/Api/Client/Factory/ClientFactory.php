<?php

namespace EloGank\Api\Client\Factory;

use EloGank\Api\Client\Async\LOLAsyncClient;
use EloGank\Api\Client\LOLClient;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Component\Configuration\Exception\ConfigurationKeyNotFoundException;
use EloGank\Api\Model\Region\Exception\RegionNotFoundException;
use EloGank\Api\Model\Region\RegionInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ClientFactory
{
    /**
     * @param int    $accountKey
     * @param string $clientId
     * @param bool   $forceSynchronous
     *
     * @return LOLClient
     *
     * @throws \RuntimeException
     */
    public static function create($accountKey, $clientId, $forceSynchronous = false)
    {
        if (!$forceSynchronous && true === ConfigurationLoader::get('client.async.enabled')) {
            return new LOLAsyncClient($accountKey, $clientId);
        }

        $configs = ConfigurationLoader::get('client.accounts')[$accountKey];

        return new LOLClient($clientId, self::createRegion(
            $configs['region']), $configs['username'], $configs['password'],
            ConfigurationLoader::get('client.version'), ConfigurationLoader::get('client.locale')
        );
    }

    /**
     * @param $regionUniqueName
     *
     * @return RegionInterface
     *
     * @throws \EloGank\Api\Model\Region\Exception\RegionNotFoundException
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