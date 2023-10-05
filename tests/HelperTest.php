<?php

namespace Kaishiyoku\HeraRssCrawler;

use Exception;
use GuzzleHttp\Client;
use Kaishiyoku\HeraRssCrawler\TestClasses\FailingTestClass;
use Mockery;
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

    public function testGetImageUrlsForFeedItem(): void
    {
        $feedItemUrl = 'https://www.golem.de/2107';
        $content = '<img src="/2107/158391-284735-284731_rc.jpg" width="140" height="140" vspace="3" hspace="8" align="left">Mit einer Tages- oder Monatskarte des E-Scooter-Anbieters Voi sollen Nutzer so viel fahren können, wie sie wollen - können sie aber nicht. (<a href="https://www.golem.de/specials/e-scooter/">E-Scooter</a>, <a href="https://www.golem.de/specials/verbraucherschutz/">Verbraucherschutz</a>) <img src="https://cpx.golem.de/cpx.php?class=17&amp;aid=158391&amp;page=1&amp;ts=1627054380" alt="" width="1" height="1" />';

        $imageUrls = Helper::getImageUrlsForFeedItem($feedItemUrl, $content, new Client());

        static::assertEquals(['https://www.golem.de/2107/158391-284735-284731_rc.jpg'], $imageUrls->toArray());
    }

    /**
     * @dataProvider faviconProvider
     */
    public function testGetHttpContentTypeForUrl(string $faviconUrl, ?string $expectedContentType): void
    {
        static::assertSame($expectedContentType, Helper::getHttpContentTypeForUrl($faviconUrl, new Client()));
    }

    /**
     * @return array<string, array<string|null>>
     */
    public static function faviconProvider(): array
    {
        return [
            'PetaPixel' => [
                'https://petapixel.com/wp-content/themes/petapixel-2017/assets/prod/img/favicon.ico',
                'image/x-icon',
            ],
            'Hacker News' => [
                'https://news.ycombinator.com/favicon.ico',
                'image/x-icon',
            ],
            'Statamic' => [
                'https://statamic.dev/img/favicons/apple-touch-icon-57x57.png',
                'image/png',
            ],
            'Invalid Url' => [
                'https://test.dev',
                null,
            ],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        static::addToAssertionCount($this->failingTestClassMock->mockery_getExpectationCount());
    }
}
