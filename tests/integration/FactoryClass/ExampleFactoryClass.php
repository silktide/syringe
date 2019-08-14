<?php


namespace Silktide\Syringe\IntegrationTests\FactoryClass;


class ExampleFactoryClass
{
    public static function create(ServiceExample $serviceExample, iterable $tagCollection)
    {
        return new ExampleClass($serviceExample, $tagCollection);
    }
}