<?php
namespace Tests\Core;
use Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Level;

class LevelTest extends TestCase
{
    public function test_set_level()
    {
        $level = 'test';
        
        Level::setLevel($level);
        $result = Level::getLevel();

        $this->assertEquals($level, $result);
    }
    
}