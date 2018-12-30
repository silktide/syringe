<?php


namespace Silktide\Syringe;


class CompiledConfig
{
    protected $services;
    protected $aliases;
    protected $parameters;
    protected $tags;
    protected $filenameContentHashes = [];

    public function __construct(array $services, array $aliases, array $parameters, array $tags, array $filenameContentHashes = [])
    {
        $this->services = $services;
        $this->aliases = $aliases;
        $this->parameters = $parameters;
        $this->tags = $tags;
        $this->filenameContentHashes = $filenameContentHashes;
    }

    /**
     * @return array
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function verifyContentHashes()
    {
        foreach ($this->filenameContentHashes as $filename => $contentHash) {
            if (!FileHasher::verify($filename, $contentHash)) {
                return false;
            }
        }

        return true;
    }
}