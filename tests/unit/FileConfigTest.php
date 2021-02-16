<?php

namespace Silktide\Syringe\Tests;

use PHPUnit\Framework\TestCase;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\FileConfig;

class FileConfigTest extends TestCase
{
    public function validationFailureProvider()
    {
        return [
            [
                [
                    "foo" => "bar"
                ],
                "No class specified"
            ],
            [
                [
                    "class" => "DateTime",
                    "factoryMethod" => "foo"
                ],
                "No FactoryClass or FactoryService"
            ],
            [
                [
                    "class" => "DateTime",
                    "factoryMethod" => "foo",
                    "factoryClass" => "foo",
                    "factoryService" => "bar"
                ],
                "Both a FactoryClass and a FactoryService"
            ],
            [
                [
                    "class" => "DateTime",
                    "factoryClass" => "Foo",
                    "factoryMethod" => "foo"
                ],
                "FactoryClass class does not exist"
            ],
            [
                [
                    "class" => "DateTime",
                    "factoryClass" => "DateTime",
                    "factoryMethod" => "foo"
                ],
                "FactoryMethod does not exist on factoryClass"
            ],
            [
                [
                     "class" => "DateTime",
                     "invalidkey" => "InvalidValue"
                ],
                "Uses an invalid services =key"
            ],
            [
                [
                    "aliasOf" => "foo"
                ],
                "Uses an alias not prefixed with @"
            ]
        ];
    }

    /**
     * @dataProvider validationFailureProvider
     */
    public function testFailures(array $data, string $reason)
    {
        $this->expectException(ConfigException::class);
        $fileConfig = new FileConfig("filename.yml", ["services" => ["service" => $data]]);
        $fileConfig->validate();
    }

    public function validationSuccessProvider()
    {
        return [
            [
                [
                    "class" => "DateTime"
                ]
            ],
            [
                [
                    "class" => "DateTime",
                    "factoryClass" => "DateTime",
                    "factoryMethod" => "createFromFormat"
                ]
            ],
            [
                [
                    "aliasOf" => "@foo"
                ]
            ],
            [
                [
                    "abstract" => true
                ]
            ],
            [
                [
                    "class" => "DateTime",
                    "factoryService" => "Foo",
                    "factoryMethod" => "foo"
                ]
            ]
        ];
    }


    /**
     * @dataProvider validationSuccessProvider
     */
    public function testSuccesses(array $data)
    {
        $fileConfig = new FileConfig("filename.yml", ["services" => ["service" => $data]]);
        $fileConfig->validate();
        self::assertTrue(true);
    }
}