<?php
namespace Tests;
use Mockery;
use PHPUnit\Framework\TestCase as original_testcase;

class TestCase extends original_testcase
{
    /**
     * Run Mockery.
     *
     * @return void
     */
    public function tearDown() {
        \Mockery::close();
    }
}