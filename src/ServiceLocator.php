<?php

namespace Silktide\Syringe;

use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\Exception\ReferenceException;

/**
 * ServiceLocator
 */
class ServiceLocator
{

    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container = null)
    {
        if (!empty($container)) {
            $this->setContainer($container);
        }
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    public function get($serviceName, $resolveTags = true)
    {
        if (!$this->container instanceof Container) {
            throw new ConfigException("No Container has been set on the ServiceLocator");
        }

        if (!is_string($serviceName)) {
            throw new \InvalidArgumentException("Service name must be a string, received " . gettype($serviceName));
        }

        if (!$this->container->offsetExists($serviceName)) {
            throw new ReferenceException("The key '$serviceName' is not registered in this Container");
        }

        $service = $this->container[$serviceName];

        // resolve tags if required
        if ($service instanceof TagCollection && $resolveTags) {
            $services = $service->getServices();
            $service = [];
            foreach ($services as $key => $taggedService) {
                $service[$key] = $this->get($taggedService, false);
            }
        }

        return $service;
    }

}