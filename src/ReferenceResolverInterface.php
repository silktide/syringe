<?php

namespace Silktide\Syringe;

use Pimple\Container;

/**
 *
 */
interface ReferenceResolverInterface {

    /**
     * Checks if $arg is a a service reference and loads it from the container
     *
     * @param $arg
     * @param Container $container
     * @param string $alias
     * @return mixed
     */
    public function resolveService($arg, Container $container, $alias = "");

    /**
     * inserts parameters references into $arg, recursively if required
     *
     * @param $arg
     * @param Container $container
     * @param string $alias
     * @return mixed
     */
    public function resolveParameter($arg, Container $container, $alias = "");

    /**
     * returns an array of services that have been tagged with the specified value
     *
     * @param string $tag
     * @param Container $container
     * @return array
     */
    public function resolveTag($tag, Container $container);

    /**
     * @param array $aliases
     */
    public function setRegisteredAliases(array $aliases);

    /**
     * @param string $key
     * @param string $alias
     * @return string
     */
    public function aliasThisKey($key, $alias);

    /**
     * @param string $key
     * @return bool
     */
    public function keyIsAliased($key);

    /**
     * Obfuscate a service name to make it private, while keeping a record to allow access to other services within
     * the same namespace alias
     *
     * @param string $hashedName - the unique obfuscated service name
     * @param string $actualName - the fully aliased service name
     */
    public function registerPrivateService($hashedName, $actualName);

} 