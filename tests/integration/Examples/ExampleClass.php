<?php


namespace Silktide\Syringe\IntegrationTests\Examples;


class ExampleClass
{
    protected $arguments;

    public function __construct($argument)
    {
        $this->arguments[] = $argument;
    }

    public function getFirstArgument()
    {
        return $this->arguments[0];
    }
}