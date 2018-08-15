<?php

namespace Silktide\Syringe\Loader;

use Silktide\Syringe\Exception\LoaderException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class YamlLoader implements LoaderInterface
{
    protected $hasSymfony;
    protected $hasExtension;
    protected $forceSymfony;

    public function __construct(bool $useSymfony = false)
    {
        $this->hasSymfony = class_exists('Symfony\Component\Yaml\Yaml') && method_exists('Symfony\Component\Yaml\Yaml', 'parse');
        $this->hasExtension = function_exists("yaml_parse");

        if (!$this->hasSymfony && !$this->hasExtension) {
            throw new \Exception('Either Symfony\Yaml or the Yaml PHP extension is required to use this loader');
        }
        $this->forceSymfony = $useSymfony;
    }

    /**
     * {@inheritDoc}
     */
    public function getName() : string
    {
        return "YAML Loader";
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $file) : bool
    {
        return (in_array(pathinfo($file, PATHINFO_EXTENSION), ["yml", "yaml"]));
    }

    /**
     * {@inheritDoc}
     * @throws \Silktide\Syringe\Exception\LoaderException
     */
    public function loadFile(string $file) : array
    {
        if (!file_exists($file)) {
            throw new LoaderException("Requested YAML file '{$file}' doesn't exist");
        }

        if (!$this->forceSymfony && $this->hasExtension) {
            $data = yaml_parse_file($file);
        } else {
            $data = Yaml::parse(file_get_contents($file));
        }

        if (!is_array($data)) {
            throw new LoaderException("Requested YAML file '{$file}' does not parse to an array");
        }

        return $data;
    }
}