<?php

namespace Kaishiyoku\HeraRssCrawler\Models\Rss;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kaishiyoku\HeraRssCrawler\Helper;
use Kaishiyoku\HeraRssCrawler\HeraRssCrawler;
use Laminas\Feed\Reader\Feed\FeedInterface;
use ReflectionException;

class Feed
{
    /**
     * @var string
     */
    private $checksum;

    /**
     * @var Collection<string>
     */
    private $categories;

    /**
     * @var Collection<string>
     */
    private $authors;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string|null
     */
    private $copyright;

    /**
     * @var Carbon|null
     */
    private $createdAt;

    /**
     * @var Carbon|null
     */
    private $updatedAt;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string|null
     */
    private $feedUrl;

    /**
     * @var string;
     */
    private $id;

    /**
     * @var string|null
     */
    private $language;

    /**
     * @var string|null
     */
    private $url;

    /**
     * @var Collection<FeedItem>
     */
    private $feedItems;

    /**
     * @return string
     */
    public function getChecksum(): string
    {
        return $this->checksum;
    }

    /**
     * @param string $checksum
     */
    public function setChecksum(string $checksum): void
    {
        $this->checksum = $checksum;
    }

    /**
     * @return Collection
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @param Collection $categories
     */
    public function setCategories(Collection $categories): void
    {
        $this->categories = $categories->map(function ($category) {
            return Helper::trimOrDefaultNull($category);
        });
    }

    /**
     * @return Collection
     */
    public function getAuthors(): Collection
    {
        return $this->authors;
    }

    /**
     * @param Collection $authors
     */
    public function setAuthors(Collection $authors): void
    {
        $this->authors = $authors->map(function ($author) {
            return Helper::trimOrDefaultNull($author);
        });
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = Helper::trimOrDefaultNull($title);
    }

    /**
     * @return string|null
     */
    public function getCopyright(): ?string
    {
        return $this->copyright;
    }

    /**
     * @param string|null $copyright
     */
    public function setCopyright(?string $copyright): void
    {
        $this->copyright = Helper::trimOrDefaultNull($copyright);
    }

    /**
     * @return Carbon|null
     */
    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    /**
     * @param Carbon|null $createdAt
     */
    public function setCreatedAt(?Carbon $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return Carbon|null
     */
    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    /**
     * @param Carbon|null $updatedAt
     */
    public function setUpdatedAt(?Carbon $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = Helper::trimOrDefaultNull($description);
    }

    /**
     * @return string|null
     */
    public function getFeedUrl(): ?string
    {
        return $this->feedUrl;
    }

    /**
     * @param string|null $feedUrl
     */
    public function setFeedUrl(?string $feedUrl): void
    {
        $this->feedUrl = Helper::trimOrDefaultNull($feedUrl);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = Helper::trimOrDefaultNull($id);
    }

    /**
     * @return string|null
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @param string|null $language
     */
    public function setLanguage(?string $language): void
    {
        $this->language = Helper::trimOrDefaultNull($language);
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     */
    public function setUrl(?string $url): void
    {
        $this->url = Helper::trimOrDefaultNull($url);
    }

    /**
     * @return Collection
     */
    public function getFeedItems(): Collection
    {
        return $this->feedItems;
    }

    /**
     * @param Collection $feedItems
     */
    public function setFeedItems(Collection $feedItems): void
    {
        $this->feedItems = $feedItems;
    }

    /**
     * @param FeedInterface $zendFeed
     * @return Feed
     * @throws ReflectionException
     */
    public static function fromZendFeed(FeedInterface $zendFeed): Feed
    {
        $authors = collect($zendFeed->getAuthors())->map(function ($authorData) {
            return Arr::get($authorData, 'name');
        });

        $feed = new self();
        $feed->setCategories(collect($zendFeed->getCategories()->getValues()));
        $feed->setAuthors($authors);
        $feed->setTitle($zendFeed->getTitle() ?? '');
        $feed->setCopyright($zendFeed->getCopyright());
        $feed->setCreatedAt(Helper::parseDate($zendFeed->getDateCreated()));
        $feed->setUpdatedAt(Helper::parseDate($zendFeed->getDateModified()));
        $feed->setDescription($zendFeed->getDescription());
        $feed->setFeedUrl($zendFeed->getFeedLink());
        $feed->setId($zendFeed->getId());
        $feed->setLanguage($zendFeed->getLanguage());
        $feed->setUrl($zendFeed->getLink());

        $feedItems = collect();

        foreach ($zendFeed as $zendFeedItem) {
            $feedItems->add(FeedItem::fromZendFeedItem($zendFeedItem));
        }

        $feed->setFeedItems($feedItems);
        $feed->setChecksum(HeraRssCrawler::generateChecksumForFeed($feed));

        return $feed;
    }
}
