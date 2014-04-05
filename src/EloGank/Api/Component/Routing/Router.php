<?php

namespace EloGank\Api\Component\Routing;

use EloGank\Api\Component\Controller\Exception\UnknownControllerException;

/**
 * @author Sylvain Lorinet <sylvain.lorinet@gmail.com>
 */
class Router
{
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

            $name = $this->underscore($name);
            $this->routes[$name] = [];

            $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
            /** @var \ReflectionMethod $method */
            foreach ($methods as $method) {
                $params     = $method->getParameters();
                $paramsName = [];

                /** @var \ReflectionParameter $param */
                foreach ($params as $param) {
                    $paramsName[] = $param->getName();
                }

                $this->routes[$name][$this->underscore($method->getName())] = $paramsName;
            }
        }
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param string $string A camelized string
     *
     * @return string An underscore string
     */
    public function underscore($string)
    {
        return strtolower(preg_replace(array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), array('\\1_\\2', '\\1_\\2'), strtr($string, '_', '.')));
    }
} 