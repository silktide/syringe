<?php


namespace Silktide\Syringe;

// Todo: How does it know about hierarchy if a sub-project overwrites another sub-project
use Silktide\Syringe\Exception\ConfigException;

class BaseConfig
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

    protected $nonSelfAliasedParameters = [];
    //protected $nonSelfAliasedParameters = [];


    protected $selfAliasedParameters = [];
    protected $selfAliasedServices = [];
    protected $selfAliasedExtensions = [];

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

    /**
     * @return array
     */
    public function getImports() : array
    {
        return $this->imports;
    }

    /**
     * @return array
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getServices() : array
    {
        return $this->services;
    }

    /**
     * @return string|null
     */
    public function getInherit(): ?string
    {
        return $this->inherit;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * @return null|string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getParametersByAlias(bool $selfAliased)
    {
        return $this->getByAlias($this->getParameters(), $selfAliased);
    }

    public function getServicesByAlias(bool $selfAliased)
    {
        return $this->getByAlias($this->getServices(), $selfAliased);
    }

    public function getExtensionsByAlias(bool $selfAliased)
    {
        return $this->getByAlias($this->getExtensions(), $selfAliased);
    }

    protected function getByAlias(array $data, bool $selfAliased)
    {
        $return = [];
        foreach ($data as $key => $value) {
            $aliasedKey = $this->alias($key);
            if ($selfAliased && $this->isSelfAliased($aliasedKey)) {
                $return[$aliasedKey] = $this->recursivelyAlias($value);
            } elseif (!$selfAliased && !$this->isSelfAliased($aliasedKey)) {
                $return[$aliasedKey] = $this->recursivelyAlias($value);
            }
        }
        return $return;
    }


    public function getAliasedParameters()
    {
        $return = [];
        foreach ($this->getParameters() as $k => $value) {
            $return[$this->alias($k)] = $this->recursivelyAlias($value);
        }
        return $return;
    }

    public function getAliasedServices(bool $self = false)
    {
        if (is_null($this->alias)) {
            return $this->getServices();
        }

        $return = [];
        foreach ($this->getServices() as $k => $value) {
            $return[$this->alias($k)] = $this->recursivelyAlias($value);
        }
        return $return;
    }

    public function getAliasedExtensions(bool $self = false)
    {
        if (is_null($this->alias)) {
            return $this->getExtensions();
        }

        $return = [];
        foreach ($this->getExtensions() as $k => $value) {
            $return[$this->alias($k)] = $this->recursivelyAlias($value);
        }
        return $return;
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

    protected function isSelfAliased(string $key)
    {
        // Todo: This is kind of terrible as the regex will of course depend on the alias separator
        // If we change the separator, the regex will most likely need updated =/
        $regexSeparator = '\\' .Token::ALIAS_SEPARATOR;
        return $this->alias === null || preg_match("/^{$this->alias}{$regexSeparator}.+$/", $key);
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

    public function mergeSelfAliased(BaseConfig $config)
    {
        $this->merge($config, true);
    }


    public function mergeNonSelfAliased(BaseConfig $config)
    {
        $this->merge($config, false);
    }

    protected function merge(BaseConfig $config, bool $selfAliased)
    {
        foreach ($config->getParametersByAlias($selfAliased) as $k => $v) {
            $this->parameters[$k] = $v;
        }

        foreach ($config->getServicesByAlias($selfAliased) as $serviceName => $definition) {
            if (isset($this->services[$serviceName]) && !isset($definition["aliasOf"])) {
                throw new ConfigException("Overwriting existing service '{$serviceName}'. Services can only be overwritten using aliasOf");
            }
            $this->services[$serviceName] = $definition;
        }

        foreach ($config->getExtensionsByAlias($selfAliased) as $k => $v) {
            $this->extensions[$k] = $v;
        }
    }

    /**
     * Weak merge is used when we merge in an *inherited* config, we only copy across stuff if it doesn't already exist
     *
     * @param BaseConfig $config
     */
    public function inheritMerge(BaseConfig $config)
    {
        if (count($config->getServices()) > 0) {
            throw new ConfigException("Inherited configs should not contain services.");
        }

        if (count($config->getImports()) > 0) {
            throw new ConfigException("Inherited configs should not contain imports.");
        }

        foreach ($config->getParameters() as $k => $v) {
            if (!isset($this->parameters[$k])) {
                $this->parameters[$k] = $v;
            }
        }
    }
}