<?php

namespace Silktide\Syringe\Loader;

use Silktide\Syringe\Exception\LoaderException;

/**
 * Load a config file in JSON format
 */
class JsonLoader implements LoaderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName() : string
    {
        return "JSON Loader";
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $file) : bool
    {
        return (pathinfo($file, PATHINFO_EXTENSION) == "json");
    }

    /**
     * {@inheritDoc}
     * @throws \Silktide\Syringe\Exception\LoaderException
     */
    public function loadFile(string $file) : array
    {
        if (!file_exists($file)) {
            throw new LoaderException("Requested JSON file '{$file}' doesn't exist");
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new LoaderException("Could ont succesfully load requested JSON file '{$file}'");
        }

        $data = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new LoaderException(sprintf("Could not load the JSON file '%s': %s", $file, json_last_error_msg()));
        }

        if (!is_array($data)) {
            throw new LoaderException(sprintf("JSON file '%s' did not parse to an array", $file));
        }

        return $data;
    }

} 