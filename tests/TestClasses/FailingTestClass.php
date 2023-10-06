<?php

namespace Kaishiyoku\HeraRssCrawler\TestClasses;

use JsonException;

class FailingTestClass
{
    /**
     * @return mixed
     *
     * @throws JsonException
     */
    public function fail()
    {
        return json_decode('this will fail', true, 512, JSON_THROW_ON_ERROR);
    }
}
