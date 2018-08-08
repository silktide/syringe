<?php


namespace Silktide\Syringe;


class CompiledConfig
{
    protected $services;
    protected $aliases;
    protected $parameters;
    protected $tags;

    public function __construct(array $services, array $aliases, array $parameters, array $tags)
    {
        $this->services = $services;
        $this->aliases = $aliases;
        $this->parameters = $parameters;
        $this->tags = $tags;
    }

    /**
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}