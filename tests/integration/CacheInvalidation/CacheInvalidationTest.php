<?php


namespace Silktide\Syringe\IntegrationTests\CacheInvalidation;


use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\Syringe;

class CacheInvalidationTest extends TestCase
{
    /**
     * Test that the caching is working!
     */
    public function testFileCacheInvalidation()
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


    public function testEnvCacheInvalidation()
    {
        $cache = new ArrayCachePool();

        putenv("MY_ENV_VAR=env_value_1");

        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file3.yml"],
            "cache" => $cache
        ]);
        self::assertSame("env_value_1", $container->offsetGet("my_environment_var"));

        putenv("MY_ENV_VAR=env_value_2");

        // Assert without validation that it'll not notice the difference
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file3.yml"],
            "cache" => $cache
        ]);
        self::assertSame("env_value_1", $container->offsetGet("my_environment_var"));

        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file3.yml"],
            "cache" => $cache,
            "validateCache" => true
        ]);
        self::assertSame("env_value_2", $container->offsetGet("my_environment_var"));
    }


    public function testConstCacheInvalidation()
    {
        // Testing constants changing is a pain as a constant is... well, constant
        // So we need to push this logic off to a separate process
        $constValue = "const1";
        $value = exec("php -d xdebug.remote_autostart=0 " . realpath(__DIR__ . "/constant.php"). " {$constValue}");
        self::assertSame($constValue, $value);

        $constValue = "const2";
        $value = exec("php -d xdebug.remote_autostart=0 " . realpath(__DIR__ . "/constant.php"). " {$constValue}");
        self::assertSame($constValue, $value);
    }


    public function testParameterCacheInvalidation()
    {
        $cache = new ArrayCachePool();

        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file5.yml"],
            "cache" => $cache,
            "parameters" => [
                "param_value" => "value1"
            ]
        ]);
        self::assertSame("value1", $container->offsetGet("my_param"));

        // This is handled at the key choosing level, so this will not require validateCache
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file5.yml"],
            "cache" => $cache,
            "parameters" => [
                "param_value" => "value2"
            ]
        ]);
        self::assertSame("value2", $container->offsetGet("my_param"));
    }
}