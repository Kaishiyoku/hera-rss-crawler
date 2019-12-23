<?php

namespace Kaishiyoku\HeraRssCrawler;

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
     * @return Feed
     */
    public function parseFeed(string $url): Feed
    {
        $content = $this->httpClient->get($url)->getBody()->getContents();
        $zendFeed = Reader::importString($content);

        return Feed::fromZendFeed($zendFeed);
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
            function ($responseContainer) { return $this->discoverFeedUrlByContentType($responseContainer); },
            function ($responseContainer) { return $this->discoverFeedUrlByHtmlHeadElements($responseContainer); },
            function ($responseContainer) { return $this->discoverFeedUrlByHtmlAnchorElements($responseContainer); },
            function ($responseContainer) { return $this->discoverFeedUrlByFeedly($responseContainer); },
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

        if (Helper::isValidUrl($href)) {
            return $href;
        }

        return $baseUrl . '/' . $href;
    }

    /**
     * @param FeedItem $feedItem
     * @param string $algo
     * @param string $delimiter
     * @return string|null
     */
    public static function generateChecksumForFeedItem(FeedItem $feedItem, string $algo = 'sha256', string $delimiter = '|'): ?string
    {
        try {
            $class = new ReflectionClass(FeedItem::class);
            $allValuesConcatenated = trim(collect($class->getMethods(ReflectionMethod::IS_PUBLIC))
                ->filter(function (ReflectionMethod $method) {
                    return Str::startsWith($method->getName(), 'get') && $method->getName() != 'getChecksum';
                })
                ->reduce(function ($carry, ReflectionMethod $method) use ($feedItem, $delimiter) {
                    return $carry . $delimiter . $method->invoke($feedItem);
                }, ''), $delimiter);

            return hash($algo, $allValuesConcatenated);
        }
        catch (Exception $e) {
            return null;
        }
    }
}
