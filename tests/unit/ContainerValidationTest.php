<?php


namespace Silktide\Syringe\Tests;


use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\Syringe;
use Silktide\Syringe\Tests\Container\InvalidContainer;
use Silktide\Syringe\Tests\Container\ValidContainer;

class ContainerValidationTest extends TestCase
{
    public function validDataProvider()
    {
        return [
            [Container::class],
            [ValidContainer::class]
        ];
    }

    public function invalidDataProvider()
    {
        return [
            [InvalidContainer::class]
        ];
    }

    /**
     * @dataProvider validDataProvider
     * @param string $class
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Silktide\Syringe\Exception\ConfigException
     * @throws \Silktide\Syringe\Exception\LoaderException
     */
    public function testValidContainerClass(string $class)
    {
        Syringe::build([
            "files" => [],
            "containerClass" => $class
        ]);
        self::assertTrue(true);
    }

    /**
     * @dataProvider invalidDataProvider
     * @param string $class
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Silktide\Syringe\Exception\ConfigException
     * @throws \Silktide\Syringe\Exception\LoaderException
     */
    public function testInvalidContainerClass(string $class)
    {
        self::expectException(ConfigException::class);
        Syringe::build([
            "files" => [],
            "containerClass" => $class
        ]);
    }
}