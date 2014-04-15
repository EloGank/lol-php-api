<?php

namespace EloGank\Api\Client\Factory;

use EloGank\Api\Client\LOLClient;
use EloGank\Api\Configuration\ConfigurationLoader;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ClientFactory
{
    /**
     * @param $clientId
     * @param $region
     * @param $username
     * @param $password
     * @param $clientVersion
     * @param $locale
     *
     * @return LOLClient
     *
     * @throws \RuntimeException
     */
    public static function create($clientId, $region, $username, $password, $clientVersion, $locale)
    {
        if (true === ConfigurationLoader::get('client.async')) {
            throw new \RuntimeException('Not implemented yet !');
        }

        return new LOLClient($clientId, $region, $username, $password, $clientVersion, $locale);
    }
} 