<?php
namespace extSkel\Compilers;

class FunctionsCompiler extends AbstractCompiler
{

    /**
     * The extension name.
     *
     * @var string
     */
    private $extension;

    /**
     * The parameter api string.
     *
     * @var string
     */
    private $parametersApi;

    /**
     * The functions list.
     *
     * @var array
     */
    private $functions = [];

    /**
     * Create a new FunctionsCompiler instance.
     *
     * @param array $functions
     * @param string $extension
     * @param string $parametersApi
     *
     * @return void
     */
    public function init($functions, $extension, $parametersApi)
    {
        $this->functions = $functions;
        $this->extension = $extension;
        $this->parametersApi = $parametersApi;

        return $this;
    }

    /**
     * Compiles the PHP_FUNCTION & functions entry stub.
     *
     * @return array
     */
    public function compile()
    {
        $functionEntriesStub = file_get_contents('stubs/zend_function_entry.stub');
        $output = [];
        foreach ($this->functions as $key => $function) {
            $output[$key] = $this->internalCompiler($function);
            $functionEntries[$key] = $this->entriesCompiler($function);
        }

        return [
            'functions' => implode(PHP_EOL, $output),
            'functions_entry' => str_ireplace(
                '%FUNCTIONS_ENTRIES%',
                implode(PHP_EOL, $functionEntries),
                $functionEntriesStub
            )
        ];
    }

    /**
     * Compiles the functions entry stub.
     *
     * @param array $function
     *
     * @return array
     */
    protected function entriesCompiler($function)
    {
        $entries = [];
        if (isset($function['namespace'])) {
            $entries[] = "ZEND_NS_NAMED_FE('{$function['namespace']}',";
            $entries[] = "{$function['name']},";
            $entries[] = "PHP_FN({$this->extension}_{$function['name']}),";
            $entries[] = "arginfo_{$this->extension}_{$function['name']})";
        } else {
            $entries[] = "PHP_FE({$this->extension}_{$function['name']},";
            $entries[] = "arginfo_{$this->extension}_{$function['name']})";
        }

        return self::TAB . implode(' ', $entries);
    }

    /**
     * Compiles the PHP_FUNCTION stub.
     *
     * @return string
     */
    public function internalCompiler($function)
    {
        $stub = file_get_contents('stubs/functions.stub');

        $paramtersCompiler = new ParametersCompiler($function, $this->extension, $this->parametersApi);

        $parametersStub = $paramtersCompiler->compile();

        $stub = str_ireplace('%PARAMETERS_STUB%', $parametersStub, $stub);
        $stub = str_ireplace('%FUNCNAME%', $function['name'], $stub);

        return $stub;
    }

    /**
     * Compile functions skeleton
     *
     * @param array $options
     * @param array $classInfo
     * @param string $skeleton
     *
     * @return string
     */
    public function compileSkeleton($options, $classInfo, $skeleton)
    {
        $functions = $classInfo['methods'];

        $argInfoStub = $functionsStub = '';
        if (isset($functions)) {

            if (key_exists('fast-zpp', $options)) {
                $this->parametersApi = 'fastzpp';
            }

            $functionsCompiler = $this->init($functions, $options['extension'], $this->parametersApi);
            $functionsStub = $this->compile();

            $argInfoCompiler = new ArgInfoCompiler($functions, $options['extension']);
            $argInfoStub = $argInfoCompiler->compile();
        }

        $skeleton = str_ireplace('%arginfo_stub%', $argInfoStub, $skeleton);
        $skeleton = str_ireplace('%functions_stub%', $functionsStub['functions'], $skeleton);
        $skeleton = str_ireplace('%functions_entry_stub%', $functionsStub['functions_entry'], $skeleton);

        $skeleton = str_ireplace('%year%', date("Y"), $skeleton);

        return $skeleton;
    }
}
