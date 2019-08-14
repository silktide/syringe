<?php


namespace Silktide\Syringe\IntegrationTests\AppDir;


use PHPUnit\Framework\TestCase;
use Silktide\Syringe\Syringe;

class AppDirTest extends TestCase
{
    public function testAppDir()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"]
        ]);

        self::assertSame(getcwd(), $container["foo"]);
    }
}