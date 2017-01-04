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

    protected static $loaders;
    
    protected static $appDir;
    
    protected static $configFiles;

    /**
     * Initialise the DI container builder
     * 
     * @param string $appDir - the root directory of the application
     * @param array $configFiles - the syringe config files to load into the container
     */
    public static function init($appDir, array $configFiles)
    {
        self::$loaders = [
            new JsonLoader(),
            new YamlLoader()
        ];
        
        self::$appDir = $appDir;
        self::$configFiles = $configFiles;
    }

    /**
     * Build a new DI container
     * 
     * @return \Pimple\Container
     */
    public static function createContainer()
    {
        $resolver = new ReferenceResolver();
        
        $builder = new ContainerBuilder($resolver, [self::$appDir]);

        foreach (self::$loaders as $loader) {
            $builder->addLoader($loader);
        }
        $builder->setApplicationRootDirectory(self::$appDir);

        $builder->addConfigFiles(self::$configFiles);

        return $builder->createContainer();
    }

}