<?php

use Illuminate\Support\Str;

if (!function_exists('isValidUrl')) {
    function isValidUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
}

if (!function_exists('normalizeUrl')) {
    function normalizeUrl($url)
    {
        return trim(preg_replace("#(^|[^:])//+#", "\\1/", $url), '/');
    }
}
