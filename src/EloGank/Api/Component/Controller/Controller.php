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
use EloGank\Api\Client\Exception\RequestTimeoutException;
use EloGank\Api\Client\Formatter\ResultFormatter;
use EloGank\Api\Client\LOLClientInterface;
use EloGank\Api\Component\Callback\Callback;
use EloGank\Api\Component\Configuration\ConfigurationLoader;
use EloGank\Api\Component\Controller\Exception\ApiException;
use EloGank\Api\Manager\ApiManager;
use React\EventLoop\Timer\TimerInterface;
use React\Socket\Connection;

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
     * @var Connection
     */
    protected $conn;

    /**
     * @var array
     */
    protected $results       = [];

    /**
     * @var int
     */
    protected $responseCount = 0;

    /**
     * @var int
     */
    protected $invokeCount   = 0;

    /**
     * @var bool
     */
    protected $hasError      = false;

    /**
     * @var array
     */
    protected $listeners     = [];


    /**
     * @param ApiManager $apiManager The API manager
     * @param Connection $conn       The client connection
     * @param string     $region     The region unique name (like "EUW", "NA", ...)
     */
    public function __construct(ApiManager $apiManager, Connection $conn, $region)
    {
        $this->apiManager = $apiManager;
        $this->region     = $region;
        $this->conn       = $conn;
    }

    /**
     * Get the next available client
     *
     * @param callable $callback
     *
     * @return LOLClientInterface
     */
    protected function onClientReady(\Closure $callback)
    {
        $this->apiManager->getClient($this->region, $callback);
    }

    /**
     * Fetch the result from client, transform it into an array and store it into the $this->results array
     *
     * @param int           $invokeId
     * @param null|callable $resultsCallback This callback will format the result if needed
     * @param int           $timeout
     * @param bool          $bypassOverload  Some API return nothing on error, we need to bypass overload system to<br />
     *                                       avoid timeout issue.
     */
    protected function fetchResult($invokeId, \Closure $resultsCallback = null, $timeout = null, $bypassOverload = false)
    {
        $this->invokeCount++;

        if (null == $timeout) {
            $timeout = ConfigurationLoader::get('client.request.timeout');
        }

        $timedOut = time() + $timeout;
        $this->onClientReady(function (LOLClientInterface $client) use ($invokeId, $timedOut, $bypassOverload, $resultsCallback, $timeout) {
            $this->apiManager->getLoop()->addPeriodicTimer(0.0001, function (TimerInterface $timer) use ($invokeId, $timedOut, $bypassOverload, $client, $resultsCallback, $timeout) {
                if ($this->hasError) {
                    $timer->cancel();

                    return;
                }

                // Timeout process
                if (time() > $timedOut) {
                    $this->hasError = true;
                    $this->conn->emit('api-error', [
                        new RequestTimeoutException('Request timeout, the client will reconnect', $client)
                    ]);

                    $timer->cancel();

                    return null;
                }

                $resultParams = $client->getResult($invokeId);
                if (null == $resultParams) {
                    return;
                }

                list($data, $callback) = $resultParams;
                $formatter = new ResultFormatter();

                try {
                    // RTMP API return error
                    if ('_error' == $data['result']) {
                        $this->hasError = true;
                        $errorParams = $formatter->format($data['data']->getData()->rootCause);

                        $this->conn->emit('api-error', [
                            new ApiException($errorParams['rootCauseClassname'], $errorParams['message'])
                        ]);

                        $timer->cancel();

                        return;
                    }

                    $result = $formatter->format($data['data']->getData()->body);
                    if (null != $callback) {
                        if ($callback instanceof Callback) {
                            $result = $callback->getResult($result);
                        }
                        else {
                            $result = $callback($result);
                        }
                    }

                    if (null != $resultsCallback) {
                        $this->results = $resultsCallback($result, $this->results);
                    }
                    else {
                        $this->results[] = $result;
                    }

                    $this->responseCount++;
                    $timer->cancel();
                }
                catch (ClientOverloadException $e) {
                    if ($bypassOverload) {
                        $this->results[] = []; // empty response
                        $timer->cancel();
                    }

                    // Flag client as overloaded & retry
                    $client->setIsOverloaded();
                    $timer->cancel();

                    $this->fetchResult($invokeId, $resultsCallback, $timeout, $bypassOverload);
                }
            });
        });
    }

    /**
     * @param null|callable $callback This callback will format the results array
     */
    protected function sendResponse(\Closure $callback = null)
    {
        $this->apiManager->getLoop()->addPeriodicTimer(0.0001, function (TimerInterface $timer) use ($callback) {
            if ($this->hasError) {
                $timer->cancel();

                return;
            }

            if (0 < $this->invokeCount && $this->invokeCount == $this->responseCount) {
                // Convert indexed array to associative if count = 1
                if (isset($this->results[0]) && !isset($this->results[1])) {
                    $this->results = $this->results[0];
                }

                $this->conn->emit('api-response', [[
                        'success' => true,
                        'result'  => null != $callback ? $callback($this->results) : $this->results
                    ]
                ]);

                $timer->cancel();
            }
        });
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
        $this->apiManager->getRouter()->process($this->apiManager, $this->conn, array(
            'route'      => $route,
            'region'     => $this->region,
            'parameters' => $parameters
        ));
    }

    /**
     * Revoke client connection listeners
     */
    protected function revokeListeners()
    {
        foreach (['response', 'error'] as $listener) {
            $this->listeners[$listener] = $this->conn->listeners('api-' . $listener);
            $this->conn->removeAllListeners('api-' . $listener);
        }
    }

    /**
     * Apply revoked client connection listeners
     */
    protected function applyListeners()
    {
        foreach ($this->listeners as $listenerName => $listeners) {
            foreach ($listeners as $listener) {
                $this->conn->on('api-' . $listenerName, $listener);
            }
        }

        $this->listeners = [];
    }
}