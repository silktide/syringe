<?php

namespace Silktide\Syringe\Loader;

interface LoaderInterface {

    /**
     * @return string
     */
    public function getName() : string;

    /**
     * @param string $file
     * @return bool
     */
    public function supports(string $file) : bool;

    /**
     * @param string $file
     * @return array
     */
    public function loadFile(string $file) : array;

} 