<?php


namespace Silktide\Syringe;


use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;

class ReferenceResolver
{
    private const ESCAPED_TOKEN = "||ESCAPED_TOKEN||";

    public function resolveArray(Container $container, array $array)
    {
        foreach ($array as $k => $v) {
            $array[$k] = is_array($v) ? $this->resolveArray($container, $v) : $this->resolve($container, $v);
        }
        return $array;
    }

    public function resolve(Container $container, $parameter)
    {
        if (!is_string($parameter) || strlen($parameter) === 0) {
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
            if (is_array($parameter)) {
                return $this->resolveArray($container, $parameter);
            }
            return $parameter;
        }

        $parameter = $this->replaceConstants($container, $parameter);
        if (!is_string($parameter)) {
            if (is_array($parameter)) {
                return $this->resolveArray($container, $parameter);
            }
            return $parameter;
        }

        return $this->replaceEnvironment($container, $parameter);
    }

    protected function replaceParameters(Container $container, string $parameter)
    {
        return $this->replaceSurroundingToken($container, $parameter, Token::PARAMETER_CHAR, function($value) use ($container) {
            if ($container->offsetExists($value)) {
                return $container->offsetGet($value);
            }

            throw new ConfigException("Referenced parameter '{$value}' does not exist");
        });
    }

    protected function replaceConstants(Container $container, string $parameter)
    {
        return $this->replaceSurroundingToken($container, $parameter, Token::CONSTANT_CHAR, function($value) {
            if (defined($value)) {
                return constant($value);
            }

            throw new ConfigException("Referenced constant '{$value}' does not exist");
        });
    }

    protected function replaceEnvironment(Container $container, string $parameter)
    {
        return $this->replaceSurroundingToken($container, $parameter, Token::ENV_CHAR, function($value) {
            if (($env = getenv($value)) !== false) {
                return $env;
            }

            throw new ConfigException("Referenced environment variable '{$value}' is not set'");
        });
    }

    protected function replaceSurroundingToken(Container $container, string $parameter, string $token, callable $callable)
    {
        $oldParameter = $parameter;

        while (mb_substr_count($parameter, $token) > 0) {
            $pos1 = mb_strpos($parameter, $token);
            $pos2 = mb_strpos($parameter, $token, $pos1 + 1);

            if ($pos2 === $pos1 + 1) {
                $parameter = mb_substr($parameter, 0, $pos1) . self::ESCAPED_TOKEN . mb_substr($parameter, $pos2 + 1);
                continue;
            }

            if ($pos2 === false) {
                throw new ConfigException("An uneven number of '{$token}' token bindings exists for '{$oldParameter}'");
            }
            $value = mb_substr($parameter, $pos1 + 1, $pos2 - ($pos1 + 1));
            $newValue = $callable($value);
            if (!is_string($newValue) && !is_numeric($newValue)) {
                if (mb_strlen($value) + 2 === mb_strlen($parameter)) {
                    return $newValue;
                }

                throw new ConfigException(
                    "Parameter '{$value}' as part of '{$oldParameter}' resolved to a non-string. This is only permissible if the parameter attempts no interpolation"
                );
            }

            $parameter = mb_substr($parameter, 0, $pos1) . $newValue . mb_substr($parameter, $pos2 + 1);
        }

        return str_replace(self::ESCAPED_TOKEN, $token, $parameter);
    }
}