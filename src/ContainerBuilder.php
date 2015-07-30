<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Syringe;
use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\Exception\LoaderException;
use Silktide\Syringe\Loader\LoaderInterface;

/**
 * ContainerBuilder parses configuration files and build a Pimple Container
 */
class ContainerBuilder {

    /**
     * The character that identifies a service
     * e.g. "@service"
     */
    const SERVICE_CHAR = "@";

    /**
     * The character that identifies the boundaries of a parameter
     * e.g. "%parameter%"
     */
    const PARAMETER_CHAR = "%";

    /**
     * Default class name for the container
     */
    const DEFAULT_CONTAINER_CLASS = "Pimple\\Container";

    /**
     * Contains the class name for the container we want to create
     *
     * @var string
     */
    protected $containerClass = self::DEFAULT_CONTAINER_CLASS;

    /**
     * @var array
     */
    protected $loaders = [];

    /**
     * @var array
     */
    protected $configPaths = [];

    /**
     * @var array
     */
    protected $configFiles = [];

    /**
     * @var ReferenceResolverInterface
     */
    protected $referenceResolver;

    /**
     * @var ServiceFactory
     */
    protected $serviceFactory;

    /**
     * @var array
     */
    protected $abstractDefinitions = [];

    /**
     * @var array
     */
    protected $parameterNames = [];

    /**
     * @param ReferenceResolverInterface $resolver
     * @param array $configPaths
     */
    public function __construct(ReferenceResolverInterface $resolver, array $configPaths = [])
    {
        $this->referenceResolver = $resolver;

        $this->configPaths = ["/"];
        foreach ($configPaths as $path) {
            $this->addConfigPath($path);
        }
    }

    public function setContainerClass($containerClass)
    {
        // check existence
        if (!class_exists($containerClass)) {
            throw new ConfigException(sprintf("The container class '%s' does not exist", $containerClass));
        }
        // check the class is a container
        if ($containerClass != self::DEFAULT_CONTAINER_CLASS && !is_subclass_of($containerClass, self::DEFAULT_CONTAINER_CLASS)) {
            throw new ConfigException(sprintf("The class '%s' is not a subclass of '%s'", $containerClass, self::DEFAULT_CONTAINER_CLASS));
        }

        $this->containerClass = $containerClass;
    }

    /**
     * Add a directory to search in when loading configuration files
     *
     * @param string $path
     * @throws Exception\LoaderException
     */
    public function addConfigPath($path)
    {
        if (!is_dir($path)) {
            throw new LoaderException(sprintf("The config path '%s' is not a valid directory", $path));
        }
        if ($path[strlen($path) - 1] != "/") {
            $path .= "/";
        }
        $this->configPaths[] = $path;
    }

