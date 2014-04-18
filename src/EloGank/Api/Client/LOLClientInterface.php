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

    public function kill();
}