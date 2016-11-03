<?php

namespace Silktide\Syringe;

use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\Exception\LoaderException;
use Silktide\Syringe\Exception\ReferenceException;
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
     * The character that identifies a reference to a constant
     * e.g. "^MyCompany\\MyClass::MY_CONSTANT^" or "^STDOUT^"
     */
    const CONSTANT_CHAR = "^";

    /**
     * The character that identifies a collection of service with a specific tag
     */
    const TAG_CHAR = "#";

    /**
     * The prefix of any environment variables we want to try to automatically import
     */
    const ENVIRONMENT_PREFIX = "SYRINGE__";

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
     * @var string
     */
    protected $applicationRootDirectory;

    /**
     * @var string
     */
    protected $applicationRootDirectoryKey;

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
     * @var array
     */
    protected $serviceAliases = [];

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

    public function setApplicationRootDirectory($directory, $key = "")
    {
        if (!is_dir($directory)) {
            throw new ConfigException(sprintf("Cannot set the application root directory. '%s' is not a directory", $directory));
        }
        if (empty($key)) {
            $key = "app.dir";
        }

        $this->applicationRootDirectory = $directory;
        $this->applicationRootDirectoryKey = $key;
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

    protected function findImportedConfigFile($file, $dir)
    {
        $filePath = $dir . "/" . $file;
        if (file_exists($filePath)) {
            return $filePath;
        }
        throw new LoaderException(sprintf("The import file '%s' does not exist in the directory '%s'", $file, $dir));
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
        $this->populateContainer($container);
        return $container;
    }

    /**
     * @param Container $container
     * @throws ConfigException
     * @throws LoaderException
     * @throws ReferenceException
     */
    public function populateContainer(Container $container)
    {
        // setup the service factory if necessary
        if (empty($this->serviceFactory)) {
            $this->serviceFactory = new ServiceFactory($container, $this->referenceResolver);
        }

        $aliases = array_keys($this->configFiles);
        $this->referenceResolver->setRegisteredAliases($aliases);

        $configs = [];
        foreach ($this->configFiles as $alias => $file) {
            if (!is_string($alias)) {
                // empty alias for numeric keys
                $alias = "";
            }

            $config = $this->loadConfig($file);
            $config = $this->processImports($config, dirname($file));
            $this->processParameters($config, $container, $alias);
            $this->processServices($config, $container, $alias);
            $configs[] = ["config" => $config, "alias" => $alias];
        }

        // process service extensions, now that all the services have been defined
        foreach ($configs as $aliasedConfig) {
            $this->processExtensions($aliasedConfig["config"], $container, $aliasedConfig["alias"]);
        }

        $this->processEnvironment($container);
        $this->applyApplicationRootDirectory($container);

        // add the service locator to the container
        if (!$container->offsetExists("serviceLocator")) {
            $container["silktide_syringe.serviceLocator"] = function () use ($container) {
                return new ServiceLocator($container);
            };
        }
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

    protected function applyApplicationRootDirectory(Container $container)
    {
        if (!empty($this->applicationRootDirectory)) {
            $container[$this->applicationRootDirectoryKey] = $this->applicationRootDirectory;
        }
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
     * Both are restricted to importing files relative to the parent config file
     *
     * @param array $config
     * @param $importDir
     * @return array
     */
    protected function processImports(array $config, $importDir)
    {
        if (isset($config["inherit"])) {
            $filePath = $this->findImportedConfigFile($config["inherit"], $importDir);
            $inheritedConfig = $this->loadConfig($filePath);
            // check for recursive imports or inheritance
            $inheritedConfig = $this->processImports($inheritedConfig, $importDir);
            $config = array_replace_recursive($inheritedConfig, $config);
        }
        if (isset($config["imports"]) && is_array($config["imports"])) {
            foreach ($config["imports"] as $file) {
                $filePath = $this->findImportedConfigFile($file, $importDir);
                $importConfig = $this->loadConfig($filePath);
                // check for recursive imports or inheritance
                $importConfig = $this->processImports($importConfig, $importDir);
                $config = array_replace_recursive($config, $importConfig);
            }
        }

        return $config;
    }

    /**
     * @param Container $container
     * @return mixed
     */
    protected function processEnvironment(Container $container)
    {
        $containerKeys = $container->keys();

        // Note: This will only set parameters IF they already exist in some form in the configuration
        foreach ($_SERVER as $key => $value) {
            if (0 === stripos($key, self::ENVIRONMENT_PREFIX)) {
                $key = substr($key, strlen(self::ENVIRONMENT_PREFIX));
                $key = str_replace("__", ".", $key);
                $key = strtolower($key);

                // Look to see if the environment variable exists purely as lowercase
                if (!$container->offsetExists($key)) {
                    // If it doesn't, then lowercase the container keys and see if we can find it there
                    if (($offsetKey = array_search(strtolower($key), array_map('strtolower', $containerKeys)))===false) {
                        // If we can't, then we shouldn't be setting this variable
                        continue;
                    }
                    // Otherwise, use the correct key
                    $key = $containerKeys[$offsetKey];
                }

                $container->offsetSet($key, $value);
            }
        }
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
            $aliasedKey = $this->referenceResolver->aliasThisKey($key, $alias);
            if ($container->offsetExists($aliasedKey) && $alias != "") {
                continue;
            }
            if (!$this->referenceResolver->keyIsAliased($key)) {
                $key = $aliasedKey;
            }

            $resolver = $this->referenceResolver;
            $container[$key] = function () use ($value, $resolver, $container, $key) {
                try {
                    return $resolver->resolveParameter($value, $container);
                } catch (ReferenceException $e) {
                    throw new ReferenceException("Error with key '$key'. " . $e->getMessage());
                }
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
     * @throws ConfigException
     * @throws ReferenceException
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
            // check if this is an alias of another service
            if (!empty($definition["aliasOf"])) {
                // override any existing definitions for this key
                $aliasedService = $definition["aliasOf"];
                $container[$key] = function() use ($container, $aliasedService, $alias) {
                    return $this->serviceFactory->aliasService($aliasedService, $alias);
                };
                $this->serviceAliases[$key] = true;
                continue;
            }

            $key = $this->referenceResolver->aliasThisKey($key, $alias);

            // check for collisions
            if (isset($container[$key])) {
                if (isset($this->serviceAliases[$key])) {
                    // this service has been aliased by another service. We can ignore the definition.
                    continue;
                }
                throw new ConfigException(sprintf("Tried to define a service named '%s', but that name already exists in the container", $key));
            }

            if (!$this->isAssocArray($definition)) {
                throw new ConfigException("A service definition must be an associative array");
            }
            
            $maxIterations = 20;
            $currentIterations = 0;
            // check if this definition extends an abstract one
            while (!empty($definition["extends"]) && $currentIterations < $maxIterations) {
                $extends = self::SERVICE_CHAR . $this->referenceResolver->aliasThisKey(ltrim($definition["extends"], self::SERVICE_CHAR), $alias);
                if (!isset($this->abstractDefinitions[$extends])) {
                    throw new ConfigException(
                        sprintf(
                            "The service definition for '%s' extends '%s' but there is no abstract definition of that name",
                            $key,
                            $extends
                        )
                    );
                }

                // As calls gets wiped out by the replace_recursive, so we need to store it and merge it separately
                $calls = !empty($definition["calls"]) ? $definition["calls"] : [];
                if (!empty($this->abstractDefinitions[$extends]["calls"])) {
                    $calls = array_merge($calls, $this->abstractDefinitions[$extends]["calls"]);
                }
                unset($definition["extends"]);
                $definition = array_replace_recursive($this->abstractDefinitions[$extends], $definition);
                $definition["calls"] = $calls;
                $currentIterations++;
            }

            // get class
            if (empty($definition["class"])) {
                throw new ConfigException(sprintf("The service definition for %s does not have a class", $key));
            }
            // get class, resolving parameters if necessary
            try {
                $class = $this->referenceResolver->resolveParameter($definition["class"], $container, $alias);
            } catch (ReferenceException $e) {
                throw new ReferenceException("Error resolving class for '$key'. " . $e->getMessage());
            }

            if (!class_exists($class) && !interface_exists($class)) {
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
                try {
                    $factoryClass = $this->referenceResolver->resolveParameter($definition["factoryClass"], $container, $alias);
                } catch (ReferenceException $e) {
                    throw new ReferenceException("Error parsing factory class for '$key'. " . $e->getMessage());
                }
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
                // add the service char if it does not exist
                if ($definition["factoryService"][0] != self::SERVICE_CHAR) {
                    $definition["factoryService"] = "@" . $definition["factoryService"];
                }
                // don't alias the service here, we'll do that later when we're about to use it
                $factory["service"] = $definition["factoryService"];
            }

            // arguments
            $arguments = !empty($definition["arguments"])? $definition["arguments"]: [];

            // calls / setters
            $calls = !empty($definition["calls"])? $definition["calls"]: [];
            foreach ($calls as $i => $call) {
                $calls[$i] = $this->processCall($call, $i, $key, $class);
            }

            // tags
            if (!empty($definition["tags"])) {
                if (!is_array($definition["tags"])) {
                    throw new ConfigException(sprintf("Error for service '%s': the tags definition was not in the expected array format", $key));
                }
                foreach ($definition["tags"] as $tag => $tagKey) {
                    // tags are either defined as ' - "#tagName" ' or ' "#tagName": "tagKey" ', so
                    // we have to detect the type of $tag and change the variables around if required
                    if (is_numeric($tag)) {
                        $tag = $tagKey;
                        $tagKey = null;
                    }
                    $tag = self::TAG_CHAR . $tag;
                    if (!isset($container[$tag])) {
                        $container[$tag] = function() {
                            return new TagCollection();
                        };
                    }
                    /** @var TagCollection $collection */
                    $collection = $container[$tag];
                    $collection->addService($key, $this->referenceResolver->resolveParameter($tagKey, $container, $alias));
                }
            }

            // add the definition to the container
            $container[$key] = function() use ($class, $factory, $arguments, $calls, $alias) {
                // parse arguments for injected parameters and services
                return $this->serviceFactory->createService($class, $factory, $arguments, $calls, $alias);
            };

        }
    }

    protected function processExtensions(array $config, Container $container, $alias)
    {
        $extensions = !empty($config["extensions"])? $config["extensions"]: [];

        foreach ($extensions as $service => $extension) {
            if (!$container->offsetExists($service)) {
                $aliasedService = $this->referenceResolver->aliasThisKey($service, $alias);
                if (!$container->offsetExists($aliasedService)) {
                    throw new ConfigException(sprintf("Cannot use extension for the service '%s' as it does not exist", $service));
                }
                $service = $aliasedService;
            }

            foreach ($extension as $i => $call) {
                $extension[$i] = $this->processCall($call, $i, $service . " (extension)");
            }

            if (!empty($extension)) {
                $container->extend($service, function($object) use ($extension, $alias) {
                    return $this->serviceFactory->extendService($object, $extension, $alias);
                });
            }

        }
    }

    protected function processCall($call, $i, $key, $class = "")
    {
        if (empty($call["method"])) {
            throw new ConfigException(sprintf("Call '%s' for the service '%s' does not specify a method name", $i, $key));
        }
        if (!empty($class) && !method_exists($class, $call["method"])) {
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
        return $call;
    }

}
