<?php

namespace Kaishiyoku\HeraRssCrawler;

use DOMElement;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Kaishiyoku\HeraRssCrawler\FeedDiscoverers\FeedDiscoverer;
use Kaishiyoku\HeraRssCrawler\FeedDiscoverers\FeedDiscovererByContentType;
use Kaishiyoku\HeraRssCrawler\FeedDiscoverers\FeedDiscovererByFeedly;
use Kaishiyoku\HeraRssCrawler\FeedDiscoverers\FeedDiscovererByHtmlAnchorElements;
use Kaishiyoku\HeraRssCrawler\FeedDiscoverers\FeedDiscovererByHtmlHeadElements;
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
    private const VERSION = '5.1.4';

    private ?Client $httpClient;

    private ?CssSelectorConverter $cssConverter;

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

    /**
     * @var Collection<int, mixed>
     */
    private Collection $feedDiscoverers;

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
            'timeout' => 5,
        ]);
        $this->cssConverter = new CssSelectorConverter();
        $this->feedDiscoverers = new Collection([
            new FeedDiscovererByContentType(),
            new FeedDiscovererByHtmlHeadElements(),
            new FeedDiscovererByHtmlAnchorElements(),
            new FeedDiscovererByFeedly(),
        ]);
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
     * Set feed discoverer classes.
     *
     * @param Collection<int, mixed> $feedDiscoverers
     * @return void
     */
    public function setFeedDiscoverers(Collection $feedDiscoverers): void
    {
        // make sure that every discoverer implements the FeedDiscoverer interface
        $feedDiscoverers->each(function ($discoverer) {
            if (!is_subclass_of($discoverer, FeedDiscoverer::class)) {
                throw new InvalidArgumentException($discoverer::class . ' is not a valid feed discoverer.');
            }
        });

        $this->feedDiscoverers = $feedDiscoverers;
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

            return Feed::fromZendFeed($url, $zendFeed, $this->httpClient);
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

            /*** @var Collection $urls */
            $urls = $this->feedDiscoverers->reduce(function (Collection $carry, FeedDiscoverer $discoverer) use ($responseContainer) {
                // only get the firstly fetched urls
                if ($carry->isEmpty()) {
                    return $discoverer->discover($this->httpClient, $responseContainer);
                }

                return $carry;
            }, new Collection());

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
            $nodes = $crawler->filterXPath($this->cssConverter->toXPath('head > link'));

            $faviconUrls = (new Collection($nodes))
                ->filter(fn(DOMElement $node) => Str::contains($node->getAttribute('rel'), 'icon')) /** @phpstan-ignore-line */
                ->map(fn(DOMElement $node) => Helper::normalizeUrl(Helper::transformUrl($url, $node->getAttribute('href')))) /** @phpstan-ignore-line */
                ->filter(fn(string $imageUrl) => Helper::getHttpContentTypeForUrl($imageUrl, $this->httpClient) !== null);

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
        $allValuesConcatenated = trim((new Collection($class->getMethods(ReflectionMethod::IS_PUBLIC)))
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
        $allValuesConcatenated = trim((new Collection($class->getMethods(ReflectionMethod::IS_PUBLIC)))
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
