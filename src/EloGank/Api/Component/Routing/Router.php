<?php

namespace EloGank\Api\Component\Routing;

use EloGank\Api\Component\Controller\Exception\UnknownControllerException;
use EloGank\Api\Component\Routing\Exception\MalformedRouteException;
use EloGank\Api\Component\Routing\Exception\UnknownRouteException;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class Router
{
    /**
     * @var array
     */
    protected $routes = [];


    /**
     * @throws \EloGank\Api\Component\Controller\Exception\UnknownControllerException
     */
    public function init()
    {
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
            $this->routes[$routeName] = [
                'class'   => $name . 'Controller',
                'methods' => []
            ];

            $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
            /** @var \ReflectionMethod $method */
            foreach ($methods as $method) {
                $params     = $method->getParameters();
                $paramsName = [];

                /** @var \ReflectionParameter $param */
                foreach ($params as $param) {
                    $paramsName[] = $param->getName();
                }

                $this->routes[$routeName]['methods'][$this->underscore($method->getName())] = [
                    'name'       => $method->getName(),
                    'parameters' => $paramsName
                ];
            }
        }
    }

    /**
     * @param array $data
     *
     * @return mixed
     *
     * @throws Exception\MalformedRouteException
     * @throws Exception\UnknownRouteException
     */
    public function process(array $data)
    {
        $route = $data['route'];
        if (!preg_match('/^[a-zA-Z_]+\.[a-zA-Z_]+$/', $route)) {
            throw new MalformedRouteException('The route "' . $route . '" is malformed. Please send a route following this pattern : "controller_name.method_name"');
        }

        list ($controllerName, $methodName) = explode('.', $route);
        if (!isset($this->routes[$controllerName]['methods'][$methodName])) {
            throw new UnknownRouteException('The route "' . $route . '" is unknown. To known all available routes, use the command "elogank:router:dump"');
        }

        $class = '\\EloGank\\Api\\Controller\\' . $this->routes[$controllerName]['class'];
        $controller = new $class();

        return call_user_func_array(array($controller, $this->routes[$controllerName]['methods'][$methodName]['name']), $data['parameters']);
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        $routes = [];
        foreach ($this->routes as $controllerName => $route) {
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