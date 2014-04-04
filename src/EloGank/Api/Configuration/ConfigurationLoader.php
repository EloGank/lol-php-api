<?php

namespace EloGank\Api\Configuration;

use EloGank\Api\Configuration\Exception\ConfigurationFileNotFoundException;
use EloGank\Api\Configuration\Exception\ConfigurationKeyNotFoundException;
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
     * @return array
     *
     * @throws Exception\ConfigurationFileNotFoundException
     */
    private static function load()
    {
        // FIXME need to be overrided, but for now, threads need to get this static value, maybe create a temporary file from the config.dist
        $path =  __DIR__ . '/../../../../config/config.yml';

        if (!isset(self::$configs)) {
            if (!is_file($path)) {
                throw new ConfigurationFileNotFoundException('The configuration file (' . $path . ') is not found');
            }

            $parser = new Parser();
            self::$configs = $parser->parse(file_get_contents($path));
        }

        return self::$configs;
    }

    /**
     * @param string $path
     */
    public static function setPath($path)
    {
        self::$path = $path;
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
        $configs = self::load();

        $name = 'config.' . $name;
        $parts = explode('.', $name);
        $config = $configs;

        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                throw new ConfigurationKeyNotFoundException('The configuration key "' . $name . '" is not found');
            }

            $config = $config[$part];
        }

        return $config;
    }
}