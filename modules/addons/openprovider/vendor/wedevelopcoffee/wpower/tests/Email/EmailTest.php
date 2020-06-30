<?php
namespace WeDevelopCoffee\wPower\Tests\Core;
use Mockery;
use WeDevelopCoffee\wPower\Core\API;
use WeDevelopCoffee\wPower\Email\Email;
use WeDevelopCoffee\wPower\Tests\TestCase;

class EmailTest extends TestCase
{
    protected $email;
    protected $mockedAPI;


    public function test_send_valid_mail_template()
    {
        // Set config
        $messageName = 'some-template';
        $id = 21;

        // Set expectations
        $expectedResult = 'success';

        // Set mocks
        $postData = [
            'id' => $id,
            'messagename' => $messageName
        ];

        $this->mockedAPI->shouldReceive('exec')
            ->with('SendEmail', $postData)
            ->once()
            ->andReturn(['result' => 'success']);

        // Execute
        $this->email->setMessageName($messageName);
        $this->email->setId($id);
        $result = $this->email->send();

        $this->assertEquals($expectedResult, $result);
    }

    public function test_send_invalid_mail_template()
    {
        // Set config
        $messageName = 'some-incorrect-template';
        $id = 21;
        $errorMessage = 'some error message';

        // Set expectations
        $expectedResult = 'error';

        // Set mocks
        $postData = [
            'id' => $id,
            'messagename' => $messageName
        ];

        $this->mockedAPI->shouldReceive('exec')
            ->with('SendEmail', $postData)
            ->once()
            ->andReturn([
                'result' => $expectedResult,
                'message' => $errorMessage
            ]);

        // Execute
        $this->email->setMessageName($messageName);
        $this->email->setId($id);

        $this->expectException(\Exception::class);

        $result = $this->email->send();

        $this->assertEquals($expectedResult, $result);
    }

    public function test_send_custom_mail()
    {
        // Set config
        $id = 21;
        $type = 'product';
        $subject = 'some-subject';
        $message = 'some-message';
        $varKey = 'some-key';
        $varValue = 'some-value';

        // Set expectations
        $expectedResult = 'success';

        // Set mocks
        $postData = [
            'id' => $id,
            'customtype' => $type,
            'customsubject' => $subject,
            'custommessage' => $message,
            'customvars' => [$varKey => $varValue]
        ];

        $this->mockedAPI->shouldReceive('exec')
            ->with('SendEmail', $postData)
            ->once()
            ->andReturn([
                'result' => 'success'
            ]);

        // Execute
        $this->email->setCustomType($type);
        $this->email->setCustomSubject($subject);
        $this->email->setCustomMessage($message);
        $this->email->setCustomVar($varKey, $varValue);
        $this->email->setId($id);

        $result = $this->email->send();

        $this->assertEquals($expectedResult, $result);
    }
   
    /**
    * setUp
    * 
    */
    public function setUp ()
    {
        $this->mockedAPI = Mockery::mock(API::class);
        $this->email   = new Email($this->mockedAPI);
    }
    
    
}