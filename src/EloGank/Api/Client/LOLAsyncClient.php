<?php

namespace EloGank\Api\Client;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class LOLAsyncClient implements LOLClientInterface
{
    /**
     * @var int
     */
    private $clientId;


    /**
     * @param int $accountKey
     * @param int $clientId
     */
    public function __construct($accountKey, $clientId)
    {
        $this->clientId = $clientId;

        pclose(popen(sprintf('php %s/console elogank:client:create %d %d 2>&1 & echo $!', __DIR__ . '/../../../..', $accountKey, $this->clientId), 'r'));
    }

    public function authenticate()
    {

    }

    public function isAuthenticated()
    {
        // TODO: Implement isAuthenticated() method.
    }

    public function getId()
    {
        // TODO: Implement getId() method.
    }

    public function getRegion()
    {
        // TODO: Implement getRegion() method.
    }

    public function getError()
    {
        // TODO: Implement getError() method.
    }

} 