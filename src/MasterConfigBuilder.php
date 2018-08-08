<?php


namespace Silktide\Syringe;


use Silktide\Syringe\Exception\LoaderException;
use Silktide\Syringe\Loader\JsonLoader;
use Silktide\Syringe\Loader\LoaderInterface;
use Silktide\Syringe\Loader\PhpLoader;
use Silktide\Syringe\Loader\YamlLoader;

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
        foreach ($files as $file) {
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
    protected function buildFileList(array $files = [], array $paths, bool $inVendor = false) : array
    {
        $returnFiles = [];
        foreach ($files as $alias => $filenames) {
            $alias = !is_int($alias) && !empty($alias) ? $alias : null;
            $fileInVendor = $inVendor;

            if (!is_array($filenames)) {
                $filenames = [$filenames];
            }

            foreach ($filenames as $filename) {
                $data = $this->loadFile($filename, $paths);
                $config = $this->createConfig($data, $alias);

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
                    $inherited = $this->buildFileList([$alias => $inherit], $internalPaths, $fileInVendor);
                    $returnFiles = array_merge($returnFiles, $inherited);
                }

                $returnFiles[] = $config;

                if (count($imports = $config->getImports()) > 0){
                    $returnFiles = array_merge($returnFiles, $this->buildFileList([$alias => array_values($imports)], $internalPaths, $fileInVendor));
                }
            }
        }

        return $returnFiles;
    }

    protected function createConfig(array $data, string $alias = null)
    {
        return new FileConfig($data, $alias);
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
     * @param array $paths
     * @return array
     * @throws LoaderException
     */
    protected function loadFile(string &$file, array $paths)
    {
        $file = $this->findConfigFile($file, $paths);
        foreach ($this->loaders as $loader) {
            if ($loader->supports($file)) {
                return $loader->loadFile($file);
            }
        }

        throw new \Exception("Unable to load file '{$file}'");
    }
}