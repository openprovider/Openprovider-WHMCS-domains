<?php

namespace phpmock\integration;

use PHPUnit\Framework\TestCase;

/**
 * Tests MockDelegateFunction.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 * @see MockDelegateFunction
 */
class MockDelegateFunctionTest extends TestCase
{
    use TestCaseTrait;
    
    /**
     * @var string The class name of a generated class.
     */
    private $className;
    
    protected function setUpCompat()
    {
        parent::setUp();
        
        $builder = new MockDelegateFunctionBuilder();
        $builder->build();
        $this->className = $builder->getFullyQualifiedClassName();
    }

    /**
     * Tests delegate() returns the mock's result.
     *
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function testDelegateReturnsMockResult()
    {
        $expected    = 3;
        $mockBuilder = $this->getMockBuilder($this->className);

        // `setMethods` is gone from phpunit 10, alternative is `onlyMethods`
        if (method_exists($mockBuilder, 'onlyMethods')) {
            $mockBuilder->onlyMethods([MockDelegateFunctionBuilder::METHOD]);
        } else {
            $mockBuilder->setMethods([MockDelegateFunctionBuilder::METHOD]);
        }

        $mock = $mockBuilder->getMock();

        $mock->expects($this->once())
             ->method(MockDelegateFunctionBuilder::METHOD)
             ->willReturn($expected);
        
        $result = call_user_func($mock->getCallable());
        $this->assertEquals($expected, $result);
    }

    /**
     * Tests delegate() forwards the arguments.
     *
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function testDelegateForwardsArguments()
    {
        $mockBuilder = $this->getMockBuilder($this->className);

        // `setMethods` is gone from phpunit 10, alternative is `onlyMethods`
        if (method_exists($mockBuilder, 'onlyMethods')) {
            $mockBuilder->onlyMethods([MockDelegateFunctionBuilder::METHOD]);
        } else {
            $mockBuilder->setMethods([MockDelegateFunctionBuilder::METHOD]);
        }

        $mock = $mockBuilder->getMock();

        $mock->expects($this->once())
             ->method(MockDelegateFunctionBuilder::METHOD)
             ->with(1, 2);

        call_user_func($mock->getCallable(), 1, 2);
    }
}
