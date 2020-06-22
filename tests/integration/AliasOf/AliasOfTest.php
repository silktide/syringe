<?php


namespace Silktide\Syringe\IntegrationTests\AliasOf;


use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\Syringe;

class AliasOfTest extends TestCase
{
    public function testAliasOf()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"]
        ]);

        self::assertInstanceOf(ExampleClass::class, $container["my_service"]);
        self::assertEquals("MyService1", $container["my_service"]->getFirstArgument());

        self::assertInstanceOf(ExampleClass::class, $container["my_service_2"]);
        self::assertEquals("MyService2", $container["my_service_2"]->getFirstArgument());

        self::assertInstanceOf(ExampleClass::class, $container["my_service_3"]);
        self::assertEquals("MyService2", $container["my_service_3"]->getFirstArgument());
    }
}