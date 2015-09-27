<?php

namespace Silktide\Syringe;

class TagCollection 
{

    protected $services;

    public function addService($serviceName)
    {
        $this->services[] = $serviceName;
    }

    public function getServices()
    {
        return $this->services;
    }

} 