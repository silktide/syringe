<?php

namespace Silktide\Syringe\Test\Service;

class CollectionService
{

    public $services;

    public function __construct(array $services)
    {
        $this->services = $services;
    }

}