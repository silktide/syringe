<?php

namespace Silktide\Syringe;

use Pimple\Container;
use Silktide\Syringe\Exception\ReferenceException;

class ContainerBuilder
{
    /**
     * Default class name for the container
     */
    const DEFAULT_CONTAINER_CLASS = "Pimple\\Container";

    protected $referenceResolver;

    public function __construct(ReferenceResolver $referenceResolver)
    {
        $this->referenceResolver = $referenceResolver;
    }

    public function populateContainer(Container $container, CompiledConfig $compiledConfig)
    {
        //
        // Do the parameters!
        //
        foreach ($compiledConfig->getParameters() as $key => $value) {
            $container[$key] = function () use ($container, $key, $value) {
                try {
                    return $this->referenceResolver->resolve($container, $value);
                } catch (ReferenceException $e) {
                    throw new ReferenceException("Error with key '$key'. " . $e->getMessage());
                }
            };
        }

        foreach ($compiledConfig->getServices() as $key => $definition) {
            $container[$key] = function () use ($container, $key, $definition) {
                $isFactoryCreated = isset($definition["factoryMethod"]);

                if ($isFactoryCreated) {
                    $service = null;
                    if (isset($definition["factoryService"])) {
                        $service = $this->referenceResolver->resolve($container, $definition["factoryService"]);
                        $method = $definition["factoryMethod"];
                        $arguments = $this->referenceResolver->resolveArray($container, $definition["arguments"] ?? []);
                        return call_user_func_array([$service, $method], $arguments);
                    } else {
                        $arguments = $this->referenceResolver->resolveArray($container, $definition["arguments"] ?? []);
                        $factoryClass = $definition["factoryClass"];
                        $factoryMethod = $definition["factoryMethod"];
                        return call_user_func_array([$factoryClass, $factoryMethod], $arguments);
                    }
                }

                $ref = new \ReflectionClass($definition["class"]);
                $args = $this->referenceResolver->resolveArray($container, $definition["arguments"] ?? []);
                $service = $ref->newInstanceArgs(
                    $args
                );

                foreach ($definition["calls"] ?? [] as $call) {
                    call_user_func_array(
                        [$service, $call["method"]],
                        $this->referenceResolver->resolveArray($container, $call["arguments"] ?? [])
                    );
                }
                return $service;
            };
        }

        foreach ($compiledConfig->getTags() as $tag => $services) {
            $container[Token::TAG_CHAR . $tag] = function () use ($container, $services) {
                return new TagCollection($container, $services);
            };
        }

        foreach ($compiledConfig->getAliases() as $alias => $service) {
            $container[$alias] = function () use ($container, $service) {
                return $this->referenceResolver->resolve($container, $service);
            };
        }

        return $container;
    }
}
