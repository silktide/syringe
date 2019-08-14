<?php

namespace Silktide\Syringe;

use Silktide\Syringe\Exception\ConfigException;

class CompiledConfigBuilder
{
    public function build(MasterConfig $masterConfig, array $parameters = [])
    {
        $parameterResolver = new ParameterResolver();

        $abstractServices = [];
        $aliases = [];

        $services = $masterConfig->getServices();
        $parameters = array_merge($masterConfig->getParameters(), $parameters);
        // These'll get run too as part of this!
        $extensions = $masterConfig->getExtensions();
        $tags = [];

        // Deal with abstract functions
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

            // We allow a service to extend an abstract service which extends an abstract service
            while (!empty($definition["extends"])) {
                $abstractKey = mb_substr($definition["extends"], 1);
                if (!isset($abstractServices[$abstractKey])) {
                    $errorMessage = "The service definition for '%s' extends '%s' but there is no abstract definition of that name";
                    throw new ConfigException(sprintf($errorMessage, $key, $abstractKey));
                }

                $abstractDefinition = $abstractServices[$abstractKey];
                unset($definition["extends"]);

                foreach ($abstractDefinition as $abstractKey => $value) {
                    if ($abstractKey === "abstract") {
                        continue;
                    }

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


        foreach ($parameters as $key => $value) {
            // Resolve all the parameters up front
            $parameters[$key] = $parameterResolver->resolve($parameters, $value);
        }

        foreach ($services as $serviceName => &$definition) {
            if (isset($definition["arguments"])) {
                $definition["arguments"] = $parameterResolver->resolveArray($parameters, $definition["arguments"]);
            }

            if (isset($definition["calls"])) {
                foreach ($definition["calls"] as &$call) {
                    if (isset($call["arguments"])) {
                        $call["arguments"] = $parameterResolver->resolveArray($parameters, $call["arguments"]);
                    }
                }
            }
        }

        return new CompiledConfig([
            "services" => $services,
            "aliases" => $aliases,
            "parameters" => $parameters,
            "tags" => $tags,
            "state" => [
                "files" => FileStateCollection::build($masterConfig->getFilenames()),
                "constants" => $parameterResolver->getResolvedConstants(),
                "envVars" => $parameterResolver->getResolvedEnvVars()
            ]
        ]);
    }
}