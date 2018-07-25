<?php


namespace Orthite\Http;


class RouteBuilder
{
    protected $routes = [];

    public function add($route, $call, $access)
    {
        $controller = explode('@', $call)[0];
        $method = explode('@', $call)[1];

        preg_match_all('/{([a-zA-Z0-9_]+)}/', $route, $wildcards);

        $wildcards = $wildcards[1];

        $this->routes[trim($route, ' /')] = compact('controller', 'method', 'access', 'wildcards');
    }

    public function post($route, $call)
    {
        $this->add($route, $call, 'post');
    }

    public function get($route, $call)
    {
        $this->add($route, $call, 'get');
    }

    public function build()
    {
        return $this->routes;
    }
}