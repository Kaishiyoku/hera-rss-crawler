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
     * @var string
     */
    private $title;

    /**
     * the website associated with this feed.
     * @var string|null
     */
    private $website;

    /**
     * the unique, immutable id of this feed
     * @var string
     */
    private $feedId;

    /**
     * the timestamp, in ms, of the last article received for this feed. This value is useful to find “dormant” feeds
     * (feeds that haven’t updated in over 3 months
     * @var Carbon|null
     */
    private $lastUpdated;

    /**
     * the average number of articles published weekly. This number is updated every few days.
     * @var float
     */
    private $velocity;

    /**
     * number of feedly cloud subscribers who have this feed in their subscription list.
     * @var int
     */
    private $subscribers;

    /**
     * @var bool
     */
    private $curated;

    /**
     * if true, this feed is featured (recommended) for the topic or search query
     * @var bool
     */
    private $featured;

    /**
     * the auto-detected type of entries this feed publishes. Values include “article” (most common),
     * “longform” (for longer article), “videos” (for YouTube, Vimeo and other video-centric feeds),
     * and “audio” (for podcast feeds etc).
     * @var string|null
     */
    private $contentType;

    /**
     * this field is a combination of the language reported by the RSS feed, and the language automatically
     * detected from the feed’s content. It might not be accurate, as many feeds misreport it.
     * @var string|null
     */
    private $language;

    /**
     * the feed description.
     * @var string|null
     */
    private $description;

    /**
     * a small (square) icon URL
     * @var string|null
     */
    private $iconUrl;

    /**
     * a larger (square) icon URL
     * @var string|null
     */
    private $visualUrl;

    /**
     * a large (rectangular) background image
     * @var string|null
     */
    private $coverUrl;

    /**
     * a small (square) icon URL with transparency
     * @var string|null
     */
    private $logo;

    /**
     * @var bool
     */
    private $partial;

    /**
     * the background cover color
     * @var string|null
     */
    private $coverColor;

    /**
     * @var Collection<string>
     */
    private $deliciousTags;

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
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * @param string|null $website
     */
    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    /**
     * @return string
     */
    public function getFeedId(): string
    {
        return $this->feedId;
    }

    /**
     * @param string $feedId
     */
    public function setFeedId(string $feedId): void
    {
        $this->feedId = $feedId;
    }

    /**
     * @return Carbon|null
     */
    public function getLastUpdated(): ?Carbon
    {
        return $this->lastUpdated;
    }

    /**
     * @param Carbon|null $lastUpdated
     */
    public function setLastUpdated(?Carbon $lastUpdated): void
    {
        $this->lastUpdated = $lastUpdated;
    }

    /**
     * @return float
     */
    public function getVelocity(): float
    {
        return $this->velocity;
    }

    /**
     * @param float $velocity
     */
    public function setVelocity(float $velocity): void
    {
        $this->velocity = $velocity;
    }

    /**
     * @return int
     */
    public function getSubscribers(): int
    {
        return $this->subscribers;
    }

    /**
     * @param int $subscribers
     */
    public function setSubscribers(int $subscribers): void
    {
        $this->subscribers = $subscribers;
    }

    /**
     * @return bool
     */
    public function isCurated(): bool
    {
        return $this->curated;
    }

    /**
     * @param bool $curated
     */
    public function setCurated(bool $curated): void
    {
        $this->curated = $curated;
    }

    /**
     * @return bool
     */
    public function getFeatured(): bool
    {
        return $this->featured;
    }

    /**
     * @param bool $featured
     */
    public function setFeatured(bool $featured): void
    {
        $this->featured = $featured;
    }

    /**
     * @return string|null
     */
    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * @param string|null $contentType
     */
    public function setContentType(?string $contentType): void
    {
        $this->contentType = $contentType;
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
    public function getIconUrl(): ?string
    {
        return $this->iconUrl;
    }

    /**
     * @param string|null $iconUrl
     */
    public function setIconUrl(?string $iconUrl): void
    {
        $this->iconUrl = $iconUrl;
    }

    /**
     * @return string|null
     */
    public function getVisualUrl(): ?string
    {
        return $this->visualUrl;
    }

    /**
     * @param string|null $visualUrl
     */
    public function setVisualUrl(?string $visualUrl): void
    {
        $this->visualUrl = $visualUrl;
    }

    /**
     * @return string|null
     */
    public function getCoverUrl(): ?string
    {
        return $this->coverUrl;
    }

    /**
     * @param string|null $coverUrl
     */
    public function setCoverUrl(?string $coverUrl): void
    {
        $this->coverUrl = $coverUrl;
    }

    /**
     * @return string|null
     */
    public function getLogo(): ?string
    {
        return $this->logo;
    }

    /**
     * @param string|null $logo
     */
    public function setLogo(?string $logo): void
    {
        $this->logo = $logo;
    }

    /**
     * @return bool
     */
    public function isPartial(): bool
    {
        return $this->partial;
    }

    /**
     * @param bool $partial
     */
    public function setPartial(bool $partial): void
    {
        $this->partial = $partial;
    }

    /**
     * @return string|null
     */
    public function getCoverColor(): ?string
    {
        return $this->coverColor;
    }

    /**
     * @param string|null $coverColor
     */
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

    /**
     * @return string
     */
    public function getFeedUrl(): string
    {
        if (Str::start($this->getFeedId(), 'feed/')) {
            return Str::replaceFirst('feed/', '', $this->getFeedId());
        }

        return $this->getFeedId();
    }

    /**
     * @param array $json
     * @return Result
     */
    public static function fromJson(array $json): Result
    {
        $result = new Result();
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
