<?php

namespace Silktide\Syringe;

use Pimple\Container;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;

class ContainerBuilder
{
    /**
     * Default class name for the container
     */
    const DEFAULT_CONTAINER_CLASS = Container::class;

    private $lazyLoadingValueHolderFactory;

    public function __construct(LazyLoadingValueHolderFactory $lazyLoadingValueHolderFactory)
    {
        $this->lazyLoadingValueHolderFactory = $lazyLoadingValueHolderFactory;
    }

    public function populateContainer(Container $container, CompiledConfig $compiledConfig)
    {
        foreach ($compiledConfig->getParameters() as $key => $value) {
            $container[$key] = function () use ($value){
                return $value;
            };
        }

        foreach ($compiledConfig->getServices() as $key => $definition) {
            //
            // Here lies some interesting behaviour!
            // Because we feed $container into the closure, this increments the refcount for the container!
            //
            // This means that if you unset the $container, anything held in the container won't be garbage collected
            // until the next full garbage collection sweep as it ends up with recursive references
            //
            // While there may be a better way of handling this, it isn't worth the current research and implementation,
            // especially given that Containers tend to be around for the life of the application
            //
            $container[$key] = (function () use ($container, $definition) {
                if ($definition["lazy"] ?? false) {
                    return $this->lazyLoadingValueHolderFactory->createProxy(
                        $definition["class"],
                        function (&$wrappedObject, $proxy, $method, $parameters, &$initializer) use ($container, $definition) {
                            if (($definition["lazySkipDestruct"] ?? false) && $method === "__destruct") {
                                $wrappedObject = new class {public function __destruct(){}};
                                return;
                            }
                            $wrappedObject = $this->buildService($container, $definition);
                            $initializer = null; // turning off further lazy initialization
                        }
                    );
                }

                return $this->buildService($container, $definition);
            });
        }

        foreach ($compiledConfig->getTags() as $tag => $services) {
            $container[Token::TAG_CHAR . $tag] = function () use ($container, $services) {
                return new TagCollection($container, $services);
            };
        }

        foreach ($compiledConfig->getAliases() as $alias => $service) {
            $container[$alias] = function () use ($container, $service) {
                return $container->offsetGet(mb_substr($service, 1));
            };
        }

        return $container;
    }

    private function buildService(Container $container, array $definition)
    {
        $isFactoryCreated = isset($definition["factoryMethod"]);

        $arguments = $this->resolveArray($container, $definition["arguments"] ?? []);

        if ($isFactoryCreated) {
            $service = call_user_func_array(
                [
                    $definition["factoryClass"] ?? $container->offsetGet(\mb_substr($definition["factoryService"], 1)),
                    $definition["factoryMethod"]
                ],
                $arguments
            );
        } else {
            $service = (new \ReflectionClass($definition["class"]))->newInstanceArgs($arguments);
        }

        foreach ($definition["calls"] ?? [] as $call) {
            call_user_func_array(
                [$service, $call["method"]],
                $this->resolveArray($container, $call["arguments"] ?? [])
            );
        }
        return $service;
    }

    protected function resolveArray(Container $container, array $array)
    {
        $arguments = [];
        foreach ($array as $k => $value) {
            if (\is_string($value) && \strlen($value) > 0 && $value[0] === "\0") {
                $arguments[$k] = $container->offsetGet(\mb_substr($value, 1));
            } elseif (\is_array($value)) {
                $arguments[$k] = $this->resolveArray($container, $value);
            } else {
                $arguments[$k] = $value;
            }
        }
        return $arguments;
    }
}
