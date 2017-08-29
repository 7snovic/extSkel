<?php
namespace hassan\extSkel;

use hassan\extSkel\Compilers\ArgInfoCompiler;
use hassan\extSkel\Compilers\FunctionsCompiler;

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
    protected $options = [];

    /**
     * The used parameters API.
     *
     * @see http://www.phpinternalsbook.com/php7/extensions_design/php_functions.html
     * @var string
     */
    protected $parametersApi = 'zpp';

    /**
     * The namespace that all the proto functions will be under it.
     *
     * @var string
     */
    protected $namespace = 'hassan\extSkel\Extension';

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

            $definedFunctions = $this->getFunctions();

            $this->filterFunctions($definedFunctions);
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
		return $this->definedFunctions = get_defined_functions()['user'];
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
		foreach ($definedFunctions as $key => $function) {
			$functionReflection = new \ReflectionFunction($function);
			if (strstr($functionReflection->getNamespaceName(), $this->namespace) === false) {
				continue;
			}
            if ($functionReflection->isUserDefined()) {
                $this->functions[$key]['name'] = $functionReflection->getShortName();
    			$this->functions[$key]['namespace'] = $functionReflection->getNamespaceName();
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
     * Analyze the dump-header option.
     *
     * @return void
     */
    public function analyzeDumpheader()
    {
        $this->options['dump-header'] = true;
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
            throw new \Exception('Destination directory does not exists.');
        }
        if (is_dir($directory . $options['extenstion'])) {
            throw new \Exception('The extension name is allready exists.');
        }

        $this->option['dest-dir'] = $directory;
    }

    /**
     * Compile the extension skeleton and the header file skeleton.
     *
     * @param array $options
     *
     * @return bool
     */
    public function compile($options)
    {
        $this->headerStub = file_get_contents('stubs/header.stub');
        $this->footerStub = file_get_contents('stubs/footer.stub');

        $this->extensionName = $options['extension'];

        if (isset($options['credits'])) {
            $this->headerStub = str_ireplace('%credits%', $options['credits'], $this->headerStub);
        } else {
            $this->headerStub = str_ireplace('%credits%', str_pad("extSkel", 60), $this->headerStub);
        }

        $this->destDir = $options['dest-dir'];

        if (
            !$this->compileExtension($options) or
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
    public function compileExtension($options)
    {
        $skeleton = file_get_contents('stubs/skeleton.stub');

        if (isset($options['dump-header'])) {
            $skeleton = str_ireplace('%header%', $this->headerStub, $skeleton);
        } else {
            $skeleton = str_ireplace('%header%', '', $skeleton);
        }

        if (isset($this->options['dest-dir'])) {
            $destDir = $this->options['dest-dir'];
        }

        $argInfoStub = $functionsStub = '';
        if (isset($this->functions)) {

            if (key_exists('fast-zpp', $options)) {
                $this->parametersApi = 'fastzpp';
            }

            $functionsCompiler = new FunctionsCompiler($this->functions, $this->extensionName, $this->parametersApi);
            $functionsStub = $functionsCompiler->compile();

            $argInfoCompiler = new ArgInfoCompiler($this->functions, $this->extensionName);
            $argInfoStub = $argInfoCompiler->compile();
        }

        $skeleton = str_ireplace('%arginfo_stub%', $argInfoStub, $skeleton);
        $skeleton = str_ireplace('%functions_stub%', $functionsStub['functions'], $skeleton);
        $skeleton = str_ireplace('%functions_entry_stub%', $functionsStub['functions_entry'], $skeleton);
        $skeleton = str_ireplace('%extname%', $this->extensionName, $skeleton);
        $skeleton = str_ireplace('%extnamecaps%', strtoupper($this->extensionName), $skeleton);
        $skeleton = str_ireplace('%year%', date("Y"), $skeleton);
        $skeleton = str_ireplace('%footer%', $this->footerStub, $skeleton);

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

        $skeleton = str_ireplace('%extnamecaps%', strtoupper($this->extensionName), $skeleton);

        if (isset($this->options['dump-header'])) {
            $skeleton = str_ireplace('%header%', $this->headerStub, $skeleton);
        } else {
            $skeleton = str_ireplace('%header%', '', $skeleton);
        }

        $skeleton = str_ireplace('%footer%', $this->footerStub, $skeleton);

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

        $skeleton = str_ireplace('%extname%', $this->extensionName, $skeleton);

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

        return file_put_contents($this->destDir . '/' . $configw32, $skeleton);
    }
}
