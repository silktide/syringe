<?php


namespace Silktide\Syringe\IntegrationTests\FactoryClass;


class ExampleClass
{
    protected $serviceExample;
    protected $tagCollection;
    protected $customParameter;

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

    public function setCustomParameter(string $customParameter)
    {
        $this->customParameter = $customParameter;
    }

    public function getCustomParameter() : ?string
    {
        return $this->customParameter;
    }
}