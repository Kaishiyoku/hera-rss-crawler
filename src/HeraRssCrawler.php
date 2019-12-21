<?php

namespace Kaishiyoku\HeraRssCrawler;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
     * HeraRssCrawler constructor.
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->httpClient = new Client();

        $this->url = $url;
    }

    public function discoverFeedUrl()
    {
        $converter = new CssSelectorConverter();

        $response = $this->httpClient->get($this->url);

        $contentType = Arr::get($response->getHeaders(), 'Content-Type');

        if (is_array($contentType)) {
            $contentType = Arr::first($contentType);
        }

        // the given url itself already is a rss feed
        if (Str::startsWith($contentType, 'application/rss+xml')) {
            return collect([$this->url]);
        }

        $crawler = new Crawler($response->getBody()->getContents());
        $nodes = $crawler->filterXPath($converter->toXPath('head > link[type="application/rss+xml"], head > link[type="application/atom+xml"]'));

        $urls = collect($nodes->each(function (Crawler $node) {
            return $this->transformNodesToUrls($node);
        }));

        // fallback: search for links with rss in its content or href
        if ($urls->isEmpty()) {
            $nodes = $crawler->filterXPath($converter->toXPath('a'));

            $urls = collect($nodes->each(function (Crawler $node) {
                return $this->transformNodesToUrls($node);
            }))->filter(function ($url) {
                return Str::contains($url, 'rss');
            });
        }

        return $urls->values();
    }

    private function transformNodesToUrls(Crawler $node)
    {
        $href = $node->attr('href');

        if (filter_var($href, FILTER_VALIDATE_URL)) {
            return $href;
        }

        if (Str::endsWith($this->url, '/') || Str::startsWith($href, '/')) {
            return $this->url . $href;
        } else {
            return $this->url . '/' . $href;
        }
    }
}
