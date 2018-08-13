<?php


namespace Silktide\Syringe\Tests\Loader;


use PHPStan\Testing\TestCase;
use Symfony\Component\Yaml\Yaml;

class YamlLoaderTest extends TestCase
{
    public function colonProvider()
    {
        return [
            [
                "foo: bar",
                ["foo" => "bar"]
            ],
            [
                "foo:bar",
                "foo:bar"
            ],
            [
                "foo::bar: chicken",
                ["foo::bar" => "chicken"]
            ],
            [
                "'foo::bar': chicken",
                ["foo::bar" => "chicken"]
            ],
            [
                "foo::bar: banana::salad",
                ["foo::bar" => "banana::salad"]
            ]
        ];
    }

    /**
     * @dataProvider colonProvider
     */
    public function testDoubleColon($input, $output)
    {
        $this->assertEquals($output, Yaml::parse($input));

        if (function_exists("yaml_parse")) {
            $this->assertEquals($output, yaml_parse($input));
        }
    }
}