<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Nibbler\Syringe\Loader;

interface LoaderInterface {

    /**
     * @return string
     */
    public function getName();

    /**
     * @param $file
     * @return bool
     */
    public function supports($file);

    /**
     * @param $file
     * @return array
     */
    public function loadFile($file);

} 