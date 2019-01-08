<?php


namespace Silktide\Syringe\IntegrationTests\CacheInvalidation;


use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\Syringe;

class CacheInvalidation extends TestCase
{
    /**
     * Test that the caching is working!
     */
    public function testCacheInvalidation()
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
            "cache" => $cache,
            "validateCache" => true
        ]);

        $this->assertEquals("Value_1", $container["my_parameter"]);

        // We need to sleep as we pay attention to filemtime
        sleep(1);
        copy(__DIR__ . "/file2.yml", $tempFilename);
        $container = Syringe::build([
            "paths" => [$tempDir],
            "files" => ["file1.yml"],
            "cache" => $cache,
            "validateCache" => true
        ]);

        $this->assertEquals("Value_2", $container["my_parameter"]);



        unlink($tempFilename);
    }
}