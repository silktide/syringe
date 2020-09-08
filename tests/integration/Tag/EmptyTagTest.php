<?php


namespace Silktide\Syringe\IntegrationTests\Tag;


use PHPUnit\Framework\TestCase;
use Silktide\Syringe\IntegrationTests\Examples\ExampleClass;
use Silktide\Syringe\IntegrationTests\Examples\TestTagInterface;
use Silktide\Syringe\Syringe;
use Silktide\Syringe\TagCollection;

class EmptyTagTest extends TestCase
{
    public function testConstructorTags()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file2.yml"]
        ]);

        /**
         * @var ExampleClass $service
         */
        $service = $container["my_service"];
        self::assertInstanceOf(ExampleClass::class, $service);
        self::assertInstanceOf(TagCollection::class, $service->getFirstArgument());

        $tags = $container["#untagged"];
        self::assertInstanceOf(TagCollection::class, $tags);
    }


    public function testCallableTags()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file2.yml"]
        ]);

        /**
         * @var ExampleClass $service
         */
        $service = $container["my_service2"];
        self::assertInstanceOf(ExampleClass::class, $service);

        $value = $service->getValue("vegetable");
        self::assertInstanceOf(TagCollection::class, $value);

        self::assertCount(0, $value);

        $tags = $container["#sweetcorn"];
        self::assertInstanceOf(TagCollection::class, $tags);
    }
}