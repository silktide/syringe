<?php

namespace Silktide\Syringe\IntegrationTests\Lazy;

class LazyDestructorClass
{
    public static $loaded = false;

    public function __construct()
    {
        self::$loaded = true;
    }

    public function getTrue() : bool
    {
        return true;
    }

    public function __destruct()
    {

    }
}