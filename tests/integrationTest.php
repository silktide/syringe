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
if (count($collection->services) != 1 || !$collection->services[0] instanceof \Silktide\Syringe\Tests\Service\DudService) {
    throw new \Exception("An incorrect service was injected: " . print_r($collection->services, true));
}

$duds = $container["duds"];
if ($duds !== $collection) {
    throw new \Exception("Aliased service did not return the same object as the original service");
}



// check aliases are namespaced
if ($container->offsetExists("publicAlias")) {
    throw new \Exception("Aliases should be namespaced where appropriate.");
}
if (!$container["private_test.publicAlias"] instanceof \Silktide\Syringe\Tests\Service\DudConsumer) {
    throw new \Exception("Namespaced Alias was not accessible");
}

// check private services are hidden
if ($container->offsetExists("private_test.privateService")) {
    throw new \Exception("Services marked as private should not be accessible from the container directly");
}
try {
    $service = $container["privacyIgnorer"];
    throw new \Exception("Services marked as private should not be accessible from outside of their alias");
} catch (\Silktide\Syringe\Exception\ReferenceException $e) {
    // expected behaviour
}

// check private services can be used within the same namespace
try {
    $service = $container["private_test.usesPrivateService"];
} catch (\Silktide\Syringe\Exception\ReferenceException $e) {
    throw new \Exception("An unexpected ReferenceException was thrown when trying to access a service that uses a private service:\n" . $e->getMessage());
}

// This is a bug. Need to work out how to make aliases respect privacy
//if ($container->offsetExists("private_test.privateAlias")) {
//    throw new \Exception("Aliases do not respect privacy");
//}

echo "\nAll tests passed\n";
