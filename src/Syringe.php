<?php


namespace Silktide\Syringe;

use Pimple\Psr11\ServiceLocator;
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
            "appDirKey" => "app.dir", // Todo: Don't forget about this... although wtf is a "app.dir" anyway
            "serviceLocatorKey" => "_syringe.service_locator",
            "cacheDir" => sys_get_temp_dir() . "/syringe/",
            "loaders" => [new YamlLoader(), new PhpLoader(), new JsonLoader()],
            "paths" => [getcwd() . "/config"],
            "files" => ["syringe.yml"],
            "containerClass" => ContainerBuilder::DEFAULT_CONTAINER_CLASS,
            "cache" => null
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($config[$key])) {
                $config[$key] = $value;
            }
        }

        self::validateConfig($config);

        /**
         * @var CacheInterface|null $cache
         */
        $cache = $config["cache"];
        $cacheEnabled = $cache instanceof CacheInterface;
        $cacheKey = "syringe.compiled_config";

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

        $containerBuilder = new ContainerBuilder(new ReferenceResolver());
        $container = $containerBuilder->createContainer($compiledConfig, $config["containerClass"]);


        $container[$config["appDirKey"]] = $config["appDir"];
        /*$container["silktide_syringe.serviceLocator"] = function () use ($container) {
            return new ServiceLocator($container);
        }*/
        return $container;
    }

    protected static function validateConfig(array $config = [])
    {
        // Validate config
        if ($config["containerClass"] !== ContainerBuilder::DEFAULT_CONTAINER_CLASS) {
            $containerClass = $config["containerClass"];
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
}
