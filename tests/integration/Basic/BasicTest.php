<?php


namespace Silktide\Syringe\IntegrationTests\Basic;


use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\Syringe;

class BasicTest extends TestCase
{
    public function testBasic()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"]
        ]);

        $this->assertInstanceOf(ExampleClass::class, $container["my_service"]);
        $this->assertEquals("MyService1", $container["my_service"]->getFirstArgument());
        $this->assertInstanceOf(ExampleClass::class, $container["my_service_2"]);
        $this->assertEquals("MyService2", $container["my_service_2"]->getFirstArgument());
    }

    public function testContainerService()
    {
        $containerService = new Container();
        $containerService["foo"] = function(){return "bar";};

        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"],
            "containerService" => $containerService
        ]);

        // Just test that the basic isn't broken
        $this->assertInstanceOf(ExampleClass::class, $container["my_service"]);
        $this->assertEquals("MyService1", $container["my_service"]->getFirstArgument());
        $this->assertInstanceOf(ExampleClass::class, $container["my_service_2"]);
        $this->assertEquals("MyService2", $container["my_service_2"]->getFirstArgument());

        // Test that it did populate the correct container
        $this->assertSame("bar", $container["foo"]);
    }

    /**
     * Test that the caching is working!
     */
    public function testCache()
    {
        $cache = new ArrayCachePool();

        $tempDir = sys_get_temp_dir();
        $tempFilename = $tempDir . "/file1.yml";

        if (file_exists($tempFilename)) {
            unlink($tempFilename);
        }

        copy(__DIR__ . "/file1.yml", $tempFilename);
        $container = Syringe::build([
            "paths" => [$tempDir],
            "files" => ["file1.yml"],
            "cache" => $cache
        ]);
        $this->assertInstanceOf(ExampleClass::class, $container["my_service"]);
        $this->assertEquals("MyService1", $container["my_service"]->getFirstArgument());
        unlink($tempFilename);
        copy(__DIR__ . "/file2.yml", $tempFilename);
        $container = Syringe::build([
            "paths" => [$tempDir],
            "files" => ["file1.yml"],
            "cache" => $cache
        ]);

        $this->assertInstanceOf(ExampleClass::class, $container["my_service"]);
        unlink($tempFilename);
    }

    public function testCacheOnParameters()
    {
        $cache = new ArrayCachePool();
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"],
            "cache" => $cache
        ]);

        $this->assertInstanceOf(ExampleClass::class, $container["my_service"]);
        $this->assertEquals("MyService1", $container["my_service"]->getFirstArgument());

        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file2.yml"],
            "cache" => $cache
        ]);

        $this->assertInstanceOf(ExampleClass::class, $container["my_service_3"]);
    }


    /**
     * @expectedException \Silktide\Syringe\Exception\ConfigException
     */
    public function testIncorrectCache()
    {
        Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"],
            "cache" => new ExampleClass("foo")
        ]);
    }

}