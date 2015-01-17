<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Controller;

use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Component\Controller\Controller;

/**
 * This is a common controller, used by default API calls.
 *
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class CommonController extends Controller
{
    /**
     * @param string       $destination
     * @param string       $service
     * @param string|array $parameters
     */
    public function commonCall($destination, $service, $parameters)
    {
        $this->onClientReady(function (LOLClientInterface $client) use ($destination, $service, $parameters) {
            $this->fetchResult(
                $client->invoke($destination, $service, $parameters),
                null, // callback
                null, // timeout
                $this->isOverloadServiceException($destination, $service)
            );
        });

        $this->sendResponse(function ($response) {
            return $response;
        });
    }

    /**
     * Some routes return a "NULL" response body in case of not found item.
     * This response is the same as the client is overloaded (temporary banned from the server).
     * To avoid client reconnection, some exceptions are created.
     *
     * @param string $destination
     * @param string $service
     *
     * @return bool
     */
    protected function isOverloadServiceException($destination, $service)
    {
        return 'summonerService' == $destination && 'getSummonerByName' == $service
               || 'gameService' == $destination && 'retrieveInProgressSpectatorGameInfo' == $service;
    }
} 