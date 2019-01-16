<?php
namespace WeDevelopCoffee\wPower\Core;

use WHMCS\Database\Capsule;
use ReflectionClass;
use WeDevelopCoffee\wPower\Database\Connection;
use Illuminate\Database\ConnectionResolver;

class Launcher
{
    protected $map = [
        '\Illuminate\Database\Migrations\MigrationRepositoryInterface' => 'launchMigrationRepository',
        '\Illuminate\Database\ConnectionResolverInterface' => \Illuminate\Database\ConnectionResolver::class,
    ];


    /**
    *  Launch a class
    * 
    * Also performs automatic dependency injection for a class.
    * 
    * @param  string  $view
    * @param  array   $data
    * @return object
    */
    public function launchClass ($class)
    {
        if($class == 'WeDevelopCoffee\wPower\wPower' && isset($GLOBALS['wPower']))
        {
            // Exists!
            return $GLOBALS['wPower'];
        }
        
        $class = $this->mapClass($class);

        // If the class does not exist, it is a local method.
        if(method_exists($this, $class))
            return $this->$class();

        $reflect = new ReflectionClass($class);
        
        try
        {
            $reflectmethod  = $reflect->getMethod('__construct');

            // Check for extra parameters
            if(count(func_get_args()) > 1)
            {
                $extra_arg = func_get_args();
                unset($extra_arg[0]);
                $extra_arg_next = 1;
            }

            if(count($reflectmethod->getParameters()) != 0)
            {
                $params = [];
                foreach($reflectmethod->getParameters() as $num => $param) {
                    if ($param->getClass()) {

                        $className = $param->getClass()->name;                            

                        $params[] = $this->launchClass($className);
                    }
                    else
                    {
                        // Check if we have received an extra argument that we can pass on.
                        if(isset($extra_arg[$extra_arg_next]))
                        {
                            $params[] = $extra_arg[$extra_arg_next];
                            $extra_arg_next++; // Increase the count for the next argument.
                        }    
                    }
                }
                
                $instance = $reflect->newInstanceArgs($params);
            }
            else
                $instance = new $class();
        }
        catch ( \ReflectionException $e)
        {
            // There is no constructor.
            $instance = new $class();
        }

        if($class == 'WeDevelopCoffee\wPower\wPower')
            $GLOBALS['wPower'] = $instance;
        
        return $instance;
    }

    protected function launchMigrationRepository()
    {
        $connection = Capsule::connection();
        $resolver   = new ConnectionResolver([ null => $connection]);
        
        $table      = 'wMigrations';
        
        $object = new \Illuminate\Database\Migrations\DatabaseMigrationRepository($resolver, $table);
        
        return $object;
    }

    /**
    * Convert interface/class to a mapped class
    * 
    * @return string $class
    */
    protected function mapClass ($class)
    {
        if(isset($this->map[$class]))
            return $this->map[$class];
        
        if(isset($this->map['\\'.$class]))
            return $this->map['\\'.$class];
        
        return $class;
    }
}
