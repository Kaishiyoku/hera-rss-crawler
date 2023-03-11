<?php

namespace Kaishiyoku\HeraRssCrawler\FeedDiscoverers;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Kaishiyoku\HeraRssCrawler\Models\ResponseContainer;

interface FeedDiscoverer
{
    /**
     * @param Client $httpClient
     * @param ResponseContainer $responseContainer
     * @return Collection<int, string>
     */
    public function discover(Client $httpClient, ResponseContainer $responseContainer): Collection;
}
