<?php

namespace Kaishiyoku\HeraRssCrawler;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class Helper
{
    public static function isValidUrl(string $url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }

    public static function normalizeUrl(string $url): string
    {
        return trim(preg_replace('#(^|[^:])//+#', '\\1/', $url), '/');
    }

    public static function trimOrDefaultNull(?string $str): ?string
    {
        return $str === null ? null : trim($str);
    }

    /**
     * Transform a Crawler node to a string URL.
     */
    public static function transformUrl(string $baseUrl, string $url): string
    {
        if (self::isValidUrl($url)) {
            return $url;
        }

        if (Str::startsWith($url, '/')) {
            return $baseUrl.'/'.$url;
        }

        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
        $host = parse_url($baseUrl, PHP_URL_HOST);

        return $scheme.'://'.$host.'/'.$url;
    }

    public static function transformNodeToUrl(string $baseUrl, Crawler $node): string
    {
        return self::transformUrl($baseUrl, $node->attr('href'));
    }

    public static function replaceBaseUrl(string $url, string $oldBaseUrl, string $newBaseUrl): string
    {
        return preg_replace('/^'.preg_quote($oldBaseUrl, '/').'/', $newBaseUrl, $url);
    }

    /**
     * @param  string[]  $urlReplacementMap
     */
    public static function replaceBaseUrls(string $url, array $urlReplacementMap): string
    {
        return (new Collection($urlReplacementMap))->keys()->reduce(
            fn ($carry, $oldBaseUrl) => self::replaceBaseUrl($carry, $oldBaseUrl, $urlReplacementMap[$oldBaseUrl]), $url
        );
    }

    /**
     * @throws Exception
     */
    public static function withRetries(callable $callback, int $delay = 1, int $retries = 3): mixed
    {
        try {
            return $callback();
        } catch (Exception $e) {
            if ($retries <= 0) {
                throw $e;
            }

            sleep($delay);

            return self::withRetries($callback, $delay, $retries - 1);
        }
    }

    /**
     * @param  mixed  $value
     */
    public static function parseDate($value): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value);
    }

    /**
     * @return Collection<int, string>
     */
    public static function getImageUrlsForFeedItem(string $feedItemUrl, ?string $content, Client $httpClient): Collection
    {
        $urlScheme = parse_url($feedItemUrl, PHP_URL_SCHEME);
        $urlHost = parse_url($feedItemUrl, PHP_URL_HOST);

        $baseUrl = $urlScheme.'://'.$urlHost;

        if (! $content) {
            return new Collection();
        }

        preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches);

        [, $imageUrls] = $matches;

        // don't allow GIF images because those will most likely be tracking pixels
        return (new Collection($imageUrls))
            ->map(function (string $imageUrl) use ($baseUrl) {
                if (Str::startsWith($imageUrl, 'http')) {
                    return $imageUrl;
                }

                return $baseUrl.'/'.ltrim($imageUrl, '/');
            })
            ->filter(fn (string $imageUrl) => self::getHttpContentTypeForUrl($imageUrl, $httpClient) !== 'image/gif');
    }

    public static function getHttpContentTypeForUrl(string $url, Client $httpClient): ?string
    {
        try {
            return $httpClient->get($url)->getHeaderLine('Content-Type');
        } catch (ConnectException) {
            return null;
        } catch (ClientException $exception) {
            // return null for HTTP Not Found Exceptions
            if ($exception->getResponse()->getStatusCode() === 404) {
                return null;
            }

            throw $exception;
        }
    }

    /**
     * Trims all collection values and filters out NULL values.
     *
     * @param  Collection<int, string|null>  $collection
     * @return Collection<int, string>
     */
    public static function trimStringCollection(Collection $collection): Collection
    {
        /** @phpstan-ignore-next-line */
        return $collection->map(fn ($category) => Helper::trimOrDefaultNull($category))->filter();
    }

    public static function entityDecode(string $encoding, ?string $str): ?string
    {
        if (! $str) {
            return $str;
        }

        return html_entity_decode($str, ENT_QUOTES | ENT_HTML5, $encoding);
    }
}
