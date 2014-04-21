<?php

namespace EloGank\Api\Client;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
interface LOLClientInterface
{
    public function authenticate();

    public function isAuthenticated();

    public function getId();

    public function getRegion();

    public function getError();

    public function getPort();

    public function isAvailable();

    public function invoke($destination, $operation, $parameters = array(), $packetClass = 'flex.messaging.messages.RemotingMessage', $headers = array(), $body = array());

    public function getResults($timeout = 10);

    public function __toString();
}