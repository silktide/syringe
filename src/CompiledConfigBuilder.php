<?php


namespace Silktide\Syringe;


use Silktide\Syringe\Exception\ConfigException;

class CompiledConfigBuilder
{
    public function build(BaseConfig $baseConfig)
    {

        // So the idea of compiled config should be to take anything that says anything like extends and glomp it
        // together

        /*foreach ($baseConfig->getParameters() as $key => $value) {
            $container[$key] = function() use ($container, $key, $value) {
                try{
                    return $this->referenceResolver->resolve($container, $value);
                } catch (ReferenceException $e) {
                    throw new ReferenceException("Error with key '$key'. " . $e->getMessage());
                }
            };
        }*/

        //
        // Do the services!
        //


        $abstractServices = [];
        $aliases = [];

        $services = $baseConfig->getServices();
        $parameters = $baseConfig->getParameters();
        // These'll get run too as part of this!
        $extensions = $baseConfig->getExtensions();
        $tags = [];

        foreach ($services as $key => $definition) {
            if (!empty($definition["abstract"])) {
                $abstractServices[$key] = $definition;
                unset($services[$key]);
            }
        }

        //$parameters = $this->
        // We'll want to recursively build up the parameters to something actual
        // Also, any constants we use OR any environment variables will need special handling
        //foreach ($parameters as $key => $definition) {

        //}

        //print_r($parameters);
        //die();

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
                foreach ($definition["tags"] as $tag) {
                    $tags[$tag][] = $key;
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