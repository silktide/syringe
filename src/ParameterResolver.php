<?php


namespace Silktide\Syringe;


use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;

class ParameterResolver
{
    private const ESCAPED_TOKEN = "||ESCAPED_TOKEN||";

    protected $resolvedConstants = [];
    protected $resolvedEnvVars = [];

    public function resolveArray(array $parameters, array $array)
    {
        foreach ($array as $k => $v) {
            $array[$k] = is_array($v) ? $this->resolveArray($parameters, $v) : $this->resolve($parameters, $v);
        }
        return $array;
    }

    public function resolve(array $parameters, $parameter)
    {
        if (!is_string($parameter) || strlen($parameter) === 0) {
            return $parameter;
        }

        // We use a null character here as otherwise a parameter that resolved to having a TOKEN_SERVICE_CHAR at the
        // start would try to load it as a service
        switch ($parameter[0]) {
            case "\0":
                return $parameter;

            case Token::SERVICE_CHAR:
                return "\0" . mb_substr($parameter, 1);

            case Token::TAG_CHAR:
                return "\0" . $parameter;
        }

        $parameter = $this->replaceParameters($parameters, $parameter);
        if (!is_string($parameter)) {
            if (is_array($parameter)) {
                return $this->resolveArray($parameters, $parameter);
            }
            return $parameter;
        }

        $parameter = $this->replaceConstants($parameter);
        if (!is_string($parameter)) {
            if (is_array($parameter)) {
                return $this->resolveArray($parameters, $parameter);
            }
            return $parameter;
        }

        return $this->replaceEnvironment($parameter);
    }

    protected function replaceParameters(array $parameters, string $parameter)
    {
        if (mb_strpos($parameter, Token::PARAMETER_CHAR) === false) {
            return $parameter;
        }

        return $this->replaceSurroundingToken($parameter, Token::PARAMETER_CHAR, function($value) use ($parameters) {
            // We need array_key_exists for if a value has been set as null, but isset is far far faster than array_key_exists
            if (isset($parameters[$value]) || array_key_exists($value, $parameters)) {
                return $parameters[$value];
            }

            throw new ConfigException("Referenced parameter '{$value}' does not exist");
        });
    }

    protected function replaceConstants(string $parameter, array &$resolvedConstants = [])
    {
        if (mb_strpos($parameter, Token::CONSTANT_CHAR) === false) {
            return $parameter;
        }

        return $this->replaceSurroundingToken($parameter, Token::CONSTANT_CHAR, function($value) { //} use (&$resolvedConstants) {
            if (defined($value)) {
                $const = constant($value);
                $this->resolvedConstants[$value] = $const;
                return $const;
            }

            throw new ConfigException("Referenced constant '{$value}' does not exist");
        });
    }

    protected function replaceEnvironment(string $parameter, array &$resolvedEnvs = [])
    {
        if (mb_strpos($parameter, Token::ENV_CHAR) === false) {
            return $parameter;
        }

        return $this->replaceSurroundingToken($parameter, Token::ENV_CHAR, function($value) {// use (&$resolvedEnvs) {

            if (($env = getenv($value)) !== false) {
                $this->resolvedEnvVars[$value] = $env;
                return $env;
            }

            throw new ConfigException("Referenced environment variable '{$value}' is not set'");
        });
    }

    protected function replaceSurroundingToken(string $parameter, string $token, callable $callable)
    {
        $oldParameter = $parameter;

        while (is_string($parameter) && mb_substr_count($parameter, $token) > 0) {
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

            // If it took up the entire parameter
            if (($pos1 === 0 && ($pos2 + 1) === mb_strlen($parameter))) {
                $parameter = $newValue;
                continue;
            }

            if (!is_string($newValue) && !is_numeric($newValue)) {
                throw new ConfigException(
                    "Parameter '{$value}' as part of '{$oldParameter}' resolved to a non-string. This is only permissible if the parameter attempts no interpolation"
                );
            }
            $parameter = mb_substr($parameter, 0, $pos1) . $newValue . mb_substr($parameter, $pos2 + 1);
        }

        return is_string($parameter) ? str_replace(self::ESCAPED_TOKEN, $token, $parameter) : $parameter;
    }

    public function getResolvedConstants() : array
    {
        return $this->resolvedConstants;
    }

    public function getResolvedEnvVars() : array
    {
        return $this->resolvedEnvVars;
    }
}