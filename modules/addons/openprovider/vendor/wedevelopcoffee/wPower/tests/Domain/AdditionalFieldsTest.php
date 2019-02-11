<?php
namespace Tests\Domain;
use Mockery;
use Tests\TestCase;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Core\Path;
use WeDevelopCoffee\wPower\Domain\AdditionalFields;
use WeDevelopCoffee\wPower\Domain\Registrar;

class AdditionalFieldsTest extends TestCase
{
    // Mocks
    protected $path;
    protected $registrar;

    //class
    protected $additionalFields;


    public function test_get_dist_additional_fields()
    {
        $testPath = $this->prepDistAdditionalFieldsPath();
        $result = $this->additionalFields->getDistAdditionalFields();

        include($testPath . '/resources/domains/dist.additionalfields.php');
        $expectedResult = $additionaldomainfields;

        $this->assertEquals($expectedResult, $result);
    }

    public function test_filtered_additional_fields()
    {
        $testPath = $this->prepDistAdditionalFieldsPath();
        include($testPath . '/resources/domains/expected.additionalfields.php');

        // Prep tlds
        $this->additionalFields->setRegistrarAdditionalFields($additionaldomainfields);

        // Prep tlds supported by registrar
        $this->registrar->shouldReceive('getTlds')
            ->andReturn(['.us' => true])
            ->once();

        $this->additionalFields->setRegistrarName('some-registrar');

        $result = $this->additionalFields->getFilteredAdditionalFields();

        $expectedResult = $additionaldomainfields;

        $this->assertEquals($expectedResult, $result);
    }

   
    /**
    * setUp
    * 
    */
    public function setUp ()
    {
        $this->path = Mockery::mock(Path::class);
        $this->registrar = Mockery::mock(Registrar::class);

        $this->additionalFields   = new AdditionalFields($this->path, $this->registrar);
    }

    /**
     * Prep the additional fields expectations.
     */
    public function prepDistAdditionalFieldsPath()
    {
        $testPath = realpath(__DIR__ . '/../dependencies');

        $this->path->shouldReceive('getDocRoot')
            ->andReturn($testPath)
            ->once();

        return $testPath;
    }


}