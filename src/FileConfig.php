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
        "extensions" => 1,
        "static" => 1
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

            if (!empty($definition["aliasOf"]) || !empty($definition["abstract"])) {
                continue;
            }

            // Validate classes
            if (empty($definition["class"])) {
                throw new ConfigException(sprintf("The service definition for '%s' does not have a class", $key));
            }

            if (!class_exists($definition["class"]) && !interface_exists($definition["class"])) {
                throw new ConfigException("Class: '{$definition["class"]}' was referenced but does not exist'");
            }

            // Validate factories
            if (!empty($definition["factoryMethod"])) {
                // If factoryMethod is set, then it must have either a factoryClass OR a factoryService, not both
                if (!(isset($definition["factoryClass"]) xor isset($definition["factoryService"]))) {
                    throw new ConfigException(sprintf("The service definition for '%s' should ONE of a factoryClass or a factoryService.", $key));
                }
            }

            if (isset($definition["factoryClass"]) && !class_exists($definition["factoryClass"])) {
                throw new ConfigException("Class: '{$definition["factoryClass"]}' was referenced but does not exist'");
            }

            if (isset($definition["factoryService"])) {
                if (mb_strlen($definition["factoryService"]) === 0) {
                    throw new ConfigException("Service '{$serviceName}' references a factoryService but doesn't provide one");
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
        // Todo: This is kind of terrible as the regex will of course depend on the alias separator
        // If we change the separator, the regex will most likely need updated =/
        $regexSeparator = '\\' .Token::ALIAS_SEPARATOR;
        return (preg_match("/^silktide_.*{$regexSeparator}.+$/", $key));
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

                    $placeholder = '||£'.$n.'£||';

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

    public function inheritMerge(FileConfig $config)
    {
        if (count($config->getImports()) > 0) {
            throw new ConfigException("Inherited configs should not contain imports.");
        }

        foreach ($config->getServices() as $k => $v) {
            if (!isset($this->services[$k])) {
                $this->services[$k] = $v;
            }
        }

        foreach ($config->getAliasedExtensions() as $k => $v) {
            if (!isset($this->extensions[$k])) {
                $this->extensions[$k] = array_merge($this->extensions[$k], $v);
            }
        }

        foreach ($config->getParameters() as $k => $v) {
            if (!isset($this->parameters[$k])) {
                $this->parameters[$k] = $v;
            }
        }
    }
}