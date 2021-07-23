<?php

namespace Kaishiyoku\HeraRssCrawler;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;

class Helper
{
    /**
     * @param string $url
     * @return bool
     */
    public static function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * @param string $url
     * @return string
     */
    public static function normalizeUrl(string $url): string
    {
        return trim(preg_replace('#(^|[^:])//+#', "\\1/", $url), '/');
    }

    /**
     * @param string|null $str
     * @return string|null
     */
    public static function trimOrDefaultNull(?string $str): ?string
    {
        if ($str === null) {
            return $str;
        }

        return trim($str);
    }

    /**
     * @param string $baseUrl
     * @param string $url
     * @return string
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

    /**
     * @param string $url
     * @param string $oldBaseUrl
     * @param string $newBaseUrl
     * @return string
     */
    public static function replaceBaseUrl(string $url, string $oldBaseUrl, string $newBaseUrl): string
    {
        return preg_replace('/^' . preg_quote($oldBaseUrl, '/') . '/', $newBaseUrl, $url);
    }

    /**
     * @param string $url
     * @param array $urlReplacementMap
     * @return string
     */
    public static function replaceBaseUrls(string $url, array $urlReplacementMap): string
    {
        return collect($urlReplacementMap)->keys()->reduce(function ($carry, $oldBaseUrl) use ($urlReplacementMap) {
            return self::replaceBaseUrl($carry, $oldBaseUrl, $urlReplacementMap[$oldBaseUrl]);
        }, $url);
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
            if ($retries > 0) {
                sleep($delay);

                return self::withRetries($callback, $delay, $retries - 1);
            }

            throw $e;
        }
    }

    /**
     * @param mixed $value
     * @return Carbon|null
     */
    public static function parseDate($value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value);
    }

    /**
     * @param string|null $content
     * @return array
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
