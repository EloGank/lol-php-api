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
     * When an error has occured, you can retrieve it with this method
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
     * @param string   $destination  The service manager name
     * @param string   $operation    The service method name
     * @param array    $parameters   The service method parameters
     * @param callable $callback     The callback will be called after parsing the packet,
     *                               and retrieving the result.<br /> It must return the final result
     * @param string   $packetClass  The packet class for the body
     * @param array    $headers      The additionnal headers
     * @param array    $body         The additionnal body
     *
     * @return int The invoke unique id
     */
    public function invoke($destination, $operation, $parameters = array(), \Closure $callback = null, $packetClass = 'flex.messaging.messages.RemotingMessage', $headers = array(), $body = array());

    /**
     * @param int $invokeId The invoke unique id
     * @param int $timeout  The timeout, after that the client will throw an exception
     *
     * @return array
     */
    public function getResults($invokeId, $timeout);

    /**
     * @return string
     */
    public function __toString();
}