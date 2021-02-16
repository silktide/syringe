<?php

namespace Silktide\Syringe\IntegrationTests\Recursion;

use PHPUnit\Framework\TestCase;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\Syringe;

class RecursionTest extends TestCase
{
    /**
     * We expect this to throw a recursion exception
     * @throws ConfigException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Silktide\Syringe\Exception\LoaderException
     */
    public function testRecursive()
    {
        self::expectException(ConfigException::class);

        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"]
        ]);

        self::assertSame(["fridge", "magnet"], $container["my_parameter_9"]);
    }

    /**
     * This is complicated, but it shouldn't trigger a recursion exception
     *
     * @throws ConfigException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Silktide\Syringe\Exception\LoaderException
     */
    public function testConfusingNonRecursive()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file2.yml"]
        ]);

        self::assertSame("hello hello", $container["my_parameter_1"]);
    }

}