<?php

namespace Kaishiyoku\HeraRssCrawler;

use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * @covers HeraRssCrawler
 */
class HeraRssCrawlerTest extends TestCase
{
    use MatchesSnapshots;

    /**
     * @dataProvider websiteProvider
     * @covers HeraRssCrawler::discoverFeedUrls()
     */
    public function testDiscoverFeedUrl($url, $expectedUrls)
    {
        $heraRssCrawler = new HeraRssCrawler($url);

        $actual = $heraRssCrawler->discoverFeedUrls();

        if ($actual->count() != count($expectedUrls)) {
            dd($actual);
        }

        $this->assertEquals($expectedUrls, $actual->toArray());
    }

    /**
     * @dataProvider feedProvider
     * @covers HeraRssCrawler::discover()
     */
    public function testParse($feedUrls)
    {
        foreach ($feedUrls as $key => $feedUrl) {
            $heraRssCrawler = new HeraRssCrawler($feedUrl);

            $feed = $heraRssCrawler->parse();

            $feedArr = [
                'title' => $feed->getTitle(),
                'copyright' => $feed->getCopyright(),
                'description' => $feed->getDescription(),
                'feedUrl' => $feed->getFeedUrl(),
                'id' => $feed->getId(),
                'language' => $feed->getLanguage(),
                'url' => $feed->getUrl(),
            ];

            $this->assertMatchesSnapshot($feedArr);

//            $this->assertNotEmpty($feed->getCategories());
//            $this->assertNotEmpty($feed->getAuthors());
            $this->assertNotEmpty($feed->getCreatedAt());
            $this->assertNotEmpty($feed->getUpdatedAt());
            $this->assertGreaterThanOrEqual(0, $feed->getItems()->count());
        }
    }

    public function websiteProvider()
    {
        return [
            'Zeit' => [
                'https://www.zeit.de',
                [
                    'https://newsfeed.zeit.de/index'
                ],
            ],
            'FAZ' => [
                'https://www.faz.net',
                [
                    'https://www.faz.net/rss/aktuell'
                ],
            ],
            'Anime2You' => [
                'https://www.anime2you.de',
                [
                    'http://www.anime2you.de/feed'
                ],
            ],
            'blog :: Brent -> [String]' => [
                'https://byorgey.wordpress.com/',
                [
                    'https://byorgey.wordpress.com/feed',
                    'https://byorgey.wordpress.com/comments/feed'
                ],
            ],
            'Echo JS' => [
                'http://www.echojs.com/',
                [
                    'http://www.echojs.com/rss'
                ],
            ],
            'Hacker News: Newest (min. 100 points)' => [
                'https://news.ycombinator.com/newest',
                [
                    'http://hnrss.org/newest?points=100'
                ],
            ],
            'Laravel News' => [
                'https://laravel-news.com/',
                [
                    'https://feed.laravel-news.com'
                ],
            ],
            'Unknown Worlds Entertainment' => [
                'https://unknownworlds.com/',
                [
                    'https://unknownworlds.com/feed',
                    'https://unknownworlds.com/homepage-2/feed'
                ],
            ],
            'Welt - Politcs' => [
                'https://www.welt.de/feeds/section/politik.rss',
                [
                    'https://www.welt.de/feeds/section/politik.rss'
                ]
            ],
            'TrekCast' => [
                'https://www.startrek-index.de/trekcast',
                [
                    'https://www.startrek-index.de/trekcast/feed',
                    'https://www.startrek-index.de/trekcast/feed/atom',
                    'https://www.startrek-index.de/trekcast/comments/feed',
                    'https://www.startrek-index.de/trekcast/feed/podcast',
                ],
            ],
            'Stephan Wiesner Blog' => [
                'https://www.stephanwiesner.de/blog',
                [
                    'https://www.stephanwiesner.de/blog/feed',
                    'https://www.stephanwiesner.de/blog/comments/feed',
                ],
            ],
            'Shiroku' => [
                'http://shiroutang.blogspot.com/',
                [
                    'https://shiroutang.blogspot.com/feeds/posts/default',
                    'https://shiroutang.blogspot.com/feeds/posts/default?alt=rss',
                    'https://www.blogger.com/feeds/7456002711322960081/posts/default',
                ],
            ],
            'React' => [
                'https://facebook.github.io/react',
                [
                    'https://facebook.github.io/react/feed.xml'
                ],
            ],
            'PHP' => [
                'http://php.net',
                [
                    'https://www.php.net/releases/feed.php',
                    'https://www.php.net/feed.atom',
                ],
            ],
            'PHP Internals' => [
                'https://phpinternals.news/',
                [
                    'https://phpinternals.news/feed.rss',
                ],
            ],
            'Nutrition Facts' => [
                'https://nutritionfacts.org/',
                [
                    'http://nutritionfacts.org/feed',
                    'http://nutritionfacts.org/feed/?post_type=video',
                    'http://nutritionfacts.org/audio/feed/podcast',
                    'https://nutritionfacts.org/videos/feed/podcast',
                    'http://nutritionfacts.org/feed/podcast',
                ],
            ],
            'JRock News' => [
                'https://www.jrocknews.com/',
                [
                    'https://jrocknews.com/feed',
                    'https://jrocknews.com/comments/feed',
                ],
            ],
            'Non-existent website' => [
                'https://www.nonexistent-website.dev',
                [],
            ],
        ];
    }

