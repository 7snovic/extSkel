<?php
namespace extSkel;

use extSkel\Compilers\ArgInfoCompiler;
use extSkel\Compilers\FunctionsCompiler;

class FunctionsAnalyzer extends Analyzer
{
    /*
    * {@inheritDoc}
    *
    */
    public function compileExtension($options)
    {
        $skeleton = file_get_contents('stubs/skeleton.stub');

        if (!isset($options['no-header'])) {
            $skeleton = str_ireplace('%header%', $this->headerStub, $skeleton);
        } else {
            $skeleton = str_ireplace('%header%', '', $skeleton);
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
}
