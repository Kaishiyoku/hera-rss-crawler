<?php

namespace Kaishiyoku\HeraRssCrawler\Models;

use Psr\Http\Message\ResponseInterface;

class ResponseContainer
{
    private string $requestUrl;

    private ResponseInterface $response;

    /**
     * ResponseContainer constructor.
     */
    public function __construct(string $requestUrl, ResponseInterface $response)
    {
        $this->requestUrl = $requestUrl;
        $this->response = $response;
    }

    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
