<?php

namespace Kaishiyoku\HeraRssCrawler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Kaishiyoku\HeraRssCrawler\Models\Feedly\Result;
use Kaishiyoku\HeraRssCrawler\Models\Feedly\SearchResponse;
use Kaishiyoku\HeraRssCrawler\Models\ResponseContainer;
use Kaishiyoku\HeraRssCrawler\Models\Rss\Feed;
use Kaishiyoku\HeraRssCrawler\Models\Rss\Item;
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
    private $url;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var CssSelectorConverter
     */
    private $converter;

    /**
     * @var string
     */
    private const FEEDLY_API_BASE_URL = 'https://cloud.feedly.com/v3';

    /**
     * HeraRssCrawler constructor.
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->httpClient = new Client();
        $this->converter = new CssSelectorConverter();

        $this->url = $url;
    }

    /**
     * @return Feed
     */
    public function parse(): Feed
    {
        $content = $this->httpClient->get($this->url)->getBody()->getContents();
        $zendFeed = Reader::importString($content);

        return Feed::fromZendFeed($zendFeed);
    }

    /**
     * @return Collection<string>
     */
    public function discoverFeedUrls(): Collection
    {
        $responseContainer = null;

        try {
            $response = $this->httpClient->get($this->url);
            $responseContainer = new ResponseContainer($this->url, $response);
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
            return normalizeUrl($url);
        })->unique()->values();
    }

    /**
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
     * @return Collection<string>
     */
    private function discoverFeedUrlByContentType(ResponseContainer $responseContainer): Collection
    {
        $contentTypeMixedValue = Arr::get($responseContainer->getResponse()->getHeaders(), 'Content-Type');

        $contentType = is_array($contentTypeMixedValue) ? Arr::first($contentTypeMixedValue) : $contentTypeMixedValue;

        // the given url itself already is a rss feed
        if (Str::startsWith($contentType, ['application/rss+xml', 'application/atom+xml'])) {
            return collect([$this->url]);
        }

        return collect();
    }

    /**
     * @return Collection<string>
     */
    private function discoverFeedUrlByHtmlHeadElements(ResponseContainer $responseContainer): Collection
    {
        $crawler = new Crawler($responseContainer->getResponse()->getBody()->getContents());
        $nodes = $crawler->filterXPath($this->converter->toXPath('head > link[type="application/rss+xml"], head > link[type="application/atom+xml"]'));

        return collect($nodes->each(function (Crawler $node) {
            return $this->transformNodeToUrl($node);
        }));
    }

    /**
     * @return Collection<string>
     */
    private function discoverFeedUrlByHtmlAnchorElements(ResponseContainer $responseContainer): Collection
    {
        $crawler = new Crawler($responseContainer->getResponse()->getBody()->getContents());
        $nodes = $crawler->filterXPath($this->converter->toXPath('a'));

        return collect($nodes->each(function (Crawler $node) {
            return $this->transformNodesToUrls($node);
        }))->filter(function ($url) {
            return Str::contains($url, 'rss');
        });
    }

    /**
     * @var Crawler $node
     * @return string
     */
    private function transformNodeToUrl(Crawler $node): string
    {
        $href = $node->attr('href');

        if (isValidUrl($href)) {
            return $href;
        }

        return $this->url . '/' . $href;
    }

    /**
     * @param Item $item
     * @param string $algo
     * @param string $delimiter
     * @return string
     * @throws ReflectionException
     */
    public static function generateChecksumForFeedItem(Item $item, string $algo = 'sha256', string $delimiter = '|'): string
    {
        $class = new ReflectionClass(Item::class);
        $allValuesConcatenated = trim(collect($class->getMethods(ReflectionMethod::IS_PUBLIC))
            ->filter(function (ReflectionMethod $method) {
                return Str::startsWith($method->getName(), 'get') && $method->getName() != 'getChecksum';
            })
            ->reduce(function ($carry, ReflectionMethod $method) use ($item, $delimiter) {
                return $carry . $delimiter . $method->invoke($item);
            }, ''), $delimiter);

        return hash($algo, $allValuesConcatenated);
    }
}
