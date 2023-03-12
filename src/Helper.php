<?php

namespace Kaishiyoku\HeraRssCrawler;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
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
        return trim(preg_replace('#(^|[^:])//+#', "\\1/", $url), '/');
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
            return $baseUrl . '/' . $url;
        }

        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
        $host = parse_url($baseUrl, PHP_URL_HOST);

        return $scheme . '://' . $host . '/' . $url;
    }

    public static function transformNodeToUrl(string $baseUrl, Crawler $node): string
    {
        return self::transformUrl($baseUrl, $node->attr('href'));
    }

    public static function replaceBaseUrl(string $url, string $oldBaseUrl, string $newBaseUrl): string
    {
        return preg_replace('/^' . preg_quote($oldBaseUrl, '/') . '/', $newBaseUrl, $url);
    }

    /**
     * @param string $url
     * @param string[] $urlReplacementMap
     * @return string
     */
    public static function replaceBaseUrls(string $url, array $urlReplacementMap): string
    {
        return (new Collection($urlReplacementMap))->keys()->reduce(
            fn($carry, $oldBaseUrl) => self::replaceBaseUrl($carry, $oldBaseUrl, $urlReplacementMap[$oldBaseUrl]), $url
        );
    }

    /**
     * @param callable $callback
     * @param int $delay
     * @param int $retries
     * @return mixed
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
     * @param mixed $value
     * @return Carbon|null
     */
    public static function parseDate($value): ?Carbon
    {
        return $value === null ? null : Carbon::parse($value);
    }

    /**
     * @param string|null $content
     * @param Client $httpClient
     * @return Collection<int, string>
     */
    public static function getImageUrls(?string $content, Client $httpClient): Collection
    {
        if (!$content) {
            return new Collection();
        }

        preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches);

        [, $imageUrls] = $matches;

        // don't allow GIF images because those will most likely be tracking pixels
        return (new Collection($imageUrls))->filter(function (string $imageUrl) use ($httpClient) {
            return $httpClient->get($imageUrl)->getHeaderLine('Content-Type') !== 'image/gif';
        });
    }

    /**
     * Trims all collection values and filters out NULL values.
     *
     * @param Collection<int, string|null> $collection
     * @return Collection<int, string>
     */
    public static function trimStringCollection(Collection $collection): Collection
    {
        return $collection->map(fn($category) => Helper::trimOrDefaultNull($category))->filter(); /** @phpstan-ignore-line */
    }
}
