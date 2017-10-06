<?php
namespace extSkel;

class Analyzer implements AnalyzerInterface
{
    /**
     * The user defined functions which will be in the proto file.
     *
     * @var array
     */
    protected $definedFunctions = [];

    /**
     * The functions array which holds the analyzed functions in proto file.
     *
     * @var array
     */
	protected $functions = [];

    /**
     * The parameters array which holds the analyzed functions in proto file.
     *
     * @var array
     */
	protected $parameters = [];

    /**
     * The array of options.
     *
     * @var array
     */
    protected $options = [
        'php-arg' => 'enable'
    ];

    /**
     * The used parameters API.
     *
     * @see http://www.phpinternalsbook.com/php7/extensions_design/php_functions.html
     * @var string
     */
    protected $parametersApi = 'zpp';

    /**
     * Analyze the no-header option.
     *
     * @return void
     */
    public function analyzeNoheader()
    {
        $this->options['no-header'] = true;
    }

    /**
     * Analyze the credits option.
     *
     * @param string $credits
     * @param array $options
     *
     * @return void
     */
    public function analyzeCredits($credits, $options)
    {
        $this->options['credits'] = $credits;
    }

    /**
     * Analyze the dest-dir option.
     *
     * @param string $credits
     * @param array $options
     *
     * @return void
     *
     * @throws \Exception
     */
    public function analyzeDestDir($directory, $options)
    {
        if (!is_dir($directory)) {
            throw new \Exception("Destination directory does not exists.\n");
        }
        if (is_dir($directory . $options['extension'])) {
            throw new \Exception("The extension name is allready exists.\n");
        }

        $directory = rtrim($options['dest-dir'], '/');

        mkdir($extensionPath = $directory . '/' . $options['extension'], 0755);

        $this->destDir = $this->option['dest-dir'] = $extensionPath;
    }

    public $destDir;

    /**
     * Analyze the php-arg option.
     *
     * @return void
     */
    public function analyzePhpArg($phpArg)
    {
        $this->options['php-arg'] = $phpArg;
    }

    /**
     * Compile the extension skeleton and the header file skeleton.
     *
     * @param array $options
     *
     * @return bool
     */
    public function compile($options, $classInfo, $protoType)
    {
        // $this->functions = $functions;
        // $this->parameters = $parameters;
        $this->headerStub = file_get_contents('stubs/header.stub');
        $this->footerStub = file_get_contents('stubs/footer.stub');

        $this->extensionName = $options['extension'];

        if (isset($options['credits'])) {
            $this->headerStub = str_ireplace('%credits%', $options['credits'], $this->headerStub);
        } else {
            $this->headerStub = str_ireplace('%credits%', str_pad("extSkel", 60), $this->headerStub);
        }

        if (
            !$this->compileExtension($options, $classInfo, $protoType) or
            !$this->compileHeaderFile() or
            !$this->compileConfigm4File() or
            !$this->compileConfigw32File()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Compile the extension skeleton body.
     *
     * @param array $options
     *
     * @return bool
     */
    public function compileExtension($options, $classInfo, $protoType)
    {
        $skeleton = file_get_contents('stubs/skeleton.stub');

        if (!isset($options['no-header'])) {
            $skeleton = str_ireplace('%header%', $this->headerStub, $skeleton);
        } else {
            $skeleton = str_ireplace('%header%', '', $skeleton);
        }

        $skeleton = str_ireplace('%footer%', $this->footerStub, $skeleton);

        switch ($protoType) {
            case 'functions':
                $skeleton = (new FunctionsAnalyzer)->compileSkeleton($options, $classInfo, $skeleton);
        }

        $skeleton = str_ireplace('%extname%', $this->extensionName, $skeleton);
        $skeleton = str_ireplace('%extnamecaps%', strtoupper($this->extensionName), $skeleton);


        return file_put_contents($this->destDir . '/' . $this->extensionName . '.c', trim($skeleton));
    }

    /**
     * Compile the header file body.
     *
     * @return bool
     */
    public function compileHeaderFile()
    {
        $skeleton = file_get_contents('stubs/php_skeleton.stub');
        $phpHeader = "php_{$this->extensionName}.h";

        $skeleton = str_ireplace('%extname%', $this->extensionName, $skeleton);
        $skeleton = str_ireplace('%extnamecaps%', strtoupper($this->extensionName), $skeleton);

        if (!isset($this->options['no-header'])) {
            $skeleton = str_ireplace('%header%', $this->headerStub, $skeleton);
        } else {
            $skeleton = str_ireplace('%header%', '', $skeleton);
        }

        $skeleton = str_ireplace('%footer%', $this->footerStub, $skeleton);
        $skeleton = str_ireplace('%year%', date("Y"), $skeleton);

        return file_put_contents($this->destDir . '/' . $phpHeader, $skeleton);
    }

    /**
     * Compile config.m4 file.
     *
     * @return bool
     */
    public function compileConfigm4File()
    {
        $skeleton = file_get_contents('stubs/config.m4.stub');
        $configm4 = "config.m4";
        $phpArg = $this->options['php-arg'];

        $skeleton = str_ireplace('%extname%', $this->extensionName, $skeleton);
        $skeleton = str_ireplace('%extnamecaps%', strtoupper($this->extensionName), $skeleton);

        $skeleton = str_ireplace('%PHPARGCAPS%', strtoupper($phpArg), $skeleton);
        $skeleton = str_ireplace('%PHPARG%', $phpArg, $skeleton);

        if ($this->options['php-arg'] == 'with') {
            $skeleton = str_ireplace('%PHPARGMSGHEAD%', "for {$this->extensionName} support", $skeleton);
            $skeleton = str_ireplace('%PHPARGMSG%', "Include {$this->extensionName} support", $skeleton);
        } else {
            $skeleton = str_ireplace('%PHPARGMSGHEAD%', "whether to enable {$this->extensionName} support", $skeleton);
            $skeleton = str_ireplace('%PHPARGMSG%', "Enable {$this->extensionName} support", $skeleton);
        }

        return file_put_contents($this->destDir . '/' . $configm4, $skeleton);
    }

    /**
     * Compile config.w32 file.
     *
     * @return bool
     */
    public function compileConfigw32File()
    {
        $skeleton = file_get_contents('stubs/config.w32.stub');
        $configw32 = "config.w32";

        $skeleton = str_ireplace('%extname%', $this->extensionName, $skeleton);
        $skeleton = str_ireplace('%extnamecaps%', strtoupper($this->extensionName), $skeleton);

        $skeleton = str_ireplace('%PHPARGCAPS%', strtoupper($this->options['php-arg']), $skeleton);

        return file_put_contents($this->destDir . '/' . $configw32, $skeleton);
    }
}