    public function feedProvider()
    {
        return [
            'Zeit' => [
                [
                    'https://newsfeed.zeit.de/index',
                ],
            ],
            'FAZ' => [
                [
                    'https://www.faz.net/rss/aktuell',
                ],
            ],
            'Anime2You' => [
                [
                    'http://www.anime2you.de/feed'
                ],
            ],
            'blog :: Brent -> [String]' => [
                [
                    'https://byorgey.wordpress.com/feed',
                    'https://byorgey.wordpress.com/comments/feed'
                ],
            ],
            'Echo JS' => [
                [
                    'http://www.echojs.com/rss'
                ],
            ],
            'Hacker News: Newest (min. 100 points)' => [
                [
                    'http://hnrss.org/newest?points=100'
                ],
            ],
            'Laravel News' => [
                [
                    'https://feed.laravel-news.com'
                ],
            ],
            'Unknown Worlds Entertainment' => [
                [
                    'https://unknownworlds.com/feed',
                    'https://unknownworlds.com/homepage-2/feed'
                ],
            ],
            'Welt - Politcs' => [
                [
                    'https://www.welt.de/feeds/section/politik.rss'
                ],
            ],
            'TrekCast' => [
                [
                    'https://www.startrek-index.de/trekcast/feed',
                    'https://www.startrek-index.de/trekcast/feed/atom',
                    'https://www.startrek-index.de/trekcast/comments/feed',
                    'https://www.startrek-index.de/trekcast/feed/podcast',
                ],
            ],
            'Stephan Wiesner Blog' => [
                [
                    'https://www.stephanwiesner.de/blog/feed',
                    'https://www.stephanwiesner.de/blog/comments/feed',
                ],
            ],
            'Shiroku' => [
                [
                    'https://shiroutang.blogspot.com/feeds/posts/default',
                    'https://shiroutang.blogspot.com/feeds/posts/default?alt=rss',
                    'https://www.blogger.com/feeds/7456002711322960081/posts/default',
                ],
            ],
            'React' => [
                [
                    'https://facebook.github.io/react/feed.xml'
                ],
            ],
            'PHP' => [
                [
                    'https://www.php.net/releases/feed.php',
                    'https://www.php.net/feed.atom',
                ],
            ],
            'PHP Internals' => [
                [
                    'https://phpinternals.news/feed.rss',
                ],
            ],
            'Nutrition Facts' => [
                [
                    'http://nutritionfacts.org/feed',
                    'http://nutritionfacts.org/feed/?post_type=video',
                    'http://nutritionfacts.org/audio/feed/podcast',
                    'https://nutritionfacts.org/videos/feed/podcast',
                    'http://nutritionfacts.org/feed/podcast',
                ],
            ],
            'JRock News' => [
                [
                    'https://jrocknews.com/feed',
                    'https://jrocknews.com/comments/feed',
                ],
            ],
        ];
    }
}
