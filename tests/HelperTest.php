<?php

namespace Kaishiyoku\HeraRssCrawler;

use Exception;
use Kaishiyoku\HeraRssCrawler\TestClasses\FailingTestClass;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers HeraRssCrawler
 */
class HelperTest extends TestCase
{
    /**
     * @var mixed
     */
    private $failingTestClassMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->failingTestClassMock = Mockery::mock(FailingTestClass::class);
    }

    public function testWithRetries(): void
    {
        try {
            Helper::withRetries(function () {
                return $this->failingTestClassMock->fail();
            });
        } catch (Exception $e) {

        }

        $this->failingTestClassMock->shouldHaveReceived('fail')->times(4);
    }

    public function testGetImageUrls(): void
    {
        $content = '<img src="https://www.golem.de/2107/158391-284735-284731_rc.jpg" width="140" height="140" vspace="3" hspace="8" align="left">Mit einer Tages- oder Monatskarte des E-Scooter-Anbieters Voi sollen Nutzer so viel fahren können, wie sie wollen - können sie aber nicht. (<a href="https://www.golem.de/specials/e-scooter/">E-Scooter</a>, <a href="https://www.golem.de/specials/verbraucherschutz/">Verbraucherschutz</a>) <img src="https://cpx.golem.de/cpx.php?class=17&amp;aid=158391&amp;page=1&amp;ts=1627054380" alt="" width="1" height="1" />';

        $imageUrls = Helper::getImageUrls($content);

        $this->assertEquals([
            'https://www.golem.de/2107/158391-284735-284731_rc.jpg',
            'https://cpx.golem.de/cpx.php?class=17&amp;aid=158391&amp;page=1&amp;ts=1627054380'
        ], $imageUrls);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->addToAssertionCount($this->failingTestClassMock->mockery_getExpectationCount());
    }
}
