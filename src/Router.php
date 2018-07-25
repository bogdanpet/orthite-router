<?php

namespace Orthite\Http;

use Orthite\DI\Container;

class Router
{
    /**
     * Mode of routing system. Can be 'strict', with manually defined routes or
     * 'auto', when router tries to automatically resolve routes.
     *
     * @var string
     */
    protected $mode = 'strict';

    /**
     * Holds the Request object.
     *
     * @var Request
     */
    protected $request;

    /**
     * Holds the Response object.
     *
     * @var Response
     */
    protected $response;

    /**
     * Orthite-di container
     *
     * @var Container
     */
    protected $container;

    /**
     * Routes array for 'strict' mode.
     *
     * @var array
     */
    protected $routes = [];

    protected $controllersNamespace = '';

    public function __construct(Request $request, Response $response, Container $container) {
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;
    }

    public function define(callable $execute)
    {
        $routeBuilder = $this->container->get(RouteBuilder::class);

        $this->routes = $execute($routeBuilder);
    }

    public function run()
    {
        $modeMethod = 'run' . ucfirst($this->mode);

        $this->$modeMethod();
    }

    protected function runStrict()
    {
        if ($route = $this->resolveRoute()) {
            if ($route['access'] == strtolower($_SERVER['REQUEST_METHOD'])) {
                $controller = $this->controllersNamespace . '\\' . $route['controller'];
                $method = $route['method'];
                $access = $route['access'];
                $params = $this->request->$access;

                $this->container->call($controller, $method, $params);
            } else {
                // TODO: Add exception
                die('Forbidden access');
            }
        } else {
            // TODO: Add 404 page
            die('Page not found.');
        }
    }

    public function setControllerNamespace($namespace)
    {
        $this->controllersNamespace = trim($namespace, ' \\');
    }

    protected function resolveRoute()
    {
        $requestedRoute = explode('/', $this->request->route);
        $routes = [];

        foreach ($requestedRoute as $place => $segment) {
            $routes = array_filter($this->routes, function($route) use ($place, $segment) {
                $routeSegments = explode('/', $route);
                if (isset($routeSegments[$place])) {
                    return $segment == $routeSegments[$place] || preg_match('/{[a-zA-Z0-9_]+}/', $routeSegments[$place]);
                } else {
                    return false;
                }
            }, ARRAY_FILTER_USE_KEY);
        }

        return $routes[0];
    }
}