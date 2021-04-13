<?php


namespace Silktide\Syringe\IntegrationTests\ParameterResolutionOrder;


use PHPUnit\Framework\TestCase;
use Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\Syringe;
use Silktide\Syringe\TagCollection;

class ParameterResolutionTest extends TestCase
{
    public function testBasicParameter()
    {
        putenv("TEST_ENV=dev");

        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => [
                "file1.yml",
            ]
        ]);

        self::assertSame("my_value", $container["my_environment_parameter"]);
    }
}