<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Component\Controller;

use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Manager\ApiManager;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
abstract class Controller
{
    /**
     * @var ApiManager
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
     * Get the next available client
     *
     * @return LOLClientInterface
     */
    protected function getClient()
    {
        return $this->apiManager->getClient();
    }

    /**
     * Call another controller method
     *
     * @param string $route
     * @param array  $parameters
     *
     * @return mixed
     */
    protected function call($route, array $parameters = array())
    {
        return $this->apiManager->getRouter()->process($this->apiManager, array(
            'route'      => $route,
            'parameters' => $parameters
        ));
    }
}