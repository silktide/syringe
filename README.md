# Syringe

![](https://img.shields.io/badge/owner-danny smart-brightgreen.svg)

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

Once defined, a parameter can be referenced inside a string value by surrounding it's name with the `%` symbol and the parameters value will the be inserted when the the string value is resolved. This can be done in service arguments or in other parameters, like so:

```yml
parameters:
    firstName: "Joe"
    lastName: "Bloggs"
    fullName: "%firstName% %lastName%"
```

Parameters can have any scalar or array value

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
## Constants

Quite often, a value set in a PHP constant is required to be injected into a services. Hard coding these value directly into DI config is brittle and requires maintenance to keep in sync, which should be avoided where possible. 
Syringe solves this problem by allowing PHP constants to be referenced directly in config, by surrounding the constant name with `^` characters:

```yml
services:
    myService:
        class: MyModule\MyService
        arguments:
            - "^PHP_INT_MAX^"
            - "^MY_CUSTOM_CONSTANT^"
            - "^MyModule\\MyService::CLASS_CONSTANT^"
```

Where class constants are used, you are required to provide the fully qualified class name. As this has to be enclosed inside a string, all forward slashes must be escaped, as in the example.

## Tags

In some cases, you may want to inject all the services of a given type as a method argument. This can be done manually, by building a list of service references in config, but maintaining such a list is cumbersome and tiem consuming.
The solution is tags; allowing you to tag a service as being part of a collection and then to inject the whole collection of services in one reference.
A tag is referenced by prefixing it's name with the `#` character.

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

## Factories

## Service Aliases

## Abstract Services

## Imports

## Environment Variables

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
However, if a service in `bar.yml` wanted to inject `fooTwo`, it would have to use it's full service reference `@foo_alias.fooTwo`. Likewise if `fooOne` wanted to inject `barOne` from `bar.yml` it would have to use `@bar_alias.barOne` as the service reference.

## Private Services

For the vast majority of cases, there is no issue with services being accessed from outside of the current module. In fact this is advantageous as it promotes modular design, reuse of services and code discovery. However, there can be times when data security requires that a service be locked down and to not be available to anything outside of the control of the current module.
Services can be marked as private by adding the `private` key to their definition:

```yml
services:
    myService:
        ...
        private: true
```

Private services will only be available to other services that are defined with the same config alias, usually within the same module.

## Extensions

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


# Credits

Written by Danny Smart 
