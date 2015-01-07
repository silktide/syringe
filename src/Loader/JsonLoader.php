<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Syringe\Loader;
use Silktide\Syringe\Exception\LoaderException;

/**
 * Load a config file in JSON format
 */
class JsonLoader implements LoaderInterface {

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return "JSON Loader";
    }

    /**
     * {@inheritDoc}
     */
    public function supports($file)
    {
        return (pathinfo($file, PATHINFO_EXTENSION) == "json");
    }

    /**
     * {@inheritDoc}
     * @throws \Silktide\Syringe\Exception\LoaderException
     */
    public function loadFile($file)
    {
        $data = json_decode(file_get_contents($file), true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new LoaderException(sprintf("Could not load the JSON file '%s': %s", $file, json_last_error_msg()));
        }
        return $data;
    }

} 