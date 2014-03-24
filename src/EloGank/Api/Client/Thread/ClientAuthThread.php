<?php

namespace EloGank\Api\Client\Thread;

use EloGank\Api\Client\LOLClient;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class ClientAuthThread extends \Thread
{
    /**
     * @var LOLClient
     */
    protected $client;


    /**
     * @param LOLClient $client
     */
    public function __construct(LOLClient $client)
    {
        $this->client = $client;
    }

    /**
     *
     */
    public function run()
    {
        require __DIR__ . '/../../../../../vendor/autoload.php';

        $this->client->auth();
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return $this->client->isAuthenticated();
    }

    /**
     * @return LOLClient
     */
    public function getClient()
    {
        return $this->client;
    }
}