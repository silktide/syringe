Syringe allows a [Pimple](https://github.com/silexphp/pimple) DI container to be created and populated with services defined in configuration files, in the same fashion as Symfony's [DI module](https://github.com/symfony/dependency-injection).

# Installation

``composer require silktide/syringe``

# Getting Started

The simplest method to create and set up a new Container is to use the `Silktide\Syringe\Syringe` class. It requires the path to the application directory and a list of filepaths that are relative to that directory

```php
use Silktide\Syringe\Syringe;

$appDir = __DIR__;
$configFiles = [
    "config/syringe.yml" // add paths to your configuration files here
];

Syringe::init($appDir, $configFiles);
$container = Syringe::createContainer();
```

# Configuration Files

By default, Syringe allows config files to be in JSON or YAML format. Each file can define parameters, services and tags to inject into the container, and these entities can be referenced in other areas of configuration.

## Parameters

A Parameter is a named, static value, that can be accessed directly from the Container, or injected into other parameters or services.
For a config file to define a parameter, it uses the `parameters` key and then states the parameters name and value.

```yml
parameters:
    myParam: "value"
```

Once defined, a parameter can be referenced inside a string value by surrounding its name with the `%` symbol and the parameters value will the be inserted when the the string value is resolved. This can be done in service arguments or in other parameters, like so:

```yml
parameters:
    firstName: "Joe"
    lastName: "Bloggs"
    fullName: "%firstName% %lastName%"
```

Parameters can have any scalar or array value.

## Constants

Quite often, a value set in a PHP constant is required to be injected. Hard coding these value directly into DI config is brittle and requires maintenance to keep in sync, which should be avoided where possible. 
Syringe solves this problem by allowing PHP constants to be referenced directly in config, by surrounding the constant name with `^` characters:

```yml
parameters:
    maxIntValue: "^PHP_INT_MAX^"
    custom: "^MY_CUSTOM_CONSTANT^"
    classConstant: "^MyModule\\MyService::CLASS_CONSTANT^"
```

Where class constants are used, you are required to provide the fully qualified class name. As this has to be enclosed inside a string, all forward slashes must be escaped, as in the example.

## Services

Services are instances of a class that can have other services, parameters or values injected into them. A config file defines services inside the `services` key and gives each entry a `class` key, containing the fully qualified class name to instantiate. 
For classes which have constructor arguments, these can be specified by setting the `arguments` key to a list of values, parameters or other services, as required by the constructor

```yml
services:
    myService:
        class: MyModule\MyService
        arguments:
            - "first constructor argument"
            - 12345
            - false
```

### Service injection

Services can have parameters or other services injected into them as method arguments, by referencing a service name prefixed with the `@` character. This is done in one of two ways:

#### Constructor injection

Injection can be done when a service is instantiated, by setting references in `arguments` key of a service definition. This is typically done for dependencies which are required.

```yml
services:
    injectable:
        class: MyModule\MyDependency

    myService:
        class: MyModule\MyService
        arguments:
            - "@injectable"
            - "%myParam%"
```

#### Setter injection

Services can also be injected by calling a method after the service has been instantiated, passing the dependant service in as an argument. This form is useful for optional dependencies.

```yml
services:
    injectable:
        class: MyModule\MyDependency

    myService:
        class: MyModule\MyService
        calls:
            -
                method: "setInjectable"
                arguments:
                    - "@injectable"
```

The `calls` key can be used to run any method on a service, not necessarily one to inject a dependency. They are executed in the order they are defined.

```yml
services:
    myService:
        class: MyModule\MyService
        calls:
            - method: "warmCache"
            - method: "setTimeout"
              arguments: ["%myTimeout%"]
            - method: "setLogger"
              arguments: ["@myLogger"]
```

### Tags

In some cases, you may want to inject all the services of a given type as a method argument. This can be done manually, by building a list of service references in config, but maintaining such a list is cumbersome and time consuming.

The solution is tags; allowing you to tag a service as being part of a collection and then to inject the whole collection of services in one reference.

A tag is referenced by prefixing its name with the `#` character.

```yml
services:
    logHandler1:
        ...
        tags:
            - "logHandlers"
            
    logHandler2:
        ...
        tags:
            - "logHandlers"
            
    loggerService:
        ...
        arguments:
            - "#logHandlers"
```

When the tag is resolved, the collection is passed through as a simple numeric array. The parent service will have no knowledge that a tag was used to generate this list.

### Factories

If you have a number of services to be available that use the same class or interface, it can be advantageous to abstract the creation of these services into a factory class, to aid maintenance and reusability.
Syringe provides two methods of using factories in this way; via a call to a static method on the factory class, or by calling a method on a separate factory service.

```yml
services:
    newService1:
        class: MyModule\MyService
        factoryClass: MyModule\MyServiceFactory
        factoryMethod: "createdWithStatic"
        
    newService2:
        class: MyModule\MyService
        factoryService: "@myServiceFactory"
        factoryMethod: "createdWithService"
        
    myServiceFactory:
        class: MyModule\MyServiceFactory
```

If the factory methods require arguments, you can pass them through using the `arguments` key, in the same way you would for a normal service or a method call.

### Service Aliases

Syringe allows you to alias a service name to point to another definition, using the `aliasOf` key. 
This is useful if you deal with other modules and need to use your own version of a service instead of the module's default one.

```yml
# [foo.yml]
services:
    default:
        class: MyModule\DefaultService
        ...

# [bar.yml]
services:
    default:
        aliasOf: "@custom"
        
    custom:
        class: MyModule\MyService
        ...
```

### Abstract Services

Services can often have definitions that are very similar or contain portions that will always be the same. 
As a method to reduce duplicated config, a service's definition can "extend" a base definition. This has the effect of merging the two definitions together. Any key conflicts take the service's value rather than the one from the base, however the list of calls is merged rather than overwritten. There is no restriction on what keys you can define in the base definition.
Base definitions have to be marked as `abstract` and cannot be used directly as a service. These abstract definitions can extend other definitions in the same way, similar to how inheritence works in OOP.

```yml
services:
    loggable:
        abstract: true
        calls:
            - method: "setLogger"
            - arguments: "@logger"

    myService:
        class: MyModule\MyService
        extends: "@loggable"            # this will import the "setLogger" call into this service definition
        
    factoriedService:
        abstract: true
        extends: "@loggable"
        factoryClass: MyModule\MyServiceFactory
        factoryMethod: "create"

    myFactoriedService:
        class: MyModule\MyService
        extends: "@factoriedService"    # imports both the factory config and the "setLogger" call
```

### Private Services

For the vast majority of cases, there is no issue with services being accessed from outside of the current module. In fact this is advantageous as it promotes modular design, reuse of services and code discovery. However, there can be times when data security requires that a service be locked down and to not be available to anything outside of the control of the current module.
Services can be marked as private by adding the `private` key to their definition:

```yml
services:
    myService:
        ...
        private: true
```

Private services will only be available to other services that are defined with the same config alias, usually within the same module.

## Imports

When your object graph becomes large enough, it is often useful to split your configuration into separate files; keeping related parameters and services together. This can be done by using the `imports` key:

```yml
imports:
    - "loggers.yml"
    - "users.yml"
    - "report/orders.yml"
    - "report/products.yml"
    
services:
    ...
```

If any imported files contain duplicated keys, the file that is further down the list wins. As the parent file is always processed last, its services and parameters always take precedence over the imported config.

```yml
# [foo.yml]
parameters:
    baz: "from foo"

# [bar.yml]
imports: 
    - "foo.yml"
    
parameters:
    baz: "from bar"
    
# when bar.yml is loaded into Syringe, the "baz" parameter will have a value of "from bar"
```

## Environment Variables

If required, Syringe allows you to set environment variables on the server that will be imported at runtime. This can be used to set different parameter values for local development machines and production servers, for example.
Any environment variable prefixed with `SYRINGE__` will be imported as a parameter:

## Config Aliases and Namespacing

When dealing with a large object graph, conflicting service names can become an issue. To avoid this, Syringe allows you to set an "alias" or namespace for a config file. Within the file, services can be referenced as normal, but files which use different aliases or no alias need to prefix the service name with the alias.
This allows you to compartmentalise your DI config for better organisation and to promote modular coding.

For example, the two config files, `foo.yml` and `bar.yml` can be given aliases when setting up the config files to create a Container from:

```php
$configFiles = [
  "foo_alias" => "foo.yml",
  "bar_alias" => "bar.yml"
];
```

`foo.yml` could defined a service, `fooOne`, which injected another service in the same file, `fooTwo`, as normal.
However, if a service in `bar.yml` wanted to inject `fooTwo`, it would have to use its full service reference `@foo_alias.fooTwo`. Likewise if `fooOne` wanted to inject `barOne` from `bar.yml` it would have to use `@bar_alias.barOne` as the service reference.

## Extensions

There can be times where you need to call setters on a dependent module's services, in order to inject your own dependent service as a replacement for the module's default one.
In order to do this, you need to use the `extensions` key. This allows you to specify the service and provide a list of calls to make on it, essentially appending them to the service's own `calls` key

```yml
# [foo.yml, aliased with "foo_alias"]
services:
    myService:
        class: MyModule\MyService
        ...

# [bar.yml]
services:
    myCustomLogger:
        ...
        
extensions:
    foo_alias.myService:
        - method: "addLogger"
          arguments: "@myCustomLogger"
```

## Reference characters

In order to identify references, the following characters are used:

* `@` - Services
* `%` - Parameters
* `#` - Tags
* `^` - Constants

## Conventions

Syringe does not enforce naming or style conventions, with one exception. A service's name can be any you like, as long as it does not start with one of the reference characters, but a config alias is always seperated from a service name with a `.`, e.g. `myAlias.serviceName`. For this reason it can be useful to use `.` as a separator in your own service names, to "namespace" related services and parameters:

```yml
parameters:
    database.host: "..."
    database.username: "..."
    database.password: "..."
    
services:
    database.client:
        ...
```

# Advanced Usage

## The ContainerBuilder

The `ContainerBuilder` class is the main component of Syringe. It has several configuration options that allow you to customise the containers it builds.

### Base paths for config files

In order to use configuration in a particular file, its filepath must be passed to the `ContainerBuilder`, which will use the loading system to convert a file into a PHP array. Syringe uses absolute paths when loading files, but this is obviously not ideal when you're passing config filepaths to the `ContainerBuilder`. 

In order to get around this, the `ContainerBuilder` allows you to set a path or collection of paths to use as a base, so you can use relative filepaths when setting it up. For example, for a config file with absolute path of `/var/www/app/config/syringe.yml`, you could set a base path of `/var/www/app` and use `config/syringe.yml` as the relative filepath.

```php
$basePath = "/var/www/app";
$resolver = new Silktide\Syringe\ReferenceResolver();

$builder = new Silktide\Syringe\ContainerBuilder($resolver, [$basePath]);
$builder->addConfigfile("config/syringe.yml");
...
```

If you use several base paths, Syringe will look for a config file in each base path in turn, so the order is important.

```php
$basePaths = [
    "my-dir/config",    // both these paths contain a file called "foo.yml"
    "my-dir/app"
];
$resolver = new Silktide\Syringe\ReferenceResolver();

$builder = new Silktide\Syringe\ContainerBuilder($resolver, $basePaths);
$builder->addConfigfile("foo.yml");     // will load my-dir/config/foo.yml, as that is the first base path in the list
```

### Application root directory

If you have services that deal with files, it can be very useful to have the base directory of the application as a parameter in DI config, so you can be sure any relative paths you use are correct.
The `ContainerBuilder` allows you to set the base directory and the parameter name at runtime:

```php
$builder->setApplicationRootDirectory("my/application/directory", "myParameterName");
```

If no key is passed, the default parameter name is `app.dir`.

### Container class

Some projects that use Pimple, such a [Silex](http://silex.sensiolabs.org/), extend the `Container` class to add functionality to their API. Syringe can create custom containers in this way by allowing you to set the container class it instantiates:

```php
$builder->setContainerClass(Silex\Application::class);
$app = $builder->createContainer(); // returns a new Silex Application
```

### Loaders

Syringe can support any data format that can be translated into a nested PHP array. Each config file is processed by the loader system, which is comprised of a series of `Loader` objects, each handling a single data format, that take a file's contents and decode it into an array of configuration.

By default the `ContainerBuilder` has no loaders, so you need to add at least one before a container can be built:

```php
$builder->addLoader(new Silktide\Syringe\Loader\YamlLoader());
```

#### Custom loaders

By default Syringe supports YAML and JSON data formats for the configurations files, but it is possible to use any format that can be translated into a nested PHP array.
The translation is done by a `Loader`; a class which takes a filepath, reads the file and decodes the data. 

To create a `Loader` for your chosen data format, the class needs to implement the `LoaderInterface` and state what its name is and what file extensions it supports. For example, a hypothetical XML `Loader` would look something like this:

```php
use Silktide\Syringe\Loader\LoaderInterface;

class XmlLoader implements LoaderInterface
{
    public function getName()
    {
        return "XML Loader";
    }
    
    public function supports($file)
    {
        return pathinfo($file, PATHINFO_EXTENSION) == "xml";
    }
    
    public function loadFile($file)
    {
        // load and decode the file, returning the configuration array
    }
}
```

Once created, such a loader can be used by adding it to the `ContainerBuilder` in the normal way.

### Populating a Container

In addition to creating a new container, the `ContainerBuilder` can also populate an existing container that has been created elsewhere, with the `populateContainer` method:

```php
$container = new Pimple\Container();
$builder->populateContainer($contianer);
```

### Method reference

The `ContainerBuilder` class has the following methods available:

#### Constructor

* `__construct(Silktide\Syringe\ReferenceResolver $resolver, array $configPaths = [])`
  
  Constructs a new `ContainerBuilder` instance, with each $configPath set using the `addConfigPath` method

#### Container

* `createContainer()`

  Create a brand new container populated with all services defined in the configuration files that have been loaded into the `ContainerBuilder`
* `populateContainer(Pimple\Container $container)`

  Populate an existing container with services as per `createContainer`
* `setContainerClass($className)`

  Sets the class which will be instantiated when using `createContainer`

#### Config Files

* `addConfigFile($file, $alias = "")`

  Adds a new file path to load configuration from, optionally with an alias to prefix its keys with
* `addConfigFiles(array $files)`

  Adds several config files in one go. Elements with numeric keys are added without an alias, otherwise the key is used as the alias for that file:
```php
  $files = [
      "file1.yml",
      "alias_two" => "file2.yml",
      "file3.yml",
      "alias_four" => "file4.yml"
  ]
```
* `addConfigPath($path)`

  Register a path to use as a base for relative config filepaths
  
#### Loaders

* `addLoader(Silktide\Syringe\Loader\LoaderInterface $loader)`

  Registers a loader to add support for a specific data format
* `removeLoader($name)`

  Remove a loader based on its name
* `removeLoaderByFile($file)`

  Remove any loader that supports this file
  
#### Misc

* `setApplicationRootDirectory($path, $key = "")`

  Sets the directory to use as the root for this application, useful when processing relative file paths. The parameter name will be the $key, or `app.dir` if $key is empty

# Credits

Written by Danny Smart (dannysmart@silktide.com).
