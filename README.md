# Syringe

![](https://img.shields.io/badge/owner-danny smart-brightgreen.svg)

Syringe allows a [Pimple](https://github.com/silexphp/pimple) DI container to be created and populated with services defined in configuration files, in the same fashion as Symfony's [DI module](https://github.com/symfony/dependency-injection).

# Installation

``composer require silktide/syringe``

# Basic Usage

The simplest method to create and set up a new Container is to use the `Silktide\Syringe\Syringe` class. It requires the path to the application directory and a list of filepaths that are relative to that directory

```php
use Silktide\Syringe\Syringe;

$appDir = __DIR__;
$configFiles = [
    "config/syringe.yml"
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
    
services:
    userService:
        class: MyModule\UserService
        arguments:
            - "%fullName%" # inserts "Joe Bloggs" as the first constructor argument of the UserService
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
            - "%myParam%"
            - "@anotherService"
```

Services are referenced by prefixing their name with the `@` symbol

# Credits

Written by Danny Smart 
