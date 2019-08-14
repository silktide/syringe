<?php


namespace Silktide\Syringe\IntegrationTests\FactoryClass;


use PHPUnit\Framework\TestCase;
use Silktide\Syringe\IntegrationTests\Examples\TestTagInterface;
use Silktide\Syringe\Syringe;

class FactoryTest extends TestCase
{
    public function testFactoryService()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"]
        ]);

        /**
         * @var ExampleClass $exampleClass
         */
        $exampleClass = $container->offsetGet("example.class");
        self::assertInstanceOf(ExampleClass::class, $exampleClass);
        self::assertInstanceOf(ServiceExample::class, $exampleClass->getServiceExample());
        foreach ($exampleClass->getTagCollection() as $tag) {
            self::assertInstanceOf(TestTagInterface::class, $tag);
        }
    }

    public function testFactoryClass()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"]
        ]);

        /**
         * @var ExampleClass $exampleClass
         */
        $exampleClass = $container->offsetGet("example.class.2");
        self::assertInstanceOf(ExampleClass::class, $exampleClass);
        self::assertInstanceOf(ServiceExample::class, $exampleClass->getServiceExample());
        foreach ($exampleClass->getTagCollection() as $tag) {
            self::assertInstanceOf(TestTagInterface::class, $tag);
        }
    }
}