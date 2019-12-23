<?php

namespace Kaishiyoku\HeraRssCrawler;

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
}
