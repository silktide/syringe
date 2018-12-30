<?php


namespace Silktide\Syringe;


class FileHasher
{
    public static function hash(string $filename)
    {
        return hash_file('md5', $filename);
    }

    public static function verify(string $filename, string $hash)
    {
        return self::hash($filename) === $hash;
    }
}