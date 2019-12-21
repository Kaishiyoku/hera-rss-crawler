<?php

namespace Kaishiyoku\HeraRssCrawler;

use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class HeraRssCrawlerTest extends TestCase
{
    use MatchesSnapshots;

    /**
     * @dataProvider websiteProvider
     */
    public function testCrawlsWebsite($url, $expectedUrls)
    {
        $heraRssCrawler = new HeraRssCrawler($url);

        $actual = $heraRssCrawler->discoverFeedUrl();

        $this->assertEquals($expectedUrls, $actual->toArray());
    }

    public function websiteProvider()
    {
        return [
            'Zeit' => [
                'https://www.zeit.de',
                ['https://newsfeed.zeit.de/index'],
            ],
            'FAZ' => [
                'https://www.faz.net',
                ['https://www.faz.net/rss/aktuell/'],
            ],
            'Anime2You' => [
                'https://www.anime2you.de',
                [],
            ],
            'blog :: Brent -> [String]' => [
                'https://byorgey.wordpress.com/',
                ['https://byorgey.wordpress.com/feed/', 'https://byorgey.wordpress.com/comments/feed/'],
            ],
            'Echo JS' => [
                'http://www.echojs.com/',
                ['http://www.echojs.com//rss'],
            ],
            'Hacker News: Newest (min. 100 points)' => [
                'https://news.ycombinator.com/newest',
                [],
            ],
            'Laravel News' => [
                'https://laravel-news.com/',
                ['https://feed.laravel-news.com/'],
            ],
            'Unknown Worlds Entertainment' => [
                'https://unknownworlds.com/',
                ['https://unknownworlds.com/feed/', 'https://unknownworlds.com/homepage-2/feed/'],
            ],
            'Welt - Politcs' => [
                'https://www.welt.de/feeds/section/politik.rss',
                ['https://www.welt.de/feeds/section/politik.rss']
            ],
        ];
    }
}
