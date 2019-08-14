<?php


namespace Silktide\Syringe\IntegrationTests\ServiceLocator;


use PHPUnit\Framework\TestCase;
use Silktide\Syringe\ServiceLocator;
use Silktide\Syringe\Syringe;

class ServiceLocatorTest extends TestCase
{
    public function testServiceLocator()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"],
            "serviceLocatorKey" => "service.locator"
        ]);

        self::assertInstanceOf(ServiceLocator::class, $container["example"]->getServiceLocator());
    }
}