<?php

namespace Silktide\Syringe;

use Silktide\Syringe\Exception\LoaderException;
use Silktide\Syringe\Loader\LoaderInterface;

class MasterConfigBuilder
{
    /**
     * @var LoaderInterface[]
     */
    protected $loaders = [];
    protected $fileCache = [];

    public function __construct(array $loaders = [])
    {
        $this->loaders = $loaders;
    }

    /**
     * @param array $files
     * @param array $paths
     * @return MasterConfig
     * @throws Exception\ConfigException
     * @throws LoaderException
     */
    public function load(array $files, array $paths = []) : MasterConfig
    {
        $files = $this->buildFileList($files, $paths);

        $masterConfig = new MasterConfig();
        foreach ($files as $filename => $file) {
            $file->validate();
            $masterConfig->addFileConfig($file);
        }
        return $masterConfig;
    }

    /**
     * @param array $files
     * @param array $paths
     * @param bool $inVendor
     * @return FileConfig[]
     * @throws Exception\ConfigException
     * @throws LoaderException
     */
    protected function buildFileList(array $files, array $paths, bool $inVendor = false) : array
    {
        $returnFiles = [];
        foreach ($files as $namespace => $filenames) {
            $namespace = !is_int($namespace) && !empty($namespace) ? $namespace : null;
            $fileInVendor = $inVendor;

            if (!is_array($filenames)) {
                $filenames = [$filenames];
            }

            foreach ($filenames as $filename) {
                $filename = $this->findConfigFile($filename, $paths);
                $data = $this->loadFile($filename);
                $config = $this->createConfig($filename, $data, $namespace);

                // The first time we enter the vendor directory we should flush the paths. We never want a vendor/syringe.yml
                // loading a base services.yml or similar
                $internalPaths = $paths;
                if (!$fileInVendor && strpos($filename, "vendor/") !== false) {
                    $internalPaths = [];
                    $fileInVendor = true;
                }

                if (($pos = mb_strrpos($filename, "/")) !== false) {
                    $internalPaths[] = mb_substr($filename, 0, $pos);
                }

                if (!is_null($inherit = $config->getInherit())) {
                    $inherited = $this->buildFileList([$namespace => $inherit], $internalPaths, $fileInVendor);
                    $returnFiles = array_merge($returnFiles, $inherited);
                }

                $returnFiles[] = $config;

                if (count($imports = $config->getImports()) > 0){
                    $returnFiles = array_merge($returnFiles, $this->buildFileList([$namespace => array_values($imports)], $internalPaths, $fileInVendor));
                }
            }
        }

        return $returnFiles;
    }

    protected function createConfig(string $filename, array $data, string $namespace = null)
    {
        return new FileConfig($filename, $data, $namespace);
    }


    protected function findConfigFile(string $file, array $paths)
    {
        // We give precedence to the most local
        for ($i=count($paths)-1; $i>=0; $i--) {
            $filePath = $paths[$i] . DIRECTORY_SEPARATOR . $file;
            if (file_exists($filePath)) {
                return realpath($filePath);
            }
        }

        throw new LoaderException(sprintf("The config file '%s' does not exist in any of the configured paths", $file));
    }

    /**
     * @param string $file
     * @return array
     * @throws LoaderException
     */
    protected function loadFile(string $file)
    {
        foreach ($this->loaders as $loader) {
            if ($loader->supports($file)) {
                return $loader->loadFile($file);
            }
        }

        throw new LoaderException("Unable to load file '{$file}'");
    }
}