<?php

namespace Kaishiyoku\HeraRssCrawler;

use Carbon\Carbon;
use Kaishiyoku\HeraRssCrawler\Models\Rss\FeedItem;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * @covers HeraRssCrawler
 */
class HeraRssCrawlerTest extends TestCase
{
    use MatchesSnapshots;

    /**
     * @var HeraRssCrawler|null
     */
    private $heraRssCrawler;

    protected function setUp(): void
    {
        $this->heraRssCrawler = new HeraRssCrawler();
    }

    /**
     * @dataProvider websiteProvider
     * @covers       HeraRssCrawler::discoverFeedUrls()
     * @param $url
     * @param $expectedUrls
     * @return void
     */
    public function testDiscoverFeedUrl($url, $expectedUrls): void
    {
        $actual = $this->heraRssCrawler->discoverFeedUrls($url);

        $this->assertEquals($expectedUrls, $actual->toArray());
    }

    /**
     * @dataProvider feedProvider
     * @covers       HeraRssCrawler::parseFeed()
     * @param $feedUrls
     * @return void
     */
    public function testParseFeed($feedUrls): void
    {
        foreach ($feedUrls as $key => $feedUrl) {
            $feed = $this->heraRssCrawler->parseFeed($feedUrl);

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

            $this->assertNotEmpty($feed->getCreatedAt());
            $this->assertNotEmpty($feed->getUpdatedAt());
            $this->assertGreaterThanOrEqual(0, $feed->getFeedItems()->count());
        }
    }

    /**
     * @covers       HeraRssCrawler::generateChecksumForFeedItem()
     * @return void
     */
    public function testGenerateChecksumForFeedItem(): void
    {
        $expected = '0339bfd7b25e3ae5bc304a5d64a8474baac5eb30036356534a29802bf5ad2e5f';

        $feedItem = new FeedItem();
        $feedItem->setCategories(collect(['Zeitgeschehen']));
        $feedItem->setAuthors(collect(['ZEIT ONLINE: Zeitgeschehen - Alena Kammer']));
        $feedItem->setTitle('Gabun: Piraten töten Kapitän und entführen Matrosen');
        $feedItem->setCommentCount(0);
        $feedItem->setCommentFeedLink(null);
        $feedItem->setCommentLink(null);
        $feedItem->setCreatedAt(Carbon::parse('2019-12-22 18:28:44.0 +00:00'));
        $feedItem->setUpdatedAt(Carbon::parse('2019-12-22 18:28:44.0 +00:00'));
        $feedItem->setDescription('<a href="https://www.zeit.de/gesellschaft/zeitgeschehen/2019-12/gabun-piraterie-angriff-libreville-entfuehrung"><img style="float:left; margin-right:5px" src="https://img.zeit.de/gesellschaft/zeitgeschehen/2019-12/gabun-libreville-hafen-piraterie-angriff/wide__148x84"></a> Im Hafen der Hauptstadt Libreville haben Piraten vier Schiffe überfallen. Nach Angaben der Regierung wurde ein Kapitän getötet und vier Matrosen wurden entführt.');
        $feedItem->setEnclosureUrl(null);
        $feedItem->setEncoding('UTF-8');
        $feedItem->setId('{urn:uuid:a56e1e5f-a630-4cd6-aa51-cdb896904ee9}');
        $feedItem->setLinks(collect(['https://www.zeit.de/gesellschaft/zeitgeschehen/2019-12/gabun-piraterie-angriff-libreville-entfuehrung']));
        $feedItem->setPermalink('https://www.zeit.de/gesellschaft/zeitgeschehen/2019-12/gabun-piraterie-angriff-libreville-entfuehrung');
        $feedItem->setType('rss-20');
        $feedItem->setContent('');
        $feedItem->setChecksum('not important here');

        $this->assertEquals($expected, HeraRssCrawler::generateChecksumForFeedItem($feedItem));

        $feedItem2 = clone $feedItem;
        $feedItem2->setTitle('Title has changed');

        $this->assertNotEquals($expected, HeraRssCrawler::generateChecksumForFeedItem($feedItem2));
    }

    /**
     * @return array
     */
    public function websiteProvider(): array
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

    /**
     * @return array
     */
    public function feedProvider(): array
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
