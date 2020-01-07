<?php

namespace Kaishiyoku\HeraRssCrawler;

use DOMElement;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Kaishiyoku\HeraRssCrawler\Models\Feedly\Result;
use Kaishiyoku\HeraRssCrawler\Models\Feedly\SearchResponse;
use Kaishiyoku\HeraRssCrawler\Models\ResponseContainer;
use Kaishiyoku\HeraRssCrawler\Models\Rss\Feed;
use Kaishiyoku\HeraRssCrawler\Models\Rss\FeedItem;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\DomCrawler\Crawler;
use Zend\Feed\Reader\Reader;

class HeraRssCrawler
{
    /**
     * @var string
     */
    private const VERSION = '0.6.0';

    /**
     * @var Client|null
     */
    private $httpClient;

    /**
     * @var CssSelectorConverter|null
     */
    private $converter;

    /**
     * @var int
     */
    private $retryCount = 0;

    /**
     * @var array
     */
    private $urlReplacementMap = [
        'https://www.reddit.com/' => 'https://old.reddit.com/',
    ];

    /**
     * @var string
     */
    private const FEEDLY_API_BASE_URL = 'https://cloud.feedly.com/v3';

    public function __construct()
    {
        $name = Str::snake(Arr::last(explode('\\', self::class)));
        $userAgent = $name . '/' . self::VERSION;

        $this->httpClient = new Client([
            'headers' => [
                'User-Agent' => $userAgent,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate',
            ],
        ]);
        $this->converter = new CssSelectorConverter();
    }

    /**
     * @return int
     */
    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    /**
     * @param int $retryCount
     */
    public function setRetryCount(int $retryCount): void
    {
        $this->retryCount = $retryCount;
    }

    /**
     * @param array $urlReplacementMap
     */
    public function setUrlReplacementMap(array $urlReplacementMap): void
    {
        $this->urlReplacementMap = $urlReplacementMap;
    }

    /**
     * @param string $url
     * @return Feed|null
     * @throws ConnectException
     * @throws Exception
     */
    public function parseFeed(string $url): ?Feed
    {
        return $this->withRetries(function () use ($url) {
            $content = $this->httpClient->get($url)->getBody()->getContents();
            $zendFeed = Reader::importString($content);

            return Feed::fromZendFeed($zendFeed);
        });
    }

    /**
     * @param string $url
     * @return Collection<Feed>
     * @throws Exception
     */
    public function discoverAndParseFeeds(string $url): Collection
    {
        return $this->withRetries(function () use ($url) {
            return $this->discoverFeedUrls($url)
                ->map(function ($feedUrl) {
                    return $this->parseFeed($feedUrl);
                });
        });
    }

    /**
     * @param string $url
     * @return Collection<string>
     * @throws Exception
     */
    public function discoverFeedUrls(string $url): Collection
    {
        return $this->withRetries(function () use ($url) {
            $adjustedUrl = Helper::replaceBaseUrls($url, $this->urlReplacementMap);

            $response = $this->httpClient->get($adjustedUrl);
            $responseContainer = new ResponseContainer($adjustedUrl, $response);

            $discoveryFns = collect([
                function ($responseContainer) {
                    return $this->discoverFeedUrlByContentType($responseContainer);
                },
                function ($responseContainer) {
                    return $this->discoverFeedUrlByHtmlHeadElements($responseContainer);
                },
                function ($responseContainer) {
                    return $this->discoverFeedUrlByHtmlAnchorElements($responseContainer);
                },
                function ($responseContainer) {
                    return $this->discoverFeedUrlByFeedly($responseContainer);
                },
            ]);

            $urls = $discoveryFns->reduce(function (Collection $carry, $discoveryFn) use ($responseContainer) {
                // only get the firstly fetched urls
                if ($carry->isEmpty()) {
                    return $discoveryFn($responseContainer);
                }

                return $carry;
            }, collect());

            return $urls->map(function ($adjustedUrl) {
                return Helper::normalizeUrl($adjustedUrl);
            })->unique()->values();
        });
    }

    /**
     * @param string $url
     * @return string|null
     * @throws Exception
     */
    public function discoverFavicon(string $url): ?string
    {
        return $this->withRetries(function () use ($url) {
            $response = $this->httpClient->get($url);

            $crawler = new Crawler($response->getBody()->getContents());
            $nodes = $crawler->filterXPath($this->converter->toXPath('head > link'));

            $faviconUrls = collect($nodes)->filter(function (DOMElement $node) {
                return Str::contains($node->getAttribute('rel'), 'icon');
            })->map(function (DOMElement $node) use ($url) {
                return Helper::normalizeUrl(Helper::transformUrl($url, $node->getAttribute('href')));
            });

            if ($faviconUrls->isEmpty()) {
                return null;
            }

            return $faviconUrls->first();
        });
    }

