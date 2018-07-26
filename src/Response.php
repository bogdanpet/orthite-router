<?php


namespace Orthite\Http;


class Response
{

    public function output($response)
    {
        if (is_array($response) || is_object($response)) {
            echo json_encode($response);
        } else {
            echo $response;
        }
    }
}