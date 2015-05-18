<?php
/**
 * Created by PhpStorm.
 * User: doug
 * Date: 18/05/15
 * Time: 11:42
 */

namespace Silktide\Syringe\Loader;


use Silktide\Syringe\Exception\LoaderException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class YamlLoader implements LoaderInterface {

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
        $parser = new Parser();
        try {
            $data = $parser->parse(file_get_contents($file));
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
            throw new LoaderException(sprintf("Could not load the YAML file '%s': %s", $file, $e->getMessage()));
        }
        return $data;
    }
}