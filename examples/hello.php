<?php
namespace extSkel\Extension;

class hello
{
    public $namespace = 'namespace';

    public function printHello() : string
    {
        return "Hello";
    }

    public function printParameter(string $paramter)
    {

    }

    public function printWithOptional(string $x, $paramter = null, $y = 'bb')
    {

    }
}
