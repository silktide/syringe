<?php

use Cache\Adapter\Filesystem\FilesystemCachePool;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Silktide\Syringe\Syringe;

include(__DIR__ . "/../../../vendor/autoload.php");

define("MY_CONSTANT_VALUE", $argv[1]);

$container = Syringe::build([
    "paths" => [__DIR__],
    "files" => ["file4.yml"],
    "cache" => new FilesystemCachePool(new Filesystem(new Local(sys_get_temp_dir()))),
    "validateCache" => true
]);

echo $container["my_constant_var"];