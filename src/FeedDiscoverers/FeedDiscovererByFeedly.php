<?php

namespace Kaishiyoku\HeraRssCrawler\FeedDiscoverers;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Kaishiyoku\HeraRssCrawler\Models\Feedly\Result;
use Kaishiyoku\HeraRssCrawler\Models\Feedly\SearchResponse;
use Kaishiyoku\HeraRssCrawler\Models\ResponseContainer;

/**
 * Discover feed URLs using the Feedly API.
 */
class FeedDiscovererByFeedly implements FeedDiscoverer
{
    private const FEEDLY_API_BASE_URL = 'https://cloud.feedly.com/v3';

    /**
     * @return Collection<int, string>
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function discover(Client $httpClient, ResponseContainer $responseContainer): Collection
    {
        $response = $httpClient->get(self::FEEDLY_API_BASE_URL.'/search/feeds', [
            'query' => ['query' => $responseContainer->getRequestUrl()],
        ]);

        $searchResponse = SearchResponse::fromJson(json_decode($response->getBody()->getContents(), true));

        return $searchResponse->getResults()->map(fn (Result $result) => $result->getFeedUrl());
    }
}
