<?php

namespace Silktide\Syringe;

use Pimple\Container;
use ProxyManager\Configuration;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\FileLocator\FileLocator;
use ProxyManager\GeneratorStrategy\FileWriterGeneratorStrategy;
use Psr\SimpleCache\CacheInterface;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\Loader\JsonLoader;
use Silktide\Syringe\Loader\PhpLoader;
use Silktide\Syringe\Loader\YamlLoader;
use Exception;

class Syringe
{
    /**
     * @param array $config
     * @return Container
     * @throws ConfigException
     * @throws Exception\LoaderException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
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
            "cache" => null,
            "validateCache" => false,
            // We allow the user to set some parameters as part of the config step
            "parameters" => []
        ];

        foreach ($defaults as $key => $value) {
            if (!isset($config[$key])) {
                $config[$key] = $value;
            }
        }

        $config = self::validateConfig($config);

        if (!is_null($config["appDirKey"])) {
            $config["parameters"][$config["appDirKey"]] = $config["appDir"];
        }

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
            /**
             * @var CompiledConfig|null $compiledConfig
             */
            $compiledConfig = $cache->get($cacheKey);
        }

        if (!is_null($compiledConfig) && $config["validateCache"]) {
            if (!$compiledConfig->isValid()) {
                $compiledConfig = null;
            }
        }

        if (is_null($compiledConfig)) {
            $masterConfigBuilder = new MasterConfigBuilder($config["loaders"]);
            $masterConfig = $masterConfigBuilder->load($config["files"], $config["paths"]);

            $compiledConfigBuilder = new CompiledConfigBuilder();
            $compiledConfig = $compiledConfigBuilder->build($masterConfig, $config["parameters"]);

            if ($cacheEnabled) {
                $cache->set($cacheKey, $compiledConfig, new \DateInterval("P365D"));
            }
        }

        if (is_null($container = $config["containerService"])) {
            $container = new $config["containerClass"];
        }

        if (!is_null($config["serviceLocatorKey"])) {
            $container[$config["serviceLocatorKey"]] = new ServiceLocator($container);
        }

        $cacheDir = $config["cacheDir"];

        if (!file_exists($cacheDir)) {
            if (@mkdir($cacheDir) !== true && !is_dir($cacheDir)) {
                // This is to guard against race conditions where concurrent processes try to create the directory
                // at the same time
                $error = error_get_last();
                throw new Exception(sprintf('Failed to create "%s", error message is "%s".', $cacheDir, $error['message']), 0, null);
            }
        }

        // This can be potentially optimised as none of this will need to be built unless someone is actually using
        // the lazy functionality. I suspect that
        $proxyConfig = new Configuration();
        $fileLocator = new FileLocator($cacheDir);
        $proxyConfig->setGeneratorStrategy(new FileWriterGeneratorStrategy($fileLocator));
        $proxyConfig->setProxiesTargetDir($cacheDir);
        spl_autoload_register($proxyConfig->getProxyAutoloader());
        $factory = new LazyLoadingValueHolderFactory($proxyConfig);

        $containerBuilder = new ContainerBuilder($factory);
        $containerBuilder->populateContainer($container, $compiledConfig);

        return $container;
    }

    protected static function createCacheKey(array $config)
    {
        $uniqueConfig = array_intersect_key($config, array_flip(["appDir", "paths", "files", "parameters"]));
        return "syringe.compiled_config." . md5(serialize($uniqueConfig));
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
        if (!is_a($containerClass, ContainerBuilder::DEFAULT_CONTAINER_CLASS, true)) {
            throw new ConfigException(sprintf("The class '%s' is not a subclass of '%s'", $containerClass, ContainerBuilder::DEFAULT_CONTAINER_CLASS));
        }
    }
}
