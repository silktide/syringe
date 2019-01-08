<?php

namespace Silktide\Syringe\Loader;

use Silktide\Syringe\Exception\LoaderException;

/**
 * Load a config file in JSON format
 */
class PhpLoader implements LoaderInterface
{

    /**
     * {@inheritDoc}
     */
    public function getName() : string
    {
        return "Php Loader";
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $file) : bool
    {
        return (pathinfo($file, PATHINFO_EXTENSION) == "php");
    }

    /**
     * {@inheritDoc}
     * @throws \Silktide\Syringe\Exception\LoaderException
     */
    public function loadFile(string $file) : array
    {
        if (!file_exists($file)) {
            throw new LoaderException("Requested file '{$file}' doesn't exist");
        }

        $data = include($file);

        if (!is_array($data)) {
            throw new LoaderException("Requested file '{$file}' is expected to return an array");
        }

        return $data;
    }

} 