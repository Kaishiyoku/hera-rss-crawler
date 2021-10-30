<?php

namespace Kaishiyoku\HeraRssCrawler;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;

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
        return collect($urlReplacementMap)->keys()->reduce(
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
    public static function withRetries(callable $callback, int $delay = 1, int $retries = 3)
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
     * @return string[]
     */
    public static function getImageUrls(?string $content): array
    {
        if (!$content) {
            return [];
        }

        preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches);

        [, $imageUrls] = $matches;

        return $imageUrls;
    }
}
