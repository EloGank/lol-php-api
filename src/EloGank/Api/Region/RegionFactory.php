<?php

namespace EloGank\Api\Region;

use EloGank\Api\Configuration\Config;
use EloGank\Api\Region\Exception\RegionNotFoundException;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class RegionFactory
{
    /**
     * @param string $uniqueName
     *
     * @return Region
     *
     * @throws Exception\RegionNotFoundException
     */
    public static function getRegion($uniqueName)
    {
        $regions = Config::get('regions');

        if (!isset($regions[$uniqueName])) {
            throw new RegionNotFoundException('The region with unique name "' . $uniqueName . '" is not found, please check the configuration file');
        }

        $region = $regions[$uniqueName];

        return new Region($uniqueName, $region['name'], $region['server'], $region['loginQueue']);
    }
} 