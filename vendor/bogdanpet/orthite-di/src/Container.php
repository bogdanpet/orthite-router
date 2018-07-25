<?php

namespace Orthite\DI;

use Orthite\DI\Exceptions\DependencyResolverException;
use Orthite\DI\Exceptions\MethodInvokerException;

class Container
{

    /**
     * Holds the resolved objects for reusing.
     *
     * @var array
     */
    protected $pool = [];

    /**
     * Get the  object from the pool if it exists, or try to resolve it.
     *
     * @param string $key
     * @param array $params
     * @return mixed|object
     */
    public function get($key, $params = [])
    {
        // If object is in pool return it.
        if ($this->has($key)) {
            return $this->pool[$key];
        }

        // Otherwise proceed with resolving
        return $this->resolve($key, $params);
    }

    /**
     * Add element to the pool.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->pool[$key] = $value;
    }

    /**
     * Check if element exist in the pool.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->pool);
    }

    /**
     * Resolve the class with dependencies and return the new instance.
     *
     * @param string $class
     * @param array $params
     * @return object
     * @throws DependencyResolverException
     */
    protected function resolve($class, $params = [])
    {
        // Create a reflection of a class
        try {
            $reflection = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new DependencyResolverException($e->getMessage(), $e->getCode());
        }

        // Check if class is instantiable
        if (!$reflection->isInstantiable()) {
            throw new DependencyResolverException('Class ' . $reflection->getName() . 'is not instantiable');
        }

        // Get class constructor
        $constructor = $reflection->getConstructor();

        // If constructor is null (not exists) proceed with simple instantiation
        // And put the object in pool for future use
        if (is_null($constructor)) {
            $instance = $reflection->newInstance();
            $this->set($class, $instance);
            return $instance;
        }

        // Otherwise get constructor params
        $constructorParams = $constructor->getParameters();

        // Try to resolve them and return the new instance with args
        $dependencies = $this->resolveDependencies($constructorParams, $params);

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Resolve function parameters. Constructor or method.
     *
     * @param array $functionParams
     * @param array $params
     * @return array
     * @throws DependencyResolverException
     */
    private function resolveDependencies($functionParams, $params)
    {
        $dependencies = [];

        foreach ($functionParams as $param) {
            // Check if the value is passed through $params
            // This value should override the default
            if (array_key_exists($param->name, $params)) {
                $dependencies[] = $params[$param->name];
            } else {
                // Otherwise try to resolve it as class using type hinting
                $dep = $param->getClass();

                // If it is a class try to resolve it
                if ($dep !== null) {
                    $dependencies[] = $this->get($dep->name);
                } else {
                    // Otherwise check for default value
                    if ($param->isDefaultValueAvailable()) {
                        // get default value of parameter
                        $dependencies[] = $param->getDefaultValue();
                    } else {
                        throw new DependencyResolverException('Can\'t resolve parameter ' . $param->name);
                    }
                }
            }
        }

        return $dependencies;
    }

    /**
     * Resolve method and invoke it.
     *
     * @param string $class
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws MethodInvokerException
     */
    public function call($class, $method, $params = [])
    {
        // Resolve the class
        $object = $this->get($class, $params);

        // Check if method exists and proceed with resolving
        if (method_exists($object, $method)) {
            // Create method reflection
            $reflection = new \ReflectionMethod($class, $method);

            // Check if method is public
            if (!$reflection->isPublic()) {
                throw new MethodInvokerException('Method ' . $class . '::' . $method . ' is not accessible. It is either private or protected');
            }

            // Get method params
            $methodParams = $reflection->getParameters();

            // If there is not method params invoke the method
            if (empty($methodParams)) {
                return $reflection->invoke($object);
            }

            // Otherwise resolve method's dependencies and invoke method
            $dependencies = $this->resolveDependencies($methodParams, $params);

            return $reflection->invokeArgs($object, $dependencies);
        } else {
            throw new MethodInvokerException('Method ' . $class . '::' . $method . ' does not exist.');
        }
    }

    /**
     * Add multiple declarations to container pool.
     * Ideal when reading from configuration files.
     *
     * @param array $config
     */
    public function build(array $config)
    {
        foreach ($config as $key => $value) {
            $this->set($key, $value);
        }
    }
}