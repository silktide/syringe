<?php


namespace Silktide\Syringe;


use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;

class ReferenceResolver
{
    public function resolveArray(Container $container, array $array)
    {
        foreach ($array as $k => $v) {
            $array[$k] = $this->resolve($container, $v);
        }
        return $array;
    }

    public function resolve(Container $container, $parameter)
    {
        if (!is_string($parameter) || mb_strlen($parameter) === 0) {
            return $parameter;
        }

        switch ($parameter[0]) {
            case Token::SERVICE_CHAR:
                return $container->offsetGet(mb_substr($parameter, 1));

            case Token::TAG_CHAR:
                return $container->offsetGet($parameter);
        }

        $parameter = $this->replaceParameters($container, $parameter);
        if (!is_string($parameter)) {
            return $parameter;
        }

        $parameter = $this->replaceConstants($container, $parameter);
        if (!is_string($parameter)) {
            return $parameter;
        }

        return $this->replaceEnvironment($container, $parameter);
    }

    // Todo: These need some serious error handling
    protected function replaceParameters(Container $container, string $parameter)
    {
        return $this->replaceSurroundingToken($container, $parameter, Token::PARAMETER_CHAR, function($value) use ($container) {
            return $container->offsetGet($value);
        });
    }

    protected function replaceConstants(Container $container, string $parameter)
    {
        return $this->replaceSurroundingToken($container, $parameter, Token::CONSTANT_CHAR, function($value) {
            return constant($value);
        });
    }

    protected function replaceEnvironment(Container $container, string $parameter)
    {
        return $this->replaceSurroundingToken($container, $parameter, Token::ENV_CHAR, function($value) {
            return getenv($value);
        });
    }

    protected function replaceSurroundingToken(Container $container, string $parameter, string $token, callable $callable)
    {
        // Todo:
        // This is a good basic implementation, but it doesn't take into account the potential need to escape stuff
        // Todo: Some of these responses are bollocks, we only sometimes would require the escaping of the percent?
        // How would you best be meant to handle that?
        // Some expected responses
        // $bar = "banana"
        //  - "foo%bar%" -> "foobanana"
        //  - "foo\%bar%" -> "foo%bar%"
        //  - "foo\\%bar%" -> "foo\banana"
        //  - "foo\\\%bar%" -> "foo\%bar%
        // I think we can potentially run this
        while (mb_substr_count($parameter, $token) > 1) {
            $pos1 = mb_strpos($parameter, $token);
            $pos2 = mb_strpos($parameter, $token, $pos1 + 1);
            $value = mb_substr($parameter, $pos1 + 1, $pos2 - ($pos1 + 1));
            $newValue = $callable($value);
            if (!is_string($newValue) && !is_numeric($newValue)) {
                if (mb_strlen($value) + 2 === mb_strlen($parameter)) {
                    return $newValue;
                }

                throw new ConfigException(
                    "Parameter '{$value}' as part of '{$parameter}' resolved to a non-string. This is only permissible if the parameter attempts no interpolation "
                );
            }

            $parameter = mb_substr($parameter, 0, $pos1) . $newValue . mb_substr($parameter, $pos2 + 1);
        }

        return $parameter;
    }
}