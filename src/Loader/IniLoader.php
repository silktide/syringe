<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Nibbler\Syringe\Loader;

/**
 * Load a configuration file in .ini format
 */
class IniLoader implements LoaderInterface
{

    /**
     * @var bool
     */
    protected $processSections;

    /**
     * @param bool $processSections - optionally process sections to an assoc array
     */
    public function __construct($processSections = false)
    {
        $this->processSections = (bool) $processSections;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return "Ini loader";
    }

    /**
     * {@inheritDoc}
     */
    public function supports($file)
    {
        return (pathinfo($file, PATHINFO_EXTENSION) == "ini");
    }

    /**
     * {@inheritDoc}
     */
    public function loadFile($file)
    {
        return parse_ini_file($file, $this->processSections);
    }


} 