<?php


namespace Silktide\Syringe\IntegrationTests\EmptyClass;


use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\Syringe;

class EmptyClassTest extends TestCase
{
    /**
     * @dataProvider fileDataProvider
     */
    public function testEmptyClass(string $file, string $firstArg)
    {
        $built = Syringe::build([
            "paths" => [__DIR__],
            "files" => [$file]
        ]);

        /**
         * @var ExampleClass $class
         */
        $class = $built->offsetGet(ExampleClass::class);
        self::assertSame($firstArg, $class->getFirstArgument());
    }

    public function testInvalidClass()
    {
        self::expectException(ConfigException::class);
        Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.invalid.yml"]
        ]);
    }

    public function fileDataProvider()
    {
        return [
            ["file1.yml", "Foo"],
            ["file2.yml", "Bar"]
        ];
    }
}
