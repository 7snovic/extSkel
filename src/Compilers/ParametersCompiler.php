<?php
namespace hassan\extSkel\Compilers;

class ParametersCompiler
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
     * The function array.
     * 
     * @var array
     */
    private $functions = [];

    /**
     * The current supported datatypes.
     * 
     * @var array
     */
    private $supportedTypes = [
        's' => 'string',
        'b' => 'bool',
        'd' => 'float',
        'l' => 'int',
        'z' => 'mixed',
    ];

    /**
     * Create a new ParametersCompiler instance.
     * 
     * @param array $function
     * @param string $extension
     * @param string $parametersApi
     * 
     * @return void
     */
    public function __construct($function, $extension, $parametersApi)
    {
        $this->function = $function;
        $this->extension = $extension;
        $this->parametersApi = $parametersApi;
    }

    /**
     * Get the proper FastZPP Macro.
     * 
     * @param string $type
     * @param string $varName
     * 
     * @return string
     */
    private function getFastZPP($type, $varName)
    {
        switch ($type) {
            case 'string': {
                $fastZpp = "Z_PARAM_STRING({$varName}_var, {$varName}_var_len)";
            }
            break;
            case 'int': {
                $fastZpp = "Z_PARAM_LONG({$varName}_var)";
            }
            break;
            case 'float': {
                $fastZpp = "Z_PARAM_DOUBLE({$varName}_var)";
            }
            break;
            case 'bool': {
                $fastZpp = "Z_PARAM_BOOL({$varName}_var)";
            }
            break;
            case 'mixed': {
                $fastZpp = "Z_PARAM_ZVAL({$varName}_var)";
            }
            break;
            default: {
                $fastZpp = "Z_PARAM_ZVAL({$varName}_var)";
            }
        }

        return $fastZpp;
    }

    /**
     * Compile the FastZpp API.
     * 
     * @return string
     */
    public function compileFastZPP()
    {
        $stub = file_get_contents('stubs/parameters_fastzpp.stub');

        $parameters = [];
        $isRequired = 0;
        if ($this->function['parametersCount'] == 0) {
            $parameters = 'ZEND_PARSE_PARAMETERS_NONE();';
            return $parameters;
        } else {
            foreach ($this->function['parameters'] as $key => $parameter) {
                $parameterTemplate = [];

                if (!$parameter['isRequired'] && !$isRequired) {
                    $parameterTemplate[] = 'Z_PARAM_OPTIONAL';
                    $isRequired = 1;
                }

                $parameterTemplate[] = $this->getFastZPP($parameter['type'], $parameter['name']);

                $parameters[$key] = implode(PHP_EOL, $parameterTemplate);
            }
        }

        $stub = str_ireplace('%PARAMETERS%', implode(PHP_EOL, $parameters), $stub);
        $stub = str_ireplace('%ALL_PARAMETERS%', $this->function['parametersCount'], $stub);
        $stub = str_ireplace('%REQUIRED_PARAMETERS%', $this->function['requiredParametersCount'], $stub);
        return $stub;
    }

    /**
     * Get the zpp placeholder.
     * 
     * @param string $type
     * @param string $varName
     * 
     * @return string
     */
    public function getZPP($type)
    {
        $types = array_flip($this->supportedTypes);

        $type = ($type ?: 'mixed');

        return $types[$type];
    }

    /**
     * Compile the zpp functions.
     * 
     * @return string
     */
    public function compileZPP()
    {
        $stub = file_get_contents('stubs/parameters_zpp.stub');

        $parametersList = [];
        $placeHolders = [];
        $isRequired = 0;
        if ($this->function['parametersCount'] == 0) {
            $stub = str_replace(
                'zend_parse_parameters(ZEND_NUM_ARGS(), "%PLACE_HOLDERS%", %PARAMETERS%)',
                'zend_parse_parameters_none()',
                $stub
            );
            return $stub;
        } else {
            foreach ($this->function['parameters'] as $key => $parameter) {
                $placeHoldersString = [];
                $parametersListString = [];

                if (!$parameter['isRequired'] && !$isRequired) {
                    $placeHoldersString[] = '|';
                    $isRequired = 1;
                }

                $placeHoldersString[] = $this->getZPP($parameter['type']);

                $parametersListString[] = "&{$parameter['name']}_var";

                if ($parameter['type'] == 'string') {
                    $parametersListString[] = "&{$parameter['name']}_var_len";
                }

                $placeHolders[$key] = implode('', $placeHoldersString);
                $parametersList[$key] = implode(', ', $parametersListString);
            }
        }

        $stub = str_ireplace('%PLACE_HOLDERS%', implode('', $placeHolders), $stub);
        $stub = str_ireplace('%PARAMETERS%', implode(', ', $parametersList), $stub);
        return $stub;
    }

    /**
     * Compile the parameters based on the self::$parametersApi.
     * 
     * @return string
     */
    public function compile()
    {
        if ($this->parametersApi === 'fastzpp') {
            return $this->compileFastZPP();
        } else {
            return $this->compileZPP();
        }
    }
}
