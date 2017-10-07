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
     * Variable that holds the skeleton stub string
     *
     * @var string
     */
    public $skeletonStub;

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

        $this->skeletonStub = $this->loadSkeletonStub();
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
     * Analyze proto file and return a detailed array holding each function
     * with it's parameters.
     *
     * @var string $protoFile
     * @var array $options
     *
     * @return void
     *
     * @throws \Exception
     */
    public function analyzeProto($protoFile, $options)
    {
        try {
            $this->loadProtoFile($protoFile);

            $classInfo = [];
            foreach (get_declared_classes() as $className) {
                $class = new \ReflectionClass($className);
                if (strstr($class->getNamespaceName(), $this->namespace) !== false && $class->isInternal() === false) {

                    $protoType = $class->getDefaultProperties()['protoType'];

                    $classInfo['class'] = $class->getName();
                    $classInfo['namespace'] = $class->getProperty('namespace')->getName();
                    $classInfo['methods'] = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

                    $this->filterFunctions($classInfo);

                    $this->analyzer->compile($options, $this->functions, $this->parameters);
                }
            }
            // return $classInfo;


        } catch (\Exception $e) {
            echo $e->getMessage();
            exit;
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
     * Get all the user defined functions from the user space.
     *
     * @return array
     */
    private function getFunctions()
    {
        $classInfo = [];
        foreach (get_declared_classes() as $className) {
            $class = new \ReflectionClass($className);
            if (strstr($class->getNamespaceName(), $this->namespace) !== false && $class->isInternal() === false) {
                $classInfo['class'] = $class->getName();
                $classInfo['namespace'] = $class->getProperty('namespace')->getName();
                $classInfo['methods'] = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
            }
        }
        return $classInfo;
    }

    /**
     * Filter the defined functions and extract only the function under the self::$namespace.
     *
     * @param array $definedFunctions
     *
     * @return void
     */
    private function filterFunctions($definedFunctions)
    {
        foreach ($definedFunctions['methods'] as $key => $function) {

            if (in_array($function->name, get_defined_functions()['internal'])) {
                throw new \Exception("Illegal function name\n");
            }

            $functionReflection = new \ReflectionMethod($function->class, $function->name);
            if ($functionReflection->isUserDefined()) {
                $this->functions[$key]['name'] = $functionReflection->getShortName();
                $this->functions[$key]['namespace'] = $definedFunctions['namespace'];
                $this->functions[$key]['parametersCount'] = $functionReflection->getNumberOfParameters();
                $this->functions[$key]['requiredParametersCount'] = $functionReflection->getNumberOfRequiredParameters();
                $this->parameters = [];
                foreach ($functionReflection->getParameters() as $paramterKey => $paramter) {
                    $this->parameters[$paramterKey]['name'] = $paramter->name;
                    $this->parameters[$paramterKey]['type'] = ($paramter->hasType() ? "{$paramter->getType()}" : null);
                    $this->parameters[$paramterKey]['isRequired'] = $paramter->isOptional() ? 0 : 1;
                }
                $this->functions[$key]['parameters'] = $this->parameters;
            }
        }
    }

    /**
     * call the compile function.
     *
     * @param array $options
     *
     * @return string|\hassan\extSkel\AnalyzerInterface
     */
    public function run($options)
    {
        if (key_exists('help', $options) or count($options) == 0) {
            return $this->printHelp();
        } elseif (key_exists('opt-file', $options)) {
            $options = $this->parseOptFile($options['opt-file']);
        }

        $options['extension'] = isset($options['extension']) ? $options['extension'] : 'extSkel';
        $options['dest-dir']  = isset($options['dest-dir']) ? $options['dest-dir'] : 'extension/';
        $this->analyzeOptions($options);
        // $this->analyzeProto($this->protoFile, $options);
        $this->loadProtoFile($options['proto']);

        $classInfo = [];
        foreach (get_declared_classes() as $className) {
            $class = new \ReflectionClass($className);
            if (strstr($class->getNamespaceName(), $this->namespace) !== false && $class->isInternal() === false) {

                $protoType = $class->getDefaultProperties()['protoType'];

                $classInfo['class'] = $class->getName();
                $classInfo['namespace'] = $class->getProperty('namespace')->getName();
                $classInfo['methods'] = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
                $classInfo['properties'] = $class->getDefaultProperties();

                $this->analyzer->compile($options, $classInfo, $protoType);
            }
        }
        return 1;
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
