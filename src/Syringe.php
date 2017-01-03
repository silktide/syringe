<?php

namespace Silktide\Syringe;

use Silktide\Syringe\Loader\JsonLoader;
use Silktide\Syringe\Loader\YamlLoader;

/**
 * Create Containers from DI configuration
 * 
 * Uses static methods in order to be accessible from 
 */
class Syringe
{

    /**
     * @var ContainerBuilder
     */
    protected static $builder;

    /**
     * Initialise the DI container builder
     * 
     * @param string $appDir - the root directory of the application
     * @param array $configFiles - the syringe config files to load into the container
     */
    public static function init($appDir, array $configFiles)
    {
        $resolver = new ReferenceResolver();
        $loaders = [
            new JsonLoader(),
            new YamlLoader()
        ];

        $configPaths = [
            $appDir
        ];

        self::$builder = new ContainerBuilder($resolver, $configPaths);

        foreach ($loaders as $loader) {
            self::$builder->addLoader($loader);
        }
        self::$builder->setApplicationRootDirectory($appDir);
        
        self::$builder->addConfigFiles($configFiles);
    }

    /**
     * Build a new DI container
     * 
     * @return \Pimple\Container
     */
    public static function createContainer()
    {
        return self::$builder->createContainer();
    }

}