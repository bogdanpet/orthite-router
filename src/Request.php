<?php


namespace Orthite\Http;


class Request
{
    /**
     * Holds the $_GET global.
     *
     * @var null|array
     */
    protected $get;

    /**
     * Holds the $_POST global.
     *
     * @var null|array
     */
    protected $post;

    /**
     * Holds the $_COOKIE global
     *
     * @var null|array
     */
    protected $cookie;

    /**
     * Request constructor.
     */
    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookie = $_COOKIE;
    }

    /**
     * Returns the get method parameter or the whole $_GET.
     *
     * @param null|string $key
     * @return array|mixed|null
     */
    public function get($key = null)
    {
        return $key ? $this->get['key'] : $this->get;
    }

    /**
     * Returns the post method parameter or the whole $_POST.
     *
     * @param null|string $key
     * @return array|mixed|null
     */
    public function post($key = null)
    {
        return $key ? $this->post['key'] : $this->post;
    }

    /**
     * Returns the cookie method parameter or the whole $_COOKIE.
     *
     * @param null|string $key
     * @return array|mixed|null
     */
    public function cookie($key = null)
    {
        return $key ? $this->cookie['key'] : $this->cookie;
    }

    /**
     * Magic getter.
     *
     * @param $name
     * @return null|mixed
     */
    public function __get($name)
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);

        if (array_key_exists($name, $this->$method)) {
            return $this->{$method}[$name];
        }

        return null;
    }
}