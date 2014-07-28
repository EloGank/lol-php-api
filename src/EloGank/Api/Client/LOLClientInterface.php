<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Client;

use EloGank\Api\Component\Callback\Callback;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
interface LOLClientInterface
{
    /**
     * Authenticate the client
     */
    public function authenticate();

    /**
     * Return true if the client has been successfully authenticated, false otherwise,<br />
     * see getError() method to retrieve the error
     *
     * @return bool
     */
    public function isAuthenticated();

    /**
     * Clean all variables and reconnect
     */
    public function reconnect();

    /**
     * Return the client unique id
     *
     * @return int
     */
    public function getId();

    /**
     * Return the region unique name (EUW, NA, ...)
     *
     * @return string
     */
    public function getRegion();

    /**
     * When an error has occurred, you can retrieve it with this method
     *
     * @return string
     */
    public function getError();

    /**
     * Used by asynchronous client, return the client worker port
     *
     * @return int
     */
    public function getPort();

    /**
     * Return true if the client is available to handle a new request, false otherwise
     *
     * @return bool
     */
    public function isAvailable();

    /**
     * Flag the client as overloaded for a while
     */
    public function setIsOverloaded();

    /**
     * Invoke a new RTMP service method
     *
     * @param string                 $destination  The service manager name
     * @param string                 $operation    The service method name
     * @param array                  $parameters   The service method parameters
     * @param callable|Callback|null $callback     The callback will be called after parsing the packet,
     *                                             and retrieving the result.<br /> It must return the final result
     * @param string                 $packetClass  The packet class for the body
     * @param array                  $headers      The additional headers
     * @param array                  $body         The additional body
     *
     * @return int The invoke unique id
     */
    public function invoke($destination, $operation, $parameters = array(), $callback = null, $packetClass = 'flex.messaging.messages.RemotingMessage', $headers = array(), $body = array());

    /**
     * Do heartbeat to avoid being disconnected after being inactive
     *
     * @return int|array If int: the invoke id, otherwise it's the result array, depending if it's an async client or not
     */
    public function doHeartBeat();

    /**
     * @param int      $invokeId The invoke unique id
     * @param int|null $timeout  The timeout, after that the client will throw an exception
     *
     * @return array The index 0 is the result himself, and the index 1 is the callback, if provided
     */
    public function getResult($invokeId, $timeout = null);

    /**
     * @return string
     */
    public function __toString();
}