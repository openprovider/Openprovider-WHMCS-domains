<?php
namespace OpenProvider\WhmcsDomainAddon\Tests;
use PHPUnit\Framework\TestCase as baseTestCase;

class TestCase extends baseTestCase
{
    protected $params = [
        'OpenproviderPremium' => '',
        'premiumEnabled' => '',
        'Username' => 'some-username',
        'Password' => 'some-password',
        'OpenproviderAPI' => 'https://some-url',
        'sld' => 'some-domain',
        'tld' => 'com'
    ];

    /**
     * Run Mockery.
     *
     * @return void
     */
    protected function tearDown() {
        parent::tearDown();

        \Mockery::close();
    }

}