<?php

namespace Kaishiyoku\HeraRssCrawler\TestClasses;

class FailingTestClass
{
    public function fail()
    {
        return json_decode('this will fail', true, 512, JSON_THROW_ON_ERROR);
    }
}
