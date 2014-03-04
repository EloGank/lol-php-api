<?php

namespace EloGank\Api\Configuration;

use EloGank\Api\Configuration\Exception\ConfigurationFileNotFoundException;

/**
 * Basic configuration file
 *
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ConfigLoader
{
    /**
     * @param string $filePath
     *
     * @return array
     *
     * @throws Exception\ConfigurationFileNotFoundException
     */
    public static function load($filePath)
    {
        if (!is_file($filePath)) {
            throw new ConfigurationFileNotFoundException('The configuration file (' . $filePath . ') is not found');
        }

        return \Spyc::YAMLLoad($filePath);
    }
}