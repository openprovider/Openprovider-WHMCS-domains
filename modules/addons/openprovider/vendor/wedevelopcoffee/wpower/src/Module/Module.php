<?php
namespace WeDevelopCoffee\wPower\Module;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Core\Path;

/**
 * Module functions
 */
class Module {
    /**
     * The found type.
     *
     * @var string
     */
    protected $type = 'unknown';

    /**
     * The found name of the module.
     *
     * @var string
     */
    protected $name;

    /**
     * The Path
     *
     * @var object
     */
    protected $path;

    /**
     * Run the checks
     */
    public function __construct()
    {
        $this->checkAddon();
            
        $this->checkGateway();
        
        $this->checkRegistrar();
        
        $this->checkServer();
    }

    /**
     * Check if this is an addon.
     *
     * @return boolean
     */
    public function checkAddon()
    {
        if($this->getModuleName('addons'))
            $this->type = 'addon';
    }

    public function checkGateway() {
        if($this->getModuleName('gateways'))
            $this->type = 'gateway';
    }

    public function checkRegistrar() {
        if($this->getModuleName('registrars'))
            $this->type = 'registrar';
    }

    public function checkServer() {
        if($this->getModuleName('servers'))
            $this->type = 'server';
    }

    /**
     * Backtraces the code execution to find the module name
     *
     * @param string $type the type formatted as WHMCS directory (like addons)
     * @return void
     */
    protected function getModuleName($type)
    {
        $backtrace = debug_backtrace();
        
        // Loop through every backtrace
        foreach($backtrace as $trace)
        {
            if(isset($trace['file'])
            // Ignore wPower files
            && strpos($trace['file'], 'wpower/src') === false
            // Ignore the Composer autoloader
            && strpos($trace['file'], 'composer/autoload_real') === false
            && strpos($trace['file'], 'vendor/autoload') === false
            // Only module files are allowed.
            && strpos($trace['file'], 'modules') == true)
            {
                $expression = '/\/modules\/'.$type.'\/([a-zA-Z0-9-_]+)\//';
                preg_match($expression, $trace['file'], $matches);
                
                if(isset($matches[1]))
                {
                    $this->name = $matches [1];
                    return true;
                }
                    
            }
        }

        return false;
    }

    /**
     * Return the module it's name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the module type
     *
     * @return string addon|gateway|registrar|server|unknown
     */
    public function getType()
    {
        return $this->type;
    }
}

