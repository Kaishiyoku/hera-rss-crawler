<?php

namespace Kaishiyoku\HeraRssCrawler;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use Kaishiyoku\HeraRssCrawler\Models\Rss\Feed;
use Kaishiyoku\HeraRssCrawler\Models\Rss\FeedItem;
use PHPUnit\Framework\TestCase;
use ReflectionException;
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
     * @param string|null $expectedFaviconUrl
     * @param bool $throwsConnectException
     * @return void
     * @throws Exception
     */
    public function testDiscoverFeedUrls(string $url, array $expectedUrls, ?string $expectedFaviconUrl = null, bool $throwsConnectException = false): void
    {
        if ($throwsConnectException) {
            $this->expectException(ConnectException::class);
        }

        $actual = $this->heraRssCrawler->discoverFeedUrls($url);

        if (!$throwsConnectException) {
            $this->assertEquals($expectedUrls, $actual->toArray());
        }
    }

    /**
     * @dataProvider feedProvider
     * @covers       HeraRssCrawler::parseFeed()
     * @param array $feedUrls
     * @param array $expectedValues
     * @param bool $throwsConnectException
     * @return void
     * @throws Exception
     */
    public function testParseFeed(array $feedUrls, array $expectedValues, bool $throwsConnectException = false): void
    {
        foreach ($feedUrls as $key => $feedUrl) {
            if ($throwsConnectException) {
                $this->expectException(ConnectException::class);
            }

            $feed = $this->heraRssCrawler->parseFeed($feedUrl);

            if (!$throwsConnectException) {
                if ($expectedValues[$key]) {
                    $feedArr = [
                        'title' => $feed->getTitle(),
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
    }

    /**
     * @covers HeraRssCrawler::discoverAndParseFeeds()
     * @return void
     * @throws Exception
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
     * @throws ReflectionException
     */
    public function testGenerateChecksumForFeedItem(): void
    {
        $expected = '47d6926b4f93b32e6bbadadb2a8926f14229cc75250da634229bad58d310c086';

        $feedItem = self::getSampleFeedItem();

        $this->assertEquals($expected, HeraRssCrawler::generateChecksumForFeedItem($feedItem));

        $feedItem2 = clone $feedItem;
        $feedItem2->setTitle('Title has changed');

        $this->assertNotEquals($expected, HeraRssCrawler::generateChecksumForFeedItem($feedItem2));

        $expectedSha512 = 'a64b86da753fcc3d85fdcd5c9d2ef65530b20cf9c0eb9acfa972a53dbda2fbea94b733c1958a341535ef3a9785916f6297f41ab5ed0e497cfe6540451158fc04';

        $this->assertEquals($expectedSha512, HeraRssCrawler::generateChecksumForFeedItem($feedItem, '__', Hash::SHA_512));
        $this->assertNotEquals($expectedSha512, HeraRssCrawler::generateChecksumForFeedItem($feedItem, '--', Hash::SHA_512));
    }

    /**
     * @covers HeraRssCrawler::generateChecksumForFeed()
     * @return void
     * @throws ReflectionException
     */
    public function testGenerateChecksumForFeed(): void
    {
        $expected = 'c0ccb8a70967a70fa120f7d7109165b75a5ff3355442d80774ac31fa9d6a89dd';

        $feed = self::getSampleFeed();

        $this->assertEquals($expected, HeraRssCrawler::generateChecksumForFeed($feed));

        $feed2 = clone $feed;
        $feed2->setTitle('Title has changed');

        $this->assertNotEquals($expected, HeraRssCrawler::generateChecksumForFeed($feed2));

        $expectedSha512 = '1d025eb44d8035465b5a573646e5f95379bcff8c49b6cfc704e12bee3ffef930a4c9129897bef1b9746d625e2a9894eb662160e4ecc6f088a9c96df2590d0205';

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
     * @param bool $throwsConnectException
     * @return void
     * @throws Exception
     */
    public function testDiscoverFavicon(string $url, array $expectedUrls, ?string $expectedFaviconUrl = null, bool $throwsConnectException = false): void
    {
        if ($throwsConnectException) {
            $this->expectException(ConnectException::class);
        }

        $faviconUrl = $this->heraRssCrawler->discoverFavicon($url);

        if (!$throwsConnectException) {
            $this->assertEquals($expectedFaviconUrl, $faviconUrl);
        }
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
     * @covers Helper::replaceBaseUrl
     */
    public function testReplaceBaseUrl(): void
    {
        $newUrl = Helper::replaceBaseUrl('https://www.reddit.com/r/ns2/new/', 'https://www.reddit.com/', 'https://old.reddit.com/');
        $this->assertEquals('https://old.reddit.com/r/ns2/new/', $newUrl);

        $newUrl2 = Helper::replaceBaseUrl('https://site.dev/test?query=hello_world', 'https://site.dev', 'https://new.site.dev');
        $this->assertEquals('https://new.site.dev/test?query=hello_world', $newUrl2);

        $newUrl3 = Helper::replaceBaseUrl('https://www.google.com/?query=hello_world', 'https://site.dev', 'https://new.site.dev');
        $this->assertEquals('https://www.google.com/?query=hello_world', $newUrl3);
    }

    /**
     * @covers Helper::replaceBaseUrls()
     */
    public function testReplaceBaseUrls(): void
    {
        $urlReplacementMap = [
            'https://site.dev' => 'https://new.site.dev',
            'https://www.reddit.com/' => 'https://old.reddit.com/',
        ];

        $url = Helper::replaceBaseUrls('https://www.reddit.com/r/ns2/new/', $urlReplacementMap);
        $this->assertEquals('https://old.reddit.com/r/ns2/new/', $url);

        $url2 = Helper::replaceBaseUrls('https://site.dev/test?query=hello_world', $urlReplacementMap);
        $this->assertEquals('https://new.site.dev/test?query=hello_world', $url2);

        $url3 = Helper::replaceBaseUrls('https://www.google.com/?query=hello_world', $urlReplacementMap);
        $this->assertEquals('https://www.google.com/?query=hello_world', $url3);
    }

    /**
     * @covers HeraRssCrawler::discoverFeedUrls()
     */
    public function testDiscoverRedditFeedUrls(): void
    {
        $heraRssCrawler = new HeraRssCrawler();

        $feed = $heraRssCrawler->parseFeed('https://www.reddit.com/r/ns2/new/.rss');
        $this->assertInstanceOf(Feed::class, $feed);

        $feedUrls = $heraRssCrawler->discoverFeedUrls('https://www.reddit.com/r/ns2/new/');
        $this->assertEquals(['https://old.reddit.com/r/ns2/new/.rss'], $feedUrls->toArray());

        $heraRssCrawler->setUrlReplacementMap([
            'https://site.dev' => 'https://new.site.dev',
            'https://www.reddit.com/' => 'https://old.reddit.com/',
        ]);

        $feedUrls = $heraRssCrawler->discoverFeedUrls('https://www.reddit.com/r/ns2/new/');
        $this->assertEquals(['https://old.reddit.com/r/ns2/new/.rss'], $feedUrls->toArray());

        $heraRssCrawler->setUrlReplacementMap([
            'https://site.dev' => 'https://new.site.dev',
        ]);

        $feedUrls = $heraRssCrawler->discoverFeedUrls('https://www.reddit.com/r/ns2/new/');
        $this->assertEmpty($feedUrls->toArray());
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
            '22 Places' => [
                'https://www.22places.de/fotografie-blog/',
                [
                    'https://www.22places.de/rsslatest.xml',
                ],
                'https://www.22places.de/images/2017/12/cropped-171207_22places_Logo_Favicon_tuerkis-32x32.png',
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
                true,
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
            '22 Places' => [
                [
                    'https://www.22places.de/rsslatest.xml',
                ],
                [
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
                true,
            ],
        ];
    }
}
