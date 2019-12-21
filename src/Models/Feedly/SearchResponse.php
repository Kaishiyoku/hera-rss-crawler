<?php

namespace Kaishiyoku\HeraRssCrawler\Models\Feedly;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kaishiyoku\HeraRssCrawler\Models\DeserializableModel;

class SearchResponse implements DeserializableModel
{
    /**
     * @var string|null
     */
    private $hint;

    /**
     * @var Collection<Result>
     */
    private $results;

    /**
     * @var Collection<string>
     */
    private $related;

    /**
     * @return string|null
     */
    public function getHint(): ?string
    {
        return $this->hint;
    }

    /**
     * @param string|null $hint
     */
    public function setHint(?string $hint): void
    {
        $this->hint = $hint;
    }

    /**
     * @return Collection
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    /**
     * @param Collection<Result> $results
     */
    public function setResults(Collection $results): void
    {
        $this->results = $results;
    }

    /**
     * @return Collection<string>
     */
    public function getRelated(): Collection
    {
        return $this->related;
    }

    /**
     * @param Collection $related
     */
    public function setRelated(Collection $related): void
    {
        $this->related = $related;
    }

    /**
     * @param array $json
     * @return SearchResponse
     */
    public static function fromJson(array $json): SearchResponse
    {
        $searchResponse = new SearchResponse();
        $searchResponse->setHint(Arr::get($json, 'hint'));
        $searchResponse->setRelated(collect(Arr::get($json, 'related')));

        $results = collect($json['results'])->map(function ($jsonResult) {
            return Result::fromJson($jsonResult);
        });
        $searchResponse->setResults($results);

        return $searchResponse;
    }
}
