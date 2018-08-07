<?php

namespace Silktide\Syringe;

use Silktide\Syringe\Exception\ConfigException;

class FileConfig
{
    const ACCEPTABLE_KEYS = [
        "imports" => 1,
        "parameters" => 1,
        "services" => 1,
        "inherit" => 1,
        "extensions" => 1
    ];

    const ACCEPTABLE_SERVICE_KEYS = [
        "class" => 1,
        "arguments" => 1,
        "extends" => 1,
        "factoryClass" => 1,
        "factoryMethod" => 1,
        "factoryService" => 1,
        "factoryArguments" => 1,
        "aliasOf" => 1,
        "abstract" => 1,
        "calls" => 1,
        "tags" => 1,
        "override" => 1
    ];

    protected $keys = [];
    protected $alias = null;
    protected $imports = [];
    protected $inherit = null;

    protected $parameters = [];
    protected $services = [];
    protected $extensions = [];

    public function __construct(array $data = [], string $alias = null)
    {
        $this->keys = array_keys($data);
        $this->alias = $alias;
        $this->imports = $data["imports"] ?? [];
        $this->inherit = $data["inherit"] ?? null;

        $this->parameters = $data["parameters"] ?? [];
        $this->services = $data["services"] ?? [];
        $this->extensions = $data["extensions"] ?? [];
    }


    protected function calculateWeight(string $key, string $aliased)
    {
        // If we're in a non-aliased file, then this will be the config we value most and should execute last
        if (is_null($this->alias)) {
            return 10;
        }

        // If the key equals the aliased key, then it was already aliased. This probably means that it was applied
        // in a mid-repo and thus should be given second priority
        if ($key === $aliased) {
            return 5;
        }

        // Otherwise we're a basic aliased imported repo. We should be given the lowest priority and executed first
        return 1;
    }

    public function getServices() : array
    {
        return $this->services;
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function getExtensions() : array
    {
        return $this->extensions;
    }

    /**
     * @return array
     */
    public function getImports() : array
    {
        return $this->imports;
    }

    /**
     * @return string|null
     */
    public function getInherit(): ?string
    {
        return $this->inherit;
    }

    public function getAliasedParameters()
    {
        $return = [];
        foreach ($this->parameters as $k => $value) {
            $aliased = $this->alias($k);
            $return[] = [
                "name" => $aliased,
                "weight" => $this->calculateWeight($k, $aliased),
                "value" => $this->recursivelyAlias($value)
            ];
        }
        return $return;
    }

    public function getAliasedServices()
    {
        $return = [];
        foreach ($this->services as $k => $value) {
            $aliased = $this->alias($k);
            $return[] = [
                "name" => $aliased,
                "weight" => $this->calculateWeight($k, $aliased),
                "value" => $this->recursivelyAlias($value)
            ];
        }
        return $return;
    }

    public function getAliasedExtensions(bool $self = false)
    {
        $return = [];
        foreach ($this->extensions as $k => $value) {
            $aliased = $this->alias($k);
            $return[] = [
                "name" => $aliased,
                "weight" => $this->calculateWeight($k, $aliased),
                "value" => $this->recursivelyAlias($value)
            ];
        }
        return $return;
    }

    public function validate()
    {
        foreach ($this->keys as $key) {
            if (!isset(self::ACCEPTABLE_KEYS[$key])) {
                throw new ConfigException($key." is not a valid services key");
            }
        }

        foreach ($this->services as $serviceName => $definition) {
            foreach ($definition as $key => $value) {
                if (!isset(self::ACCEPTABLE_SERVICE_KEYS[$key])) {
                    throw new ConfigException($key . " is not a valid services key");
                }
            }

            // We don't validate if we're aliasing or if this is just an abstract function
            if (!empty($definition["aliasOf"])) {
                if (mb_substr($definition["aliasOf"], 0, 1) !== "@") {
                    throw new ConfigException("AliasOf expects a service prefixed with @");
                }
                continue;
            }

            if (!empty($definition["abstract"])) {
                continue;
            }

            // Validate classes
            if (empty($definition["class"])) {
                throw new ConfigException("The service definition for '{$serviceName}' does not have a class");
            }

            if (!class_exists($definition["class"]) && !interface_exists($definition["class"])) {
                throw new ConfigException("Class: '{$definition["class"]}' was referenced but does not exist'");
            }

            // Validate factories
            if (!empty($definition["factoryMethod"])) {
                // If factoryMethod is set, then it must have either a factoryClass OR a factoryService, not both
                if (!(isset($definition["factoryClass"]) xor isset($definition["factoryService"]))) {
                    throw new ConfigException("The service definition for '{$serviceName}' should ONE of a factoryClass or a factoryService.");
                }
            }

            if (isset($definition["factoryService"])) {
                if (strlen($definition["factoryService"]) === 0) {
                    throw new ConfigException("Service '{$serviceName}' references a factoryService but doesn't provide one");
                }
            }

            if (!empty($definition["factoryClass"])) {
                if (!class_exists($definition["factoryClass"])) {
                    throw new ConfigException("Service '{$serviceName}' has a factoryClass of '{$definition["factoryClass"]}' which does not exist");
                }

                if (empty($definition["factoryMethod"])) {
                    throw new ConfigException("Service '{$serviceName}' uses a factoryClass but does not define a factoryMethod");
                }

                if (!method_exists($definition["factoryClass"], $definition["factoryMethod"])) {
                    throw new ConfigException("Service '{$serviceName}' uses a method of '{$definition["factoryMethod"]}' which does not exist");
                }
            }
        }
    }


    protected function alias(string $string)
    {
        if (is_null($this->alias) || $this->isAliased($string)) {
            return $string;
        }
        return $this->alias . Token::ALIAS_SEPARATOR . $string;
    }

    protected function isAliased(string $key)
    {
        return mb_strpos($key, Token::ALIAS_SEPARATOR) !== false;
        //return (preg_match("/^.*".Token::ALIAS_SEPARATOR.".+$/", $key));
    }

    protected function recursivelyAlias($value)
    {
        if (is_array($value)) {
            $return = [];
            foreach ($value as $k => $v) {
                $return[$k] = $this->recursivelyAlias($v);
            }
            return $return;
        } elseif (strlen($value) > 0) {
            if ($value[0] === Token::SERVICE_CHAR) {
                return Token::SERVICE_CHAR . $this->alias(mb_substr($value, 1));
            } elseif (mb_strpos($value, Token::PARAMETER_CHAR) !== false) {
                $replacements = [];
                $n = 0;
                while (mb_substr_count($value, Token::PARAMETER_CHAR) > 1) {
                    $pos1 = mb_strpos($value, Token::PARAMETER_CHAR);
                    $pos2 = mb_strpos($value, Token::PARAMETER_CHAR, $pos1 + 1);

                    $placeholder = '|||'.$n.'|||';

                    $replacements[$placeholder] =
                        Token::PARAMETER_CHAR .
                        $this->alias(mb_substr($value, $pos1 + 1, $pos2 - ($pos1 + 1))) .
                        Token::PARAMETER_CHAR;

                    $value = mb_substr($value, 0, $pos1) . $placeholder . mb_substr($value, $pos2 + 1);
                    $n++;
                }

                foreach ($replacements as $search => $replace) {
                    $value = str_replace($search, $replace, $value);
                }
            }
        }

        return $value;
    }
}