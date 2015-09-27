<?php

namespace Silktide\Syringe\Loader;

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