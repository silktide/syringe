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

    public function __construct(ParameterResolver $referenceResolver)
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
                return $value;
            };
        }

        foreach ($compiledConfig->getServices() as $key => $definition) {
            $container[$key] = function () use ($container, $definition) {
                $isFactoryCreated = isset($definition["factoryMethod"]);

                $arguments = $this->resolveArray($container, $definition["arguments"] ?? []);

                if ($isFactoryCreated) {
                    return call_user_func_array(
                        [
                            $definition["factoryClass"] ?? $container->offsetGet(mb_substr($definition["factoryService"], 1)),
                            $definition["factoryMethod"]
                        ],
                        $arguments
                    );
                }

                $service = (new \ReflectionClass($definition["class"]))->newInstanceArgs($arguments);

                foreach ($definition["calls"] ?? [] as $call) {
                    call_user_func_array(
                        [$service, $call["method"]],
                        $this->resolveArray($container, $call["arguments"] ?? [])
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
                return $container->offsetGet(mb_substr($service, 1));
            };
        }

        return $container;
    }

    protected function resolveArray(Container $container, array $array)
    {
        $arguments = [];
        foreach ($array as $value) {
            if (is_string($value) && strlen($value) > 0 && $value[0] === "\0") {
                $arguments[] = $container->offsetGet(mb_substr($value, 1));
            } else {
                $arguments[] = $value;
            }
        }
        return $arguments;
    }
}
