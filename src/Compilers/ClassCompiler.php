<?php
namespace extSkel\Compilers;

class ClassCompiler extends AbstractCompiler
{

    /**
     * The extension name.
     *
     * @var string
     */
    protected $extension;

    /**
     * The parameter api string.
     *
     * @var string
     */
    protected $parametersApi;

    /**
     * The functions list.
     *
     * @var array
     */
    protected $functions = [];

    protected $stubPath;
    protected $classInfo;

    /**
     * Create a new FunctionsCompiler instance.
     *
     * @param array $classInfo
     * @param string $extension
     * @param string $parametersApi
     *
     * @return void
     */
    public function init($classInfo, $extension, $parametersApi)
    {
        $this->functions = $classInfo['methods'];
        $this->extension = $extension;
        $this->parametersApi = $parametersApi;
        $this->className = $classInfo['className'];
        $this->qualifiedClassName = $this->getQualifiedClassName($classInfo);

        $this->stubPath = 'stubs/methods.stub';

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
        $functionEntries = [];
        foreach ($this->functions as $key => $function) {
            $output[$key] = $this->internalFunctionsCompiler($function);
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
        return self::TAB . "PHP_FE({$this->extension}_{$function['name']}, arginfo_{$this->extension}_{$function['name']})";
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

            $functionsCompiler = $this->init($classInfo, $options['extension'], $this->parametersApi);
            $functionsStub = $this->compile();

            $argInfoCompiler = new ArgInfoCompiler($functions, $options['extension']);
            $argInfoStub = $argInfoCompiler->compile();
        }

        $skeleton = str_ireplace('%arginfo_stub%', $argInfoStub, $skeleton);
        $skeleton = str_ireplace('%functions_stub%', $functionsStub['functions'], $skeleton);
        $skeleton = str_ireplace('%functions_entry_stub%', $functionsStub['functions_entry'], $skeleton);

        $skeleton = str_ireplace('%classname%', $classInfo['className'], $skeleton);

        $skeleton = str_ireplace('%zend_class_entry%', $this->getZendClassEntry(), $skeleton);
        $skeleton = str_ireplace('%register_class_entry%', $this->getMinitStub(), $skeleton);
        $skeleton = str_ireplace('%zend_function_entry%', 'NULL', $skeleton);

        $skeleton = str_ireplace('%year%', date("Y"), $skeleton);

        return $skeleton;
    }

    private function getZendClassEntry()
    {
        return 'zend_class_entry *' . $this->className . '_ce;';
    }

    private function getQualifiedClassName($classInfo)
    {
        if (isset($classInfo['namespace'])) {
            return $classInfo['namespace'] . '\\\\' . $classInfo['className'];
        } else {
            return $classInfo['className'];
        }
    }

    private function getMinitStub()
    {
        return <<<STUB
    zend_class_entry tmp_ce;
        INIT_CLASS_ENTRY(tmp_ce, "$this->qualifiedClassName", {$this->extension}_functions);

        {$this->className}_ce = zend_register_internal_class(&tmp_ce TSRMLS_CC);
STUB;
    }
}
