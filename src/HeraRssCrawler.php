<?php

namespace Kaishiyoku\HeraRssCrawler;

use DOMElement;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Kaishiyoku\HeraRssCrawler\Models\Feedly\Result;
use Kaishiyoku\HeraRssCrawler\Models\Feedly\SearchResponse;
use Kaishiyoku\HeraRssCrawler\Models\ResponseContainer;
use Kaishiyoku\HeraRssCrawler\Models\Rss\Feed;
use Kaishiyoku\HeraRssCrawler\Models\Rss\FeedItem;
use Laminas\Feed\Reader\Reader;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\DomCrawler\Crawler;

class HeraRssCrawler
{
    private const VERSION = '2.1.0';

    private ?Client $httpClient;

    private ?CssSelectorConverter $converter;

    /**
     * The number of retries to attempt on HTTP requests.
     */
    private int $retryCount = 0;

    /**
     * A replacement map where the array keys are being replaced by their values.
     * This is useful for HTTP redirect URLs, for example.
     *
     * @var string[]
     */
    private array $urlReplacementMap = [
        'https://www.reddit.com/' => 'https://old.reddit.com/',
    ];

    private ?LoggerInterface $logger = null;

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
     * This overrides the default HTTP client with your own
     */
    public function setHttpClient(Client $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    public function setRetryCount(int $retryCount): void
    {
        $this->retryCount = $retryCount;
    }

    /**
     * @param string[] $urlReplacementMap
     */
    public function setUrlReplacementMap(array $urlReplacementMap): void
    {
        $this->urlReplacementMap = $urlReplacementMap;
    }

    /**
     * Enable logging by setting a logger.
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Parse a given URL and return a Feed object.
     *
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
     * Discover the first feed URL of a website and parse the feed.
     *
     * @param string $url
     * @return Collection<int, Feed>
     * @throws Exception
     */
    public function discoverAndParseFeeds(string $url): Collection
    {
        return $this->withRetries(
            fn() => $this->discoverFeedUrls($url)
                ->map(fn($feedUrl) => $this->parseFeed($feedUrl))
        );
    }

    /**
     * Discover all available feed URLs of a website.
     *
     * @param string $url
     * @return Collection<int, string>
     * @throws Exception
     */
    public function discoverFeedUrls(string $url): Collection
    {
        return $this->withRetries(function () use ($url) {
            $adjustedUrl = Helper::replaceBaseUrls($url, $this->urlReplacementMap);

            $response = $this->httpClient->get($adjustedUrl);
            $responseContainer = new ResponseContainer($adjustedUrl, $response);

            $discoveryFns = collect([
                fn($responseContainer) => $this->discoverFeedUrlByContentType($responseContainer),
                fn($responseContainer) => $this->discoverFeedUrlByHtmlHeadElements($responseContainer),
                fn($responseContainer) => $this->discoverFeedUrlByHtmlAnchorElements($responseContainer),
                fn($responseContainer) => $this->discoverFeedUrlByFeedly($responseContainer),
            ]);

            $urls = $discoveryFns->reduce(function (Collection $carry, $discoveryFn) use ($responseContainer) {
                // only get the firstly fetched urls
                if ($carry->isEmpty()) {
                    return $discoveryFn($responseContainer);
                }

                return $carry;
            }, collect());

            return $urls->map(fn($adjustedUrl) => Helper::normalizeUrl($adjustedUrl))->unique()->values();
        });
    }

    /**
     * Discover the favicon of the website.
     *
     * @throws Exception
     */
    public function discoverFavicon(string $url): ?string
    {
        return $this->withRetries(function () use ($url) {
            $response = $this->httpClient->get($url);

            $crawler = new Crawler($response->getBody()->getContents());
            $nodes = $crawler->filterXPath($this->converter->toXPath('head > link'));

            $faviconUrls = collect($nodes)
                ->filter(fn(DOMElement $node) => Str::contains($node->getAttribute('rel'), 'icon')) /** @phpstan-ignore-line */
                ->map(fn(DOMElement $node) => Helper::normalizeUrl(Helper::transformUrl($url, $node->getAttribute('href')))); /** @phpstan-ignore-line */

            if ($faviconUrls->isEmpty()) {
                return null;
            }

            return $faviconUrls->first();
        });
    }

    /**
     * Check if the given URL is a consumable RSS feed.
     */
    public function checkIfConsumableFeed(string $url): bool
    {
        try {
            return $this->withRetries(fn() => $this->parseFeed($url) instanceof Feed);
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Feed not consumable: ' . $e->getMessage());
            }

            return false;
        }
    }

