<?php

namespace Kaishiyoku\HeraRssCrawler\Models\Rss;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kaishiyoku\HeraRssCrawler\Helper;
use Kaishiyoku\HeraRssCrawler\HeraRssCrawler;
use ReflectionException;

class Feed
{
    private string $checksum;

    /**
     * @var Collection<int, string>
     */
    private Collection $categories;

    /**
     * @var Collection<int, string>
     */
    private Collection $authors;

    private string $title;

    private ?string $copyright = null;

    private ?Carbon $createdAt = null;

    private ?Carbon $updatedAt = null;

    private ?string $description = null;

    private ?string $feedUrl = null;

    private string $id;

    private ?string $language = null;

    private ?string $url = null;

    /**
     * @var Collection<int, FeedItem>
     */
    private Collection $feedItems;

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function setChecksum(string $checksum): void
    {
        $this->checksum = $checksum;
    }

    /**
     * @return Collection<int, string>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @param Collection<int, mixed> $categories
     */
    public function setCategories(Collection $categories): void
    {
        $this->categories = Helper::trimStringCollection($categories);
    }

    /**
     * @return Collection<int, string>
     */
    public function getAuthors(): Collection
    {
        return $this->authors;
    }

    /**
     * @param Collection<int, mixed> $authors
     */
    public function setAuthors(Collection $authors): void
    {
        $this->authors = Helper::trimStringCollection($authors);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = Helper::trimOrDefaultNull($title);
    }

    public function getCopyright(): ?string
    {
        return $this->copyright;
    }

    public function setCopyright(?string $copyright): void
    {
        $this->copyright = Helper::trimOrDefaultNull($copyright);
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?Carbon $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?Carbon $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = Helper::trimOrDefaultNull($description);
    }

    public function getFeedUrl(): ?string
    {
        return $this->feedUrl;
    }

    public function setFeedUrl(?string $feedUrl): void
    {
        $this->feedUrl = Helper::trimOrDefaultNull($feedUrl);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = Helper::trimOrDefaultNull($id);
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = Helper::trimOrDefaultNull($language);
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = Helper::trimOrDefaultNull($url);
    }

    /**
     * @return Collection<int, FeedItem>
     */
    public function getFeedItems(): Collection
    {
        return $this->feedItems;
    }

    /**
     * @param Collection<int, FeedItem> $feedItems
     */
    public function setFeedItems(Collection $feedItems): void
    {
        $this->feedItems = $feedItems;
    }

    /**
     * @param mixed $zendFeed
     * @return Feed
     * @throws ReflectionException
     */
    public static function fromZendFeed($zendFeed): Feed
    {
        $authors = (new Collection($zendFeed->getAuthors()))->map(fn($authorData) => Arr::get($authorData, 'name'));

        $feed = new self();
        $feed->setCategories(new Collection($zendFeed->getCategories()->getValues()));
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

        $feedItems = new Collection();

        foreach ($zendFeed as $zendFeedItem) {
            $feedItems->add(FeedItem::fromZendFeedItem($zendFeedItem));
        }

        $feed->setFeedItems($feedItems);
        $feed->setChecksum(HeraRssCrawler::generateChecksumForFeed($feed));

        return $feed;
    }
}
