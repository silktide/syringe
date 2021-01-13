<?php


namespace Silktide\Syringe\IntegrationTests\Lazy;


use PHPUnit\Framework\TestCase;
use Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\Syringe;

class LazyDestructorTest extends TestCase
{
    public function setUp() : void
    {
        LazyClass::$loaded = false;
    }

    public function testWithLazyDestructing()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file3.yml"]
        ]);

        /**
         * @var LazyClass $myService
         */
        $myService = $container["my_service"];
        self::assertInstanceOf(LazyDestructorClass::class, $myService);
        self::assertFalse(LazyDestructorClass::$loaded);
        unset($myService, $container);
        // For information about why this is needed, see line ~30 of ContainerBuilder
        gc_collect_cycles();

        self::assertFalse(LazyDestructorClass::$loaded);
    }

    public function testWithNonLazyDestructing()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file4.yml"]
        ]);

        /**
         * @var LazyDestructorClass $myService
         */
        $myService = $container["my_service"];
        self::assertInstanceOf(LazyDestructorClass::class, $myService);
        self::assertFalse(LazyDestructorClass::$loaded);
        unset($myService, $container);
        // For information about why this is needed, see line ~30 of ContainerBuilder
        gc_collect_cycles();

        self::assertTrue(LazyDestructorClass::$loaded);
    }
}