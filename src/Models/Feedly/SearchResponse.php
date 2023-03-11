<?php

namespace Kaishiyoku\HeraRssCrawler\Models\Feedly;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kaishiyoku\HeraRssCrawler\Models\DeserializableModel;

class SearchResponse implements DeserializableModel
{
    private ?string $hint = null;

    /**
     * @var Collection<int, Result>
     */
    private Collection $results;

    /**
     * @var Collection<int, string>
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
     * @return Collection<int, Result>
     */
    public function getResults(): Collection
    {
        return $this->results;
    }

    /**
     * @param Collection<int, Result> $results
     */
    public function setResults(Collection $results): void
    {
        $this->results = $results;
    }

    /**
     * @return Collection<int, string>
     */
    public function getRelated(): Collection
    {
        return $this->related;
    }

    /**
     * @param Collection<int, string> $related
     */
    public function setRelated(Collection $related): void
    {
        $this->related = $related;
    }

    public static function fromJson(mixed $json): SearchResponse
    {
        $searchResponse = new self();
        $searchResponse->setHint(Arr::get($json, 'hint'));
        $searchResponse->setRelated(new Collection(Arr::get($json, 'related')));
        $searchResponse->setResults((new Collection($json['results']))->map(fn($jsonResult) => Result::fromJson($jsonResult)));

        return $searchResponse;
    }
}
