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

    public function populateContainer(Container $container, CompiledConfig $compiledConfig)
    {
        //
        // Do the parameters!
        //
        foreach ($compiledConfig->getParameters() as $key => $value) {
            $container[$key] = function () use ($value){
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
        foreach ($array as $k => $value) {
            if (is_string($value) && strlen($value) > 0 && $value[0] === "\0") {
                $arguments[$k] = $container->offsetGet(mb_substr($value, 1));
            } elseif (is_array($value)) {
                $arguments[$k] = $this->resolveArray($container, $value);
            } else {
                $arguments[$k] = $value;
            }
        }
        return $arguments;
    }
}
