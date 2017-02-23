<?php

namespace Silktide\Syringe\Tests\Service;

class CollectionService
{

    public $services;

    public function __construct(array $services)
    {
        $this->services = $services;
    }

}