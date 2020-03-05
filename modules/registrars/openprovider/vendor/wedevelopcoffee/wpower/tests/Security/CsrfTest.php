<?php
namespace WeDevelopCoffee\wPower\Tests\Module;
use Illuminate\Database\Migrations\Migrator;
use Mockery;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Core\Path;
use WeDevelopCoffee\wPower\Module\Setup;
use WeDevelopCoffee\wPower\Security\Csrf;
use WeDevelopCoffee\wPower\Security\Exceptions\CsrfTokenException;
use WeDevelopCoffee\wPower\Tests\TestCase;

class CsrfTest extends TestCase
{
    public function test_generate_code()
    {
        $code = $this->csrf->generateCsrf();

        $this->assertEquals(64, strlen($code));
    }

    public function test_validate_code()
    {
        $code = $this->csrf->generateCsrf();
        $result = $this->csrf->verifyToken($code);

        $this->assertEquals(true, $result);
    }

    public function test_validation_incorrect_code()
    {
        $code = $this->csrf->generateCsrf();

        $code .= "makethisincorrect";

        $this->expectException(CsrfTokenException::class);

        $result = $this->csrf->verifyToken($code);

        $this->assertEquals(true, $result);
    }


    /**
    * setUp
    * 
    */
    public function setUp ()
    {
        $this->mockedCore = Mockery::mock(Core::class);
        $this->mockedCore->shouldReceive('isCli')
            ->andReturn(true);

        $this->csrf = new Csrf($this->mockedCore);
    }
    
    
}