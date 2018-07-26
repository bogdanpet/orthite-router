<?php


namespace Orthite\Http;


class RouteBuilder
{
    /**
     * Holds the generated routes.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Generate and add route to the routes array.
     *
     * @param string $route
     * @param string $call
     * @param string $access
     */
    public function add($route, $call, $access)
    {
        $controller = explode('@', $call)[0];
        $method = explode('@', $call)[1];

        preg_match_all('/{([a-zA-Z0-9_]+)}/', $route, $wildcards);

        $wildcards = $wildcards[1];

        $route = trim($route, ' /');

        $this->routes[$route] = compact('route', 'controller', 'method', 'access', 'wildcards');
    }

    /**
     * Generate post method route.
     *
     * @param string $route
     * @param string $call
     */
    public function post($route, $call)
    {
        $this->add($route, $call, 'post');
    }

    /**
     * Generate get method route.
     *
     * @param $route
     * @param $call
     */
    public function get($route, $call)
    {
        $this->add($route, $call, 'get');
    }

    /**
     * Returns the generated routes to the Router.
     *
     * @return array
     */
    public function build()
    {
        return $this->routes;
    }
}