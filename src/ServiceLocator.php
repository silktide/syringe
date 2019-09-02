<?php

namespace Silktide\Syringe;

use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\Exception\ReferenceException;

class ServiceLocator
{
    /**
     * @var Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $serviceName)
    {
        return $this->container->offsetGet($serviceName);
    }

    public function has(string $serviceName)
    {
        return $this->container->offsetExists($serviceName);
    }
}
