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

    protected $data = [];
    protected $alias = null;

    public function __construct(array $data = [], string $alias = null)
    {
        $this->data = $data;
        $this->data["imports"] = $data["imports"] ?? [];
        $this->data["parameters"] = $data["parameters"] ?? [];
        $this->data["services"] = $data["services"] ?? [];
        $this->data["extensions"] = $data["extensions"] ?? [];
        $this->data["inherit"] = $data["inherit"] ?? null;
        $this->validate();
        $this->alias = $alias;
    }

    public function validate()
    {
        foreach ($this->data as $k => $_) {
            if (!isset(self::ACCEPTABLE_KEYS[$k])) {
                throw new \Exception($k." is not a valid services key");
            }
        }

        foreach ($this->data["services"] as $definition) {
            foreach ($definition as $key => $value) {
                if (!isset(self::ACCEPTABLE_SERVICE_KEYS[$key])) {
                    throw new \Exception($key . " is not a valid services key");
                }
            }

            if (!empty($definition["aliasOf"])) {
                continue;
            }

            // Validate classes
            if (empty($definition["class"])) {
                throw new ConfigException(sprintf("The service definition for '%s' does not have a class", $key));
            }

            if (!class_exists($definition["class"]) && !interface_exists($definition["class"])) {
                throw new ConfigException("Class: '{$definition["class"]}' was referenced but does not exist'");
            }

            if (isset($definition["factoryClass"]) && !class_exists($definition["factoryClass"])) {
                throw new ConfigException("Class: '{$definition["factoryClass"]}' was referenced but does not exist'");
            }

            // Validate factories
            if (!empty($definition["factoryMethod"])) {
                // If factoryMethod is set, then it must have either a factoryClass OR a factoryService, not both
                if (!(isset($definition["factoryClass"]) xor isset($definition["factoryService"]))) {
                    throw new ConfigException(sprintf("The service definition for '%s' should ONE of a factoryClass or a factoryService.", $key));
                }
            }
        }


    }
    /**
     * @return array
     */
    public function getImports() : array
    {
        return $this->data["imports"];
    }

    /**
     * @return array
     */
    public function getParameters() : array
    {
        return $this->data["parameters"];
    }

    /**
     * @return array
     */
    public function getServices() : array
    {
        return $this->data["services"];
    }

    /**
     * @return string|null
     */
    public function getInherit(): ?string
    {
        return $this->data["inherit"];
    }

    public function getExtensions(): array
    {
        return $this->data["extensions"];
    }

    /**
     * @return null|string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    public function toAliasedArray()
    {
        if (is_null($this->alias)) {
            return [
                "parameters" => $this->getParameters(),
                "services" => $this->getServices(),
                "extensions" => $this->getExtensions()
            ];
        }

        $return = [];
        foreach (["parameters", "services", "extensions"] as $type) {
            $return[$type] = [];
            foreach ($this->data[$type] as $k => $value) {
                $return[$type][$this->alias($k)] = $this->recursivelyAlias($value);
            }
        }
        return $return;
    }

    public function getAliasedParameters()
    {
        if (is_null($this->alias)) {
            return $this->getParameters();
        }

        $return = [];
        foreach ($this->getParameters() as $k => $value) {
            $return[$this->alias($k)] = $this->recursivelyAlias($value);
        }
        return $return;
    }

    public function getAliasedServices()
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

    public function getAliasedExtensions()
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
        $regexSeparator = '\\' .Token::ALIAS_SEPARATOR;

        if (preg_match("/^silktide_.*".$regexSeparator.".+$/", $string)) {
            return $string;
        }

        return $this->alias . Token::ALIAS_SEPARATOR . $string;
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

    public function merge(BaseConfig $config)
    {
        // Todo: Possibly throw an exception when we overwrite things within services when aliasOf isn't set.
        // Todo: Work out how aliasOf should effect it anyway...
        foreach ($config->getAliasedParameters() as $k => $v) {
            $this->data["parameters"][$k] = $v;
        }

        foreach ($config->getAliasedServices() as $k => $v) {
            $this->data["services"][$k] = $v;
        }

        foreach ($config->getAliasedExtensions() as $k => $v) {
            $this->data["extensions"][$k] = $v;
        }
    }
}