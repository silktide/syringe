<?php

namespace Silktide\Syringe\Loader;

use Silktide\Syringe\Exception\LoaderException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class YamlLoader implements LoaderInterface
{
    protected $forceSymfony;

    public function __construct(bool $useSymfony = false)
    {
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

        if (!$this->forceSymfony && function_exists("yaml_parse_file")) {
            $data = yaml_parse_file($file);
        } else {
            $data = Yaml::parseFile($file);
        }

        if (!is_array($data)) {
            throw new LoaderException("Requested YAML file '{$file}' does not parse to an array");
        }

        return $data;
    }
}