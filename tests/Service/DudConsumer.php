<?php

namespace Silktide\Syringe\Tests\Service;

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