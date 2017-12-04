<?php
namespace extSkel;

class extSkel
{
    /**
     * All the supported and available options with
     * it's description used @ printhelp method.
     *
     * @var array
     */
    public $availableOptions = [
        'proto:' => '--proto=file              File contains prototypes of functions to create.',

        'extension::' => '--extension=extname       Module is the name of your extension.',
        'dest-dir::' => '--dest-dir=path           Path to the extension directory.',
        'credits::' => '--credits=author          Credits string to be added to headers.',
        'php-arg::' => '--php-arg=enable          If your extension references something external, use with'
        . PHP_EOL . '                            Otherwise use enable.',

        'help' => '--help                    This message.',
        'no-header' => '--no-header             Don\' append header string to your extension.',
        'fast-zpp' => '--fast-zpp                Use FastZPP API instead of zpp functions.',
        'opt-file' => '--opt-file                Use an options file instead of command-line args.',
    ];

    /**
     * The namespace that all the proto functions will be under it.
     *
     * @var string
     */
    protected $namespace = 'extSkel\Extension';

    /**
     * The functions array which holds the analyzed functions in proto file.
     *
     * @var array
     */
    protected $functions = [];

    /**
     * Extension name.
     *
     * @var string
     */
    public $extensionName;

    /**
     * The array of options.
     *
     * @var array
     */
    protected $options = [
        'php-arg' => 'enable'
    ];

    /**
     * The skeleton  stub file contents.
     *
     * @var string
     */
    public $skeletonStub;

    /**
     * The header skeleton stub file contents.
     *
     * @var string
     */
    public $headerStub;

    /**
     * The config.m4 skeleton stub file contents.
     *
     * @var string
     */
    public $configM4Stub;

    /**
     * The config.w32 skeleton stub file contents.
     *
     * @var string
     */
    public $configW32Stub;

    /**
     * Create a new extSkel instance.
     *
     * @param \AnalyzerInterface $analyzer
     *
     * @return void
     */
    public function __construct(AnalyzerInterface $analyzer)
    {
        $this->analyzer = $analyzer;

        $this->analyzer->setSkeletonStub(
            $this->loadSkeletonStub()
        );

        $this->loadStubs();
    }

    /**
     * get the skeleton stub contents.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function loadSkeletonStub()
    {
        if (file_exists('stubs/skeleton.stub')) {
            return file_get_contents('stubs/skeleton.stub');
        }

        throw new \Exception('Can not load skeleton stub');
    }

    /**
     * Bootstrap function to load stub files.
     *
     * @return void
     *
     */
    public function loadStubs()
    {
        $this->skeletonStub = $this->loadStub('stubs/skeleton.stub');

        $this->headerStub = $this->loadStub('stubs/php_skeleton.stub');

        $this->configM4Stub = $this->loadStub('stubs/config.m4.stub');

        $this->configW32Stub = $this->loadStub('stubs/config.w32.stub');
    }

    /**
     * Function to load stub file.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function loadStub($stub)
    {
        if (file_exists($stub)) {
            return file_get_contents($stub);
        }

        throw new \Exception('Can not load ' . $stub);
    }

    /**
     * Check the sapi that runs the script.
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function checkSapi()
    {
        if (php_sapi_name() === 'cli') {
            return true;
        }

        throw new \Exception("This package only run under \"CLI\" sapi");
    }

    /**
     * Gets the options passed to php via command-line.
     *
     * @return array
     */
    public function getOptions()
    {
        return getopt('', array_flip($this->availableOptions));
    }

    /**
     * Analyzing each option and assign it for a method to perform any required action.
     *
     * @param array $options
     *
     * @return void
     */
    public function analyzeOptions($options)
    {
        foreach ($options as $key => $option) {

            if ($key == 'help' || $key == 'opt-file') {
                continue;
            }

            if ($key == 'proto') {
                // $this->analyzeProto($option, $options);
                $this->protoFile = $option;
                continue;
            }

            $key = str_replace('-', '', $key);

            if (method_exists($this->analyzer, 'analyze' . ucfirst($key))) {
                $this->analyzer->{'analyze' . $key}(
                    (isset($option) ? $option : null), $options
                );
            }
        }
    }

    /**
     * Load the given proto file.
     *
     * @var string $protoFile
     *
     * @return bool
     *
     * @throws \Exception
     */
    private function loadProtoFile($protoFile)
    {
        if (file_exists($protoFile)) {
            require_once($protoFile);
            return true;
        }

        throw new \Exception("please set a valid path to \"proto\" option\n");
    }

    /**
     * call the compile function.
     *
     * @param array $options
     *
     * @return string|bool
     */
    public function run($options)
    {
        if (key_exists('help', $options) or count($options) == 0) {
            return $this->printHelp();
        }

        if (key_exists('opt-file', $options)) {
            $options = $this->parseOptFile($options['opt-file']);
        }

        $options['extension'] = isset($options['extension']) ? $options['extension'] : 'extSkel';
        $options['dest-dir']  = isset($options['dest-dir']) ? $options['dest-dir'] : 'extension/';
        $this->analyzeOptions($options);

        $this->loadProtoFile($options['proto']);

        $this->extensionName = $options['extension'];

        $this->headerStub = file_get_contents('stubs/header.stub');
        $this->footerStub = file_get_contents('stubs/footer.stub');

        $this->extensionName = $options['extension'];

        if (isset($options['credits'])) {
            $this->headerStub = str_ireplace('%credits%', $options['credits'], $this->headerStub);
        } else {
            $this->headerStub = str_ireplace('%credits%', str_pad("extSkel", 60), $this->headerStub);
        }

        $classesBag = $this->analyzer->analyzeProtoFile();
        $this->optionsBag = $options;
        foreach ($classesBag as $class) {
            $this->compileExtension($options, $class, $class['properties']['protoType']);
        }

        if (
            !$this->compileHeaderFile() or
            !$this->compileConfigm4File() or
            !$this->compileConfigw32File()
        ) {
            return false;
        }
        $outputFileName = $this->analyzer->destDir . '/' . $options['extension'] . '.c';
        return file_put_contents(
            $outputFileName,
            trim($this->skeletonStub)
        ) && $this->cleanUp($outputFileName);
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
                $this->skeletonStub = $this->compilerFactory(Compilers\FunctionsCompiler::class, $classInfo);
                break;
            case 'ini':
                $this->skeletonStub = $this->compilerFactory(Compilers\INICompiler::class, $classInfo);
                break;
            case 'class':
                $this->skeletonStub = $this->compilerFactory(Compilers\ClassCompiler::class, $classInfo);
                break;
        }

