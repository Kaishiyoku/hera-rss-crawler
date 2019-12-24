<?php

namespace Kaishiyoku\HeraRssCrawler;

use Carbon\Carbon;
use Kaishiyoku\HeraRssCrawler\Models\Rss\Feed;
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
     * @param string $url
     * @param array $expectedUrls
     * @return void
     */
    public function testDiscoverFeedUrls(string $url, array $expectedUrls): void
    {
        $actual = $this->heraRssCrawler->discoverFeedUrls($url);

        $this->assertEquals($expectedUrls, $actual->toArray());
    }

    /**
     * @dataProvider feedProvider
     * @covers       HeraRssCrawler::parseFeed()
     * @param array $feedUrls
     * @param array $expectedValues
     * @return void
     */
    public function testParseFeed(array $feedUrls, array $expectedValues): void
    {
        foreach ($feedUrls as $key => $feedUrl) {
            $feed = $this->heraRssCrawler->parseFeed($feedUrl);

            if ($expectedValues[$key]) {
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

                $this->assertNotEmpty($feed->getChecksum());
                $this->assertGreaterThanOrEqual(0, $feed->getFeedItems()->count());

                if ($feed->getFeedItems()->isNotEmpty()) {
                    $this->assertNotEmpty($feed->getFeedItems()->first()->getChecksum());
                }
            } else {
                $this->assertNull($feed);
            }
        }
    }

    /**
     * @covers HeraRssCrawler::discoverAndParseFeeds()
     * @return void
     */
    public function testDiscoverAndParseFeeds(): void
    {
        $feeds = $this->heraRssCrawler->discoverAndParseFeeds('https://byorgey.wordpress.com/');
        $actual = $feeds->map(function (Feed $feed) {
            return $feed->getTitle();
        })->toArray();

        $this->assertMatchesSnapshot($actual);
    }

    /**
     * @covers HeraRssCrawler::generateChecksumForFeedItem()
     * @return void
     */
    public function testGenerateChecksumForFeedItem(): void
    {
        $expected = 'cf951e1f98ee8aeb47d863dc72bbf57628ff348f37f0b25da0a2d702ae118af4';

        $feedItem = self::getSampleFeedItem();

        $this->assertEquals($expected, HeraRssCrawler::generateChecksumForFeedItem($feedItem));

        $feedItem2 = clone $feedItem;
        $feedItem2->setTitle('Title has changed');

        $this->assertNotEquals($expected, HeraRssCrawler::generateChecksumForFeedItem($feedItem2));

        $expectedSha512 = '1d86c54606888d855ae65fdf0447716075a52c459f966fa3f30457fd09fc2f85f29670e123ae1f4ea2a0ef3c3bd656ce6ca12816db2868df7e5aa202d05b18fb';

        $this->assertEquals($expectedSha512, HeraRssCrawler::generateChecksumForFeedItem($feedItem, '__', Hash::SHA_512));
        $this->assertNotEquals($expectedSha512, HeraRssCrawler::generateChecksumForFeedItem($feedItem, '--', Hash::SHA_512));
    }

    /**
     * @covers HeraRssCrawler::generateChecksumForFeed()
     * @return void
     */
    public function testGenerateChecksumForFeed(): void
    {
        $expected = 'a4e32c21b9887713e1d1c355c7cdad964329d730c512d1ea114c40e060b01a1e';

        $feed = self::getSampleFeed();

        $this->assertEquals($expected, HeraRssCrawler::generateChecksumForFeed($feed));

        $feed2 = clone $feed;
        $feed2->setTitle('Title has changed');

        $this->assertNotEquals($expected, HeraRssCrawler::generateChecksumForFeed($feed2));

        $expectedSha512 = 'a1defda8006069a841074bbee33b2edccfd073acd90deb3557bb56a4b1b83280ef7c8033e65d3b98a56e066aef70fec428e7069a2c0666a35e53778e875484ac';

        $feed3 = clone $feed;
        $feed3->setFeedItems(collect([self::getSampleFeedItem(), self::getSampleFeedItem()]));

        $this->assertNotEquals($expected, HeraRssCrawler::generateChecksumForFeed($feed3));

        $this->assertEquals($expectedSha512, HeraRssCrawler::generateChecksumForFeed($feed, '__', Hash::SHA_512));
        $this->assertNotEquals($expectedSha512, HeraRssCrawler::generateChecksumForFeed($feed, '--', Hash::SHA_512));
    }

    /**
     * @dataProvider websiteProvider
     * @covers       HeraRssCrawler::discoverFeedUrls()
     * @param string $url
     * @param array $expectedUrls
     * @param string|null $expectedFaviconUrl
     * @return void
     */
    public function testDiscoverFavicon(string $url, array $expectedUrls, ?string $expectedFaviconUrl): void
    {
        $faviconUrl = $this->heraRssCrawler->discoverFavicon($url);

        $this->assertEquals($expectedFaviconUrl, $faviconUrl);
    }

    /**
     * @dataProvider feedProvider
     * @covers       HeraRssCrawler::checkIfConsumableFeed()
     * @param array $feedUrls
     * @param array $expectedValues
     */
    public function testCheckIfConsumableFeed(array $feedUrls, array $expectedValues): void
    {
        foreach ($feedUrls as $key => $feedUrl) {
            $isConsumableFeed = $this->heraRssCrawler->checkIfConsumableFeed($feedUrl);

            $this->assertEquals($expectedValues[$key], $isConsumableFeed);
        }
    }

    /**
     * @return Feed
     */
    private static function getSampleFeed(): Feed
    {
        $feed = new Feed();
        $feed->setDescription('Lorem ipsum.');
        $feed->setCreatedAt(Carbon::parse('2019-12-22 18:28:44.0 +00:00'));
        $feed->setUpdatedAt(Carbon::parse('2019-12-22 18:28:44.0 +00:00'));
        $feed->setTitle('Test');
        $feed->setAuthors(collect(['John Doe', 'Jane Doe']));
        $feed->setCategories(collect(['A', 'B']));
        $feed->setCopyright('None');
        $feed->setFeedItems(collect([self::getSampleFeedItem()]));
        $feed->setFeedUrl('https://google.com');
        $feed->setId('Sample-ID');
        $feed->setLanguage('en');
        $feed->setUrl('https://google.com');

        return $feed;
    }

    /**
     * @return FeedItem
     */
    private static function getSampleFeedItem(): FeedItem
    {
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

        return $feedItem;
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
                'https://www.zeit.de/favicon.ico',
            ],
            'FAZ' => [
                'https://www.faz.net',
                [
                    'https://www.faz.net/rss/aktuell'
                ],
                'https://www.faz.net/favicon.ico',
            ],
            'Anime2You' => [
                'https://www.anime2you.de',
                [
                    'http://www.anime2you.de/feed'
                ],
                null,
            ],
            'blog :: Brent -> [String]' => [
                'https://byorgey.wordpress.com/',
                [
                    'https://byorgey.wordpress.com/feed',
                    'https://byorgey.wordpress.com/comments/feed'
                ],
                'https://s1.wp.com/i/favicon.ico',
            ],
            'Echo JS' => [
                'http://www.echojs.com/',
                [
                    'http://www.echojs.com/rss'
                ],
                'http://www.echojs.com/favicon.ico',
            ],
            'Hacker News: Newest (min. 100 points)' => [
                'https://news.ycombinator.com/newest',
                [
                    'http://hnrss.org/newest?points=100'
                ],
                'https://news.ycombinator.com/favicon.ico',
            ],
            'Laravel News' => [
                'https://laravel-news.com/',
                [
                    'https://feed.laravel-news.com'
                ],
                'https://laravel-news.com/apple-touch-icon.png',
            ],
            'Unknown Worlds Entertainment' => [
                'https://unknownworlds.com/',
                [
                    'https://unknownworlds.com/feed',
                    'https://unknownworlds.com/homepage-2/feed'
                ],
                'https://2i1suz1s0n5g1i6ph4z0sw1b-wpengine.netdna-ssl.com/favicon.png',
            ],
            'Welt - Politcs' => [
                'https://www.welt.de/politik/',
                [
                    'http://www.welt.de/politik/?service=Rss',
                    'http://www.welt.de/politik/ausland/?service=Rss',
                    'http://www.welt.de/politik/deutschland/?service=Rss',
                ],
                'https://www.welt.de/assets/images/global/welt-w-icon-229e79389f.svg',
            ],
            'TrekCast' => [
                'https://www.startrek-index.de/trekcast',
                [
                    'https://www.startrek-index.de/trekcast/feed',
                    'https://www.startrek-index.de/trekcast/feed/atom',
                    'https://www.startrek-index.de/trekcast/comments/feed',
                    'https://www.startrek-index.de/trekcast/feed/podcast',
                ],
                'https://www.startrek-index.de/trekcast/favicon.ico',
            ],
            'Stephan Wiesner Blog' => [
                'https://www.stephanwiesner.de/blog',
                [
                    'https://www.stephanwiesner.de/blog/feed',
                    'https://www.stephanwiesner.de/blog/comments/feed',
                ],
                'https://www.stephanwiesner.de/blog/wp-content/uploads/2016/06/cropped-DSC4384-3-32x32.jpg',
            ],
            'Shiroku' => [
                'http://shiroutang.blogspot.com/',
                [
                    'https://shiroutang.blogspot.com/feeds/posts/default',
                    'https://shiroutang.blogspot.com/feeds/posts/default?alt=rss',
                    'https://www.blogger.com/feeds/7456002711322960081/posts/default',
                ],
                'https://shiroutang.blogspot.com/favicon.ico',
            ],
            'React' => [
                'https://facebook.github.io/react',
                [
                    'https://facebook.github.io/react/feed.xml'
                ],
                'https://facebook.github.io/react/favicon.ico',
            ],
            'PHP' => [
                'http://php.net',
                [
                    'https://www.php.net/releases/feed.php',
                    'https://www.php.net/feed.atom',
                ],
                'https://www.php.net/favicon.ico',
            ],
            'PHP Internals' => [
                'https://phpinternals.news/',
                [
                    'https://phpinternals.news/feed.rss',
                ],
                null,
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
                null,
            ],
            'JRock News' => [
                'https://www.jrocknews.com/',
                [
                    'https://jrocknews.com/feed',
                    'https://jrocknews.com/comments/feed',
                ],
                'https://jrocknews.com/wp-content/uploads/2015/05/cropped-JRN-icon-2017-32x32.png',
            ],
            'Non-existent website' => [
                'https://www.nonexistent-website.dev',
                [],
                null,
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
                [
                    true,
                ],
            ],
            'FAZ' => [
                [
                    'https://www.faz.net/rss/aktuell',
                ],
                [
                    true,
                ],
            ],
            'Anime2You' => [
                [
                    'http://www.anime2you.de/feed'
                ],
                [
                    true,
                ],
            ],
            'blog :: Brent -> [String]' => [
                [
                    'https://byorgey.wordpress.com/feed',
                    'https://byorgey.wordpress.com/comments/feed'
                ],
                [
                    true,
                    true,
                ],
            ],
            'Echo JS' => [
                [
                    'http://www.echojs.com/rss'
                ],
                [
                    true,
                ],
            ],
            'Hacker News: Newest (min. 100 points)' => [
                [
                    'http://hnrss.org/newest?points=100'
                ],
                [
                    true,
                ],
            ],
            'Laravel News' => [
                [
                    'https://feed.laravel-news.com'
                ],
                [
                    true,
                ],
            ],
            'Unknown Worlds Entertainment' => [
                [
                    'https://unknownworlds.com/feed',
                    'https://unknownworlds.com/homepage-2/feed'
                ],
                [
                    true,
                    true,
                ],
            ],
            'Welt - Politcs' => [
                [
                    'https://www.welt.de/feeds/section/politik.rss'
                ],
                [
                    true,
                ],
            ],
            'TrekCast' => [
                [
                    'https://www.startrek-index.de/trekcast/feed',
                    'https://www.startrek-index.de/trekcast/feed/atom',
                    'https://www.startrek-index.de/trekcast/comments/feed',
                    'https://www.startrek-index.de/trekcast/feed/podcast',
                ],
                [
                    true,
                    true,
                    true,
                    true,
                ],
            ],
            'Stephan Wiesner Blog' => [
                [
                    'https://www.stephanwiesner.de/blog/feed',
                    'https://www.stephanwiesner.de/blog/comments/feed',
                ],
                [
                    true,
                    true,
                ],
            ],
            'Shiroku' => [
                [
                    'https://shiroutang.blogspot.com/feeds/posts/default',
                    'https://shiroutang.blogspot.com/feeds/posts/default?alt=rss',
                    'https://www.blogger.com/feeds/7456002711322960081/posts/default',
                ],
                [
                    true,
                    true,
                    true,
                ],
            ],
            'React' => [
                [
                    'https://facebook.github.io/react/feed.xml'
                ],
                [
                    true,
                ],
            ],
            'PHP' => [
                [
                    'https://www.php.net/releases/feed.php',
                    'https://www.php.net/feed.atom',
                ],
                [
                    true,
                    true,
                ],
            ],
            'PHP Internals' => [
                [
                    'https://phpinternals.news/feed.rss',
                ],
                [
                    true,
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
                [
                    true,
                    true,
                    true,
                    true,
                    true,
                ],
            ],
            'JRock News' => [
                [
                    'https://jrocknews.com/feed',
                    'https://jrocknews.com/comments/feed',
                ],
                [
                    true,
                    true,
                ],
            ],
            'Non-existent website' => [
                [
                    'https://www.nonexistent-website.dev',
                ],
                [
                    false,
                ],
            ],
        ];
    }
}
