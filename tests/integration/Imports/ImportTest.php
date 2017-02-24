<?php

namespace Silktide\Syringe\IntegrationTests\Imports;

use Silktide\Syringe\ContainerBuilder;
use Silktide\Syringe\Loader\JsonLoader;
use Silktide\Syringe\Loader\YamlLoader;
use Silktide\Syringe\ReferenceResolver;

class NamespacingTest extends \PHPUnit_Framework_TestCase
{
    public function testParameterImports()
    {
        $resolver = new ReferenceResolver();
        $builder = new ContainerBuilder($resolver);
        $builder->addLoader(new YamlLoader());
        $builder->addConfigPath(__DIR__);
        $builder->addConfigFiles([
            "base.yml"
        ]);
        $container = $builder->createContainer();
        $this->assertEquals("bar", $container["foo"]);
    }
}