    /**
     * @param string $url
     * @return bool
     */
    public function checkIfConsumableFeed(string $url): bool
    {
        try {
            return $this->withRetries(function () use ($url) {
                $feed = $this->parseFeed($url);

                return $feed instanceof Feed;
            });
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param ResponseContainer $responseContainer
     * @return Collection<string>
     * @throws ConnectException
     */
    private function discoverFeedUrlByFeedly(ResponseContainer $responseContainer): Collection
    {
        $response = $this->httpClient->get(self::FEEDLY_API_BASE_URL . '/search/feeds', [
            'query' => ['query' => $responseContainer->getRequestUrl()],
        ]);

        $searchResponse = SearchResponse::fromJson(json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));

        return $searchResponse->getResults()->map(function (Result $result) {
            return $result->getFeedUrl();
        });
    }

    /**
     * @param ResponseContainer $responseContainer
     * @return Collection<string>
     */
    private function discoverFeedUrlByContentType(ResponseContainer $responseContainer): Collection
    {
        $contentTypeMixedValue = Arr::get($responseContainer->getResponse()->getHeaders(), 'Content-Type');

        $contentType = is_array($contentTypeMixedValue) ? Arr::first($contentTypeMixedValue) : $contentTypeMixedValue;

        // the given url itself already is a rss feed
        if (Str::startsWith($contentType, ['application/rss+xml', 'application/atom+xml'])) {
            return collect([$responseContainer->getRequestUrl()]);
        }

        return collect();
    }

    /**
     * @param ResponseContainer $responseContainer
     * @return Collection<string>
     */
    private function discoverFeedUrlByHtmlHeadElements(ResponseContainer $responseContainer): Collection
    {
        $crawler = new Crawler($responseContainer->getResponse()->getBody()->getContents());
        $nodes = $crawler->filterXPath($this->converter->toXPath('head > link[type="application/rss+xml"], head > link[type="application/atom+xml"]'));

        return collect($nodes->each(function (Crawler $node) use ($responseContainer) {
            return $this->transformNodeToUrl($responseContainer->getRequestUrl(), $node);
        }));
    }

    /**
     * @param ResponseContainer $responseContainer
     * @return Collection<string>
     */
    private function discoverFeedUrlByHtmlAnchorElements(ResponseContainer $responseContainer): Collection
    {
        $crawler = new Crawler($responseContainer->getResponse()->getBody()->getContents());
        $nodes = $crawler->filterXPath($this->converter->toXPath('a'));

        return collect($nodes->each(function (Crawler $node) use ($responseContainer) {
            return $this->transformNodeToUrl($responseContainer->getRequestUrl(), $node);
        }))->filter(function ($url) {
            return Str::contains($url, 'rss');
        });
    }

    /**
     *
     * @param string $baseUrl
     * @param Crawler $node
     * @return string
     */
    private function transformNodeToUrl(string $baseUrl, Crawler $node): string
    {
        $href = $node->attr('href');

        return Helper::transformUrl($baseUrl, $href);
    }

    /**
     * @param FeedItem $feedItem
     * @param string $delimiter
     * @param string $algo
     * @return string|null
     * @throws ReflectionException
     */
    public static function generateChecksumForFeedItem(FeedItem $feedItem, string $delimiter = '|', string $algo = Hash::SHA_256): ?string
    {
        $properties = [
            'categories',
            'authors',
            'title',
            'createdAt',
            'updatedAt',
            'encoding',
            'links',
            'permalink',
            'type',
        ];

        $class = new ReflectionClass(FeedItem::class);
        $allValuesConcatenated = trim(collect($class->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(function (ReflectionMethod $method) use ($properties) {
                return in_array($method->getName(), array_map(function ($property) {
                    return 'get' . Str::ucfirst($property);
                }, $properties), true);
            })
            ->reduce(function ($carry, ReflectionMethod $method) use ($feedItem, $delimiter) {
                return $carry . $delimiter . $method->invoke($feedItem);
            }, ''), $delimiter);

        return Hash::hash($algo, $allValuesConcatenated);
    }

    /**
     * @param Feed $feed
     * @param string $delimiter
     * @param string $algo
     * @return string|null
     * @throws ReflectionException
     */
    public static function generateChecksumForFeed(Feed $feed, string $delimiter = '|', string $algo = Hash::SHA_256): ?string
    {
        $properties = [
            'categories',
            'authors',
            'title',
            'copyright',
            'createdAt',
            'updatedAt',
            'feedUrl',
            'id',
            'language',
            'url',
            'feedItems',
        ];

        $class = new ReflectionClass(Feed::class);
        $allValuesConcatenated = trim(collect($class->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(function (ReflectionMethod $method) use ($properties) {
                return in_array($method->getName(), array_map(function ($property) {
                    return 'get' . Str::ucfirst($property);
                }, $properties), true);
            })
            ->reduce(function ($carry, ReflectionMethod $method) use ($feed, $delimiter) {
                if ($method->getName() === 'getFeedItems') {
                    return $carry . $delimiter . $method->invoke($feed)->reduce(function ($carry, FeedItem $feedItem) use ($delimiter) {
                            return $carry . $delimiter . self::generateChecksumForFeedItem($feedItem);
                        });
                }

                return $carry . $delimiter . $method->invoke($feed);
            }, ''), $delimiter);

        return Hash::hash($algo, $allValuesConcatenated);
    }

    /**
     * @param callable $callback
     * @return mixed
     * @throws Exception
     */
    private function withRetries(callable $callback)
    {
        return Helper::withRetries($callback, 1, $this->getRetryCount());
    }
}
