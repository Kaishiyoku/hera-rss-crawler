<?php

namespace Kaishiyoku\HeraRssCrawler\Models;

use Psr\Http\Message\ResponseInterface;

class ResponseContainer
{
    /**
     * @var string
     */
    private $requestUrl;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * ResponseContainer constructor.
     * @param string $requestUrl
     * @param ResponseInterface $response
     */
    public function __construct(string $requestUrl, ResponseInterface $response)
    {
        $this->requestUrl = $requestUrl;
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getRequestUrl(): string
    {
        return $this->requestUrl;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
