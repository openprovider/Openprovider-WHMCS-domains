<?php
namespace WeDevelopCoffee\wPower\Core;

class Level
{
    /**
     * The current level.
     *
     * @var string
     */
    protected static $level = 'hooks';

    /**
     * Get the current level.
     *
     * @return  string
     */ 
    public static function getLevel()
    {
        return self::$level;
    }

    /**
     * Set the current level.
     *
     * @param  string  $level  The current level.
     *
     * @return  self
     */ 
    public static function setLevel($level)
    {
        self::$level = $level;
    }
}
