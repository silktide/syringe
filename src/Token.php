<?php

namespace Silktide\Syringe;

class Token
{
    /**
     * The character that identifies a service
     * e.g. "@service"
     */
    const SERVICE_CHAR = "@";

    /**
     * The character that identifies a collection of service with a specific tag
     * e.g. "#my_tagged_commands"
     */
    const TAG_CHAR = "#";

    /**
     * The character that identifies the boundaries of a parameter
     * e.g. "%parameter%"
     */
    const PARAMETER_CHAR = "%";

    /**
     * The character that identifies a reference to a constant
     * e.g. "^MyCompany\\MyClass::MY_CONSTANT^" or "^STDOUT^"
     */
    const CONSTANT_CHAR = "^";

    /**
     * The character that identifies the boundaries of an environment variable
     * e.g. "$myvariable$"
     */
    const ENV_CHAR = "$";

    /**
     * The cahracter that identifies that something is namespaced
     * e.g. silktide_reposition::client
     */
    const NAMESPACE_SEPARATOR = "::";

}
