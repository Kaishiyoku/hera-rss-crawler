<?php

namespace Kaishiyoku\HeraRssCrawler;

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
        return trim(preg_replace("#(^|[^:])//+#", "\\1/", $url), '/');
    }
}