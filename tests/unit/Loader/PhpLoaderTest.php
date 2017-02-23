<?php

namespace Silktide\Syringe\Tests\Loader;

use Silktide\Syringe\ContainerBuilder;
use Silktide\Syringe\Loader\PhpLoader;
use Silktide\Syringe\ReferenceResolver;

class PhpLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testParameterReturnCorrect()
    {
        $referenceResolver = new ReferenceResolver();
        $containerBuilder = new ContainerBuilder($referenceResolver, [__DIR__]);
        $containerBuilder->addLoader(new PhpLoader());
        $containerBuilder->addConfigFile("PhpLoaderExampleFile.php");
        $container = $containerBuilder->createContainer();
        $this->assertEquals($container->offsetGet("Foo"), "Bar");
        $this->assertEquals(\DateTime::class, get_class($container->offsetGet("datetime")));
    }
}