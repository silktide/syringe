<?php

namespace Silktide\Syringe\Test\Service;

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