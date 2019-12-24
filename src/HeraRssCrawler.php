<?php

namespace Kaishiyoku\HeraRssCrawler;

use DOMElement;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Kaishiyoku\HeraRssCrawler\Models\Feedly\Result;
use Kaishiyoku\HeraRssCrawler\Models\Feedly\SearchResponse;
use Kaishiyoku\HeraRssCrawler\Models\ResponseContainer;
use Kaishiyoku\HeraRssCrawler\Models\Rss\Feed;
use Kaishiyoku\HeraRssCrawler\Models\Rss\FeedItem;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\DomCrawler\Crawler;
use Zend\Feed\Reader\Reader;

class HeraRssCrawler
{
    /**
     * @var Client|null
     */
    private $httpClient;

    /**
     * @var CssSelectorConverter|null
     */
    private $converter;

    /**
     * @var string
     */
    private const FEEDLY_API_BASE_URL = 'https://cloud.feedly.com/v3';

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->converter = new CssSelectorConverter();
    }

    /**
     * @param string $url
     * @return Feed|null
     */
    public function parseFeed(string $url): ?Feed
    {
        try {
            $content = $this->httpClient->get($url)->getBody()->getContents();
            $zendFeed = Reader::importString($content);

            return Feed::fromZendFeed($zendFeed);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param string $url
     * @return Collection<Feed>
     */
    public function discoverAndParseFeeds(string $url): Collection
    {
        return $this->discoverFeedUrls($url)
            ->map(function ($feedUrl) {
                return $this->parseFeed($feedUrl);
            });
    }

    /**
     * @param string $url
     * @return Collection<string>
     */
    public function discoverFeedUrls(string $url): Collection
    {
        $responseContainer = null;

        try {
            $response = $this->httpClient->get($url);
            $responseContainer = new ResponseContainer($url, $response);
        } catch (RequestException $e) {
            return collect();
        }

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

        return $urls->map(function ($url) {
            return Helper::normalizeUrl($url);
        })->unique()->values();
    }

    /**
     * @param string $url
     * @return string|null
     */
    public function discoverFavicon(string $url): ?string
    {
        $response = null;

        try {
            $response = $this->httpClient->get($url);
        } catch (RequestException $e) {
            return null;
        }

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
    }

    /**
     * @param string $url
     * @return bool
     */
    public function checkIfConsumableFeed(string $url): bool
    {
        $feed = $this->parseFeed($url);

        return $feed instanceof Feed;
    }

    /**
     * @param ResponseContainer $responseContainer
     * @return Collection<string>
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
     */
    public static function generateChecksumForFeedItem(FeedItem $feedItem, string $delimiter = '|', string $algo = Hash::SHA_256): ?string
    {
        $properties = [
            'categories',
            'authors',
            'title',
            'commentFeedLink',
            'commentLink',
            'createdAt',
            'updatedAt',
            'enclosureUrl',
            'encoding',
            'id',
            'links',
            'permalink',
            'type',
        ];

        try {
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
        } catch (Exception $e) {
            return null;
        }
    }

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

        try {
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
        } catch (Exception $e) {
            return null;
        }
    }
}
