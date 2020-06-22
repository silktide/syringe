<?php


namespace Silktide\Syringe\IntegrationTests\Namespacing;


use PHPUnit\Framework\TestCase;
use Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\Syringe;
use Silktide\Syringe\TagCollection;

class NamespaceTest extends TestCase
{
    public function testBasicParameter()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => [
                "file1.yml",
                "my_namespace" => "namespaced.yml"
            ]
        ]);

        /**
         * @var ExampleClass $service
         */
        $service = $container["my_service"];
        self::assertSame("from-namespaced", $service->getFirstArgument());
        self::assertSame("from-namespaced", $container["my_namespace::my_parameter"]);
        self::assertSame("from-file1", $container["my_parameter"]);
        self::assertFalse($container->offsetExists("my_service_foo"));
    }

    public function testBasicService()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => [
                "file1.yml",
                "my_namespace" => "namespaced.yml"
            ]
        ]);

        /**
         * @var ExampleClass $service
         */
        $service = $container["my_namespace::my_service"];
        self::assertSame("from-namespaced-value-2", $service->getFirstArgument());
        self::assertFalse($container->offsetExists("my_service_foo"));
    }

    public function testBasicTag()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => [
                "file1.yml",
                "my_namespace" => "namespaced.yml"
            ]
        ]);

        self::assertCount(2, ($container["#tag1"])->getServiceNames());
        self::assertCount(1, ($container["#tag2"])->getServiceNames());
        self::assertSame(["my_namespace::my_service", "my_service"], ($container["#tag1"])->getServiceNames());
    }
}