<?php

namespace Kaishiyoku\HeraRssCrawler;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Kaishiyoku\HeraRssCrawler\Models\Feedly\Result;
use Kaishiyoku\HeraRssCrawler\Models\Feedly\SearchResponse;
use Kaishiyoku\HeraRssCrawler\Models\Rss\Feed;
use Kaishiyoku\HeraRssCrawler\Models\Rss\Item;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\DomCrawler\Crawler;
use Zend\Feed\Reader\Collection\Category;
use Zend\Feed\Reader\Entry\Rss;
use Zend\Feed\Reader\Feed\FeedInterface;
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

    public function discoverFeedUrls()
    {
        $html = null;

        try {
            $response = $this->httpClient->get($this->url);
            $html = $response->getBody()->getContents();
        } catch (RequestException $e) {
            return collect();
        }

        // discover by using content type
        $urls = $this->discoverFeedUrlByContentType($response);

        // discover by using link element crawler
        if ($urls->isEmpty()) {
            $urls = $this->discoverFeedUrlByHtmlHeadElements($html);
        }

        // discover by using anchor element crawler
        if ($urls->isEmpty()) {
            $url = $this->discoverFeedUrlByHtmlAnchorElements($html);
        }

        // discover by using Feedly
        if ($urls->isEmpty()) {
            $urls = $this->discoverFeedUrlByFeedly($this->url);
        }

        return $urls->map(function ($url) {
            return normalizeUrl($url);
        })->unique()->values();
    }

    private function discoverFeedUrlByFeedly($url)
    {
        $response = $this->httpClient->get(self::FEEDLY_API_BASE_URL . '/search/feeds', [
            'query' => ['query' => $url],
        ]);

        $searchResponse = SearchResponse::fromJson(json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR));

        return $searchResponse->getResults()->map(function (Result $result) {
            return $result->getFeedUrl();
        });
    }

    private function discoverFeedUrlByContentType(ResponseInterface $response): Collection
    {
        $contentTypeMixedValue = Arr::get($response->getHeaders(), 'Content-Type');

        $contentType = is_array($contentTypeMixedValue) ? Arr::first($contentTypeMixedValue) : $contentTypeMixedValue;

        // the given url itself already is a rss feed
        if (Str::startsWith($contentType, ['application/rss+xml', 'application/atom+xml'])) {
            return collect([$this->url]);
        }

        return collect();
    }

    private function discoverFeedUrlByHtmlHeadElements($html)
    {
        $crawler = new Crawler($html);
        $nodes = $crawler->filterXPath($this->converter->toXPath('head > link[type="application/rss+xml"], head > link[type="application/atom+xml"]'));

        return collect($nodes->each(function (Crawler $node) {
            return $this->transformNodesToUrls($node);
        }));
    }

    private function discoverFeedUrlByHtmlAnchorElements($html)
    {
        $crawler = new Crawler($html);
        $nodes = $crawler->filterXPath($this->converter->toXPath('a'));

        return collect($nodes->each(function (Crawler $node) {
            return $this->transformNodesToUrls($node);
        }))->filter(function ($url) {
            return Str::contains($url, 'rss');
        });
    }

    private function transformNodesToUrls(Crawler $node)
    {
        $href = $node->attr('href');

        if (isValidUrl($href)) {
            return $href;
        }

        return $this->url . '/' . $href;
    }
}
