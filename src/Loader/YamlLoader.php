<?php

namespace Silktide\Syringe\Loader;

use Silktide\Syringe\Exception\LoaderException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class YamlLoader implements LoaderInterface
{
    protected $useSymfony = false;

    /**
     * YamlLoader constructor.
     * @param bool $forceSymfony
     */
    public function __construct($forceSymfony = false)
    {
        if ($forceSymfony || !function_exists("yaml_parse")) {
            $this->useSymfony = true;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return "YAML Loader";
    }

    /**
     * {@inheritDoc}
     */
    public function supports($file)
    {
        return (in_array(pathinfo($file, PATHINFO_EXTENSION), ["yml", "yaml"]));
    }

    /**
     * {@inheritDoc}
     * @throws \Silktide\Syringe\Exception\LoaderException
     */
    public function loadFile($file)
    {
        if (!file_exists($file)) {
            throw new LoaderException("Requested YAML file '{$file}' doesn't exist");
        }

        $contents = file_get_contents($file);

        if ($this->useSymfony) {
            try {
                // Apparently parser keeps references to the things it parses? As such, we want to create a new parser
                // each time (uch)
                $parser = new Parser();
                return $parser->parse($contents);
            } catch (ParseException $e) {
                throw new LoaderException("Could not load the YAML file '{$file}': ".$e->getMessage());
            }
        }

        $data = yaml_parse($contents);
        if (!is_array($data)) {
            throw new LoaderException("Requested YAML file '{$file}' does not parse to an array");
        }

        return $data;
    }
}