<?php

namespace Silktide\Syringe;

use Pimple\Container;
use Silktide\Syringe\Exception\ReferenceException;

class TagCollection implements \Iterator
{
    private int $position = 0;
    protected array $services = [];
    protected array $aliases = [];
    protected Container $container;

    public function __construct(Container $container, array $services)
    {
        $this->container = $container;

        foreach ($services as list("service" => $service, "alias" => $alias)) {
            if (!is_null($alias)) {
                $this->aliases[$alias] = count($this->services);
            }
            $this->services[] = $service;
        }
    }

    public function getServiceNames()
    {
        return array_values($this->services);
    }

    public function getServiceNameByAlias(string $alias)
    {
        if (!isset($this->aliases[$alias])) {
            throw new ReferenceException("No service with the alias '$alias' was found in this tag");
        }

        return $this->services[$this->aliases[$alias]];
    }

    public function getServiceByAlias(string $alias)
    {
        return $this->container[$this->getServiceNameByAlias($alias)];
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->container[$this->services[$this->position]];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->services[$this->position]);
    }
}