<?php

namespace WeDevelopCoffee\wPower\Core;

/**
 * Class Path
 * @package WeDevelopCoffee\wPower\Core
 */
class Path
{
    /**
     * Core
     *
     * @var instance
     */
    protected $core;

    /**
     * __construct
     * @param object $core
     */
    public function __construct (Core $core)
    {
        $this->core = $core;
    }


    /**
     * getPath
     *
     * @return string
     */
    public function getDocRoot ()
    {
        global $customadminpath;

        if(defined('ROOTDIR'))
        {
            return ROOTDIR;
        }

        if($this->core->isCli())
        {
            // DOC_ROOT does not work with cli
            // WARNING: This part of the code is not tested!+
            $full_path = dirname(__FILE__);
            $currentDir = explode('modules', $full_path)[0];

            // If empty, fall back on the old method.
            if($currentDir == '')
            {
                $currentDir = getcwd();
                if(last(explode('/', $currentDir)) == 'crons')
                    $currentDir = realpath($currentDir . '/../');
            }

            return $currentDir;
        }

        $parts = explode(DIRECTORY_SEPARATOR, getcwd());
        if (last( $parts) == $customadminpath || last( $parts) == 'includes' || last( $parts) == 'crons') {
            unset($parts[sizeof($parts) - 1]);
        }

        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * Get the path to the addons folder.
     *
     * @return string
     */
    public function getAddonsPath()
    {
        return $this->getDocRoot() . '/modules/addons/';
    }

    /**
     * Get the path to the current addon
     *
     * @return void
     */
    public function getAddonMigrationPath()
    {
        $addon = $this->core->getModuleName();
        return $this->getDocRoot() . '/modules/addons/' . $addon . '/';
    }

    /**
     * Get the path to the current addon
     *
     * @return void
     */
    public function getModulePath()
    {
        $name   = $this->core->getModuleName();
        $type   = $this->core->getModuleType().'s';
        return $this->getDocRoot() . '/modules/'.$type.'/' . $name . '/';
    }

    /**
     * Get the path to the migration path.
     *
     * @return void
     */
    public function getModuleMigrationPath()
    {
        return $this->getModulePath() . 'migrations/';
    }
}