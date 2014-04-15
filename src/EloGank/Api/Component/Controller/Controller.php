<?php

namespace EloGank\Api\Component\Controller;

use EloGank\Api\Manager\ApiManager;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
abstract class Controller
{
    /**
     * @var \EloGank\Api\Manager\ApiManager
     */
    private $apiManager;


    /**
     * @param ApiManager $apiManager
     */
    public function __construct(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    /**
     * @return \EloGank\Api\Client\LOLClient
     */
    protected function getClient()
    {
        return $this->apiManager->getClient();
    }
}