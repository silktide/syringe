<?php


namespace Silktide\Syringe\IntegrationTests\Examples;


class ExampleClass
{
    protected $arguments;
    protected $values = [];

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

    public function setValue(string $key, $value)
    {
        $this->values[$key] = $value;
    }

    public function getValue(string $key)
    {
        return $this->values[$key];
    }

    public static function create(...$arguments)
    {
        return new self(...$arguments);
    }
}
