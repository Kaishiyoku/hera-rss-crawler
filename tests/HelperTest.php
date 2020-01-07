<?php

namespace Kaishiyoku\HeraRssCrawler;

use Exception;
use Kaishiyoku\HeraRssCrawler\TestClasses\FailingTestClass;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @covers HeraRssCrawler
 */
class HelperTest extends TestCase
{
    private $testMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testMock = Mockery::mock(FailingTestClass::class);
    }

    public function testWithRetries(): void
    {
        try {
            Helper::withRetries(function () {
                return $this->testMock->fail();
            });
        } catch (Exception $e) {

        }

        $this->testMock->shouldHaveReceived('fail')->times(4);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->addToAssertionCount($this->testMock->mockery_getExpectationCount());
    }
}
