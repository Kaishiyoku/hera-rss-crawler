<?php

namespace Kaishiyoku\HeraRssCrawler;

use Carbon\Carbon;
use Kaishiyoku\HeraRssCrawler\Models\Rss\Item;
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

            $this->assertNotEmpty($feed->getCreatedAt());
            $this->assertNotEmpty($feed->getUpdatedAt());
            $this->assertGreaterThanOrEqual(0, $feed->getItems()->count());
        }
    }

    public function testGenerateChecksumForFeedItem()
    {
        $expected = '0339bfd7b25e3ae5bc304a5d64a8474baac5eb30036356534a29802bf5ad2e5f';

        $item = new Item();
        $item->setCategories(collect(['Zeitgeschehen']));
        $item->setAuthors(collect(['ZEIT ONLINE: Zeitgeschehen - Alena Kammer']));
        $item->setTitle('Gabun: Piraten töten Kapitän und entführen Matrosen');
        $item->setCommentCount(0);
        $item->setCommentFeedLink(null);
        $item->setCommentLink(null);
        $item->setCreatedAt(Carbon::parse('2019-12-22 18:28:44.0 +00:00'));
        $item->setUpdatedAt(Carbon::parse('2019-12-22 18:28:44.0 +00:00'));
        $item->setDescription('<a href="https://www.zeit.de/gesellschaft/zeitgeschehen/2019-12/gabun-piraterie-angriff-libreville-entfuehrung"><img style="float:left; margin-right:5px" src="https://img.zeit.de/gesellschaft/zeitgeschehen/2019-12/gabun-libreville-hafen-piraterie-angriff/wide__148x84"></a> Im Hafen der Hauptstadt Libreville haben Piraten vier Schiffe überfallen. Nach Angaben der Regierung wurde ein Kapitän getötet und vier Matrosen wurden entführt.');
        $item->setEnclosure(null);
        $item->setEncoding('UTF-8');
        $item->setId('{urn:uuid:a56e1e5f-a630-4cd6-aa51-cdb896904ee9}');
        $item->setLinks(collect(['https://www.zeit.de/gesellschaft/zeitgeschehen/2019-12/gabun-piraterie-angriff-libreville-entfuehrung']));
        $item->setPermalink('https://www.zeit.de/gesellschaft/zeitgeschehen/2019-12/gabun-piraterie-angriff-libreville-entfuehrung');
        $item->setType('rss-20');
        $item->setContent('');
        $item->setChecksum('not important here');

        $this->assertEquals($expected, HeraRssCrawler::generateChecksumForFeedItem($item));

        $item2 = clone $item;
        $item2->setTitle('Title has changed');

        $this->assertNotEquals($expected, HeraRssCrawler::generateChecksumForFeedItem($item2));
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
