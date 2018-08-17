<?php

namespace Silktide\Syringe;

use Psr\SimpleCache\CacheInterface;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\Loader\JsonLoader;
use Silktide\Syringe\Loader\PhpLoader;
use Silktide\Syringe\Loader\YamlLoader;

class Syringe
{
    public static function build(array $config)
    {
        // Setup config
        $defaults = [
            "appDir" => getcwd(),
            "appDirKey" => "app.dir",
            "serviceLocatorKey" => null,
            "cacheDir" => sys_get_temp_dir() . "/syringe/",
            "loaders" => [new YamlLoader(), new PhpLoader(), new JsonLoader()],
            "paths" => [getcwd(), getcwd() . "/config"],
            "files" => ["syringe.yml"],
            "containerClass" => ContainerBuilder::DEFAULT_CONTAINER_CLASS,
            "containerService" => null,
            "cache" => null
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($config[$key])) {
                $config[$key] = $value;
            }
        }

        $config = self::validateConfig($config);

        $cacheEnabled = false;
        $cacheKey = null;
        if (!is_null($cache = $config["cache"])) {
            if (!($cache instanceof CacheInterface)) {
                throw new ConfigException("'cache' must implement the PSR-16 CacheInterface");
            }
            $cacheEnabled = true;
            $cacheKey = self::createCacheKey($config);
        };

        $compiledConfig = null;
        if ($cacheEnabled) {
            $compiledConfig = $cache->get($cacheKey);
        }

        if (is_null($compiledConfig)) {
            $masterConfigBuilder = new MasterConfigBuilder($config["loaders"]);
            $masterConfig = $masterConfigBuilder->load($config["files"], $config["paths"]);

            $compiledConfigBuilder = new CompiledConfigBuilder();
            $compiledConfig = $compiledConfigBuilder->build($masterConfig);

            if ($cacheEnabled) {
                $cache->set($cacheKey, $compiledConfig, new \DateInterval("P365D"));
            }
        }

        if (is_null($container = $config["containerService"])) {
            $container = new $config["containerClass"];
        }

        $containerBuilder = new ContainerBuilder(new ReferenceResolver());
        $containerBuilder->populateContainer($container, $compiledConfig);

        if (!is_null($config["appDirKey"])) {
            $container[$config["appDirKey"]] = $config["appDir"];
        }

        if (!is_null($config["serviceLocatorKey"])) {
            $container[$config["serviceLocatorKey"]] = new ServiceLocator($container);
        }

        return $container;
    }

    protected static function createCacheKey(array $config)
    {
        $uniqueConfig = array_intersect_key($config, array_flip(["appDir", "paths", "files"]));
        return "syringe.compiled_config-" . md5(json_encode($uniqueConfig, true));
    }

    protected static function validateConfig(array $config = [])
    {
        // Validate config
        if ($config["containerClass"] !== ContainerBuilder::DEFAULT_CONTAINER_CLASS) {
            self::validateContainerClass($config["containerClass"]);
        }

        // If the path doesn't exist or isn't a directory, then remove it now rather than later
        foreach ($config["paths"] as $key => $path) {
            if (!file_exists($path) || !is_dir($path)) {
                unset($config["paths"][$key]);
            }
        }

        return $config;
    }

    protected static function validateContainerClass(string $containerClass)
    {
        // check existence
        if (!class_exists($containerClass)) {
            throw new ConfigException(sprintf("The container class '%s' does not exist", $containerClass));
        }

        // check the class is a container
        if ($containerClass != ContainerBuilder::DEFAULT_CONTAINER_CLASS && !is_subclass_of($containerClass, ContainerBuilder::DEFAULT_CONTAINER_CLASS)) {
            throw new ConfigException(sprintf("The class '%s' is not a subclass of '%s'", $containerClass, ContainerBuilder::DEFAULT_CONTAINER_CLASS));
        }
    }
}
