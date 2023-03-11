<?php

namespace Kaishiyoku\HeraRssCrawler\FeedDiscoverers;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Kaishiyoku\HeraRssCrawler\Helper;
use Kaishiyoku\HeraRssCrawler\Models\ResponseContainer;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Discover feed URLs by searching for HTML anchor elements.
 */
class FeedDiscovererByHtmlAnchorElements implements FeedDiscoverer
{
    public function discover(Client $httpClient, ResponseContainer $responseContainer): Collection
    {
        $cssConverter = new CssSelectorConverter();
        $crawler = new Crawler($responseContainer->getResponse()->getBody()->getContents());
        $nodes = $crawler->filterXPath($cssConverter->toXPath('a'));

        return (new Collection($nodes->each(fn(Crawler $node) => Helper::transformNodeToUrl($responseContainer->getRequestUrl(), $node))))
            ->filter(fn($url) => Str::contains($url, 'rss'));
    }
}
