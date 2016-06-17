<?php

class MySubClass
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function greet()
    {
        echo $this->message."\n";
    }
}

class MyMainClass
{
    protected $mySubClass;

    public function __construct(MySubClass $mySubClass)
    {
        $this->mySubClass = $mySubClass;
    }

    public function doSomething()
    {
        $this->mySubClass->greet();
    }
}