    /**
     * @param LoaderInterface $loader
     */
    public function addLoader(LoaderInterface $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * @param string $name
     */
    public function removeLoader($name)
    {
        foreach ($this->loaders as $i => $loader) {
            /** @var LoaderInterface $loader */
            if ($name == $loader->getName()) {
                unset($this->loaders[$i]);
            }
        }
    }

    /**
     * Remove the loader that supports the specified file
     *
     * @param string $file
     */
    public function removeLoaderByFile($file)
    {
        foreach ($this->loaders as $i => $loader) {
            /** @var LoaderInterface $loader */
            if ($loader->supports($file)) {
                unset($this->loaders[$i]);
            }
        }
    }

    /**
     * Queue a config file to be processed
     *
     * @param string $file
     * @param string|null $alias
     * @throws Exception\LoaderException
     */
    public function addConfigFile($file, $alias = null) {

        $filePath = $this->findConfigFile($file);
        if (!is_string($alias)) {
            $this->configFiles[] = $filePath;
        } else {
            $this->configFiles[$alias] = $filePath;
        }
    }

    protected function findConfigFile($file)
    {
        foreach ($this->configPaths as $path) {
            $filePath = $path . $file;
            if (file_exists($filePath)) {
                return $filePath;
            }
        }
        throw new LoaderException(sprintf("The config file '%s' does not exist in any of the configured paths", $file));
    }

    /**
     * @param array $files
     */
    public function addConfigFiles(array $files)
    {
        foreach ($files as $alias => $file) {
            $this->addConfigFile($file, $alias);
        }
    }

    /**
     * @return Container
     */
    public function createContainer()
    {
        $container = new $this->containerClass();
        // setup the service factory if necessary
        if (empty($this->serviceFactory)) {
            $this->serviceFactory = new ServiceFactory($container, $this->referenceResolver);
        }
        
        foreach ($this->configFiles as $alias => $file) {
            if (!is_string($alias)) {
                // empty alias for numeric keys
                $alias = "";
            }
            $config = $this->loadConfig($file);
            $config = $this->processImports($config);
            $this->processParameters($config, $container, $alias);
            $this->processServices($config, $container, $alias);
            
        }
        return $container;
    }

    /**
     * @throws Exception\ConfigException
     */
    public function createProvider()
    {
        throw new ConfigException("Not implemented");
    }

    /**
     * @param $file
     * @return mixed
     * @throws Exception\LoaderException
     */
    protected function loadConfig($file)
    {
        $loader = $this->selectLoader($file);
        $config = $loader->loadFile($file);
        if (!is_array($config)) {
            throw new LoaderException(sprintf("The data from '%s' is invalid", $file));
        }
        return $config;
    }

    /**
     * @param $file
     * @return LoaderInterface
     * @throws Exception\LoaderException
     */
    protected function selectLoader($file)
    {
        foreach ($this->loaders as $loader) {
            /** @var LoaderInterface $loader */
            if ($loader->supports($file)) {
                return $loader;
            }
        }
        throw new LoaderException(sprintf("The file '%s' is not supported by any of the available loaders", $file));
    }

    /**
     * Utility function
     * TODO: refactor into a separate class
     *
     * @param $var
     * @return bool
     */
    protected function isAssocArray($var)
    {
        return is_array($var) && !array_key_exists(0, $var);
    }

    /**
     * Load in any dependent configuration files
     *
     * Inherited configuration can be overwritten by the current configuration
     * Imported configuration can overwrite what we already have
     *
     * @param array $config
     * @return array
     */
    protected function processImports(array $config)
    {
        if (isset($config["inherit"])) {
            $filePath = $this->findConfigFile($config["inherit"]);
            $inheritedConfig = $this->loadConfig($filePath);
            // check for recursive imports or inheritance
            $inheritedConfig = $this->processImports($inheritedConfig);
            $config = array_replace_recursive($inheritedConfig, $config);
        }
        if (isset($config["imports"]) && is_array($config["imports"])) {
            foreach ($config["imports"] as $file) {
                $filePath = $this->findConfigFile($file);
                $importConfig = $this->loadConfig($filePath);
                // check for recursive imports or inheritance
                $importConfig = $this->processImports($importConfig);
                $config = array_replace_recursive($config, $importConfig);
            }
        }
        
        return $config;
    }

    /**
     * TODO: add support for parameters defined as functions
     *
     * @param array $config
     * @param Container $container
     * @param string $alias
     * @throws Exception\ConfigException
     */
    protected function processParameters(array $config, Container $container, $alias = "")
    {
        if (!isset($config["parameters"])) {
            return;
        }
        if (!$this->isAssocArray($config["parameters"])) {
            throw new ConfigException("The 'parameters' configuration must be an associative array");
        }
        foreach ($config["parameters"] as $key => $value) {
            $key = $this->referenceResolver->aliasThisKey($key, $alias);
            $resolver = $this->referenceResolver;
            $container[$key] = function () use ($value, $resolver, $container) {
                return $resolver->resolveParameter($value, $container);
            };
            $this->parameterNames[] = $key;
        }
    }

    /**
     * parse service definition and add to the container
     *
     * @param array $config
     * @param Container $container
     * @param string $alias
     * @throws Exception\ConfigException
     */
    protected function processServices(array $config, Container $container, $alias = "")
    {
        if (!isset($config["services"])) {
            return;
        }
        if (!$this->isAssocArray($config["services"])) {
            throw new ConfigException("The 'services' configuration must be an associative array");
        }

        // scan for abstract definitions
        foreach ($config["services"] as $key => $definition) {

            if (!empty($definition["abstract"])) {
                $this->abstractDefinitions[self::SERVICE_CHAR . $this->referenceResolver->aliasThisKey($key, $alias)] = $definition;
                unset ($config["services"][$key]);
            }
        }

        // process services
        foreach ($config["services"] as $key => $definition) {
            $key = $this->referenceResolver->aliasThisKey($key, $alias);
            if (!$this->isAssocArray($definition)) {
                throw new ConfigException("A service definition must be an associative array");
            }
            // check if this definition extends an abstract one
            if (!empty($definition["extends"])) {
                $extends = $this->referenceResolver->aliasThisKey($definition["extends"], $alias);
                if (!isset($this->abstractDefinitions[$extends])) {
                    throw new ConfigException(
                        sprintf(
                            "The service definition for '%s' extends '%s' but there is no abstract definition of that name",
                            $key,
                            $extends
                        )
                    );
                }

                // As calls gets wiped out by the replace_recursive, so we need to store it and merge it seperately
                $calls = !empty($definition["calls"]) ? $definition["calls"] : [];
                if (!empty($this->abstractDefinitions[$extends]["calls"])) {
                    $calls = array_merge($calls, $this->abstractDefinitions[$extends]["calls"]);
                }
                $definition = array_replace_recursive($this->abstractDefinitions[$extends], $definition);
                $definition["calls"] = $calls;
            }

            // get class
            if (empty($definition["class"])) {
                throw new ConfigException(sprintf("The service definition for %s does not have a class", $key));
            }
            // get class, resolving parameters if necessary
            $class = $this->referenceResolver->resolveParameter($definition["class"], $container, $alias);

            if (!class_exists($class)) {
                throw new ConfigException(sprintf("The service class '%s' does not exist", $class));
            }

            // factories
            $factory = [];
            if (!empty($definition["factoryMethod"])) {
                $factory["method"] = $definition["factoryMethod"];
            }

            if (!empty($definition["factoryClass"])) {
                // check for malformed definitions ...
                if (empty($factory["method"])) {
                    throw new ConfigException(sprintf("A factory class was specified for '%s', but no method was set", $key));
                }
                //... and non-existent classes
                $factoryClass = $this->referenceResolver->resolveParameter($definition["factoryClass"], $container, $alias);
                if (!class_exists($factoryClass)) {
                    throw new ConfigException(
                        sprintf("The factory class '%s', for '%s', does not exist", $factoryClass, $key)
                    );
                }
                // make sure the method actually exists on the class
                if (!method_exists($factoryClass, $factory["method"])) {
                    throw new ConfigException(
                        sprintf(
                            "Invalid factory definition. The method '%s' does not exist on the class '%s'",
                            $factory["method"],
                            $factoryClass
                        )
                    );
                }
                $factory["class"] = $factoryClass;
            }

            if (!empty($definition["factoryService"])) {
                // check for malformed definitions
                if (empty($factory["method"])) {
                    throw new ConfigException(sprintf("A factory service was specified for '%s', but no method was set", $key));
                }
                if (!empty($factory["class"])) {
                    throw new ConfigException(sprintf("The definition for '%s' cannot have both a factory class and a factory service", $key));
                }
                // remove the service char if it exists
                if ($definition["factoryService"][0] == self::SERVICE_CHAR) {
                    $definition["factoryService"] = substr($definition["factoryService"], 1);
                }
                $factory["service"] = $this->referenceResolver->aliasThisKey($definition["factoryService"], $alias);
            }

            // arguments
            $arguments = !empty($definition["arguments"])? $definition["arguments"]: [];

            // calls / setters
            $calls = !empty($definition["calls"])? $definition["calls"]: [];
            foreach ($calls as $i => &$call) {
                if (empty($call["method"])) {
                    throw new ConfigException(sprintf("Call '%s' for the service '%s' does not specify a method name", $i, $key));
                }
                if (!method_exists($class, $call["method"])) {
                    throw new ConfigException(sprintf("Error for service '%s': the method call '%s' does not exist for the class '%s'", $key, $call["method"], $class));
                }

                if (empty($call["arguments"])) {
                    // if no arguments have been defined, set arguments to an empty array
                    $call["arguments"] = [];
                } else {
                    if (!is_array($call["arguments"])) {
                        throw new ConfigException(sprintf("Error for service '%s': the method call '%s' has invalid arguments", $key, $call["method"]));
                    }
                }
            }

            // add the definition to the container
            $container[$key] = function() use ($class, $factory, $arguments, $calls, $alias) {
                // parse arguments for injected parameters and services
                return $this->serviceFactory->createService($class, $factory, $arguments, $calls, $alias);
            };

        }
    }
    
}