<?php


namespace Silktide\Syringe\IntegrationTests\Lazy;


use PHPUnit\Framework\TestCase;
use Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\Syringe;

class LazyTest extends TestCase
{
    public function setUp() : void
    {
        LazyClass::$loaded = false;
    }

    public function testLazyLoading()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"]
        ]);

        /**
         * @var LazyClass $myService
         */
        $myService = $container["my_service"];
        self::assertInstanceOf(LazyClass::class, $myService);
        self::assertFalse(LazyClass::$loaded);
        $myService->getTrue();
        self::assertTrue(LazyClass::$loaded);
    }

    public function testNonLazyLoading()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file2.yml"]
        ]);

        self::assertFalse(LazyClass::$loaded);
        /**
         * @var LazyClass $myService
         */
        $myService = $container["my_service"];
        self::assertTrue(LazyClass::$loaded);
        self::assertInstanceOf(LazyClass::class, $myService);
    }
}