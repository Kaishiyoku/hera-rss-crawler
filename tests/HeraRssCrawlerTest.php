<?php

namespace Kaishiyoku\HeraRssCrawler;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Kaishiyoku\HeraRssCrawler\FeedDiscoverers\FeedDiscoverer;
use Kaishiyoku\HeraRssCrawler\FeedDiscoverers\FeedDiscovererByContentType;
use Kaishiyoku\HeraRssCrawler\Models\ResponseContainer;
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

    /**
     * @var FeedItem
     */
    private $sampleFeedItem;

    protected function setUp(): void
    {
        $this->heraRssCrawler = new HeraRssCrawler();

        $this->sampleFeedItem = new FeedItem();
        $this->sampleFeedItem->setCategories(new Collection(['News', 'Tech']));
        $this->sampleFeedItem->setAuthors(new Collection(['John Doe', 'Jane Doe']));
        $this->sampleFeedItem->setTitle('Telekommunikation: Vodafone Deutschland bekommt einen neuen Chef');
        $this->sampleFeedItem->setCommentCount(5);
        $this->sampleFeedItem->setCommentFeedLink(null);
        $this->sampleFeedItem->setCommentLink(null);
        $this->sampleFeedItem->setContent('<img src=\"https:\/\/www.golem.de\/2204\/164697-323081-323077_rc.jpg\" width=\"140\" height=\"140\" vspace=\"3\" hspace=\"8\" align=\"left\">Der CEO Hannes Ametsreiter tritt vorzeitig von seinem Chefposten bei Vodafone Deutschland ab. Ihm folgt ein erfahrener Microsoft-Manager. (<a href=\"https:\/\/www.golem.de\/specials\/vodafone\/\">Vodafone<\/a>, <a href=\"https:\/\/www.golem.de\/specials\/microsoft\/\">Microsoft<\/a>) <img src=\"https:\/\/cpx.golem.de\/cpx.php?class=17&amp;aid=164697&amp;page=1&amp;ts=1650381962\" alt=\"\" width=\"1\" height=\"1\" \/>');
        $this->sampleFeedItem->setCreatedAt(Carbon::parse('2022-04-19T15:26:02.000000Z'));
        $this->sampleFeedItem->setUpdatedAt(Carbon::parse('2022-04-19T15:26:02.000000Z'));
        $this->sampleFeedItem->setDescription('Der CEO Hannes Ametsreiter tritt vorzeitig von seinem Chefposten bei Vodafone Deutschland ab. Ihm folgt ein erfahrener Microsoft-Manager. (<a href=\"https:\/\/www.golem.de\/specials\/vodafone\/\">Vodafone<\/a>, <a href=\"https:\/\/www.golem.de\/specials\/microsoft\/\">Microsoft<\/a>) <img src=\"https:\/\/cpx.golem.de\/cpx.php?class=17&amp;aid=164697&amp;page=1&amp;ts=1650381962\" alt=\"\" width=\"1\" height=\"1\" \/>');
        $this->sampleFeedItem->setEnclosureUrl(null);
        $this->sampleFeedItem->setImageUrls(new Collection(['https:\/\/www.golem.de\/2204\/164697-323081-323077_rc.jpg', 'https:\/\/cpx.golem.de\/cpx.php?class=17&amp;aid=164697&amp;page=1&amp;ts=1650381962']));
        $this->sampleFeedItem->setEncoding('ISO-8859-1');
        $this->sampleFeedItem->setId('https:\/\/www.golem.de\/news\/telekommunikation-vodafone-deutschland-bekommt-einen-neuen-chef-2204-164697-rss.html');
        $this->sampleFeedItem->setLinks(new Collection(['https:\/\/www.golem.de\/news\/telekommunikation-vodafone-deutschland-bekommt-einen-neuen-chef-2204-164697-rss.html']));
        $this->sampleFeedItem->setPermalink('https:\/\/www.golem.de\/news\/telekommunikation-vodafone-deutschland-bekommt-einen-neuen-chef-2204-164697-rss.html');
        $this->sampleFeedItem->setType('rss-10');
        $this->sampleFeedItem->setXml('<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<item xmlns=\"http:\/\/purl.org\/rss\/1.0\/\" xmlns:rdf=\"http:\/\/www.w3.org\/1999\/02\/22-rdf-syntax-ns#\" xmlns:dc=\"http:\/\/purl.org\/dc\/elements\/1.1\/\" xmlns:slash=\"http:\/\/purl.org\/rss\/1.0\/modules\/slash\/\" xmlns:content=\"http:\/\/purl.org\/rss\/1.0\/modules\/content\/\" rdf:about=\"https:\/\/www.golem.de\/news\/telekommunikation-vodafone-deutschland-bekommt-einen-neuen-chef-2204-164697-rss.html\">\n        <dc:format>text\/html<\/dc:format>\n        <dc:date>2022-04-19T17:26:02+02:00<\/dc:date>\n        <dc:source>https:\/\/www.golem.de<\/dc:source>\n        <dc:creator>Oliver Nickel<\/dc:creator>\n        <title>Telekommunikation: Vodafone Deutschland bekommt einen neuen Chef<\/title>\n        <link>https:\/\/www.golem.de\/news\/telekommunikation-vodafone-deutschland-bekommt-einen-neuen-chef-2204-164697-rss.html<\/link>\n        <description>Der CEO Hannes Ametsreiter tritt vorzeitig von seinem Chefposten bei Vodafone Deutschland ab. Ihm folgt ein erfahrener Microsoft-Manager. (&lt;a href=\"https:\/\/www.golem.de\/specials\/vodafone\/\"&gt;Vodafone&lt;\/a&gt;, &lt;a href=\"https:\/\/www.golem.de\/specials\/microsoft\/\"&gt;Microsoft&lt;\/a&gt;) &lt;img src=\"https:\/\/cpx.golem.de\/cpx.php?class=17&amp;amp;aid=164697&amp;amp;page=1&amp;amp;ts=1650381962\" alt=\"\" width=\"1\" height=\"1\" \/&gt;<\/description>\n        <slash:comments\/>\n        <content:encoded><![CDATA[<img src=\"https:\/\/www.golem.de\/2204\/164697-323081-323077_rc.jpg\" width=\"140\" height=\"140\" vspace=\"3\" hspace=\"8\" align=\"left\">Der CEO Hannes Ametsreiter tritt vorzeitig von seinem Chefposten bei Vodafone Deutschland ab. Ihm folgt ein erfahrener Microsoft-Manager. (<a href=\"https:\/\/www.golem.de\/specials\/vodafone\/\">Vodafone<\/a>, <a href=\"https:\/\/www.golem.de\/specials\/microsoft\/\">Microsoft<\/a>) <img src=\"https:\/\/cpx.golem.de\/cpx.php?class=17&amp;aid=164697&amp;page=1&amp;ts=1650381962\" alt=\"\" width=\"1\" height=\"1\" \/>]]><\/content:encoded>\n    <\/item>');
        $this->sampleFeedItem->generateChecksum();
    }

    public function testToJson(): void
    {
        static::assertMatchesSnapshot($this->sampleFeedItem->toJson());
    }

    public function testFromJson(): void
    {
        static::assertEquals($this->sampleFeedItem->getChecksum(), FeedItem::fromJson($this->sampleFeedItem->toJson())->getChecksum());
    }

    public function testCompareTo(): void
    {
        // the same object should result in 100.0
        static::assertSame(100.0, $this->sampleFeedItem->compareTo($this->sampleFeedItem));
        static::assertTrue($this->sampleFeedItem->isSimilarTo(100, $this->sampleFeedItem));
        static::assertTrue($this->sampleFeedItem->isSimilarTo(90, $this->sampleFeedItem));

        // same checksum always results in 100.0
        $otherFeedItem = clone $this->sampleFeedItem;
        $otherFeedItem->setTitle('Lorem ipsum');
        static::assertSame(100.0, $this->sampleFeedItem->compareTo($otherFeedItem));
        static::assertTrue($this->sampleFeedItem->isSimilarTo(100, $otherFeedItem));
        static::assertTrue($this->sampleFeedItem->isSimilarTo(90, $otherFeedItem));

        // title has changed
        $otherFeedItem = clone $this->sampleFeedItem;
        $otherFeedItem->setTitle('Lorem ipsum');
        $otherFeedItem->setChecksum(HeraRssCrawler::generateChecksumForFeedItem($otherFeedItem));
        static::assertSame(97.57979656260962, $this->sampleFeedItem->compareTo($otherFeedItem));
        static::assertTrue($this->sampleFeedItem->isSimilarTo(97, $otherFeedItem));
        static::assertFalse($this->sampleFeedItem->isSimilarTo(100, $otherFeedItem));
    }

    /**
     * @dataProvider websiteProvider
     * @covers       HeraRssCrawler::discoverFeedUrls()
     * @param string $url
     * @param string[] $expectedUrls
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
     * @param string[] $feedUrls
     * @param bool[] $expectedValues
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
                    $feedArr = new Collection([
                        'title' => $feed->getTitle(),
                        'description' => $feed->getDescription(),
                        'feedUrl' => $feed->getFeedUrl(),
                        'id' => $feed->getId(),
                        'language' => $feed->getLanguage(),
                        'url' => $feed->getUrl(),
                    ]);

                    $this->assertMatchesSnapshot($feedArr->toArray());

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
        $actual = $feeds->map(fn(Feed $feed) => $feed->getTitle())->toArray();

        $this->assertMatchesSnapshot($actual);
    }

    public function testDiscoverAndParseFeedsCheckFeedUrls(): void
    {
        $feeds = $this->heraRssCrawler->discoverAndParseFeeds('https://www.rki.de');
        $actual = $feeds->map(fn(Feed $feed) => [
            'title' => $feed->getTitle(),
            'description' => $feed->getDescription(),
            'feedUrl' => $feed->getFeedUrl(),
            'id' => $feed->getId(),
            'language' => $feed->getLanguage(),
            'url' => $feed->getUrl(),
        ])->toArray();

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
        $feed3->setFeedItems(new Collection([self::getSampleFeedItem(), self::getSampleFeedItem()]));

        $this->assertNotEquals($expected, HeraRssCrawler::generateChecksumForFeed($feed3));

        $this->assertEquals($expectedSha512, HeraRssCrawler::generateChecksumForFeed($feed, '__', Hash::SHA_512));
        $this->assertNotEquals($expectedSha512, HeraRssCrawler::generateChecksumForFeed($feed, '--', Hash::SHA_512));
    }

    /**
     * @dataProvider websiteProvider
     * @covers       HeraRssCrawler::discoverFeedUrls()
     * @param string $url
     * @param string[] $expectedUrls
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
     * @param string[] $feedUrls
     * @param bool[] $expectedValues
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
     * @covers HeraRssCrawler::setFeedDiscoverers
     */
    public function testSetFeedDiscoverers(): void
    {
        $customFeedDiscoverer = new class implements FeedDiscoverer {
            /**
             * @param Client $httpClient
             * @param ResponseContainer $responseContainer
             * @return Collection<int, string>
             */
            public function discover(Client $httpClient, ResponseContainer $responseContainer): Collection
            {
                return new Collection(['It works!']);
            }
        };

        $feedDiscoverers = new Collection([
            $customFeedDiscoverer,
            new FeedDiscovererByContentType(),
        ]);

        $heraRssCrawler = new HeraRssCrawler();
        $heraRssCrawler->setFeedDiscoverers($feedDiscoverers);

        $feedUrls = $heraRssCrawler->discoverFeedUrls('https://zeit.de');

        static::assertSame(['It works!'], $feedUrls->toArray());
    }

    /**
     * @covers HeraRssCrawler::setFeedDiscoverers
     */
    public function testSetInvalidFeedDiscoverer(): void
    {
        $invalidFeedDiscoverer = new class {
            /**
             * @param Client $httpClient
             * @param ResponseContainer $responseContainer
             * @return Collection<int, string>
             */
            public function discover(Client $httpClient, ResponseContainer $responseContainer): Collection
            {
                return new Collection();
            }
        };

        $feedDiscoverers = new Collection([
            $invalidFeedDiscoverer,
            new FeedDiscovererByContentType(),
        ]);

        $heraRssCrawler = new HeraRssCrawler();

        $this->expectException(InvalidArgumentException::class);

        $heraRssCrawler->setFeedDiscoverers($feedDiscoverers);
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
        $feed->setAuthors(new Collection(['John Doe', 'Jane Doe']));
        $feed->setCategories(new Collection(['A', 'B']));
        $feed->setCopyright('None');
        $feed->setFeedItems(new Collection([self::getSampleFeedItem()]));
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
        $feedItem->setCategories(new Collection(['Zeitgeschehen']));
        $feedItem->setAuthors(new Collection(['ZEIT ONLINE: Zeitgeschehen - Alena Kammer']));
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
        $feedItem->setLinks(new Collection(['https://www.zeit.de/gesellschaft/zeitgeschehen/2019-12/gabun-piraterie-angriff-libreville-entfuehrung']));
        $feedItem->setPermalink('https://www.zeit.de/gesellschaft/zeitgeschehen/2019-12/gabun-piraterie-angriff-libreville-entfuehrung');
        $feedItem->setType('rss-20');
        $feedItem->setContent('');
        $feedItem->setChecksum('not important here');

        return $feedItem;
    }

    /**
     * @return array<string, array<int, array<int, string>|string|true|null>>
     */
    public static function websiteProvider(): array
    {
        return [
            'Zeit' => [
                'https://www.zeit.de',
                [
                    'http://newsfeed.zeit.de/index',
                    'http://newsfeed.zeit.de/wirtschaft/index',
                    'http://newsfeed.zeit.de/kultur/index',
                    'http://newsfeed.zeit.de/wissen/index',
                    'http://newsfeed.zeit.de/politik/index',
                ],
                'https://static.zeit.de/p/zeit.web/icons/favicon.svg',
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
                    'https://www.anime2you.de/feed',
                    'https://www.anime2you.de/comments/feed',
                ],
                'https://img.anime2you.de/2021/06/favicon.png',
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
                ],
                'https://mainwebsite.wpenginepowered.com/favicon.png',
            ],
            'React' => [
                'https://react.dev/blog',
                [
                    'https://replit-api-services.sorrycc.repl.co/react-dev-blog',
                ],
                null,
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
                'https://nutritionfacts.org/apple-touch-icon.png',
            ],
            'JRock News' => [
                'https://www.jrocknews.com/',
                [
                    'https://jrocknews.com/feed',
                ],
                'https://jrocknews.com/wp-content/uploads/2015/05/cropped-JRN-icon-2017-150x150.png',
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
     * @return array<string, array<int, array<int, bool|string>|true>>
     */
    public static function feedProvider(): array
    {
        return [
            'Grandma\'s World Of Skyrim' => [
                [
                    'https://www.blogger.com/feeds/2146985069025381500/posts/default',
                ],
                [
                    true,
                ],
            ],
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
                ],
                [
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
