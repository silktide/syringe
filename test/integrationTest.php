<?php

include_once(__DIR__ . "/../vendor/autoload.php");

use Silktide\Syringe\Syringe;

$configFiles = [
    "service.json",
    "private_test" => "aliased.json"
];
$builder->addConfigFile("aliased.json", "private_test");

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

if ($container->offsetExists("private_test.privateService")) {
    throw new \Exception("Services marked as private are accessible from outside of their alias");
}

try {
    $service = $container["private_test.usesPrivateService"];
} catch (\Silktide\Syringe\Exception\ReferenceException $e) {
    throw new \Exception("An unexpected ReferenceException was thrown when trying to access a service that uses a private service:\n" . $e->getMessage());
}

echo "\nAll tests passed\n";
