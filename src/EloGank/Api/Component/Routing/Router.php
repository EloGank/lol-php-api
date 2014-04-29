<?php

/*
 * This file is part of the "EloGank League of Legends API" package.
 *
 * https://github.com/EloGank/lol-php-api
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EloGank\Api\Component\Routing;

use EloGank\Api\Component\Controller\Exception\UnknownControllerException;
use EloGank\Api\Component\Routing\Exception\MalformedRouteException;
use EloGank\Api\Component\Routing\Exception\MissingApiRoutesFileException;
use EloGank\Api\Component\Routing\Exception\MissingParametersException;
use EloGank\Api\Component\Routing\Exception\UnknownRouteException;
use EloGank\Api\Controller\CommonController;
use EloGank\Api\Manager\ApiManager;
use Symfony\Component\Yaml\Parser;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class Router
{
    /**
     * This is the common routes, listed in the config/api_routes.yml file
     *
     * @var array
     */
    protected $commonRoutes = [];

    /**
     * @var array
     */
    protected $customRoutes = [];


    /**
     * Dump all routes in attributes
     *
     * @throws UnknownControllerException
     * @throws MissingApiRoutesFileException
     */
    public function init()
    {
        // First, register all common routes
        $filePath = __DIR__ . '/../../../../../config/api_routes.yml';
        if (!is_file($filePath)) {
            throw new MissingApiRoutesFileException('The file "config/api_routes.yml" is missing');
        }

        $parser = new Parser();
        $destinations = $parser->parse(file_get_contents($filePath))['routes'];

        foreach ($destinations as $destinationName => $services) {
            $formattedDestinationName = $this->underscore($destinationName);
            // Delete "_service" suffix
            if ('service' == substr($formattedDestinationName, -7)) {
                $formattedDestinationName = substr($formattedDestinationName, 0, -8);
            }

            $this->commonRoutes[$formattedDestinationName] = [
                'name'    => $destinationName,
                'methods' => []
            ];

            foreach ($services as $serviceName => $parameters) {
                $formattedServiceName = $this->underscore($serviceName);
                // Delete "get_" prefix
                if (0 === strpos($formattedServiceName, 'get_')) {
                    $formattedServiceName = substr($formattedServiceName, 4);
                }

                $this->commonRoutes[$formattedDestinationName]['methods'][$formattedServiceName] = [
                    'name'       => $serviceName,
                    'parameters' => $parameters
                ];
            }
        }

        // Then, register the custom routes
        $iterator = new \DirectoryIterator(__DIR__ . '/../../Controller');
        /** @var \SplFileInfo $controller */
        foreach ($iterator as $controller) {
            if ($controller->isDir()) {
                continue;
            }

            $name = substr($controller->getFilename(), 0, -4);
            $reflectionClass = new \ReflectionClass('\\EloGank\\Api\\Controller\\' . $name);
            if (!$reflectionClass->isSubclassOf('\\EloGank\\Api\\Component\\Controller\\Controller')) {
                throw new UnknownControllerException('The controller "' . $name . '" must extend the class \EloGank\Api\Component\Controller\Controller');
            }

            // Delete the "Controller" suffix
            if ('Controller' == substr($name, strlen($name) - 10)) {
                $name = substr($name, 0, -10);
            }

            $routeName = $this->underscore($name);
            $this->customRoutes[$routeName] = [
                'class'   => $name . 'Controller',
                'methods' => []
            ];

            $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
            /** @var \ReflectionMethod $method */
            foreach ($methods as $method) {
                // Wrong method definition
                if (!$method->isPublic() || !preg_match('/[a-zA-Z0-9_]+Action/', $method->getName())) {
                    continue;
                }

                $params     = $method->getParameters();
                $paramsName = [];

                /** @var \ReflectionParameter $param */
                foreach ($params as $param) {
                    $paramsName[] = $param->getName();
                }

                $methodName = $this->underscore(substr($method->getName(), 0, -6));
                // Delete useless get prefix
                if (0 === strpos($methodName, 'get_')) {
                    $methodName = substr($methodName, 4);
                }

                $this->customRoutes[$routeName]['methods'][$methodName] = [
                    'name'       => $method->getName(),
                    'parameters' => $paramsName
                ];
            }
        }
    }

    /**
     * @param ApiManager $apiManager
     * @param array      $data
     *
     * @return mixed
     *
     * @throws MalformedRouteException
     * @throws UnknownRouteException
     * @throws MissingParametersException
     */
    public function process(ApiManager $apiManager, array $data)
    {
        $route = $data['route'];
        if (!preg_match('/^[a-zA-Z_]+\.[a-zA-Z_]+$/', $route)) {
            throw new MalformedRouteException('The route "' . $route . '" is malformed. Please send a route following this pattern : "controller_name.method_name"');
        }

        list ($controllerName, $methodName) = explode('.', $route);

        // Common routes process
        if (isset($this->commonRoutes[$controllerName]['methods'][$methodName])) {
            // Missing parameters check
            if (count($data['parameters']) != count($this->commonRoutes[$controllerName]['methods'][$methodName]['parameters'])) {
                throw new MissingParametersException(sprintf('There are missing parameters for the method "%s" (controller "%s"). Please provide these parameters : %s',
                    $methodName, $controllerName, join(', ', $this->commonRoutes[$controllerName]['methods'][$methodName]['parameters'])
                ));
            }

            $controller = new CommonController($apiManager, $data['region']);

            return call_user_func_array(array($controller, 'commonCall'), [
                $this->commonRoutes[$controllerName]['name'],
                $this->commonRoutes[$controllerName]['methods'][$methodName]['name'],
                $data['parameters']
            ]);
        }

        // Custom routes process
        if (!isset($this->customRoutes[$controllerName]['methods'][$methodName])) {
            throw new UnknownRouteException('The route "' . $route . '" is unknown. To known all available routes, use the command "elogank:router:dump"');
        }

        // Missing parameters check
        if (count($data['parameters']) != count($this->customRoutes[$controllerName]['methods'][$methodName]['parameters'])) {
            throw new MissingParametersException(sprintf('There are missing parameters for the method "%s" (controller "%s"). Please provide these parameters : %s',
                $methodName, $controllerName, join(', ', $this->customRoutes[$controllerName]['methods'][$methodName]['parameters'])
            ));
        }

        $class = '\\EloGank\\Api\\Controller\\' . $this->customRoutes[$controllerName]['class'];
        $controller = new $class($apiManager, $data['region']);

        return call_user_func_array(array($controller, $this->customRoutes[$controllerName]['methods'][$methodName]['name']), $data['parameters']);
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        $routes = [];
        foreach ($this->commonRoutes as $controllerName => $route) {
            foreach ($route['methods'] as $methodName => $method) {
                $routes[$controllerName][$methodName] = $method['parameters'];
            }
        }

        foreach ($this->customRoutes as $controllerName => $route) {
            foreach ($route['methods'] as $methodName => $method) {
                $routes[$controllerName][$methodName] = $method['parameters'];
            }
        }

        return $routes;
    }

    /**
     * @param string $string A camelized string
     *
     * @return string An underscore string
     */
    protected function underscore($string)
    {
        return strtolower(preg_replace(array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), array('\\1_\\2', '\\1_\\2'), strtr($string, '_', '.')));
    }
} 