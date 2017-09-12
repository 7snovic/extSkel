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
        'proto:' => '--proto=file              File contains prototypes of functions to create.',

        'extension::' => '--extension=extname       Module is the name of your extension.',
        'dest-dir::' => '--dest-dir=path           Path to the extension directory.',
        'credits::' => '--credits=author          Credits string to be added to headers.',
        'php-arg::' => '--php-arg=enable          If your extension references something external, use with'
        . PHP_EOL . '                            Otherwise use enable.',

        'help' => '--help                    This message.',
        'dump-header' => '--dump-header             Append header string to your extension.',
        'fast-zpp' => '--fast-zpp                Use FastZPP API instead of zpp functions.',
    ];

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
        return $this->analyzer->compile($options);
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
        $help[] = '            [--credits="author name"] [--dump-header] [--fast-zpp] [--php-arg="with|enable"]';
        $help[] = '';
        foreach ($this->availableOptions as $key => $option) {
            $help[] = '  ' . $option;
        }

        echo implode(PHP_EOL, $help) . "\n";
    }
}
