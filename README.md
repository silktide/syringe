Syringe allows a [Pimple](https://github.com/silexphp/pimple) DI container to be created and populated with services defined in configuration files, in the same fashion as Symfony's [DI module](https://github.com/symfony/dependency-injection).

# Installation

``composer require silktide/syringe``

# Changes with Version 2.0
 
## BC's

Syringe 2.0 is pretty much an 100% rewrite, the functionality should remain more or less the same but the code behind it is vastly vastly different.
As such, there are quite a few BC's as I feel it's better to BC once and hard rather than repeatedly

1. Aliases are now denoted through `::` rather than `.`. This makes verifying whether something is aliased so much more clean
2. TagCollection has been reworked to implement an iterator. This means that when we inject a tag in like so: '#collection', it will now return an iterable object instead of an array. This means that we will only build services if and when they are needed. We can still get information about the serviceNames on a TagCollection using `->getServiceNames`
3. The container is now originally updated using `Syringe::build([])`. Instead of chaining several slightly non-intuitive internal classes as the end user, we now provide a static
method that takes an array of configuration options
4. Containers are now always generated as part of Syringe rather than exposing populating an existing container.
5. Files that inherit from each other will now throw exceptions if they overwrite each others services. A new parameter `override` has been added to services. If you are adding a service into the container and are well aware of the fact that it will overwrite an existing service you can set the `override` flag and it will not throw an error.
6. Private services have been removed, in practice they added nothing useful but complicated affairs. 
7. Environment variables are no longer injected through prefixing parameters with `SYRINGE__FOO` as this was a bit clunky and the wrong way around to do it. A new token of `&` means we can inject environment variables as parameters like so `&foo&`
8. IniLoader has been removed, the format doesn't suit DI particularly nicely.
9. Now requires PHP 7.1
10. LoaderInterface updated, now requires typehints 
11. We now support escaping of special tokens (environment, parameter, constant) by character repeating. e.g. a parameter value of 50% would be written as '50%%') 

## Todos:

- implement escaping of special chars, long standing need
- write unit tests
- validate on fileconfig isn't being called (it is now, verify that it's legit)
- validation doesn't cover methods not existing on factoryMethods

# Getting Started

The simplest method to create and set up a new Container is to use the `Silktide\Syringe\Syringe` class. It requires the path to the application directory and a list of filepaths that are relative to that directory

```php
use Silktide\Syringe\Syringe;

$container = \Silktide\Syringe\Syringe::build([
	"files" => ["config/syringe.yml"]
]);
```

# Key Production Configuration

When used in production, you should pass a PSR-16 cache interface (preferably as a cache parameter), like so:

```php
use Silktide\Syringe\Syringe;

$container = \Silktide\Syringe\Syringe::build([
	"cache" => new FileCache(sys_get_temp_dir())
]);
```

The most computationally expensive part of Syringe (certainly when using many syringe based libraries) is:
	1. the aliasing of the different parameters and
	2. the validating of the classes/methods inside the configuration files

By passing in the `cache` parameter we cache the generated CompiledConfig and use that instead. This leads to much, much faster code (takes about 7% of the time)


# Configuration Files

By default, Syringe allows config files to be in JSON, YAML or PHP format. Each file can define parameters, services and tags to inject into the container, and these entities can be referenced in other areas of configuration.

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

When the tag is resolved, the collection is passed through as TagCollection, which can be used as an iterator. This should be typehinted against iterator, *not* TagCollection unless you're certain you know what you're doing.

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
They can be injected similar to parameters using the token of `&` like `&myvar&` so

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
* `&` - Environment Variables

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

### Base paths for config files

In order to use configuration in a particular file, its filepath must be passed to the `ContainerBuilder`, which will use the loading system to convert a file into a PHP array. Syringe uses absolute paths when loading files, but this is obviously not ideal when you're passing config filepaths.

In order to get around this, you can add additional paths to the configuration array. For example, for a config file with absolute path of `/var/www/app/config/syringe.yml`, you could set a base path of `/var/www/app` and use `config/syringe.yml` as the relative filepath.

```php
$container = \Silktide\Syringe\Syringe::build([
   "paths" => ["/var/www/app"]
	"files" => ["config/syringe.yml"]
]);
```

If you use several base paths, Syringe will look for a config file in each base path in turn, so the order is important.

### Application root directory

If you have services that deal with files, it can be very useful to have the base directory of the application as a parameter in DI config, so you can be sure any relative paths you use are correct.

```php
$container = \Silktide\Syringe\Syringe::build([
   "appDir" => "my/application/directory", # Application Directory
   "appDirKey" => "myAppParameterKey"
]);
```
If no app directory key is passed, the default parameter name is `app.dir`.

### Container class

Some projects that use Pimple, such a [Silex](http://silex.sensiolabs.org/), extend the `Container` class to add functionality to their API. Syringe can create custom containers in this way by allowing you to set the container class it instantiates:

```php
$container = \Silktide\Syringe\Syringe::build([
   "containerClass" => "Silex\Application::class"
]);
```

### Loaders

Syringe can support any data format that can be translated into a nested PHP array. Each config file is processed by the loader system, which is comprised of a series of `Loader` objects, each handling a single data format, that take a file's contents and decode it into an array of configuration.

By default the `ContainerBuilder` loads the PHPLoader, the YamlLoader and the JsonLoader

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

Once created, such a loader can be used by overwriting `$config["loaders"] = [new XmlLoader()]`

# Credits

Written by:

 - Doug Nelson (dougnelson@silktide.com)
 - Danny Smart (dannysmart@silktide.com).
