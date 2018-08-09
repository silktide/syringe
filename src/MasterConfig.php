<?php


namespace Silktide\Syringe;


use Silktide\Syringe\Exception\ConfigException;

class MasterConfig
{
    protected $services = [];
    protected $parameters = [];
    protected $extensions = [];

    public function addFileConfig(FileConfig $fileConfig)
    {
        $this->services = array_merge($this->services, $fileConfig->getNamespacedServices());
        $this->parameters = array_merge($this->parameters, $fileConfig->getNamespacedParameters());
        $this->extensions = array_merge($this->extensions, $fileConfig->getNamespacedExtensions());
    }

    public function getServices()
    {
        $this->stableWeightSort($this->services);

        $services = [];
        foreach ($this->services as $array) {
            $serviceName = $array["name"];
            $definition = $array["value"];

            if (isset($this->services[$serviceName]) && !isset($definition["aliasOf"]) && ($definition["override"] ?? false)) {
                throw new ConfigException("Overwriting existing service '{$serviceName}'. Services can only be overwritten using either aliasOf or override");
            }
            $services[$serviceName] = $definition;
        }

        return $services;
    }

    public function getParameters()
    {
        $this->stableWeightSort($this->parameters);

        $parameters = [];
        foreach ($this->parameters as $array) {
            $parameters[$array["name"]] = $array["value"];
        }
        return $parameters;
    }

    public function getExtensions()
    {
        $this->stableWeightSort($this->extensions);

        $extensions = [];
        foreach ($this->extensions as $array) {
            $extensions[$array["name"]] = array_merge($extensions[$array["name"]] ?? [], $array["value"]);
        }
        return $extensions;
    }

    /**
     * All of these should be ordered by weight, but keep their order if they were equal, aka, a stable sort
     * Unfortunately, all of PHPs underlying sorting is done by QuickSort, so we have to do the sorting ourselves
     *
     * @param array $array
     * @return array
     */
    protected function stableWeightSort(array &$array)
    {
        foreach ($array as $i => &$value) {
            $value["key"] = $i;
        }
        unset($value);

        usort($array, function(array $v1, array $v2) {
            $byWeight = $v1["weight"] - $v2["weight"];
            if ($byWeight === 0) {
                return $v1["key"] - $v2["key"];
            }
            return $byWeight;
        });
    }
}