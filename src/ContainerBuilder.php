<?php


namespace Silktide\Syringe;


use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;
use Silktide\Syringe\Exception\LoaderException;
use Silktide\Syringe\Exception\ReferenceException;
use Silktide\Syringe\Loader\LoaderInterface;

class ContainerBuilder
{
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
     * @var string
     */
    protected $applicationRootDirectory;

    protected $loaders = [];

    protected $referenceResolver;

    public function __construct()
    {
        $this->referenceResolver = new ReferenceResolver();
    }

    /**
     * @return Container
     */
    public function createContainer(CompiledConfig $compiledConfig)
    {
        $container = new $this->containerClass();
        $this->populateContainer($container, $compiledConfig);
        return $container;
    }

    public function populateContainer(Container $container, CompiledConfig $compiledConfig)
    {
        //
        // Do the parameters!
        //
        foreach ($compiledConfig->getParameters() as $key => $value) {
            $container[$key] = function () use ($container, $key, $value) {
                try {
                    return $this->referenceResolver->resolve($container, $value);
                } catch (ReferenceException $e) {
                    throw new ReferenceException("Error with key '$key'. " . $e->getMessage());
                }
            };
        }

        foreach ($compiledConfig->getServices() as $key => $definition) {
            $container[$key] = function () use ($container, $key, $definition) {
                $isFactoryCreated = isset($definition["factoryMethod"]);

                if ($isFactoryCreated) {
                    $service = null;
                    if (isset($definition["factoryService"])) {
                        $service = $this->referenceResolver->resolve($container, $definition["factoryService"]);
                        $method = $definition["factoryMethod"];
                        $arguments = $this->referenceResolver->resolveArray($container, $definition["arguments"] ?? []);
                        return call_user_func_array([$service, $method], $arguments);
                    } else {
                        $arguments = $this->referenceResolver->resolveArray($container, $definition["arguments"] ?? []);
                        $factoryClass = $definition["factoryClass"];
                        $factoryMethod = $definition["factoryMethod"];
                        return call_user_func_array([$factoryClass, $factoryMethod], $arguments);
                    }
                }

                $ref = new \ReflectionClass($definition["class"]);
                $args = $this->referenceResolver->resolveArray($container, $definition["arguments"] ?? []);
                $service = $ref->newInstanceArgs(
                    $args
                );

                foreach ($definition["calls"] ?? [] as $call) {
                    call_user_func_array(
                        [$service, $call["method"]],
                        $this->referenceResolver->resolveArray($container, $call["arguments"] ?? [])
                    );
                }
                return $service;
            };
        }

        foreach ($compiledConfig->getTags() as $tag => $services) {
            $container[Token::TAG_CHAR . $tag] = function () use ($container, $services) {
                // Although I've never come across anything with the latter...
                // tags are either defined as ' - "#tagName" ' or ' "#tagName": "tagKey" ', so
                // we have to detect the type of $tag and change the variables around if required
                $tagCollection = new TagCollection();
                foreach ($services as $serviceName) {
                    $tagCollection->addService($serviceName);
                }
                return $tagCollection;
            };
        }

        foreach ($compiledConfig->getAliases() as $alias => $service) {
            $container[$alias] = function () use ($container, $service) {
                return $this->referenceResolver->resolve($container, $service);
            };
        }

        return $container;
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
}
