<?php

include_once(__DIR__ . "/../vendor/autoload.php");

$resolver = new \Silktide\Syringe\ReferenceResolver();
$builder = new \Silktide\Syringe\ContainerBuilder($resolver, [__DIR__]);

$builder->addLoader(new \Silktide\Syringe\Loader\JsonLoader());
$builder->addConfigFile("service.json");

$container = $builder->createContainer();

$collection = $container["tagCollection"];

if (empty($collection->services)) {
    throw new \Exception("No services were injected using the tag #duds");
}
if (count($collection->services) != 1 || !$collection->services[0] instanceof \Silktide\Syringe\Test\Service\DudService) {
    throw new \Exception("An incorrect service was injected: " . print_r($collection->services, true));
}

$duds = $container["duds"];
if ($duds !== $collection) {
    throw new \Exception("Aliased service did not return the same object as the original service");
}

echo "\nAll tests passed\n";
