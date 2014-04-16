<?php

namespace EloGank\Api\Client\Factory;

use EloGank\Api\Client\LOLClient;
use EloGank\Api\Configuration\ConfigurationLoader;
use EloGank\Api\Model\Region\RegionInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ClientFactory
{
    /**
     * @param string          $clientId
     * @param RegionInterface $region
     * @param string          $username
     * @param string          $password
     * @param string          $clientVersion
     * @param string          $locale
     *
     * @return LOLClient
     *
     * @throws \RuntimeException
     */
    public static function create($clientId, $region, $username, $password, $clientVersion, $locale)
    {
        if (true === ConfigurationLoader::get('client.async.enabled')) {
            throw new \RuntimeException('Not implemented yet !');
        }

        return new LOLClient($clientId, $region, $username, $password, $clientVersion, $locale);
    }
} 