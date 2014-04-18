<?php

namespace EloGank\Api\Logger;

use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Logger\Handler\RedisHandler;
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
    private static $logger;

    /**
     * @var Client
     */
    private static $redisClient;

    /**
     * @var string
     */
    private static $logKey;


    /**
     * @param string $name
     * @param bool   $saveOnRedis
     *
     * @return LoggerInterface
     */
    public static function create($name = 'EloGankAPI', $saveOnRedis = false)
    {
        if (!isset(self::$logger)) {
            self::$redisClient = new Client('tcp://127.0.0.1:6379');

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
            if ($saveOnRedis && true === ConfigurationLoader::get('client.async.enabled')) {
                self::$logger->pushHandler(new RedisHandler(self::$redisClient, ConfigurationLoader::get('client.async.logKey'), $verbosity));
            }
        }

        return self::$logger;
    }

    /**
     * @return string|null
     *
     * @throws \RuntimeException
     */
    public static function subscribe()
    {
        if (!isset(self::$redisClient)) {
            throw new \RuntimeException('Redis client has not been initialised');
        }

        // FIXME improve configuration loader lazy loading
        if (!isset(self::$logKey)) {
            self::$logKey = ConfigurationLoader::get('client.async.logKey');
        }

        $log = self::$redisClient->rpop(self::$logKey);
        if (null == $log) {
            return;
        }

        list ($level, $message) = explode('|', $log);

        self::$logger->addRecord($level, $message);
    }
}