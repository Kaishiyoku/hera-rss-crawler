<?php

namespace Kaishiyoku\HeraRssCrawler\Models;

interface DeserializableModel
{
    /**
     * @param array $json
     * @return mixed
     */
    public static function fromJson(array $json);
}
