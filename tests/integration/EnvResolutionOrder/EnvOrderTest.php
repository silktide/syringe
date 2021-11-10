<?php


namespace Silktide\Syringe\IntegrationTests\EnvOrder;


use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\Syringe;

class EnvOrderTest extends TestCase
{
    /**
     * @dataProvider fileDataProvider
     */
    public function testEnvOrder(string $file)
    {
        putenv("MY_ENV_VAR=env_value_1");

        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => [$file]
        ]);

        self::assertSame("env_value_1", $container->offsetGet("my_parameter"));
    }

    public function fileDataProvider()
    {
        return [
            ["file1.yml"],
            ["file2.yml"],
            ["file3.yml"],
        ];
    }
}