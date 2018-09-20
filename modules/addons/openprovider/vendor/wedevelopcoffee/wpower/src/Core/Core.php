<?php
namespace WeDevelopCoffee\wPower\Core;

class Core
{
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
     * Get the current level. Proxies to Level to make classes more testable.
     *
     * @return  string
     */ 
    public function getLevel()
    {
        return Level::getLevel();
    }

    /**
     * Set the current level. Proxies to Level to make classes more testable.
     *
     * @param  string  $level  The current level.
     *
     * @return  self
     */ 
    public function setLevel($level)
    {
        Level::setLevel($level);

        return $this;
    }
}
