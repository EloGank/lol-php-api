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

use EloGank\Api\Client\Exception\ClientOverloadException;
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
     * @var string
     */
    protected $region;


    /**
     * @param ApiManager $apiManager
     * @param string     $region     The region unique name (like "EUW", "NA", ...)
     */
    public function __construct(ApiManager $apiManager, $region)
    {
        $this->apiManager = $apiManager;
        $this->region     = $region;
    }

    /**
     * Get the next available client
     *
     * @return LOLClientInterface
     */
    protected function getClient()
    {
        return $this->apiManager->getClient($this->region);
    }

    /**
     * Transform the result into an array
     *
     * @param int  $invokeId
     * @param int  $timeout
     * @param bool $bypassOverload Some API return nothing on error, we need to bypass overload system to<br />
     *                             avoid timeout issue.
     *
     * @return array
     */
    protected function getResults($invokeId, $timeout = null, $bypassOverload = false)
    {
        if (null == $timeout) {
            $timeout = ConfigurationLoader::get('client.request.timeout');
        }

        $client = $this->getClient();
        $response = $client->getResults($invokeId, $timeout);
        $formatter = new ResultFormatter();

        try {
            // RTMP API return error
            if ('_error' == $response['result']) {
                $errorParams = $formatter->format($response['data']->getData()->rootCause);

                $results = [
                    'success'   => false,
                    'error' => [
                        'caused_by' => $errorParams['rootCauseClassname'],
                        'message'   => $errorParams['message']
                    ]
                ];

                return $results;
            }

            return [
                'success' => true,
                'results' => $formatter->format($response['data']->getData()->body)
            ];
        }
        catch (ClientOverloadException $e) {
            if ($bypassOverload) {
                return [];
            }

            // Flag client as overloaded & retry
            $client->setIsOverloaded();

            return $this->getResults($invokeId, $timeout);
        }
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