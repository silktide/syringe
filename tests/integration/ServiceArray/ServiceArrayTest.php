<?php


namespace Silktide\Syringe\IntegrationTests\ServiceLocator;


use PHPUnit\Framework\TestCase;
use Silktide\Syringe\IntegrationTests\ServiceArray\ServiceArray;
use \Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\Syringe;

class ServiceArrayTest extends TestCase
{
    public function testServiceArray()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"]
        ]);

        /**
         * @var ServiceArray $serviceArray
         */
        $serviceArray = $container["service_array"];
        self::assertInstanceOf(ServiceArray::class, $serviceArray);

        foreach ($serviceArray->getServices() as $service) {
            self::assertInstanceOf(ExampleClass::class, $service);
        }
    }
}