<?php

namespace Kaishiyoku\HeraRssCrawler\Models\Feedly;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kaishiyoku\HeraRssCrawler\Models\DeserializableModel;

class SearchResponse implements DeserializableModel
{
    private ?string $hint = null;

    /**
     * @var Collection<Result>
     */
    private Collection $results;

    /**
     * @var Collection<string>
     */
    private Collection $related;

    public function getHint(): ?string
    {
        return $this->hint;
    }

    public function setHint(?string $hint): void
    {
        $this->hint = $hint;
    }

    /**
     * @return Collection<Result>
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
     * @param Collection<string> $related
     */
    public function setRelated(Collection $related): void
    {
        $this->related = $related;
    }

    /**
     * @param mixed $json
     * @return SearchResponse
     */
    public static function fromJson($json): SearchResponse
    {
        $searchResponse = new self();
        $searchResponse->setHint(Arr::get($json, 'hint'));
        $searchResponse->setRelated(collect(Arr::get($json, 'related')));

        $results = collect($json['results'])->map(function ($jsonResult) {
            return Result::fromJson($jsonResult);
        });
        $searchResponse->setResults($results);

        return $searchResponse;
    }
}
