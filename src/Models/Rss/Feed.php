<?php

namespace Kaishiyoku\HeraRssCrawler\Models\Rss;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Kaishiyoku\HeraRssCrawler\Helper;
use Kaishiyoku\HeraRssCrawler\HeraRssCrawler;
use Laminas\Feed\Reader\Feed\Atom;
use Laminas\Feed\Reader\Feed\FeedInterface;
use Laminas\Feed\Reader\Feed\Rss;
use ReflectionException;

class Feed
{
    private string $checksum;

    /**
     * @var Collection<string>
     */
    private Collection $categories;

    /**
     * @var Collection<string>
     */
    private Collection $authors;

    private string $title;

    private ?string $copyright;

    private ?Carbon $createdAt;

    private ?Carbon $updatedAt;

    private ?string $description;

    private ?string $feedUrl;

    private string $id;

    private ?string $language;

    private ?string $url;

    /**
     * @var Collection<FeedItem>
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
     * @return Collection<string>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @param Collection<string> $categories
     */
    public function setCategories(Collection $categories): void
    {
        $this->categories = $categories->map(function ($category) {
            return Helper::trimOrDefaultNull($category);
        });
    }

    /**
     * @return Collection<string>
     */
    public function getAuthors(): Collection
    {
        return $this->authors;
    }

    /**
     * @param Collection<string> $authors
     */
    public function setAuthors(Collection $authors): void
    {
        $this->authors = $authors->map(function ($author) {
            return Helper::trimOrDefaultNull($author);
        });
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
     * @return Collection<FeedItem>
     */
    public function getFeedItems(): Collection
    {
        return $this->feedItems;
    }

    /**
     * @param Collection<FeedItem> $feedItems
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
