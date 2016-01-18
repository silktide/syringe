<?php

namespace Silktide\Syringe;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silktide\Syringe\ContainerBuilder;

class SyringeServiceProvider implements ServiceProviderInterface
{
    protected $containerBuilder;

    public function __construct(ContainerBuilder $containerBuilder)
    {
        $this->containerBuilder = $containerBuilder;
    }

    public function register(Container $container)
    {
        $this->containerBuilder->populateContainer($container);
    }
}
