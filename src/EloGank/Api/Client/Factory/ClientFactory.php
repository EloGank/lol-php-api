<?php

namespace EloGank\Api\Client\Factory;

use EloGank\Api\Client\Async\LOLAsyncClient;
use EloGank\Api\Client\LOLClient;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Component\Configuration\Exception\ConfigurationKeyNotFoundException;
use EloGank\Api\Model\Region\Exception\RegionNotFoundException;
use EloGank\Api\Model\Region\RegionInterface;
use Predis\Client;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ClientFactory
{
    /**
     * @param Client $redis
     * @param int    $accountKey
     * @param string $clientId
     * @param bool   $forceSynchronous
     *
     * @return LOLClient
     *
     * @throws \RuntimeException
     */
    public static function create(Client $redis, $accountKey, $clientId, $forceSynchronous = false)
    {
        $configs = ConfigurationLoader::get('client.accounts')[$accountKey];
        $port = ConfigurationLoader::get('client.async.startPort');
        if (isset($configs['async']['port'])) {
            $port = $configs['async']['port'];
        }

        if (!$forceSynchronous && true === ConfigurationLoader::get('client.async.enabled')) {
            return new LOLAsyncClient($redis, $accountKey, $clientId, self::createRegion($configs['region']), $port);
        }

        return new LOLClient(
            $clientId, self::createRegion($configs['region']), $configs['username'], $configs['password'],
            ConfigurationLoader::get('client.version'), ConfigurationLoader::get('client.locale'), $port
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