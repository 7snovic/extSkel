<?php
namespace extSkel\Extension;

class iniDirectives
{
    public $namespace = 'namespace';

    public $protoType = 'ini';

    public $entries = [
        'enable' => true,
        'limit' => '50MB'
    ];
}

class helloExtension
{
    public $namespace = 'namespace';

    public $protoType = 'functions';

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
