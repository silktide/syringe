<?php

use Silktide\Syringe\Syringe;

class PrivacyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Pimple\Container
     */
    protected $container;

    public function setUp()
    {
        $configFiles = [
            "service.json",
            "private_test" => "aliased.json"
        ];

        Syringe::init(__DIR__, $configFiles);
        $this->container = Syringe::createContainer();
    }

    public function testPrivacy()
    {

        $collection = $this->container["tagCollection"];

        if (empty($collection->services)) {
            throw new \Exception("No services were injected using the tag #duds");
        }
        if (count($collection->services) != 1 || !$collection->services[0] instanceof \Silktide\Syringe\IntegrationTests\Service\DudService) {
            throw new \Exception("An incorrect service was injected: " . print_r($collection->services, true));
        }

        $duds = $this->container["duds"];
        if ($duds !== $collection) {
            throw new \Exception("Aliased service did not return the same object as the original service");
        }

        // check aliases are namespaced
        if ($this->container->offsetExists("publicAlias")) {
            throw new \Exception("Aliases should be namespaced where appropriate.");
        }
        if (!$this->container["private_test.publicAlias"] instanceof \Silktide\Syringe\IntegrationTests\Service\DudConsumer) {
            throw new \Exception("Namespaced Alias was not accessible");
        }

        // check private services are hidden
        if ($this->container->offsetExists("private_test.privateService")) {
            throw new \Exception("Services marked as private should not be accessible from the container directly");
        }
        try {
            $service = $this->container["privacyIgnorer"];
            throw new \Exception("Services marked as private should not be accessible from outside of their alias");
        } catch (\Silktide\Syringe\Exception\ReferenceException $e) {
            // expected behaviour
        }

        // check private services can be used within the same namespace
        try {
            $service = $this->container["private_test.usesPrivateService"];
        } catch (\Silktide\Syringe\Exception\ReferenceException $e) {
            throw new \Exception("An unexpected ReferenceException was thrown when trying to access a service that uses a private service:\n" . $e->getMessage());
        }

        // This is a bug. Need to work out how to make aliases respect privacy
        //if ($container->offsetExists("private_test.privateAlias")) {
        //    throw new \Exception("Aliases do not respect privacy");
        //}
    }
}