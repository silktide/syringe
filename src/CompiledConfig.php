<?php


namespace Silktide\Syringe;


class CompiledConfig
{
    protected $services;
    protected $aliases;
    protected $parameters;
    protected $tags;

    protected $fileState;
    protected $envState;
    protected $constState;

    public function __construct(array $config)
    {
        $this->services = $config["services"] ?? [];
        $this->aliases = $config["aliases"] ?? [];
        $this->parameters = $config["parameters"] ?? [];
        $this->tags = $config["tags"] ?? [];
        $this->fileState = $config["state"]["files"] ?? FileStateCollection::build([]);
        $this->envState = $config["state"]["envVars"] ?? [];
        $this->constState = $config["state"]["constants"] ?? [];
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

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        // Verify that the environment variables haven't changed
        foreach ($this->envState as $key => $originalValue) {
            if (getenv($key) !== $originalValue) {
                return false;
            }
        }

        // Verify that the constant variables haven't changed
        foreach ($this->constState as $key => $originalValue) {
            if (!defined($key) || constant($key) !== $originalValue) {
                return false;
            }
        }

        return $this->fileState->isValid();
    }
}