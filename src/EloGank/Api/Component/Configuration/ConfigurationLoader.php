<?php

namespace EloGank\Api\Component\Configuration;

use EloGank\Api\Component\Configuration\Exception\ConfigurationFileNotFoundException;
use EloGank\Api\Component\Configuration\Exception\ConfigurationKeyNotFoundException;
use Symfony\Component\Yaml\Parser;

/**
 * Basic configuration file
 *
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ConfigurationLoader
{
    /**
     * @var array
     */
    private static $configs;

    /**
     * @var array
     */
    private static $configsAsString;


    /**
     * @return array
     *
     * @throws Exception\ConfigurationFileNotFoundException
     */
    private static function load()
    {
        if (!isset(self::$configs)) {
            $path =  __DIR__ . '/../../../../../config/config.yml';

            if (!is_file($path)) {
                throw new ConfigurationFileNotFoundException('The configuration file (' . $path . ') is not found');
            }

            $parser = new Parser();
            self::$configs = $parser->parse(file_get_contents($path));
        }

        return self::$configs;
    }

    /**
     * @param string $name
     *
     * @return mixed
     *
     * @throws Exception\ConfigurationKeyNotFoundException
     */
    public static function get($name)
    {
        $name = 'config.' . $name;
        if (isset(self::$configsAsString[$name])) {
            return self::$configsAsString[$name];
        }

        $configs = self::load();
        $parts = explode('.', $name);
        $config = $configs;

        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                throw new ConfigurationKeyNotFoundException('The configuration key "' . $name . '" is not found');
            }

            $config = $config[$part];
        }

        // Save to avoid later iteration
        self::$configsAsString[$name] = $config;

        return $config;
    }
}