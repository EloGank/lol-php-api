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

use EloGank\Api\Client\Formatter\ResultFormatter;
use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Manager\ApiManager;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
abstract class Controller
{
    /**
     * @var ApiManager
     */
    protected $apiManager;


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
     * Transform the result into an array
     *
     * @param int $invokeId
     * @param int $timeout
     *
     * @return array
     */
    protected function getResults($invokeId, $timeout = null)
    {
        if (null == $timeout) {
            $timeout = ConfigurationLoader::get('client.request.timeout');
        }

        $client = $this->getClient();
        $results = $client->getResults($invokeId, $timeout);
        $formatter = new ResultFormatter();

        // RTMP API return error
        if ('_error' == $results['result']) {
            $errorParams = $formatter->format($results['data']->getData()->rootCause);

            $results = [
                'success'   => false,
                'caused_by' => $errorParams['rootCauseClassname'],
                'message'   => $errorParams['message']
            ];

            return $results;
        }

        $results = $formatter->format($results['data']->getData()->body);
        $results['success'] = true;

        return $results;
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