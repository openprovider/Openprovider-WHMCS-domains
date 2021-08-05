<?php
/**
 * @author Michał Bundyra (webimpress) <contact@webimpress.com>
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 */

namespace phpmock\mockery;

use Mockery\CompositeExpectation;
use Mockery\MockInterface;
use phpmock\integration\MockDelegateFunctionBuilder;

/**
 * Proxy to CompositeExpectation which clear all expectations created on mock.
 */
class ExpectationProxy extends CompositeExpectation
{
    private $isCleared = false;

    private $mock;

    public function __construct(MockInterface $mock)
    {
        $this->mock = $mock;
    }

    public function __call($name, array $args)
    {
        if (! $this->isCleared) {
            $callback = function () {
                $this->_mockery_expectations = [];
            };

            $bind = $callback->bindTo($this->mock, get_class($this->mock));
            $bind();

            $this->isCleared = true;
        }

        $expectation = $this->mock->shouldReceive(MockDelegateFunctionBuilder::METHOD);

        return call_user_func_array([$expectation, $name], $args);
    }
}
