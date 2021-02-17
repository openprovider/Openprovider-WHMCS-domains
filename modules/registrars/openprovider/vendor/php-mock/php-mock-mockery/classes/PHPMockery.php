<?php

namespace phpmock\mockery;

use Mockery;
use Mockery\Expectation;
use phpmock\MockBuilder;
use phpmock\integration\MockDelegateFunctionBuilder;

/**
 * Mock built-in PHP functions with Mockery.
 *
 * <code>
 * namespace foo;
 *
 * use phpmock\mockery\PHPMockery;
 *
 * $mock = PHPMockery::mock(__NAMESPACE__, "time")->andReturn(3);
 * assert (3 == time());
 *
 * \Mockery::close();
 * </code>
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 */
class PHPMockery
{

    /**
     * Builds a function mock.
     *
     * Disable this mock after the test with Mockery::close().
     *
     * @param string $namespace The function namespace.
     * @param string $name      The function name.
     *
     * @return Expectation The mockery expectation.
     * @SuppressWarnings(PHPMD)
     */
    public static function mock($namespace, $name)
    {
        $delegateBuilder = new MockDelegateFunctionBuilder();
        $delegateBuilder->build($name);
        
        $mockeryMock = Mockery::mock($delegateBuilder->getFullyQualifiedClassName());
        $mockeryMock->makePartial()->shouldReceive(MockDelegateFunctionBuilder::METHOD);

        $builder = new MockBuilder();
        $builder->setNamespace($namespace)
                ->setName($name)
                ->setFunctionProvider($mockeryMock);
        $mock = $builder->build();
        $mock->enable();
        
        $disabler = new MockDisabler($mock);
        Mockery::getContainer()->rememberMock($disabler);
        
        return new ExpectationProxy($mockeryMock);
    }
    
    /**
     * Defines the mocked function in the given namespace.
     *
     * In most cases you don't have to call this method. {@link mock()}
     * is doing this for you. But if the mock is defined after the first call in the
     * tested class, the tested class doesn't resolve to the mock. This is
     * documented in Bug #68541. You therefore have to define the namespaced
     * function before the first call.
     *
     * Defining the function has no side effects. If the function was
     * already defined this method does nothing.
     *
     * @see mock()
     * @link https://bugs.php.net/bug.php?id=68541 Bug #68541
     *
     * @param string $namespace The function namespace.
     * @param string $name      The function name.
     */
    public static function define($namespace, $name)
    {
        $builder = new MockBuilder();
        $builder->setNamespace($namespace)
                ->setName($name)
                ->setFunction(function () {
                })
                ->build()
                ->define();
    }
}
