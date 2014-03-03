<?php

namespace EloGank\Api\Configuration;

use EloGank\Api\Configuration\Exception\ConfigurationFileNotFoundException;
use EloGank\Api\Configuration\Exception\ConfigurationNotFoundException;

/**
 * Basic configuration file
 *
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class Config
{
    /**
     * @var array
     */
    private static $configs = array();


    /**
     * @param string $filePath
     *
     * @throws Exception\ConfigurationFileNotFoundException
     */
    public static function load($filePath)
    {
        if (!is_file($filePath)) {
            throw new ConfigurationFileNotFoundException('The configuration file (' . $filePath . ') is not found');
        }

        self::$configs = \Spyc::YAMLLoad($filePath);
    }

    /**
     * @param string $name
     *
     * @return mixed
     *
     * @throws Exception\ConfigurationNotFoundException
     */
    public static function get($name)
    {
        if (!isset(self::$configs[$name])) {
            throw new ConfigurationNotFoundException('The configuration with name "' . $name . '" is not found');
        }

        return self::$configs[$name];
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public static function set($name, $value)
    {
        self::$configs[$name] = $value;
    }
} 