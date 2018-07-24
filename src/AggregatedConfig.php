<?php


namespace Silktide\Syringe;


use Silktide\Syringe\Exception\ConfigException;

class AggregatedConfig
{
    protected $services = [];
    protected $parameters = [];
    protected $extensions = [];

    public function addFileConfig(FileConfig $fileConfig)
    {
        $this->services = array_merge($this->services, $fileConfig->getAliasedServices());
        $this->parameters = array_merge($this->parameters, $fileConfig->getAliasedParameters());
        $this->extensions = array_merge($this->extensions, $fileConfig->getAliasedExtensions());
    }

    public function getServices()
    {
        usort($this->services, function(array $v1, array $v2) {
            return $v1["weight"] - $v2["weight"];
        });

        $services = [];
        foreach ($this->services as $array) {
            $serviceName = $array["name"];
            $definition = $array["value"];
            if (isset($this->services[$serviceName]) && !isset($definition["aliasOf"])) {
                throw new ConfigException("Overwriting existing service '{$serviceName}'. Services can only be overwritten using aliasOf");
            }
            $services[$serviceName] = $definition;
        }

        return $services;
    }

    public function getParameters()
    {
        usort($this->parameters, function(array $v1, array $v2) {
            return $v1["weight"] - $v2["weight"];
        });

        $parameters = [];
        foreach ($this->parameters as $array) {
            $parameters[$array["name"]] = $array["value"];
        }
        return $parameters;
    }

    public function getExtensions()
    {
        usort($this->extensions, function(array $v1, array $v2) {
            return $v1["weight"] - $v2["weight"];
        });

        $extensions = [];
        foreach ($this->extensions as $array) {
            $extensions[$array["name"]] = $array["value"];
        }
        return $extensions;
    }
}