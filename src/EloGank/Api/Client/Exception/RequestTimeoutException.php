<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Client\Exception;

use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Server\Exception\ServerException;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class RequestTimeoutException extends ServerException
{
    /**
     * @var LOLClientInterface
     */
    protected $client;


    /**
     * @param string             $message
     * @param LOLClientInterface $client
     */
    public function __construct($message, LOLClientInterface $client = null)
    {
        $this->client = $client;

        parent::__construct($message);
    }

    /**
     * @return LOLClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param LOLClientInterface $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }
}