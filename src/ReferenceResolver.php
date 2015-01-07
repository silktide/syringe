<?php
/**
 * Silktide Nibbler. Copyright 2013-2014 Silktide Ltd. All Rights Reserved.
 */
namespace Silktide\Syringe;
use Silktide\Syringe\Exception\ConfigException;
use Pimple\Container;
use Silktide\Syringe\Exception\ReferenceException;

/**
 * Resolves references to existing container definitions
 */
class ReferenceResolver implements ReferenceResolverInterface
{

    protected $replacedParams = [];

    /**
     * {@inheritDoc}
     * @throws ReferenceException
     */
    public function resolveService($arg, Container $container, $alias = "")
    {
        if (!is_string($alias)) {
            $alias = "";
        }
        if ($arg[0] == ContainerBuilder::SERVICE_CHAR) {
            $name = $this->aliasThisKey(substr($arg, 1), $alias);
            // check if the service exists
            if (!$container->offsetExists($name)) {
                throw new ReferenceException(sprintf("Tried to inject the service '%s', but it doesn't exist", $name));
            }
            $arg = $container[$name];
        }
        return $arg;
    }

    /**
     * {@inheritDoc}
     * @throws ReferenceException
     */
    public function resolveParameter($arg, Container $container, $alias = "")
    {
        if (!is_string($arg)) {
            return $arg;
        }
        if (!is_string($alias)) {
            $alias = "";
        }
        $maxLoops = 100;
        $thisLoops = 0;
        while ($thisLoops < $maxLoops && substr_count($arg, ContainerBuilder::PARAMETER_CHAR) > 1) {
            ++$thisLoops;
            // parameters
            $char = ContainerBuilder::PARAMETER_CHAR;
            // find the first parameter in the string
            $start = strpos($arg, $char) + 1;
            $end = strpos($arg, $char, $start);
            $param = substr($arg, $start, $end - $start);

            // alias the param and check if it has already been replaced (circular reference)
            $name = $this->aliasThisKey($param, $alias);
            if (isset($this->replacedParams[$name])) {
                if (isset($this->replacedParams[$param])) {
                    throw new ReferenceException("Circular reference found for the key '$param'");
                }
                // the aliased param has been replaced, check for a non aliased version
                $name = $param;
            }
            if (!$container->offsetExists($name)) {
                throw new ReferenceException(sprintf("Tried to inject the parameter '%s' in an argument list, but it doesn't exist", $name));
            }
            if (strlen($arg) > strlen($name) + 2) {
                // string replacement
                $arg = str_replace($char . $name . $char, $container[$name], $arg);

            } else {
                // value replacement
                $arg = $container[$name];
            }
            // add param name to the replacement list
            $this->replacedParams[$name] = true;
        }
        if ($thisLoops >= $maxLoops) {
            throw new ReferenceException("Could not resolve parameter '$arg'. The maximum recursion limit was exceeded");
        }
        $this->replacedParams = [];
        return $arg;
    }

    /**
     * {@inheritDoc}
     */
    public function aliasThisKey($key, $alias)
    {
        if (empty($alias)) {
            return $key;
        }
        if (!is_string($alias)) {
            throw new ConfigException("Alias must be a string");
        }
        return $alias . "." . $key;
    }

} 