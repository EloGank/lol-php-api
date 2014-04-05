<?php

namespace EloGank\Api\Logger;

use EloGank\Api\Configuration\ConfigurationLoader;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
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
     * @param string $name
     *
     * @return LoggerInterface
     */
    public static function create($name = 'EloGankAPI')
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
                    new RotatingFileHandler(
                        ConfigurationLoader::get('log.path'),
                        ConfigurationLoader::get('log.max_file'),
                        $verbosity
                    )
                )
            );
        }

        return self::$logger;
    }
}