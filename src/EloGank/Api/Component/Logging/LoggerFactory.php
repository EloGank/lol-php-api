<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Component\Logging;

use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Component\Logging\Handler\RedisHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class LoggerFactory
{
    /**
     * @var LoggerInterface
     */
    protected static $logger;

    /**
     * @var Client
     */
    protected static $redisClient;


    /**
     * @param string $name
     * @param bool   $saveOnRedis
     *
     * @return LoggerInterface
     */
    public static function create($name = 'EloGankAPI', $saveOnRedis = false)
    {
        if (!isset(self::$logger)) {
            $verbosity = constant('Monolog\Logger::' . strtoupper(ConfigurationLoader::get('log.verbosity')));
            self::$logger = new Logger($name, array(
                    new ConsoleHandler(new ConsoleOutput(), true, array(
                        OutputInterface::VERBOSITY_NORMAL       => $verbosity,
                        OutputInterface::VERBOSITY_VERBOSE      => Logger::DEBUG,
                        OutputInterface::VERBOSITY_VERY_VERBOSE => Logger::DEBUG,
                        OutputInterface::VERBOSITY_DEBUG        => Logger::DEBUG
                    )),
                    new RotatingFileHandler(ConfigurationLoader::get('log.path'), ConfigurationLoader::get('log.max_file'), $verbosity)
                )
            );

            // Allow the server to retrieve clients logs
            if (true === ConfigurationLoader::get('client.async.enabled')) {
                self::$redisClient = new Client(sprintf('tcp://%s:%s', ConfigurationLoader::get('client.async.redis.host'), ConfigurationLoader::get('client.async.redis.port')));

                if ($saveOnRedis) {
                    self::$logger->pushHandler(new RedisHandler(self::$redisClient, ConfigurationLoader::get('client.async.redis.key') . '.client.logs', $verbosity));
                }
            }
        }

        return self::$logger;
    }

    /**
     * Show the asynchronous client logs in the main process logger (console)
     *
     * @return string|null
     *
     * @throws \RuntimeException
     */
    public static function subscribe()
    {
        if (!isset(self::$redisClient)) {
            throw new \RuntimeException('Redis client has not been initialised');
        }

        while (null != ($log = self::$redisClient->lpop(ConfigurationLoader::get('client.async.redis.key') . '.client.logs'))) {
            list ($level, $message) = explode('|', $log);
            self::$logger->addRecord($level, $message);
        }
    }
}