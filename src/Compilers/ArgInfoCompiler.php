<?php
namespace extSkel\Compilers;

class ArgInfoCompiler extends AbstractCompiler
{
    /**
     * The extension name.
     *
     * @var string
     */
    private $extension;

    /**
     * The functions list.
     *
     * @var array
     */
    private $functions = [];

    /**
     * Create a new ArginfoCompiler instance.
     *
     * @param array $functions
     * @param string $extension
     *
     * @return void
     */
    public function __construct($functions, $extension)
    {
        $this->functions = $functions;
        $this->extension = $extension;
    }

    /**
     * Compiles each function into an arginfo compilers.
     *
     * @return string
     */
    public function compile()
    {
        $output = [];
        foreach ($this->functions as $key => $function) {
            $output[$key] = $this->internalCompiler($function);
        }

        return implode(PHP_EOL, $output);
    }

    /**
     * Compiles an array of functions into arg info macros.
     *
     * @return string
     */
    public function internalCompiler($function)
    {
        $stub = file_get_contents('stubs/arginfo.stub');

        $argInfoStub = [];
        if ($function['parametersCount'] > 0) {
            foreach ($function['parameters'] as $parameter) {
                $argInfoStub[] = self::TAB . "ZEND_ARG_INFO(0, {$parameter['type']}_{$parameter['name']})";
            }
        }

        $stub = str_ireplace('%ARGINFO%', implode(PHP_EOL, $argInfoStub), $stub);
        $stub = str_ireplace('%EXTNAME%', $this->extension, $stub);
        $stub = str_ireplace('%FUNCNAME%', $function['name'], $stub);

        return $stub;
    }
}
