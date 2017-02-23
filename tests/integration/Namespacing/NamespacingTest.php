<?php

namespace Silktide\Syringe\IntegrationTests\Namespacing;

use Silktide\Syringe\ContainerBuilder;
use Silktide\Syringe\Loader\JsonLoader;
use Silktide\Syringe\Loader\YamlLoader;
use Silktide\Syringe\ReferenceResolver;

class NamespacingTest extends \PHPUnit_Framework_TestCase
{
    public function testParameterLayering()
    {
        $resolver = new ReferenceResolver();
        $builder = new ContainerBuilder($resolver);
        $builder->addLoader(new YamlLoader());
        $builder->addLoader(new JsonLoader());
        $builder->addConfigPath(__DIR__);
        $builder->addConfigFiles([
            "dependency" => "dependency.yml",
            "parent.yml"
        ]);
        $container = $builder->createContainer();
        $this->assertEquals("42", $container["my_api_key"]);
        $this->assertEquals("42", $container["dependency.key_using_api_key"]);
    }
}