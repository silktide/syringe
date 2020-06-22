<?php

namespace Silktide\Syringe\Tests;

use PHPUnit\Framework\TestCase;
use Silktide\Syringe\CompiledConfigBuilder;
use Silktide\Syringe\FileConfig;
use Silktide\Syringe\MasterConfig;
use Silktide\Syringe\ParameterResolver;

class CompiledConfigBuilderTest extends TestCase
{
    public function testExtendingAbstract()
    {
        $compiledConfigBuilder = new CompiledConfigBuilder();

        $masterConfig = self::createMock(MasterConfig::class);
        $masterConfig->method("getParameters")->willReturn([]);
        $masterConfig->method("getExtensions")->willReturn([]);
        $masterConfig->method("getServices")->willReturn([
            "my_abstract_service" => [
                "class" => "FakeClass",
                "abstract" => true,
                "arguments" => [
                    "argument1"
                ]
            ],
            "my_extending_abstract_service" => [
                "extends" => "@my_abstract_service"
            ]

        ]);
        $compiledConfig = $compiledConfigBuilder->build($masterConfig);

        self::assertSame([
            "class" => "FakeClass",
            "arguments" => [
                "argument1"
            ]
        ], $compiledConfig->getServices()["my_extending_abstract_service"]);
    }

    public function testRecursiveExtendingAbstract()
    {
        $compiledConfigBuilder = new CompiledConfigBuilder();

        $masterConfig = self::createMock(MasterConfig::class);
        $masterConfig->method("getParameters")->willReturn([]);
        $masterConfig->method("getExtensions")->willReturn([]);
        $masterConfig->method("getServices")->willReturn([
            "my_abstract_service" => [
                "class" => "FakeClass",
                "abstract" => true,
                "arguments" => [
                    "argument1"
                ]
            ],
            "my_middle_abstract_service" => [
                "abstract" => true,
                "extends" => "@my_abstract_service",
                "calls" => [
                    "method" => "addMessage",
                    "arguments" => [
                        "my_message"
                    ]
                ]
            ],
            "my_extending_abstract_service" => [
                "extends" => "@my_middle_abstract_service"
            ]

        ]);
        $compiledConfig = $compiledConfigBuilder->build($masterConfig);

        self::assertEquals([
            "class" => "FakeClass",
            "arguments" => [
                "argument1"
            ],
            "calls" => [
                "method" => "addMessage",
                "arguments" => [
                    "my_message"
                ]
            ]
        ], $compiledConfig->getServices()["my_extending_abstract_service"]);
    }


    public function testTagCreation()
    {
        $compiledConfigBuilder = new CompiledConfigBuilder();

        $masterConfig = self::createMock(MasterConfig::class);
        $masterConfig->method("getParameters")->willReturn([]);
        $masterConfig->method("getExtensions")->willReturn([]);
        $masterConfig->method("getServices")->willReturn([
            "service1" => [
                "class" => "FakeClass",
                "tags" => [
                    // Method 1
                    "common_tag"
                ]
            ],
            "service2" => [
                "class" => "FakeClass2",
                "tags" => [
                    "common_tag" => "my_alias"
                ]
            ]
        ]);
        $compiledConfig = $compiledConfigBuilder->build($masterConfig);

        self::assertEquals([
            [
                "service" => "service1",
                "alias" => ""
            ],
            [
                "service" => "service2",
                "alias" => "my_alias"
            ]
        ], $compiledConfig->getTags()["common_tag"]);
    }

    public function testExtensions()
    {
        $compiledConfigBuilder = new CompiledConfigBuilder();
        $masterConfig = self::createMock(MasterConfig::class);
        $masterConfig->method("getParameters")->willReturn([]);
        $masterConfig->method("getServices")->willReturn([
            "service1" => [
                "class" => "FakeClass",
                "calls" => [
                    [
                        "method" => "methodCall1",
                        "arguments" => [
                            "methodArgument1"
                        ]
                    ]
                ]
            ]
        ]);
        $masterConfig->method("getExtensions")->willReturn([
            "service1" => [
                [
                    "method" => "methodCall1",
                    "arguments" => [
                        "methodArgument1-2"
                    ]
                ],
                [
                    "method" => "methodCall2",
                    "arguments" => [
                        "methodArgument2"
                    ]
                ]
            ]
        ]);
        $compiledConfig = $compiledConfigBuilder->build($masterConfig);

        self::assertEquals([
            "service1" => [
                "class" => "FakeClass",
                "calls" => [
                    [
                        "method" => "methodCall1",
                        "arguments" => [
                            "methodArgument1"
                        ]
                    ],
                    [
                        "method" => "methodCall1",
                        "arguments" => [
                            "methodArgument1-2"
                        ]
                    ],
                    [
                        "method" => "methodCall2",
                        "arguments" => [
                            "methodArgument2"
                        ]
                    ]
                ]
            ]
        ], $compiledConfig->getServices());
    }
}