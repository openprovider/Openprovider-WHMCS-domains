<?php

namespace phpmock\integration;

use PHPUnit\Framework\TestCase;

/**
 * Tests MockDelegateFunctionBuilder.
 *
 * @author Markus Malkusch <markus@malkusch.de>
 * @link bitcoin:1335STSwu9hST4vcMRppEPgENMHD2r1REK Donations
 * @license http://www.wtfpl.net/txt/copying/ WTFPL
 * @see MockDelegateFunctionBuilder
 */
class MockDelegateFunctionBuilderTest extends TestCase
{

    /**
     * Test build() defines a class.
     */
    public function testBuild()
    {
        $builder = new MockDelegateFunctionBuilder();
        $builder->build();
        $this->assertTrue(class_exists($builder->getFullyQualifiedClassName()));
    }

    /**
     * Test build() would never create the same class name for different signatures.
     */
    public function testDiverseSignaturesProduceDifferentClasses()
    {
        $builder = new MockDelegateFunctionBuilder();

        $builder->build('f0');
        $class1 = $builder->getFullyQualifiedClassName();

        $builder->build('f1');
        $class2 = $builder->getFullyQualifiedClassName();
        
        $builder2 = new MockDelegateFunctionBuilder();
        $builder2->build('f2');
        $class3 = $builder2->getFullyQualifiedClassName();
        
        $this->assertNotEquals($class1, $class2);
        $this->assertNotEquals($class1, $class3);
        $this->assertNotEquals($class2, $class3);
    }

    /**
     * Test build() would create the same class name for identical signatures.
     */
    public function testSameSignaturesProduceSameClass()
    {
        $builder   = new MockDelegateFunctionBuilder();

        $builder->build('f1');
        $class1 = $builder->getFullyQualifiedClassName();
        
        $builder->build('f1');
        $class2 = $builder->getFullyQualifiedClassName();
        
        $this->assertEquals($class1, $class2);
    }
    
    /**
     * Tests declaring a class with enabled backupStaticAttributes.
     *
     * @backupStaticAttributes enabled
     * @dataProvider provideTestBackupStaticAttributes
     *
     * @doesNotPerformAssertions
     */
    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    #[\PHPUnit\Framework\Attributes\DataProvider('provideTestBackupStaticAttributes')]
    #[\PHPUnit\Framework\Attributes\BackupStaticProperties(true)]
    public function testBackupStaticAttributes()
    {
        $builder = new MockDelegateFunctionBuilder();
        $builder->build("min");
    }
    
    /**
     * Just repeat testBackupStaticAttributes a few times.
     *
     * @return array Test cases.
     */
    public static function provideTestBackupStaticAttributes()
    {
        return [
            [],
            []
        ];
    }

    /**
     * Tests deserialization.
     *
     * @runInSeparateProcess
     *
     * @doesNotPerformAssertions
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testDeserializationInNewProcess()
    {
        $builder = new MockDelegateFunctionBuilder();
        $builder->build("min");

        $data = serialize($this->getMockBuilder($builder->getFullyQualifiedClassName())->getMock());
        
        unserialize($data);
    }
}
