<?php


namespace Orthite\Http;


class Request
{
    protected $get;

    protected $post;

    protected $cookie;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookie = $_COOKIE;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->get)) {
            return $this->get[$name];
        }

        return null;
    }
}