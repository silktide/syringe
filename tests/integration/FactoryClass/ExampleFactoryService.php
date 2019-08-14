<?php


namespace Silktide\Syringe\IntegrationTests\FactoryClass;


use Silktide\Syringe\TagCollection;

class ExampleFactoryService
{
    protected $serviceExample;
    protected $tagCollection;

    public function __construct(ServiceExample $serviceExample, iterable $tagCollection)
    {
        $this->serviceExample = $serviceExample;
        $this->tagCollection = $tagCollection;
    }

    public function create()
    {
        return new ExampleClass($this->serviceExample, $this->tagCollection);
    }
}