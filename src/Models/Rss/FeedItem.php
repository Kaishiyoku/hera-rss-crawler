<?php

namespace Kaishiyoku\HeraRssCrawler\Models\Rss;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use Kaishiyoku\HeraRssCrawler\Helper;
use Kaishiyoku\HeraRssCrawler\HeraRssCrawler;
use Laminas\Feed\Reader\Entry\Atom;
use Laminas\Feed\Reader\Entry\EntryInterface;
use Laminas\Feed\Reader\Entry\Rss;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class FeedItem
{
    private const COMPARISON_FIELDS = [
        'title',
        'content',
        'createdAt',
        'updatedAt',
        'description',
        'permalink',
    ];

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

    private int $commentCount;

    private ?string $commentFeedLink = null;

    private ?string $commentLink = null;

    private ?string $content = null;

    private ?Carbon $createdAt = null;

    private ?Carbon $updatedAt = null;

    private ?string $description = null;

    private ?string $enclosureUrl = null;

    /**
     * @var Collection<string>
     */
    private Collection $imageUrls;

    private string $encoding;

    private string $id;

    /**
     * @var Collection<string>
     */
    private Collection $links;

    private string $permalink;

    private string $type;

    private ?string $xml = null;

    public function __construct()
    {
        $this->categories = collect();
        $this->authors = collect();
        $this->imageUrls = collect();
        $this->links = collect();
    }

    # region getters and setters

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

    public function getCommentCount(): int
    {
        return $this->commentCount;
    }

    public function setCommentCount(int $commentCount): void
    {
        $this->commentCount = $commentCount;
    }

    public function getCommentFeedLink(): ?string
    {
        return $this->commentFeedLink;
    }

    public function setCommentFeedLink(?string $commentFeedLink): void
    {
        $this->commentFeedLink = Helper::trimOrDefaultNull($commentFeedLink);
    }

    public function getCommentLink(): ?string
    {
        return $this->commentLink;
    }

    public function setCommentLink(?string $commentLink): void
    {
        $this->commentLink = Helper::trimOrDefaultNull($commentLink);
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = Helper::trimOrDefaultNull($content);
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

    public function getEnclosureUrl(): ?string
    {
        return $this->enclosureUrl;
    }

    public function setEnclosureUrl(?string $enclosureUrl): void
    {
        $this->enclosureUrl = Helper::trimOrDefaultNull($enclosureUrl);
    }

    /**
     * @return Collection<string>
     */
    public function getImageUrls(): Collection
    {
        return $this->imageUrls;
    }

    /**
     * @param Collection<string> $imageUrls
     */
    public function setImageUrls(Collection $imageUrls): void
    {
        $this->imageUrls = $imageUrls;
    }

    public function getEncoding(): string
    {
        return $this->encoding;
    }

    public function setEncoding(string $encoding): void
    {
        $this->encoding = Helper::trimOrDefaultNull($encoding);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = Helper::trimOrDefaultNull($id);
    }

    /**
     * @return Collection<string>
     */
    public function getLinks(): Collection
    {
        return $this->links;
    }

    /**
     * @param Collection<string> $links
     */
    public function setLinks(Collection $links): void
    {
        $this->links = $links->map(function ($link) {
            return Helper::trimOrDefaultNull($link);
        });
    }

    public function getPermalink(): string
    {
        return $this->permalink;
    }

    public function setPermalink(string $permalink): void
    {
        $this->permalink = Helper::trimOrDefaultNull($permalink);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = Helper::trimOrDefaultNull($type);
    }

    public function getXml(): ?string
    {
        return $this->xml;
    }

    public function setXml(?string $xml): void
    {
        $this->xml = Helper::trimOrDefaultNull($xml);
    }

    # endregion getters and setters

    /**
     * Generate a feed item from a given XML entity.
     */
    public static function fromZendFeedItem(EntryInterface $zendFeedItem): FeedItem
    {
        if (!$zendFeedItem instanceof Rss && !$zendFeedItem instanceof Atom) {
            throw new InvalidArgumentException('given feed item neither is from a RSS or Atom feed');
        }

        $feedItem = new self();

        $feedItem->setCategories(collect($zendFeedItem->getCategories()->getValues()));
        $feedItem->setAuthors(collect(optional($zendFeedItem->getAuthors(), function ($authors) {
            return collect($authors)->map(function ($author) {
                $name = Arr::get($author, 'name');
                $email = Arr::get($author, 'email');

                if ($name && $email) {
                    return "{$name} <{$email}>";
                }

                return $name ?? $email;
            });
        })));
        $feedItem->setTitle($zendFeedItem->getTitle() ?: ''); // TODO: investigate; why can a title be empty? maybe we should discard those items
        $feedItem->setCommentCount($zendFeedItem->getCommentCount() ?: 0);
        $feedItem->setCommentFeedLink($zendFeedItem->getCommentFeedLink());
        $feedItem->setCommentLink($zendFeedItem->getCommentLink());
        $feedItem->setContent($zendFeedItem->getContent());
        $feedItem->setCreatedAt($zendFeedItem->getDateCreated() == null ? null : Carbon::parse($zendFeedItem->getDateCreated()));
        $feedItem->setUpdatedAt($zendFeedItem->getDateModified() == null ? null : Carbon::parse($zendFeedItem->getDateModified()));
        $feedItem->setDescription($zendFeedItem->getDescription());
        $feedItem->setEnclosureUrl(optional($zendFeedItem->getEnclosure(), function ($enclosure) {
            return $enclosure->url;
        }));
        $feedItem->setImageUrls(collect(Helper::getImageUrls($feedItem->getContent())));
        $feedItem->setEncoding($zendFeedItem->getEncoding());
        $feedItem->setId($zendFeedItem->getId());
        $feedItem->setLinks(collect($zendFeedItem->getLinks()));
        $feedItem->setPermalink($zendFeedItem->getPermalink() ?: ''); // TODO: investigate; why can a permalink be empty? maybe we should discard those items
        $feedItem->setType($zendFeedItem->getType());
        $feedItem->setXml($zendFeedItem->saveXml());

        $feedItem->generateChecksum();

        return $feedItem;
    }

    /**
     * @param string|array $json
     * @return self
     */
    public static function fromJson($json): self
    {
        $jsonArr = is_string($json) ? json_decode($json, true) : $json;

        $feedItem = new self();
        $feedItem->setCategories(collect(Arr::get($jsonArr, 'categories')));
        $feedItem->setAuthors(collect(Arr::get($jsonArr, 'authors')));
        $feedItem->setTitle(Arr::get($jsonArr, 'title'));
        $feedItem->setCommentCount(Arr::get($jsonArr, 'commentCount'));
        $feedItem->setCommentFeedLink(Arr::get($jsonArr, 'commentFeedLink'));
        $feedItem->setCommentLink(Arr::get($jsonArr, 'commentLink'));
        $feedItem->setContent(Arr::get($jsonArr, 'content'));
        $feedItem->setCreatedAt(Carbon::parse(Arr::get($jsonArr, 'createdAt')));
        $feedItem->setUpdatedAt(Carbon::parse(Arr::get($jsonArr, 'updatedAt')));
        $feedItem->setDescription(Arr::get($jsonArr, 'description'));
        $feedItem->setEnclosureUrl(Arr::get($jsonArr, 'enclosureUrl'));
        $feedItem->setImageUrls(collect(Arr::get($jsonArr, 'imageUrls')));
        $feedItem->setEncoding(Arr::get($jsonArr, 'encoding'));
        $feedItem->setId(Arr::get($jsonArr, 'id'));
        $feedItem->setLinks(collect(Arr::get($jsonArr, 'links')));
        $feedItem->setPermalink(Arr::get($jsonArr, 'permalink'));
        $feedItem->setType(Arr::get($jsonArr, 'type'));
        $feedItem->setXml(Arr::get($jsonArr, 'xml'));

        $feedItem->generateChecksum();

        return $feedItem;
    }

    /**
     * @throws JsonException
     */
    public function toJson(array $fields = []): string
    {
        try {
            $class = new ReflectionClass(self::class);
            $methods = count($fields) > 0
                ? collect($fields)->map(fn(string $field) => $class->getMethod(Str::of($field)->ucfirst()->prepend('get')->toString()))
                : collect($class->getMethods(ReflectionMethod::IS_PUBLIC))->filter(fn(ReflectionMethod $method) => Str::startsWith($method->getName(), 'get'));

            return $methods->mapWithKeys(function (ReflectionMethod $method) {
                return [Str::of($method->getName())->substr(3)->lcfirst()->toString() => $method->invoke($this)];
            })->toJson();
        } catch (ReflectionException $e) {
            throw new JsonException('Cannot convert the given feed item to a JSON string.');
        }
    }

    /**
     * Calculates and returns the similarity between the given and another feed item as a percentage between 0 and 100.
     *
     * @throws JsonException
     */
    public function compareTo(FeedItem $otherFeedItem): float
    {
        if ($this->getChecksum() === $otherFeedItem->getChecksum()) {
            return 100.0;
        }

        similar_text($this->toJson(self::COMPARISON_FIELDS), $otherFeedItem->toJson(self::COMPARISON_FIELDS), $percent);

        return $percent;
    }

    /**
     * Calculates if the given feed item is similar to another feed item using a minimum percentage
     *
     * @throws JsonException
     */
    public function isSimilarTo(float $minimumPercentage, FeedItem $otherFeedItem): bool
    {
        return $this->compareTo($otherFeedItem) >= $minimumPercentage;
    }

    /**
     * Generate and set the checksum.
     * Should be called after manually manipulating a feed item.
     *
     * @throws ReflectionException
     */
    public function generateChecksum(): void
    {
        $this->setChecksum(HeraRssCrawler::generateChecksumForFeedItem($this));
    }
}
