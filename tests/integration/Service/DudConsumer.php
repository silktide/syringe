<?php

namespace Silktide\Syringe\IntegrationTests\Service;

/**
 * DudConsumer
 */
class DudConsumer
{

    protected $dud;

    public function __construct(DudService $dud)
    {
        $this->dud = $dud;
    }

}