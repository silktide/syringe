<?php


namespace Silktide\Syringe;


use Silktide\Syringe\Exception\ConfigException;

class CompiledConfigBuilder
{
    public function build(MasterConfig $masterConfig)
    {
        $abstractServices = [];
        $aliases = [];

        $services = $masterConfig->getServices();
        $parameters = $masterConfig->getParameters();
        // These'll get run too as part of this!
        $extensions = $masterConfig->getExtensions();
        $tags = [];

        foreach ($services as $key => $definition) {
            if (!empty($definition["abstract"])) {
                $abstractServices[$key] = $definition;
                unset($services[$key]);
            }
        }

        foreach ($services as $key => &$definition) {
            if (!empty($definition["aliasOf"])) {
                $aliasOf = ltrim($definition["aliasOf"], Token::SERVICE_CHAR);
                $aliases[$key] = $definition["aliasOf"];

                if (!isset($services[$aliasOf])) {
                    throw new ConfigException(sprintf("The service definition for %s is an alias for '%s' but this service is not found", $key, $aliasOf));
                }
                continue;
            }

            if (!empty($definition["extends"])) {
                $abstractKey = mb_substr($definition["extends"], 1);
                if (!isset($abstractServices[$abstractKey])) {
                    $errorMessage = "The service definition for '%s' extends '%s' but there is no abstract definition of that name";
                    throw new ConfigException(sprintf($errorMessage, $key, $abstractKey));
                }

                $abstractDefinition = $abstractServices[$abstractKey];

                foreach ($abstractDefinition as $abstractKey => $value) {
                    if (isset($definition[$abstractKey])) {
                        if ($abstractKey === "calls") {
                            $definition[$abstractKey] = array_merge($definition[$abstractKey], $value);
                        }
                        continue;
                    }

                    $definition[$abstractKey] = $value;
                }
            }

            if (isset($definition["tags"])) {
                foreach ($definition["tags"] as $tagKey => $value) {
                    // Our standard tag format looks like thus:
                    //  tags:
                    //    - 'foo'
                    // The alias format used to look like this
                    //  tags:
                    //    - 'foo': "mytag"
                    // We normalise them here to be sane
                    list($tag, $alias) = is_int($tagKey) ? [$value, null] : [$tagKey, $value];

                    $tags[$tag][] = [
                        "service" => $key,
                        "alias" => $alias
                    ];
                }
            }
        }
        unset($definition);

        foreach ($extensions as $serviceName => $extensionCalls) {
            if (!isset($services[$serviceName])) {
                throw new ConfigException(sprintf("Cannot use extension for the service '%s' as it does not exist", $serviceName));
            }

            $services[$serviceName]["calls"] = array_merge($services[$serviceName]["calls"] ?? [], $extensionCalls);
        }

        return new CompiledConfig($services, $aliases, $parameters, $tags);
    }
}