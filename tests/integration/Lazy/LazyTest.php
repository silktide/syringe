<?php


namespace Silktide\Syringe\IntegrationTests\Lazy;


use PHPUnit\Framework\TestCase;
use Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\Syringe;

class LazyTest extends TestCase
{
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
}