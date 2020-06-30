<?php
namespace WeDevelopCoffee\wPower\Tests\Core;
use WeDevelopCoffee\wPower\Tests\TestCase;
use WeDevelopCoffee\wPower\Core\API;

class ApiTest extends TestCase
{
    protected $api;


    public function test_api()
    {
        $command = 'testCommand';
        $value = ['some-value'];
        $adminuser = 'admin';

        $result = $this->api->exec($command, $value, $adminuser);

        $expectedResult = [
            $command,
            $value,
            $adminuser
        ];

        $this->assertEquals($expectedResult, $result);
    }
   
    /**
    * setUp
    * 
    */
    public function setUp ()
    {
        $this->api   = new API();
    }
    
    
}