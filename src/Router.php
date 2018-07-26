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

    /**
     * Base namespace for controllers.
     *
     * @var string
     */
    protected $controllersNamespace = '';

    /**
     * Router entry point.
     *
     * @var null
     */
    protected $entry = null;

    /**
     * Router constructor.
     *
     * @param Request $request
     * @param Response $response
     * @param Container $container
     */
    public function __construct(Request $request, Response $response, Container $container) {
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;
    }

    /**
     * Execute the route building script for strict routing mode.
     *
     * @param callable $execute
     */
    public function define(callable $execute)
    {
        $routeBuilder = $this->container->get(RouteBuilder::class);

        $this->routes = $execute($routeBuilder);
    }

    /**
     * Run router.
     */
    public function run()
    {
        $modeMethod = 'run' . ucfirst($this->mode);

        $response = $this->$modeMethod();

        $this->response->output($response);
    }

    /**
     * Run router in strict mode.
     *
     * @return mixed
     */
    protected function runStrict()
    {
        if ($route = $this->resolveRouteStrict()) {
            if ($route['access'] == strtolower($_SERVER['REQUEST_METHOD'])) {
                $controller = $this->controllersNamespace . '\\' . $route['controller'];
                $method = $route['method'];
                $params = $this->getParams($route);

                return $this->container->call($controller, $method, $params);
            } else {
                // TODO: Add exception
                die('Forbidden access');
            }
        } else {
            // TODO: Add 404 page
            die('Page not found.');
        }
    }

    /**
     * Run router in automatic mode.
     *
     * @return mixed
     */
    protected function runAuto()
    {
        return $this->resolveRouteAuto();
    }

    /**
     * Base controller namespace setter.
     *
     * @param $namespace
     */
    public function setControllerNamespace($namespace)
    {
        $this->controllersNamespace = trim($namespace, ' \\');
    }

    /**
     * Mode setter.
     *
     * @param $mode
     */
    public function setResolverMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * Resolve route in strict mode.
     *
     * @return mixed
     */
    protected function resolveRouteStrict()
    {
        $requestedRoute = explode('/', $this->request->route);
        $routes = [];

        foreach ($requestedRoute as $place => $segment) {
            $routes = array_filter($this->routes, function($route) use ($place, $segment, $requestedRoute) {
                $routeSegments = explode('/', $route);
                if (isset($routeSegments[$place]) && count($routeSegments) === count($requestedRoute)) {
                    return $segment == $routeSegments[$place] || preg_match('/{[a-zA-Z0-9_]+}/', $routeSegments[$place]);
                } else {
                    return false;
                }
            }, ARRAY_FILTER_USE_KEY);
        }

        return reset($routes);
    }

    /**
     * Resolve route in automatic mode.
     *
     * @return mixed
     */
    protected function resolveRouteAuto()
    {
        $requestedRoute = $requestedRoute = explode('/', $this->request->route);

        if ($this->entry === null) {
            $namespace = $this->controllersNamespace . '\\';
            $controller = $namespace . ucfirst(!empty($requestedRoute[0]) ? $requestedRoute[0] : 'home') . 'Controller';
            $method = !empty($requestedRoute[1]) ? $requestedRoute[1] : 'index';
            $params = array_slice($requestedRoute, 2);
        } else if ($requestedRoute[0] === $this->entry) {
            $namespace = $this->controllersNamespace . '\\' . ucfirst($this->entry) . '\\';
            $controller = $namespace . ucfirst(!empty($requestedRoute[1]) ? $requestedRoute[1] : 'home') . 'Controller';
            $method = !empty($requestedRoute[2]) ? $requestedRoute[2] : 'index';
            $params = array_slice($requestedRoute, 3);
        } else {
            // TODO: Add exception.
            die('Route not found');
        }

        try {
            return $this->container->call($controller, $method, ['args' => $params]);
        } catch (\Exception $e) {
            // TODO: Add exception
            die('Route not found');
        }
    }

    /**
     * Get params for strict mode execution. Resolving wildcards.
     *
     * @param $route
     * @return array|bool
     */
    protected function getParams($route)
    {
        $resolvedRoute = explode('/', $route['route']);
        $requestedRoute = explode('/', $this->request->route);

        $keys = array_diff($resolvedRoute, $requestedRoute);
        $values = array_diff($requestedRoute, $resolvedRoute);

        $params = [];

        foreach ($keys as $index => $key) {
            if (isset($values[$index])) {
                $params[trim($key, '{}')] = $values[$index];
            } else {
                return false;
            }
        }

        $access = $route['access'];
        return array_merge($this->request->$access(), $params);
    }
}