<?php
namespace WeDevelopCoffee\wPower\Tests\Core;
use Mockery;
use WeDevelopCoffee\wPower\Core\API;
use WeDevelopCoffee\wPower\Email\AdminEmail;
use WeDevelopCoffee\wPower\Email\Email;
use WeDevelopCoffee\wPower\Tests\TestCase;

class AdminEmailTest extends TestCase
{
    protected $adminEmail;
    protected $mockedAPI;


    public function test_send_valid_mail_template()
    {
        // Set config
        $messageName = 'some-template';

        // Set expectations
        $expectedResult = 'success';

        // Set mocks
        $postData = [
            'mergefields' => [],
            'messagename' => $messageName
        ];

        $this->mockedAPI->shouldReceive('exec')
            ->with('SendAdminEmail', $postData)
            ->once()
            ->andReturn(['result' => 'success']);

        // Execute
        $this->adminEmail->setMessageName($messageName);
        $result = $this->adminEmail->send();

        $this->assertEquals($expectedResult, $result);
    }

    public function test_send_invalid_mail_template()
    {
        // Set config
        $messageName = 'some-incorrect-template';
        $errorMessage = 'some error message';

        // Set expectations
        $expectedResult = 'error';

        // Set mocks
        $postData = [
            'mergefields' => [],
            'messagename' => $messageName
        ];

        $this->mockedAPI->shouldReceive('exec')
            ->with('SendAdminEmail', $postData)
            ->once()
            ->andReturn([
                'result' => $expectedResult,
                'message' => $errorMessage
            ]);

        // Execute
        $this->adminEmail->setMessageName($messageName);

        $this->expectException(\Exception::class);

        $result = $this->adminEmail->send();

        $this->assertEquals($expectedResult, $result);
    }

    public function test_send_custom_mail()
    {
        // Set config
        $type = 'product';
        $subject = 'some-subject';
        $message = 'some-message';
        $varKey = 'some-key';
        $varValue = 'some-value';

        // Set expectations
        $expectedResult = 'success';

        // Set mocks
        $postData = [
            'mergefields' => [$varKey => $varValue],
            'customsubject' => $subject,
            'custommessage' => $message
        ];

        $this->mockedAPI->shouldReceive('exec')
            ->with('SendAdminEmail', $postData)
            ->once()
            ->andReturn([
                'result' => 'success'
            ]);

        // Execute
        $this->adminEmail->setCustomSubject($subject);
        $this->adminEmail->setCustomMessage($message);
        $this->adminEmail->setMergeFields($varKey, $varValue);

        $result = $this->adminEmail->send();

        $this->assertEquals($expectedResult, $result);
    }
   
    /**
    * setUp
    * 
    */
    public function setUp ()
    {
        $this->mockedAPI = Mockery::mock(API::class);
        $this->adminEmail   = new AdminEmail($this->mockedAPI);
    }
    
    
}