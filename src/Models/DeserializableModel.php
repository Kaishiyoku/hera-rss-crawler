<?php

namespace Kaishiyoku\HeraRssCrawler\Models;

interface DeserializableModel
{
    /**
     * @param mixed $json
     * @return mixed
     */
    public static function fromJson($json);
}
