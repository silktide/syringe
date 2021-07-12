<?php


namespace Silktide\Syringe\IntegrationTests\Imports;


use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\Syringe;

/**
 * If a file imports another file, the parameter values should have priority from the importee
 *
 * @package Silktide\Syringe\IntegrationTests\Imports
 */
class ImportOverwriteTest extends TestCase
{
    public function testBasic()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"]
        ]);

        self::assertEquals("DefaultValue", $container["my_parameter"]);
    }


    public function testImported()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file2.yml"]
        ]);

        self::assertEquals("OverwrittenValue", $container["my_parameter"]);
    }
}