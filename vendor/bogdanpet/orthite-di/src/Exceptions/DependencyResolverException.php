<?php

namespace Orthite\DI\Exceptions;

class DependencyResolverException extends \Exception
{

    public function __construct($message = "Undefined exception", $code = 0)
    {
        parent::__construct($message, $code);
    }
}