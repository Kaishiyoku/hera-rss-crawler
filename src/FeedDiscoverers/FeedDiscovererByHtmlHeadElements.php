<?php

namespace Kaishiyoku\HeraRssCrawler\FeedDiscoverers;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Kaishiyoku\HeraRssCrawler\Helper;
use Kaishiyoku\HeraRssCrawler\Models\ResponseContainer;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Discover feed URLs by parsing the website's HTML head elements.
 */
class FeedDiscovererByHtmlHeadElements implements FeedDiscoverer
{
    public function discover(Client $httpClient, ResponseContainer $responseContainer): Collection
    {
        $cssConverter = new CssSelectorConverter();
        $crawler = new Crawler($responseContainer->getResponse()->getBody()->getContents());

        $nodes = $crawler->filterXPath($cssConverter->toXPath('head > link[type="application/rss+xml"], head > link[type="application/atom+xml"]'));

        return new Collection($nodes->each(fn(Crawler $node) => Helper::transformNodeToUrl($responseContainer->getRequestUrl(), $node)));
    }
}
