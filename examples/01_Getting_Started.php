<?php

use Silktide\Syringe\ReferenceResolver;
use Silktide\Syringe\ContainerBuilder;
use Silktide\Syringe\Loader\JsonLoader;
use Silktide\Syringe\Loader\YamlLoader;

if (!file_exists(__DIR__."/../vendor")) {
    echo "You need to have the dependencies installed to run the examples";
    exit(1);
}

include(__DIR__."/../vendor/autoload.php");
include(__DIR__."/01_Getting_Started.inc.php");

$resolver = new ReferenceResolver();

// Config paths are just the directories where we should look for the config files that we've added to the directory
$configPaths = [
    __DIR__
];

$builder = new ContainerBuilder($resolver, $configPaths);

// Loaders register the file extensions they can deal with and are responsible for reading the file and returning an
// array in the syringe format
$loaders = [
    new JsonLoader(),
    new YamlLoader()
];

foreach ($loaders as $loader) {
    $builder->addLoader($loader);
}

$builder->setApplicationRootDirectory(__DIR__);
$builder->addConfigFile("01.yml");

// You've successfully created a Pimple container!
$container = $builder->createContainer();

// Typically at this point, this kind of use of Pimple will be then abstracted to either the Symfony Console command or
// to Silex to continue the work, but as this is a barebones example, we'll just get the class out of the configuration
// to show the basic service chaining working


/**
 * @var \MyMainClass $mainClass
 */
$mainClass = $container->offsetGet("my.mainclass");

$mainClass->doSomething();