<?php
namespace extSkel\Compilers;

abstract class AbstractCompiler
{
    const TAB = '    ';

    /**
     * Compiles the PHP_FUNCTION stub.
     *
     * @return string
     */
    public function internalFunctionsCompiler($function)
    {
        $stub = file_get_contents($this->stubPath);

        $paramtersCompiler = new ParametersCompiler($function, $this->extension, $this->parametersApi);

        $parametersStub = $paramtersCompiler->compile();

        $stub = str_ireplace('%PARAMETERS_STUB%', $parametersStub, $stub);
        $stub = str_ireplace('%FUNCNAME%', $function['name'], $stub);

        return $stub;
    }
}
