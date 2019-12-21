<?php

namespace Kaishiyoku\HeraRssCrawler;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\DomCrawler\Crawler;

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
     * HeraRssCrawler constructor.
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->httpClient = new Client();
        $this->converter = new CssSelectorConverter();

        $this->url = $url;
    }

    public function discoverFeedUrl()
    {
        $response = $this->httpClient->get($this->url);

        $urls = $this->discoverFeedUrlByContentType($response);

        if ($urls->isEmpty()) {
            $urls = $this->discoverFeedUrlByCrawler($response->getBody()->getContents());
        }

        return $urls->map(function ($url) {
            return normalizeUrl($url);
        })->values();
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

    private function discoverFeedUrlByCrawler($html)
    {
        $urls = $this->discoverFeedUrlByHtmlHeadElements($html);

        // fallback: search for links with rss in its content or href
        if ($urls->isEmpty()) {
            return $this->discoverFeedUrlByHtmlAnchorElements($html);
        }

        return $urls;
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
