<?php

namespace Kaishiyoku\HeraRssCrawler\Models;

interface DeserializableModel
{
    public static function fromJson(mixed $json): mixed;
}
