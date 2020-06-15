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
        $this->assertInstanceOf(LazyClass::class, $myService);
        $this->assertFalse(LazyClass::$loaded);
        $myService->getTrue();
        $this->assertTrue(LazyClass::$loaded);
    }

    public function testNonLazyLoading()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file2.yml"]
        ]);

        $this->assertFalse(LazyClass::$loaded);
        /**
         * @var LazyClass $myService
         */
        $myService = $container["my_service"];
        $this->assertTrue(LazyClass::$loaded);
        $this->assertInstanceOf(LazyClass::class, $myService);
    }
}