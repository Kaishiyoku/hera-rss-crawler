<?php

namespace Kaishiyoku\HeraRssCrawler\FeedDiscoverers;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Kaishiyoku\HeraRssCrawler\Models\ResponseContainer;

/**
 * Discover feed URL by parsing a direct RSS feed url.
 */
class FeedDiscovererByContentType implements FeedDiscoverer
{
    /**
     * @param Client $httpClient
     * @param ResponseContainer $responseContainer
     * @return Collection<int, string>
     */
    public function discover(Client $httpClient, ResponseContainer $responseContainer): Collection
    {
        $contentTypeMixedValue = Arr::get($responseContainer->getResponse()->getHeaders(), 'Content-Type');

        $contentType = is_array($contentTypeMixedValue) ? Arr::first($contentTypeMixedValue) : $contentTypeMixedValue;

        // the given url is no valid RSS feed
        if (!$contentType || !Str::startsWith($contentType, ['application/rss+xml', 'application/atom+xml'])) {
            return new Collection();
        }

        return new Collection([$responseContainer->getRequestUrl()]);
    }
}