    /**
     * Discover feed URLs using the Feedly API.
     *
     * @param ResponseContainer $responseContainer
     * @return Collection<int, string>
     * @throws GuzzleException
     */
    private function discoverFeedUrlByFeedly(ResponseContainer $responseContainer): Collection
    {
        $response = $this->httpClient->get(self::FEEDLY_API_BASE_URL . '/search/feeds', [
            'query' => ['query' => $responseContainer->getRequestUrl()],
        ]);

        $searchResponse = SearchResponse::fromJson(json_decode($response->getBody()->getContents(), true, 512));

        return $searchResponse->getResults()->map(fn(Result $result) => $result->getFeedUrl());
    }

    /**
     * Discover feed URLs by parsing the website's HTML content.
     *
     * @param ResponseContainer $responseContainer
     * @return Collection<int, string>
     */
    private function discoverFeedUrlByContentType(ResponseContainer $responseContainer): Collection
    {
        $contentTypeMixedValue = Arr::get($responseContainer->getResponse()->getHeaders(), 'Content-Type');

        $contentType = is_array($contentTypeMixedValue) ? Arr::first($contentTypeMixedValue) : $contentTypeMixedValue;

        // the given url itself already is a rss feed
        if ($contentType && Str::startsWith($contentType, ['application/rss+xml', 'application/atom+xml'])) {
            return collect([$responseContainer->getRequestUrl()]);
        }

        return collect();
    }

    /**
     * Discover feed URLs by parsing the website's HTML head elements.
     *
     * @param ResponseContainer $responseContainer
     * @return Collection<int, string>
     */
    private function discoverFeedUrlByHtmlHeadElements(ResponseContainer $responseContainer): Collection
    {
        $crawler = new Crawler($responseContainer->getResponse()->getBody()->getContents());
        $nodes = $crawler->filterXPath($this->converter->toXPath('head > link[type="application/rss+xml"], head > link[type="application/atom+xml"]'));

        return collect($nodes->each(fn(Crawler $node) => $this->transformNodeToUrl($responseContainer->getRequestUrl(), $node)));
    }

    /**
     * Discover feed URLs by searching for HTML anchor elements.
     *
     * @param ResponseContainer $responseContainer
     * @return Collection<int, string>
     */
    private function discoverFeedUrlByHtmlAnchorElements(ResponseContainer $responseContainer): Collection
    {
        $crawler = new Crawler($responseContainer->getResponse()->getBody()->getContents());
        $nodes = $crawler->filterXPath($this->converter->toXPath('a'));

        return (new Collection($nodes->each(fn(Crawler $node) => $this->transformNodeToUrl($responseContainer->getRequestUrl(), $node))))->filter(fn($url) => Str::contains($url, 'rss'));
    }

    /**
     * Transform a Crawler node to a string URL.
     */
    private function transformNodeToUrl(string $baseUrl, Crawler $node): string
    {
        return Helper::transformUrl($baseUrl, $node->attr('href'));
    }

    /**
     * Generate a checksum of a single feed item.
     *
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
            ->filter(fn(ReflectionMethod $method) => in_array($method->getName(), array_map(fn($property) => 'get' . Str::ucfirst($property), $properties), true))
            ->reduce(fn($carry, ReflectionMethod $method) => $carry . $delimiter . $method->invoke($feedItem), ''), $delimiter);

        return Hash::hash($algo, $allValuesConcatenated);
    }

    /**
     * Generate a checksum for the whole feed.
     *
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
    private function withRetries(callable $callback): mixed
    {
        return Helper::withRetries($callback, 1, $this->getRetryCount());
    }
}
