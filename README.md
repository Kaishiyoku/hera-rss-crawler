About
=====

This project tries to make fetching and parsing RSS feeds easier. With Hera RSS you can discover, fetch and parse RSS feeds.


Installation
============
1. simply run `composer require kaishiyoku/hera-rss-crawler`
2. create a new crawler instance using `$heraRssCrawler = new HeraRssCrawler()`
3. discover a feed, for example `$feedUrls = $heraRssCrawler->discoverFeedUrls('https://laravel-news.com/')`
4. pick the feed you like to use; if there multiple feeds were discovered pick one
5. fetch the feed: `$feed = $heraRssCrawler->parseFeed($feedUrls->get(0))`
6. fetch the articles: `$feedItems = $feed->getFeedItems()`


Available crawler options
=========================

`setRetryCount(int $retryCount): void`

Determines how many retries parsing or discovering feeds will be made when an exception occurs, e.g. if the feed was unreachable.


`setLogger(LoggerInterface $logger): void`

Set your own logger instance, e.g. a simple file logger.


`setUrlReplacementMap(array $urlReplacementMap): void`

Useful for websites which redirect to another subdomain when visiting the site, e.g. for Reddit.


Available crawler methods
=========================

`parseFeed(string $url): ?Feed`

Simply fetch and parse the feed of a given feed url. If no consumable RSS feed is being found `null` is being returned.


`discoverAndParseFeeds(string $url): Collection`

Discover feeds from a website url and return all parsed feeds in a collection.


`discoverFeedUrls(string $url): Collection`

Discover feeds from a website url and return all found feed urls in a collection. There are multiple ways the crawler tries to discover feeds. The order is as follows:

1. discover feed urls by content type  
if the given url is already a valid feed return this url
2. discover feed urls by HTML head elements  
find all feed urls inside a HTML document
3. discover feed urls by HTML anchor elements  
get all anchor elements of a HTML element and return the urls of those which include `rss` in its urls
4. discover feed urls by Feedly  
fetch feed urls using the Feedly API


`discoverFavicon(string $Url): ?string`

Fetch the favicon of the feed's website. If none is found then `null` is being returned.


`checkIfConsumableFeed(string $url): bool`

Check if a given url is a consumable RSS feed.


Author
======

Email: dev@andreas-wiedel.de  
Website: https://andreas-wiedel.de  
