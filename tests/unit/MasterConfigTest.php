<?php


namespace Silktide\Syringe\Tests;


use PHPUnit\Framework\TestCase;
use Silktide\Syringe\FileConfig;
use Silktide\Syringe\MasterConfig;

class MasterConfigTest extends TestCase
{
    public function testParameterOrder()
    {
        $maxFiles = 500;
        $masterConfig = new MasterConfig();
        for ($x=0; $x<=$maxFiles; $x++) {
            $masterConfig->addFileConfig("filename.yml", $this->createFakeFileConfig([
                [
                    "name" => "overwritten_key",
                    "weight" => 1,
                    "value" => "file_".$x
                ],
                [
                    "name" => "namespaced::randomly",
                    "weight" => rand(2, 10),
                    "value" => "rand"
                ]
            ]));
        }

        $parameters = $masterConfig->getParameters();
        $this->assertSame("file_".$maxFiles, $parameters["overwritten_key"]);
    }

    public function testParameterWeightOrder()
    {
        $masterConfig = new MasterConfig();
        $masterConfig->addFileConfig("filename.yml", $this->createFakeFileConfig([
            [
                "name" => "overwritten",
                "weight" => 1,
                "value" => "weight_1"
            ],
            [
                "name" => "overwritten",
                "weight" => 10,
                "value" => "weight_10"
            ],
            [
                "name" => "overwritten",
                "weight" => 5,
                "value" => "weight_5"
            ]
        ]));

        $parameters = $masterConfig->getParameters();
        $this->assertSame("weight_10", $parameters["overwritten"]);
    }
    /**
     * @param array $namespacedParameters
     * @return \PHPUnit\Framework\MockObject\MockObject|FileConfig
     */
    protected function createFakeFileConfig(array $namespacedParameters)
    {
        $fileConfig = $this->createMock(FileConfig::class);
        $fileConfig->method("getNamespacedParameters")->willReturn($namespacedParameters);
        $fileConfig->method("getNamespacedServices")->willReturn([]);
        $fileConfig->method("getNamespacedExtensions")->willReturn([]);
        return $fileConfig;
    }
}