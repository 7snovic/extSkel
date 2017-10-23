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
     * Destination directory.
     *
     * @var string
     */
    public $destDir;


    /**
     * Variable that holds the skeleton stub string
     *
     * @var string
     */
    public $skeletonStub;

    /**
     * Variable that holds the header stub string.
     *
     * @var string
     */
    private $headerStub;

    /**
     * Variable that holds the footer stub string.
     *
     * @var string
     */
    private $footerStub;

    /**
     * Extension name.
     *
     * @var string
     */
    public $extensionName;
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

    /**
     * Analyze the php-arg option.
     *
     * @return void
     */
    public function analyzePhpArg($phpArg)
    {
        $this->options['php-arg'] = $phpArg;
    }

    public function setSkeletonStub($skeleton)
    {
        $this->skeletonStub = $skeleton;

        return $skeleton;
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
            !$this->compileHeaderFile()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Compile the extension skeleton body.
     *
     * @param array $options
     * @param array $classInfo
     * @param string $protoType
     *
     * @return bool
     */
    public function compileExtension($options, $classInfo, $protoType)
    {
        if (!isset($options['no-header'])) {
            $skeleton = str_ireplace('%header%', $this->headerStub, $this->skeletonStub);
        } else {
            $this->skeletonStub = str_ireplace('%header%', '', $this->skeletonStub);
        }

        $this->skeletonStub = str_ireplace('%footer%', $this->footerStub, $this->skeletonStub);

        switch ($protoType) {
            case 'functions':
                $this->skeletonStub = (new FunctionsAnalyzer)->compileSkeleton($options, $classInfo, $this->skeletonStub);
                break;
            case 'ini':
                $this->skeletonStub = (new INIAnalyzer)->compileSkeleton($options, $classInfo, $this->skeletonStub);
                break;
            case 'class':
                $this->skeletonStub = (new ClassAnalyzer)->compileSkeleton($options, $classInfo, $this->skeletonStub);
                break;
        }

        $this->skeletonStub = str_ireplace('%extname%', $this->extensionName, $this->skeletonStub);
        $this->skeletonStub = str_ireplace('%extnamecaps%', strtoupper($this->extensionName), $this->skeletonStub);

        return $this->skeletonStub;
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
}
