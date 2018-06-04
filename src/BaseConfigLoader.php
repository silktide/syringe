<?php


namespace Silktide\Syringe;


use Silktide\Syringe\Exception\LoaderException;
use Silktide\Syringe\Loader\JsonLoader;
use Silktide\Syringe\Loader\LoaderInterface;
use Silktide\Syringe\Loader\PhpLoader;
use Silktide\Syringe\Loader\YamlLoader;

class BaseConfigLoader
{
    /**
     * @var LoaderInterface[]
     */
    protected $loaders = [];
    protected $fileCache = [];
    protected $baseDirectory;

    public function __construct(array $loaders = [])
    {
        // If we pass no loaders, use the default loaders
        $this->loaders = !empty($loaders) ? $loaders : [new YamlLoader(), new PhpLoader(), new JsonLoader()];
    }

    public function load(array $files, array $paths = [], string $baseDirectory = null)
    {
        $this->baseDirectory = realpath($baseDirectory ?? __DIR__."/../") . "/";
        $files = $this->buildFileList($files, $paths);

        $baseConfig = new BaseConfig();
        foreach ($files as $fileConfig) {
            $baseConfig->mergeSelfAliased($fileConfig);
        }

        foreach ($files as $fileConfig) {
            $baseConfig->mergeNonSelfAliased($fileConfig);
        }

        $baseConfig->validate();
        return $baseConfig;
    }

    /**
     * @param array $files
     * @param array $paths
     * @param bool $inVendor
     * @return BaseConfig[]
     * @throws LoaderException
     */
    protected function buildFileList(array $files = [], array $paths, bool $inVendor = false)
    {
        $returnFiles = [];

        foreach ($files as $filename => $alias) {

            $fileInVendor = $inVendor;
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

            // There's no real difference between import and inherit except for the order of which they are
            // processed. Inherit should be processed before and import afterwards
            // Except for the fact that Inherit shouldn't moan when we overwrite itself
            if (!is_null($inherit = $config->getInherit())) {
                $inherited = $this->buildFileList([$inherit => $alias], $internalPaths, $fileInVendor);
                $config->inheritMerge($inherited[0]);
            }

            $returnFiles[] = $config;

            if (count($imports = $config->getImports()) > 0){
                $aliasedImports = [];
                foreach ($imports as $v) {
                    $aliasedImports[$v] = $alias;
                }
                $returnFiles = array_merge($returnFiles, $this->buildFileList($aliasedImports, $internalPaths, $fileInVendor));
            }
        }

        return $returnFiles;
    }

    protected function createConfig(array $data, string $alias = null)
    {
        return new BaseConfig($data, $alias);
    }


    protected function findConfigFile(string $file, array $paths)
    {
        // We give precedence to the most local
        for ($i=count($paths)-1; $i>=0; $i--) {
            $filePath = $paths[$i] . DIRECTORY_SEPARATOR . $file;
            if (file_exists($filePath)) {
                return realpath($filePath);
                //return str_replace($this->baseDirectory, "", realpath($filePath));
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