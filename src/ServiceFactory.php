<?php

namespace Silktide\Syringe;

use Pimple\Container;

/**
 * ServiceFactory
 */
class ServiceFactory 
{

    protected $container;

    protected $resolver;

    public function __construct(Container $container, ReferenceResolverInterface $resolver)
    {
        $this->container = $container;
        $this->resolver = $resolver;
    }

    public function createService($class, array $factory, array $arguments, array $calls, $alias = "")
    {
        // resolve any parameters or services in the constructor and call arguments (thus finding resolution exceptions ASAP)
        $arguments = $this->resolveArguments($arguments, $alias);
        foreach ($calls as &$call) {
            $call["arguments"] = $this->resolveArguments($call["arguments"], $alias);
        }
        unset($call);

        // create the service instance
        if (empty($factory["class"]) && empty($factory["service"])) {
            $ref = new \ReflectionClass($class);
            $service = $ref->newInstanceArgs($arguments);
        } else {
            // create via factory
            $factoryClass = empty($factory["class"])
                ? $this->resolver->resolveService($factory["service"], $this->container, $alias)
                : $factory["class"];
            $service = call_user_func_array([$factoryClass, $factory["method"]], $arguments);
        }

        // setter injection
        foreach ($calls as $call) {
            call_user_func_array([$service, $call["method"]], $call["arguments"]);
        }

        return $service;
    }

    public function aliasService($service, $alias)
    {
        return $this->resolver->resolveService($service, $this->container, $alias);
    }

    public function extendService($service, array $calls)
    {
        foreach ($calls as $call) {
            $call["arguments"] = $this->resolveArguments($call["arguments"], "");
            call_user_func_array([$service, $call["method"]], $call["arguments"]);
        }
        return $service;
    }

    protected function resolveArguments(array $arguments, $alias)
    {
        $userData = ["container" => $this->container, "resolver" => $this->resolver, "alias" => $alias];
        array_walk_recursive(
            $arguments,
            [$this, "resolveArgument"],
            $userData
        );
        return $arguments;
    }

    private function resolveArgument(&$argument, $key, array $userData)
    {
        if (!is_string($argument)) {
            return;
        }
        $c = $userData["container"];
        /** @var ReferenceResolverInterface $resolver */
        $resolver = $userData["resolver"];
        $alias = $userData["alias"];
        $argument = $resolver->resolveService($argument, $c, $alias);
        $argument = $resolver->resolveParameter($argument, $c, $alias);
        $argument = $resolver->resolveTag($argument, $c);
    }

} 