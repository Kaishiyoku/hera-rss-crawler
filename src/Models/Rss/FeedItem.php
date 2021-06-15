<?php

namespace Kaishiyoku\HeraRssCrawler\Models\Rss;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonSerializable;
use Kaishiyoku\HeraRssCrawler\Helper;
use Kaishiyoku\HeraRssCrawler\HeraRssCrawler;
use Laminas\Feed\Reader\Entry\Atom;
use Laminas\Feed\Reader\Entry\EntryInterface;
use Laminas\Feed\Reader\Entry\Rss;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use TypeError;

class FeedItem implements JsonSerializable
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
    private $enclosureUrl;

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
     * @var string|null
     */
    private $xml;

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
        $this->commentFeedLink = Helper::trimOrDefaultNull($commentFeedLink);
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
        $this->commentLink = Helper::trimOrDefaultNull($commentLink);
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
        $this->content = Helper::trimOrDefaultNull($content);
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
    public function getEnclosureUrl(): ?string
    {
        return $this->enclosureUrl;
    }

    /**
     * @param string|null $enclosureUrl
     */
    public function setEnclosureUrl(?string $enclosureUrl): void
    {
        $this->enclosureUrl = Helper::trimOrDefaultNull($enclosureUrl);
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
        $this->encoding = Helper::trimOrDefaultNull($encoding);
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
        $this->links = $links->map(function ($link) {
            return Helper::trimOrDefaultNull($link);
        });
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
        $this->permalink = Helper::trimOrDefaultNull($permalink);
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
        $this->type = Helper::trimOrDefaultNull($type);
    }

    /**
     * @return string|null
     */
    public function getXml(): ?string
    {
        return $this->xml;
    }

    /**
     * @param string|null $xml
     */
    public function setXml(?string $xml): void
    {
        $this->xml = Helper::trimOrDefaultNull($xml);
    }

    /**
     * @param EntryInterface $zendFeedItem
     * @return FeedItem
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public static function fromZendFeedItem(EntryInterface $zendFeedItem): FeedItem
    {
        if (!$zendFeedItem instanceof Rss && !$zendFeedItem instanceof Atom) {
            throw new InvalidArgumentException('given feed item neither is from a RSS or Atom feed');
        }

        $feedItem = new self();

        $feedItem->setCategories(collect($zendFeedItem->getCategories()->getValues()));
        $feedItem->setAuthors(collect(optional($zendFeedItem->getAuthors(), function ($authors) {
            return $authors->getValues();
        })));
        $feedItem->setTitle($zendFeedItem->getTitle() ?? ''); // TODO: investigate; why can a title be empty? maybe we should discard those items
        $feedItem->setCommentCount($zendFeedItem->getCommentCount() ?? 0);
        $feedItem->setCommentFeedLink($zendFeedItem->getCommentFeedLink());
        $feedItem->setCommentLink($zendFeedItem->getCommentLink());

        try {
            $feedItem->setContent($zendFeedItem->getContent());
        } catch (TypeError $e) {
            // no content available
            $feedItem->setContent('');
        }

        $feedItem->setCreatedAt($zendFeedItem->getDateCreated() == null ? null : Carbon::parse($zendFeedItem->getDateCreated()));
        $feedItem->setUpdatedAt($zendFeedItem->getDateModified() == null ? null : Carbon::parse($zendFeedItem->getDateModified()));
        $feedItem->setDescription($zendFeedItem->getDescription());
        $feedItem->setEnclosureUrl(optional($zendFeedItem->getEnclosure(), function ($enclosure) {
            return $enclosure->url;
        }));
        $feedItem->setEncoding($zendFeedItem->getEncoding());
        $feedItem->setId($zendFeedItem->getId());
        $feedItem->setLinks(collect($zendFeedItem->getLinks()));
        $feedItem->setPermalink($zendFeedItem->getPermalink() ?? ''); // TODO: investigate; why can a permalink be empty? maybe we should discard those items
        $feedItem->setType($zendFeedItem->getType());
        $feedItem->setXml($zendFeedItem->saveXml());

        $feedItem->setChecksum(HeraRssCrawler::generateChecksumForFeedItem($feedItem));

        return $feedItem;
    }

    /**
     * @return string|null
     */
    public function jsonSerialize(): ?string
    {
        try {
            $class = new ReflectionClass(self::class);
            $methods = collect($class->getMethods(ReflectionMethod::IS_PUBLIC))
                ->filter(function (ReflectionMethod $method) {
                    return Str::startsWith($method->getName(), 'get');
                });

            return $methods->mapWithKeys(function (ReflectionMethod $method) {
                return [lcfirst(Str::substr($method->getName(), 3)) => $method->invoke($this)];
            })->toJson();
        } catch (ReflectionException $e) {
            return null;
        }
    }
}
