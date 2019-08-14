<?php


namespace Silktide\Syringe\IntegrationTests\ServiceLocator;


use Silktide\Syringe\ServiceLocator;

class ExampleClass
{
    protected $serviceLocator;

    public function __construct(ServiceLocator $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function getServiceLocator() : ServiceLocator
    {
        return $this->serviceLocator;
    }
}