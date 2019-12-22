<?php

namespace Kaishiyoku\HeraRssCrawler\Models\Rss;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Kaishiyoku\HeraRssCrawler\HeraRssCrawler;
use ReflectionException;
use TypeError;
use Zend\Feed\Reader\Entry\AbstractEntry;
use Zend\Feed\Reader\Entry\Atom;
use Zend\Feed\Reader\Entry\Rss;

class Item
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
     * @var int
     */
    private $commentCount;

    /**
     * @var string|null
     */
    private $commentFeedLink;

    /**
     * @var string|null
     */
    private $commentLink;

    /**
     * @var string
     */
    private $content;

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
    private $enclosure;

    /**
     * @var string
     */
    private $encoding;

    /**
     * @var string
     */
    private $id;

    /**
     * @var Collection<string>
     */
    private $links;

    /**
     * @var string
     */
    private $permalink;

    /**
     * @var string
     */
    private $type;

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
     * @return int
     */
    public function getCommentCount(): int
    {
        return $this->commentCount;
    }

    /**
     * @param int $commentCount
     */
    public function setCommentCount(int $commentCount): void
    {
        $this->commentCount = $commentCount;
    }

    /**
     * @return string|null
     */
    public function getCommentFeedLink(): ?string
    {
        return $this->commentFeedLink;
    }

    /**
     * @param string|null $commentFeedLink
     */
    public function setCommentFeedLink(?string $commentFeedLink): void
    {
        $this->commentFeedLink = $commentFeedLink;
    }

    /**
     * @return string|null
     */
    public function getCommentLink(): ?string
    {
        return $this->commentLink;
    }

    /**
     * @param string|null $commentLink
     */
    public function setCommentLink(?string $commentLink): void
    {
        $this->commentLink = $commentLink;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
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
    public function getEnclosure(): ?string
    {
        return $this->enclosure;
    }

    /**
     * @param string|null $enclosure
     */
    public function setEnclosure(?string $enclosure): void
    {
        $this->enclosure = $enclosure;
    }

    /**
     * @return string
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * @param string $encoding
     */
    public function setEncoding(string $encoding): void
    {
        $this->encoding = $encoding;
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
     * @return Collection
     */
    public function getLinks(): Collection
    {
        return $this->links;
    }

    /**
     * @param Collection $links
     */
    public function setLinks(Collection $links): void
    {
        $this->links = $links;
    }

    /**
     * @return string
     */
    public function getPermalink(): string
    {
        return $this->permalink;
    }

    /**
     * @param string $permalink
     */
    public function setPermalink(string $permalink): void
    {
        $this->permalink = $permalink;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @param AbstractEntry $zendFeedItem
     * @return Item
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public static function fromZendFeedItem($zendFeedItem): Item
    {
        if (!$zendFeedItem instanceof Rss && !$zendFeedItem instanceof  Atom) {
            throw new InvalidArgumentException('given feed item neither is from a Rss or Atom feed');
        }

        $feedItem = new Item();

        $feedItem->setCategories(collect($zendFeedItem->getCategories()->getValues()));
        $feedItem->setAuthors(collect($zendFeedItem->getAuthors() == null ? null : $zendFeedItem->getAuthors()->getValues()));
        $feedItem->setTitle($zendFeedItem->getTitle() == null ? '' : $zendFeedItem->getTitle()); // TODO: investigate; why can a title be empty? maybe we should discard those items
        $feedItem->setCommentCount($zendFeedItem->getCommentCount() ?? 0);
        $feedItem->setCommentFeedLink($zendFeedItem->getCommentFeedLink());
        $feedItem->setCommentLink($zendFeedItem->getCommentLink());

        try {
            $feedItem->setContent($zendFeedItem->getContent());
        } catch (TypeError $e) {
            // no content available
            $feedItem->setContent('');
        }

        $feedItem->setCreatedAt(Carbon::parse($zendFeedItem->getDateCreated()));
        $feedItem->setUpdatedAt(Carbon::parse($zendFeedItem->getDateModified()));
        $feedItem->setDescription($zendFeedItem->getDescription());
        $feedItem->setEnclosure($zendFeedItem->getEnclosure() ? $zendFeedItem->getEnclosure()->url : null);
        $feedItem->setEncoding($zendFeedItem->getEncoding());
        $feedItem->setId($zendFeedItem->getId());
        $feedItem->setLinks(collect($zendFeedItem->getLinks()));
        $feedItem->setPermalink($zendFeedItem->getPermalink() == null ? '' : $zendFeedItem->getPermalink()); // TODO: investigate; why can a permalink be empty? maybe we should discard those items
        $feedItem->setType($zendFeedItem->getType());

        $feedItem->setChecksum(HeraRssCrawler::generateChecksumForFeedItem($feedItem));

        return $feedItem;
    }
}
