About
=====

This project tries to make fetching and parsing RSS feeds easier. With Hera RSS you can discover, fetch and parse RSS feeds.


Installation
============
1. simply run `composer require kaishiyoku/hera-rss-crawler`
2. create a new crawler instance using `$heraRssCrawler = new HeraRssCrawler()`
3. discover a feed, for example `$feedUrls = $heraRssCrawler->discoverFeedUrls('https://laravel-news.com/')`
4. pick the feed you like to use; if there were multiple feeds discovered pick one
5. fetch the feed: `$feed = $heraRssCrawler->parseFeed($feedUrls->get(0))`
6. fetch the articles: `$feedItems = $feed->getFeedItems()`

Breaking Changes
================
Version 5.x introduced the following breaking changes:

* dropped support for PHP 7.4

Version 4.x introduced the following breaking changes:

* dropped support for Laravel 8

Version 3.x introduced the following breaking changes:

* FeedItem-method `jsonSerialize` has been renamed to `toJson` and doesn't return `null` anymore but throws a `JsonException` if the serialized JSON is invalid.

Available crawler options
=========================

```php
setRetryCount(int $retryCount): void
```

Determines how many retries parsing or discovering feeds will be made when an exception occurs, e.g. if the feed was unreachable.


```php
setLogger(LoggerInterface $logger): void
```

Set your own logger instance, e.g. a simple file logger.


```php
setUrlReplacementMap(array $urlReplacementMap): void
```

Useful for websites which redirect to another subdomain when visiting the site, e.g. for Reddit.


Available crawler methods
=========================

```php
parseFeed(string $url): ?Feed
```

Simply fetch and parse the feed of a given feed url. If no consumable RSS feed is being found `null` is being returned.


```php
discoverAndParseFeeds(string $url): Collection
```

Discover feeds from a website url and return all parsed feeds in a collection.


```php
discoverFeedUrls(string $url): Collection
```

Discover feeds from a website url and return all found feed urls in a collection. There are multiple ways the crawler tries to discover feeds. The order is as follows:

1. discover feed urls by content type  
if the given url is already a valid feed return this url
2. discover feed urls by HTML head elements  
find all feed urls inside a HTML document
3. discover feed urls by HTML anchor elements  
get all anchor elements of a HTML element and return the urls of those which include `rss` in its urls
4. discover feed urls by Feedly  
fetch feed urls using the Feedly API


```php
discoverFavicon(string $url): ?string
```

Fetch the favicon of the feed's website. If none is found then `null` is being returned.


```php
checkIfConsumableFeed(string $url): bool
```

Check if a given url is a consumable RSS feed.


Contribution
============

Found any issues or have an idea to improve the crawler? Feel free to open an issue or submit a pull request.


Plans for the future
====================

- [ ] add a Laravel facade


Author
======

Email: dev@andreas-wiedel.de  
Website: https://andreas-wiedel.de  