        $this->skeletonStub = str_ireplace('%extname%', $this->extensionName, $this->skeletonStub);
        $this->skeletonStub = str_ireplace('%extnamecaps%', strtoupper($this->extensionName), $this->skeletonStub);

        return $this->skeletonStub;
    }

    public function compilerFactory($compiler, $classInfo)
    {
        return (new $compiler)
            ->compileSkeleton($this->optionsBag, $classInfo, $this->skeletonStub);
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

        $this->configM4Stub = str_ireplace('%extname%', $this->extensionName, $this->configM4Stub);
        $this->configM4Stub = str_ireplace('%extnamecaps%', strtoupper($this->extensionName), $this->configM4Stub);

        $this->configM4Stub = str_ireplace('%PHPARGCAPS%', strtoupper($phpArg), $this->configM4Stub);
        $this->configM4Stub = str_ireplace('%PHPARG%', $phpArg, $this->configM4Stub);

        if ($this->options['php-arg'] == 'with') {
            $this->configM4Stub = str_ireplace('%PHPARGMSGHEAD%', "for {$this->extensionName} support", $this->configM4Stub);
            $this->configM4Stub = str_ireplace('%PHPARGMSG%', "Include {$this->extensionName} support", $this->configM4Stub);
        } else {
            $this->configM4Stub = str_ireplace('%PHPARGMSGHEAD%', "whether to enable {$this->extensionName} support", $this->configM4Stub);
            $this->configM4Stub = str_ireplace('%PHPARGMSG%', "Enable {$this->extensionName} support", $this->configM4Stub);
        }

        return file_put_contents($this->analyzer->destDir . '/' . $configm4, $this->configM4Stub);
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

        $this->configW32Stub = str_ireplace('%extname%', $this->extensionName, $this->configW32Stub);
        $this->configW32Stub = str_ireplace('%extnamecaps%', strtoupper($this->extensionName), $this->configW32Stub);

        $this->configW32Stub = str_ireplace('%PHPARGCAPS%', strtoupper($this->options['php-arg']), $this->configW32Stub);

        return file_put_contents($this->analyzer->destDir . '/' . $configw32, $this->configW32Stub);
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

        $this->headerStub = file_get_contents('stubs/header.stub');
        $this->footerStub = file_get_contents('stubs/footer.stub');

        $skeleton = str_ireplace('%extname%', $this->extensionName, $skeleton);
        $skeleton = str_ireplace('%extnamecaps%', strtoupper($this->extensionName), $skeleton);

        if (!isset($this->options['no-header'])) {
            $skeleton = str_ireplace('%header%', $this->headerStub, $skeleton);
        } else {
            $skeleton = str_ireplace('%header%', '', $skeleton);
        }

        $skeleton = str_ireplace('%footer%', $this->footerStub, $skeleton);
        $skeleton = str_ireplace('%year%', date("Y"), $skeleton);

        return file_put_contents($this->analyzer->destDir . '/' . $phpHeader, $skeleton);
    }

    /**
     * this function perform a clean up for the output
     * to make sure that there are no place holders exists
     * after the compiling
     *
     * @TODO create a simple factory object to navigate between
     * [sed, php pcre, internal str_ireplace function]
     *
     * @param string $outputFileName
     *
     * @return void
     *
     * @throws \Exception
     */
    public function cleanUp($outputFileName)
    {
        if (shell_exec('command -v sed')) {
            exec("sed -r -i 's/\%.*?%//g' $outputFileName");
        } else {
            throw new \Exception("sed program is not exists");
        }
    }

    /**
     * Decode the provided options json file.
     *
     * @param string $optFile
     *
     * @return array
     *
     * @throws \Exception
     */
    public function parseOptFile($optFile)
    {
        if (file_exists($optFile) == false) {
            throw new \Exception("Opt file does not exsists.");
        }

        if (extension_loaded('json') == false) {
            throw new \Exception(
                "Json extension not found, you may use command-line options instead."
            );
        }

        return json_decode(
            file_get_contents($optFile), true
        );
    }

    /**
     * a Helper method to print a help message to the stdout.
     *
     * @return string
     */
    public function printHelp()
    {
        $help = [];

        $help[] = 'php extSkel --proto="path/to/file" [--extension=extname] [--dest-dir=path]';
        $help[] = '            [--credits="author name"] [--no-header] [--fast-zpp] [--php-arg="with|enable"]';
        $help[] = '';
        foreach ($this->availableOptions as $key => $option) {
            $help[] = '  ' . $option;
        }

        echo implode(PHP_EOL, $help) . "\n";
    }
}
