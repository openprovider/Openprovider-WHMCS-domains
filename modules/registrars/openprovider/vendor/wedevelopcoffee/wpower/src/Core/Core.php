<?php

namespace WeDevelopCoffee\wPower\Core;
if(!class_exists(\DI\Container::class))
    require_once(__DIR__ . '/../../vendor/autoload.php'); // @todo remove this for production.

use DI\Container;
use Illuminate\Database\ConnectionResolverInterface;
use WeDevelopCoffee\wPower\Database\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use WeDevelopCoffee\wPower\Module\Setup;
use WHMCS\Database\Capsule;
use WHMCS\ClientArea;

/**
 * Class Core
 * @package WeDevelopCoffee\wPower\Core
 */
class Core
{

    /**
     * @var string $namespace The namespace of the module.
     */
    protected $namespace;

    /**
     * @var string $moduleType The module type. Can be addon, server or registrar.
     */
    protected $moduleType;

    /**
     * @var string $moduleName The module name (same as the directory name).
     */
    protected $moduleName;

    /**
     * @var string $level The level: admin, client or hook.
     */
    protected $level;

    /**
     * @var object $launcher The Launcher class.
     */
    public $launcher;

    /**
     * Launch the system.
     *
     * @return object \WeDevelopCoffee\wPower\Core\Launch
     */
    public function launch()
    {
        if(empty($this->launcher))
        {
            $this->launcher = new Container();
            $this->bindClasses();
        }

        return $this->launcher->get(Launch::class);
    }

    /**
     * Quick launcher for the setup.
     *
     * @return mixed
     */
    public function setup()
    {
        $this->launch();
        return $this->launcher->get(Setup::class);
    }


    /**
     * Determine if we are running command line or native.
     *
     * @return boolean
     */
    public function isCli()
    {
        if ( defined('STDIN') )
        {
            return true;
        }

        if ( php_sapi_name() === 'cli' )
        {
            return true;
        }

        if ( array_key_exists('SHELL', $_ENV) ) {
            return true;
        }

        if ( empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0)
        {
            return true;
        }

        if ( !array_key_exists('REQUEST_METHOD', $_SERVER) )
        {
            return true;
        }

        return false;
    }

    /**
     * Bind the classes
     */
    public function bindClasses()
    {
        $this->launcher->set(Core::class, $this);
        $this->launcher->set(DatabaseMigrationRepository::class, $this);

        // Prepare the migration class.
        $this->launcher->set(MigrationRepositoryInterface::class, function () {
            $connection = Capsule::connection();
            $resolver = new ConnectionResolver([null => $connection]);

            $table = 'modwMigrations';

            $object = new DatabaseMigrationRepository($resolver, $table);

            $object->type = $this->getModuleType();
            $object->moduleName = $this->getModuleName();

            return $object;
        });

        $this->launcher->set(ConnectionResolverInterface::class,  function () {
            $connection = Capsule::connection();
            $resolver = new ConnectionResolver([null => $connection]);
            return $resolver;
        });

        $this->launcher->set(ClientArea::class, function(){
            return new ClientArea();
        });
    }

    /**
     * @param mixed $namespace
     * @return Core
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param mixed $moduleType
     * @return Core
     */
    public function setModuleType($moduleType)
    {
        $this->moduleType = $moduleType;
        return $this;
    }

    /**
     * @return string
     */
    public function getModuleType(): string
    {
        return $this->moduleType;
    }

    /**
     * @param mixed $moduleName
     * @return Core
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;
        return $this;
    }

    /**
     * @return string
     */
    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    /**
     * @param mixed $level
     * @return Core
     */
    public function setLevel($level)
    {
        $this->level = $level;
        return $this;
    }

    /**
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }
}