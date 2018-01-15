<?php

namespace Silktide\Syringe;

use Silktide\Syringe\Exception\ReferenceException;

class TagCollection
{
    protected $services = [];

    public function addService($serviceName, $key = null)
    {
        if (!is_string($key) || empty($key)) {
            $key = count($this->services);
        }
        $this->services[$key] = $serviceName;
    }

    public function getServices()
    {
        return $this->services;
    }

    public function getService($key)
    {
        if (empty($this->services[$key])) {
            throw new ReferenceException("No service with the key '$key' was found in this tag");
        }
        return $this->services[$key];
    }


} 