<?php


namespace Silktide\Syringe;


use Pimple\Container;
use Silktide\Syringe\Exception\ConfigException;

class ParameterResolver
{
    private const ESCAPED_TOKEN = "||ESCAPED_TOKEN||";

    protected $resolvedConstants = [];
    protected $resolvedEnvVars = [];

    public function resolveArray(array $parameters, array $array, array &$tagMap, array &$resolvedParameters = [])
    {
        foreach ($array as $k => $v) {
            $array[$k] = \is_array($v) ?
                $this->resolveArray($parameters, $v, $tagMap, $resolvedParameters) :
                $this->resolve($parameters, $v, $tagMap, $resolvedParameters);
        }
        return $array;
    }

    public function resolve(array $parameters, $parameter, array &$tagMap)
    {
        if (!\is_string($parameter) || \strlen($parameter) === 0) {
            return $parameter;
        }

        // We use a null character here as otherwise a parameter that resolved to having a TOKEN_SERVICE_CHAR at the
        // start would try to load it as a service
        switch ($parameter[0]) {
            case "\0":
                return $parameter;

            case Token::SERVICE_CHAR:
                return "\0" . \mb_substr($parameter, 1);

            case Token::TAG_CHAR:
                $tagMap[\mb_substr($parameter, 1)] = true;
                return "\0" . $parameter;
        }

        // Todo: We should readdress this in the future
        // If we have a string like this:
        // %foo_bar_^ENVIRONMENT_$ENVIRONMENT$^%
        // We should be resolving from the inside out, e.g:
        //   - $ENVIRONMENT$ -> 'dev' -> %url_^ENVIRONMENT_dev^%
        //   - ^ENVIRONMENT_DEV^ -> 'development' -> %foo_bar_development%
        //   - %foo_bar_development% -> 'dev.service.com'
        //
        // Hello! I'm Doug of the future!
        // I take issue with this, as it misses the key problem we have here. Take *this* example set of strings
        // environmentId: '$ENVIRONMENT$'
        // name: '%environmentId%-1'
        //
        // This is a more significant problem in day to day usage of this library
        //

        if (\is_string($parameter)) {
            $parameter = $this->replaceParameters($parameters, $parameter);

            if (\is_string($parameter)) {
                // replaceEnvironment should only ever return back a string
                $parameter = $this->replaceEnvironment($parameter);

                // Both constants and parameters can return an array, so we need to check it before trying to further
                // resolve
                if (\is_string($parameter)) {
                    $parameter = $this->replaceConstants($parameter);
                }
            }
        }

        if (!\is_string($parameter) && \is_array($parameter)) {
            return $this->resolveArray($parameters, $parameter, $tagMap);
        }

        return $parameter;
    }

    protected function replaceParameters(array $parameters, string $parameter)
    {
        if (\mb_strpos($parameter, Token::PARAMETER_CHAR) === false) {
            return $parameter;
        }

        return $this->replaceSurroundingToken($parameter, Token::PARAMETER_CHAR, function($value) use ($parameters) {
            // We need array_key_exists for if a value has been set as null, but isset is far far faster than array_key_exists
            if (isset($parameters[$value]) || \array_key_exists($value, $parameters)) {
                return $parameters[$value];
            }

            throw new ConfigException("Referenced parameter '{$value}' does not exist");
        });
    }

    protected function replaceConstants(string $parameter, array &$resolvedConstants = [])
    {
        if (\mb_strpos($parameter, Token::CONSTANT_CHAR) === false) {
            return $parameter;
        }

        return $this->replaceSurroundingToken($parameter, Token::CONSTANT_CHAR, function($value) {
            if (defined($value)) {
                $const = constant($value);
                $this->resolvedConstants[$value] = $const;
                return $const;
            }

            throw new ConfigException("Referenced constant '{$value}' does not exist");
        });
    }

    protected function replaceEnvironment(string $parameter)
    {
        if (\mb_strpos($parameter, Token::ENV_CHAR) === false) {
            return $parameter;
        }

        return $this->replaceSurroundingToken($parameter, Token::ENV_CHAR, function($value) {
            if (($env = getenv($value)) !== false) {
                $this->resolvedEnvVars[$value] = $env;
                return $env;
            }

            throw new ConfigException("Referenced environment variable '{$value}' is not set'");
        });
    }

    protected function replaceSurroundingToken(string $parameter, string $token, callable $callable, array $stack = [])
    {
        $oldParameter = $parameter;

        while (\is_string($parameter) && \mb_substr_count($parameter, $token) > 0) {
            // The passStack keeps track of what parameters we've had to resolve on this parameter and passes this
            // down to ensure that we don't end up trying to do any recursive resolution
            $passStack = $stack;

            // Find the first entry in the parameter
            $pos1 = \mb_strpos($parameter, $token);
            $pos2 = \mb_strpos($parameter, $token, $pos1 + 1);

            // If they are next to each other, then they're escaped tokens and should be ignored
            if ($pos2 === $pos1 + 1) {
                $parameter = \mb_substr($parameter, 0, $pos1) . self::ESCAPED_TOKEN . \mb_substr($parameter, $pos2 + 1);
                continue;
            }

            // If there's only one token, then someone has screwed up the config
            if ($pos2 === false) {
                throw new ConfigException("An uneven number of '{$token}' token bindings exists for '{$oldParameter}'");
            }

            // Get the value of the parameter, so with '%foo_bar% salad' this should return 'foo_bar'
            $value = \mb_substr($parameter, $pos1 + 1, $pos2 - ($pos1 + 1));

            if (in_array($value, $stack, true)) {
                throw new ConfigException("Infinite parameter loop detected: " . implode(" -> ", $stack));
            }

            // Add that we've resolved this key to the passStack
            $passStack[] = $value;
            $newValue = $callable($value);
            // If it took up the entire parameter
            if (($pos1 === 0 && ($pos2 + 1) === \mb_strlen($parameter))) {
                if (\is_string($newValue)) {
                    $parameter = $this->replaceSurroundingToken($newValue, $token, $callable, $passStack);
                } else {
                    $parameter = $newValue;
                }
                continue;
            }

            if (!\is_string($newValue) && !\is_numeric($newValue)) {
                throw new ConfigException(
                    "Parameter '{$value}' as part of '{$oldParameter}' resolved to a non-string. This is only permissible if the parameter attempts no interpolation"
                );
            }

            $parameter = \mb_substr($parameter, 0, $pos1) . $this->replaceSurroundingToken($newValue, $token, $callable, $passStack) . \mb_substr($parameter, $pos2 + 1);
        }

        return \is_string($parameter) ? \str_replace(self::ESCAPED_TOKEN, $token, $parameter) : $parameter;
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