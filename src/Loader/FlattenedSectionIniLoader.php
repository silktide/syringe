<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Syringe\Loader;

/**
 * Load config file in .ini format
 * prepend keys with section name to flatten the array
 */
class FlattenedSectionIniLoader extends IniLoader
{

    /**
     * always process sections
     */
    public function __construct()
    {
        parent::__construct(true);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return "Flattened section ini loader";
    }

    /**
     * {@inheritDoc}
     */
    public function loadFile($file)
    {
        $initialData = parent::loadFile($file);
        $data = [];

        foreach ($initialData as $section => $subValue) {
            if (!is_array($subValue) || array_keys($subValue) === range(0, count($subValue) - 1)) {
                continue;
            }
            foreach ($subValue as $key => $value) {
                $data[$section . '-' . $key] = $value;
            }
        }
        return $data;
    }

} 