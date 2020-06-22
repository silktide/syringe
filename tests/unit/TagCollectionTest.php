<?php


namespace Silktide\Syringe\Tests;


use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Silktide\Syringe\Exception\ReferenceException;
use Silktide\Syringe\TagCollection;

class TagCollectionTest extends TestCase
{
    public static $globalState = [];

    public function setUp() : void
    {
        self::$globalState = [];
    }

    public function testIterator()
    {
        $container = new Container();
        $container["service1"] = (function(){TagCollectionTest::$globalState[] = "service1"; return "service1_run";});
        $container["service2"] = (function(){TagCollectionTest::$globalState[] = "service2"; return "service2_run";});
        $container["service3"] = (function(){TagCollectionTest::$globalState[] = "service3"; return "service3_run";});

        $tagCollection = new TagCollection($container, [
            ["service" => "service1", "alias" => null],
            ["service" => "service2", "alias" => null],
            ["service" => "service3", "alias" => null]
        ]);

        self::assertEquals(["service1", "service2", "service3"], $tagCollection->getServiceNames());
        // Ensure none of the services are running before we expect them to
        self::assertCount(0, self::$globalState);

        // Ensure that the functions are being run as expected
        $i = 0;
        foreach ($tagCollection as $service) {
            self::assertTrue(is_string($service));
            self::assertCount(++$i, self::$globalState);
        }
    }

    public function testAliasSuccess()
    {
        $container = new Container();
        $container["service1"] = (function(){TagCollectionTest::$globalState[] = "service1"; return "service1_run";});
        $container["service2"] = (function(){TagCollectionTest::$globalState[] = "service2"; return "service2_run";});
        $tagCollection = new TagCollection($container, [
            ["service" => "service1", "alias" => "myservice"],
            ["service" => "service2", "alias" => "ourservice"],

        ]);

        self::assertEquals("service1", $tagCollection->getServiceNameByAlias("myservice"));
        self::assertCount(0, self::$globalState);
        self::assertEquals("service1_run", $tagCollection->getServiceByAlias("myservice"));
        self::assertCount(1, self::$globalState);

        self::assertEquals("service2", $tagCollection->getServiceNameByAlias("ourservice"));
        self::assertEquals("service2_run", $tagCollection->getServiceByAlias("ourservice"));
        self::assertCount(2, self::$globalState);

    }

    public function testGetServiceNameByAliasFailure()
    {
        $this->expectException(ReferenceException::class);
        $container = new Container();
        $container["service1"] = (function(){TagCollectionTest::$globalState[] = "service1"; return "service1_run";});
        $tagCollection = new TagCollection($container, [
            ["service" => "service1", "alias" => "myservice"]
        ]);

        $tagCollection->getServiceNameByAlias("yourservice");
    }


    public function testGetServiceByAliasFailure()
    {
        $this->expectException(ReferenceException::class);
        $container = new Container();
        $container["service1"] = (function(){TagCollectionTest::$globalState[] = "service1"; return "service1_run";});
        $tagCollection = new TagCollection($container, [
            ["service" => "service1", "alias" => "myservice"]
        ]);

        $tagCollection->getServiceByAlias("yourservice");
    }
}