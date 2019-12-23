<?php

namespace Kaishiyoku\HeraRssCrawler\Models\Rss;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Zend\Feed\Reader\Feed\FeedInterface;

class Feed
{
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
     * @var Carbon
     */
    private $createdAt;

    /**
     * @var Carbon
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
        $this->categories = $categories;
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
        $this->authors = $authors;
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
        $this->title = $title;
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
        $this->copyright = $copyright;
    }

    /**
     * @return Carbon
     */
    public function getCreatedAt(): Carbon
    {
        return $this->createdAt;
    }

    /**
     * @param Carbon $createdAt
     */
    public function setCreatedAt(Carbon $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return Carbon
     */
    public function getUpdatedAt(): Carbon
    {
        return $this->updatedAt;
    }

    /**
     * @param Carbon $updatedAt
     */
    public function setUpdatedAt(Carbon $updatedAt): void
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
        $this->description = $description;
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
        $this->feedUrl = $feedUrl;
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
        $this->id = $id;
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
        $this->language = $language;
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
        $this->url = $url;
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
     */
    public static function fromZendFeed(FeedInterface $zendFeed): Feed
    {
        $feed = new Feed();
        $feed->setCategories(collect($zendFeed->getCategories()->getValues()));
        $feed->setAuthors(collect($zendFeed->getAuthors() == null ? null : $zendFeed->getAuthors()->getValues()));
        $feed->setTitle($zendFeed->getTitle());
        $feed->setCopyright($zendFeed->getCopyright());
        $feed->setCreatedAt(Carbon::parse($zendFeed->getDateCreated()));
        $feed->setUpdatedAt(Carbon::parse($zendFeed->getDateModified()));
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

        return $feed;
    }
}
