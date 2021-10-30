<?php

namespace Kaishiyoku\HeraRssCrawler\Models\Feedly;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Kaishiyoku\HeraRssCrawler\Models\DeserializableModel;

class Result implements DeserializableModel
{
    /**
     * the feed name.
     */
    private string $title;

    /**
     * the website associated with this feed.
     */
    private ?string $website = null;

    /**
     * the unique, immutable id of this feed
     */
    private string $feedId;

    /**
     * the timestamp, in ms, of the last article received for this feed. This value is useful to find “dormant” feeds
     * (feeds that haven’t updated in over 3 months
     */
    private ?Carbon $lastUpdated = null;

    /**
     * the average number of articles published weekly. This number is updated every few days.
     */
    private float $velocity;

    /**
     * number of feedly cloud subscribers who have this feed in their subscription list.
     */
    private int $subscribers;

    private bool $curated;

    /**
     * if true, this feed is featured (recommended) for the topic or search query
     */
    private bool $featured;

    /**
     * the auto-detected type of entries this feed publishes. Values include “article” (most common),
     * “longform” (for longer article), “videos” (for YouTube, Vimeo and other video-centric feeds),
     * and “audio” (for podcast feeds etc).
     */
    private ?string $contentType = null;

    /**
     * this field is a combination of the language reported by the RSS feed, and the language automatically
     * detected from the feed’s content. It might not be accurate, as many feeds misreport it.
     */
    private ?string $language = null;

    /**
     * the feed description.
     */
    private ?string $description = null;

    /**
     * a small (square) icon URL
     */
    private ?string $iconUrl = null;

    /**
     * a larger (square) icon URL
     */
    private ?string $visualUrl = null;

    /**
     * a large (rectangular) background image
     */
    private ?string $coverUrl = null;

    /**
     * a small (square) icon URL with transparency
     */
    private ?string $logo = null;

    private bool $partial;

    /**
     * the background cover color
     */
    private ?string $coverColor = null;

    /**
     * @var Collection<string>
     */
    private $deliciousTags;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    public function getFeedId(): string
    {
        return $this->feedId;
    }

    public function setFeedId(string $feedId): void
    {
        $this->feedId = $feedId;
    }

    public function getLastUpdated(): ?Carbon
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(?Carbon $lastUpdated): void
    {
        $this->lastUpdated = $lastUpdated;
    }

    public function getVelocity(): float
    {
        return $this->velocity;
    }

    public function setVelocity(float $velocity): void
    {
        $this->velocity = $velocity;
    }

    public function getSubscribers(): int
    {
        return $this->subscribers;
    }

    public function setSubscribers(int $subscribers): void
    {
        $this->subscribers = $subscribers;
    }

    public function isCurated(): bool
    {
        return $this->curated;
    }

    public function setCurated(bool $curated): void
    {
        $this->curated = $curated;
    }

    public function getFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): void
    {
        $this->featured = $featured;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(?string $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getIconUrl(): ?string
    {
        return $this->iconUrl;
    }

    public function setIconUrl(?string $iconUrl): void
    {
        $this->iconUrl = $iconUrl;
    }

    public function getVisualUrl(): ?string
    {
        return $this->visualUrl;
    }

    public function setVisualUrl(?string $visualUrl): void
    {
        $this->visualUrl = $visualUrl;
    }

    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }

    public function setCoverUrl(?string $coverUrl): void
    {
        $this->coverUrl = $coverUrl;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    public function isPartial(): bool
    {
        return $this->partial;
    }

    public function setPartial(bool $partial): void
    {
        $this->partial = $partial;
    }

    public function getCoverColor(): ?string
    {
        return $this->coverColor;
    }

    public function setCoverColor(?string $coverColor): void
    {
        $this->coverColor = $coverColor;
    }

    /**
     * @return Collection<string>
     */
    public function getDeliciousTags(): Collection
    {
        return $this->deliciousTags;
    }

    /**
     * @param Collection<string> $deliciousTags
     */
    public function setDeliciousTags(Collection $deliciousTags): void
    {
        $this->deliciousTags = $deliciousTags;
    }

    public function getFeedUrl(): string
    {
        if (Str::start($this->getFeedId(), 'feed/')) {
            return Str::replaceFirst('feed/', '', $this->getFeedId());
        }

        return $this->getFeedId();
    }

    /**
     * @param mixed $json
     * @return Result
     */
    public static function fromJson($json): Result
    {
        $result = new self();
        $result->setFeedId($json['feedId']);
        $result->setSubscribers($json['subscribers']);
        $result->setTitle($json['title']);
        $result->setDescription(Arr::get($json, 'description'));
        $result->setWebsite($json['website']);
        $result->setLastUpdated(Carbon::parse($json['lastUpdated']));
        $result->setVelocity($json['velocity']);
        $result->setLanguage(Arr::get($json, 'language'));
        $result->setFeatured(Arr::get($json, 'featured', false));
        $result->setIconUrl(Arr::get($json, 'iconUrl'));
        $result->setVisualUrl(Arr::get($json, 'visualUrl'));
        $result->setCoverUrl(Arr::get($json, 'coverUrl'));
        $result->setLogo(Arr::get($json, 'logo'));
        $result->setContentType(Arr::get($json, 'contentType'));
        $result->setCoverColor(Arr::get($json, 'coverColor'));

        $result->setCurated(Arr::get($json, 'curated', false));
        $result->setDeliciousTags(collect(Arr::get($json, 'deliciousTags')));
        $result->setPartial(Arr::get($json, 'partial', false));

        return $result;
    }
}
