<?php

include_once(__DIR__ . "/../vendor/autoload.php");

use Silktide\Syringe\Syringe;

$configFiles = [
    "service.json",
    "private_test" => "aliased.json"
];

Syringe::init(__DIR__, $configFiles);

$container = Syringe::createContainer();

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
