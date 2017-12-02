<?php
namespace extSkel;

class Analyzer implements AnalyzerInterface
{
    /**
     * The namespace that all the proto functions will be under it.
     *
     * @var string
     */
    protected $namespace = 'extSkel\Extension';

    /**
     * The array of options.
     *
     * @var array
     */
    protected $options = [
        'php-arg' => 'enable'
    ];

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

    public function analyzeProtoFile()
    {
        $classes = get_declared_classes();
        $properties = [];
        foreach ($classes as $classKey => $className) {
            $class = new \ReflectionClass($className);
            if (strstr($class->getNamespaceName(), $this->namespace) !== false && $class->isInternal() === false) {

                $protoType = $class->getDefaultProperties()['protoType'];

                $classInfo[$classKey]['class'] = $class->getName();

                $classInfo[$classKey]['methods'] = $this->filterFunctions($class->getMethods(\ReflectionMethod::IS_PUBLIC));
                $properties = $class->getDefaultProperties();

                if (isset($properties['namespace'])) {
                    $classInfo[$classKey]['namespace'] = $properties['namespace'];
                }
                if (isset($properties['className'])) {
                    $classInfo[$classKey]['className'] = $properties['className'];
                } else {
                    $classInfo[$classKey]['className'] = $class->getShortName();
                }

                $classInfo[$classKey]['properties'] = $properties;
            }
        }
        return $classInfo;
    }

    private function filterFunctions($definedFunctions)
    {
        $functions = [];
        foreach ($definedFunctions as $key => $function) {

            if (in_array($function->name, get_defined_functions()['internal'])) {
                throw new \Exception("Illegal function name\n");
            }

            $functionReflection = new \ReflectionMethod($function->class, $function->name);
            if ($functionReflection->isUserDefined()) {
                $functions[$key]['name'] = $functionReflection->getShortName();
                if (isset($definedFunctions['namespace'])) {
                    $functions[$key]['namespace'] = $definedFunctions['namespace'];
                }
                $functions[$key]['parametersCount'] = $functionReflection->getNumberOfParameters();
                $functions[$key]['requiredParametersCount'] = $functionReflection->getNumberOfRequiredParameters();
                $this->parameters = [];
                foreach ($functionReflection->getParameters() as $paramterKey => $paramter) {
                    $this->parameters[$paramterKey]['name'] = $paramter->name;
                    $this->parameters[$paramterKey]['type'] = ($paramter->hasType() ? "{$paramter->getType()}" : null);
                    $this->parameters[$paramterKey]['isRequired'] = $paramter->isOptional() ? 0 : 1;
                }
                $functions[$key]['parameters'] = $this->parameters;
            }
        }

        return $functions;
    }
}
