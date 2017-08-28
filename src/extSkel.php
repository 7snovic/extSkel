<?php
namespace hassan\extSkel;

class extSkel
{
    /**
     * All the supported and available options with
     * it's description used @ printhelp method.
     * 
     * @var array
     */
    public $availableOptions = [
        'proto:' => '--proto=file              File contains prototypes of functions to create',

        'extension::' => '--extension=extname       Module is the name of your extension',
        'dest-dir::' => '--dest-dir=path           Path to the extension directory',
        'credits::' => '--credits=author          Credits string to be added to headers',
        // 'credits-file::'
        // 'verbose::',
        // 'config.m4::',
        // 'use-namespace::'

        'help' => '--help                    This message',
        'dump-header' => '--dump-header             Append header string to your extension',
        'fast-zpp' => '--fast-zpp                Use FastZPP API instead of zpp functions',
    ];

    public function __construct(AnalyzerInterface $analyzer)
    {
        $this->analyzer = $analyzer;
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
     * @return void
     */
    public function analyzeOptions($options)
    {
        foreach ($options as $key => $option) {

            if ($key == 'help') {
                continue;
            }

            if ($key == 'proto') {
                $this->analyzer->analyzeProto($option, $options);
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
     * call the compile function.
     * 
     * @return string|\hassan\extSkel\AnalyzerInterface
     */
	public function run($options)
	{
        if (key_exists('help', $options) or count($options) == 0) {
            $this->printHelp();
        } else {
            $this->analyzeOptions($options);
            $options['extension'] = isset($options['extension']) ? $options['extension'] : 'extSkel';
            $options['dest-dir']  = isset($options['dest-dir']) ? $options['dest-dir'] : 'extension/';
            return $this->analyzer->compile($options);
        }
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
        $help[] = '            [--credits="author name"] [--dump-header] [--fast-zpp]';
        $help[] = '';
        foreach ($this->availableOptions as $key => $option) {
            $help[] = '  ' . $option;
        }

        echo implode(PHP_EOL, $help) . "\n";
    }
}
