<?php


namespace Silktide\Syringe\IntegrationTests\Tag;


use PHPUnit\Framework\TestCase;
use Silktide\Syringe\IntegrationTests\Examples\TestTagInterface;
use Silktide\Syringe\Syringe;
use Silktide\Syringe\TagCollection;

class TagTest extends TestCase
{
    public function testTags()
    {
        $container = Syringe::build([
            "paths" => [__DIR__],
            "files" => ["file1.yml"]
        ]);

        $tags = $container["#tagtag"];
        self::assertInstanceOf(TagCollection::class, $tags);
        self::assertIsIterable($tags);

        foreach ($tags as $tag) {
            self::assertInstanceOf(TestTagInterface::class, $tag);
        }
    }
}