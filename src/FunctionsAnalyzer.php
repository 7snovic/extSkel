<?php
namespace extSkel;

use extSkel\Compilers\ArgInfoCompiler;
use extSkel\Compilers\FunctionsCompiler;

class FunctionsAnalyzer extends Analyzer
{
    /**
     * Filter and organize a given array of functions.
     *
     * @param array $definedFunctions
     *
     * @return array
     */
    private function filterFunctions($definedFunctions)
    {
        foreach ($definedFunctions['methods'] as $key => $function) {

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
        $functions = $this->filterFunctions($classInfo);

        $argInfoStub = $functionsStub = '';
        if (isset($functions)) {

            if (key_exists('fast-zpp', $options)) {
                $this->parametersApi = 'fastzpp';
            }

            $functionsCompiler = new FunctionsCompiler($functions, $options['extension'], $this->parametersApi);
            $functionsStub = $functionsCompiler->compile();

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
