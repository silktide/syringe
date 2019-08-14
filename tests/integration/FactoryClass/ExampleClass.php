<?php


namespace Silktide\Syringe\IntegrationTests\FactoryClass;


class ExampleClass
{
    protected $serviceExample;
    protected $tagCollection;

    public function __construct(ServiceExample $serviceExample, iterable $tagCollection)
    {
        $this->serviceExample = $serviceExample;
        $this->tagCollection = $tagCollection;
    }

    public function getServiceExample()
    {
        return $this->serviceExample;
    }

    public function getTagCollection()
    {
        return $this->tagCollection;
    }
}