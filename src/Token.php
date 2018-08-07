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
     * This is a terrible choice of separator. It would be much better if this was separated by something like /
     * but that has many more things that could potentially break
     * Todo: If we change this, we will also need to change the regex at line 205 of FileConfig
     */
    const ALIAS_SEPARATOR = "::";

}
