<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Process;

use EloGank\Api\Client\LOLClientInterface;
use Psr\Log\LoggerInterface;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class Process
{
    /**
     * @param string                  $pidPath
     * @param bool                    $throwException
     * @param LoggerInterface         $logger
     * @param LOLClientInterface|null $client
     *
     * @throws \RuntimeException
     */
    public static function killProcess($pidPath, $throwException, LoggerInterface $logger, LOLClientInterface $client = null)
    {
        $pid = (int) file_get_contents($pidPath);

        // Test if process is still running
        $output = [];
        exec('ps ' . $pid, $output);

        if (!isset($output[1])) {
            if (null != $client) {
                $logger->debug('Client ' . $client . ' (pid: #' . $pid . ') not running, deleting cache pid file');
            }
            else {
                $logger->debug('Process #' . $pid . ' not running, deleting cache pid file');
            }

            unlink($pidPath);

            return;
        }

        // Kill
        if (posix_kill($pid, SIGKILL)) {
            if (null != $client) {
                $logger->debug('Client ' . $client . ' (pid: #' . $pid . ') has been killed');
            }
            else {
                $logger->debug('Process #' . $pid . ' has been killed');
            }

            unlink($pidPath);
        }
        else {
            if ($throwException) {
                throw new \RuntimeException('Cannot kill the process #' . $pid . ', please kill this process manually');
            }

            $logger->critical('Cannot kill the process #' . $pid . ', please kill this process manually');
        }
    }
} 