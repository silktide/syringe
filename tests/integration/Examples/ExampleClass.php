<?php


namespace Silktide\Syringe\IntegrationTests\Examples;


class ExampleClass
{
    protected $arguments;

    public function __construct(...$arguments)
    {
        $this->arguments = $arguments;
    }

    public function getFirstArgument()
    {
        return $this->arguments[0];
    }

    public function getArguments()
    {
        return $this->arguments;
    }
}