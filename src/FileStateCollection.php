<?php

namespace Silktide\Syringe;

class FileStateCollection
{
    protected $state = [];

    public function __construct(array $filenames)
    {
        clearstatcache();
        foreach ($filenames as $filename) {
            $this->state[$filename] = [
                "modified" => filemtime($filename),
                "hash" => hash_file('md5', $filename)
            ];
        }
    }

    public static function build(array $filenames) : FileStateCollection
    {
        return new FileStateCollection($filenames);
    }

    public function isValid() : bool
    {
        clearstatcache();
        foreach ($this->state as $filename => $info) {
            if (filemtime($filename) !== $info["modified"] && hash_file('md5', $filename) !== $info["hash"]) {
                return false;
            }
        }
        return true;
    }
}