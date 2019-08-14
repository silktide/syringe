<?php


namespace Silktide\Syringe\IntegrationTests\ServiceArray;


class ServiceArray
{
    protected $services;

    public function __construct(array $services)
    {
        $this->services = $services;
    }

    public function getServices()
    {
        return $this->services;
    }
}