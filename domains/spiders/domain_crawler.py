# -*- coding: utf-8 -*-
import scrapy
from scrapy.linkextractors import LinkExtractor
from scrapy.spiders import Rule, CrawlSpider
from urllib.parse import urlparse
from domains.items import DomainsItem


class DomainCrawlerSpider(scrapy.Spider):
	name = 'domain_crawler'
	allowed_domains = ['oxosolutions.com','darlic.com','amardeepsinghgill.com','sgssandhu.com','aioneframework.com','wikipedia.org']
	start_urls = ['https://oxosolutions.com/project/amardeepsinghgill/']

    # def parse(self, response):
    # 	for quote in response.css('a'):
    # 		yield quote.css('a::attr("href")').get()

    # This spider has one rule: extract all (unique and canonicalized) links, follow them and parse them using the parse_items method
	rules = [
		Rule(
			LinkExtractor(
				canonicalize=True,
				unique=True,
				# tags=('a', 'img', 'link', 'script', 'meta', 'iframe'),
				# attrs=('href', 'src', 'contant')
			),
			follow=True,
			callback="parse"
		)
	]
    # Method which starts the requests by visiting all URLs specified in start_urls
	#def start_requests(self):
	#	for url in self.start_urls:
	#		yield scrapy.Request(url, callback=self.parse, dont_filter=True)

    # Method for parsing items
	def parse(self, response):
        # The list of items that are found on the particular page
		items = []
        # Only extract canonicalized and unique links (with respect to the current page)
		links = LinkExtractor(canonicalize=True, unique=True).extract_links(response)
        # Now go through all the found links
		for link in links:
            # Check whether the domain of the URL of the link is allowed; so whether it is in one of the allowed domains
			is_allowed = True
			#for allowed_domain in self.allowed_domains:
			#	if allowed_domain in link.url:
			#		is_allowed = True
            # If it is allowed, create a new item and add it to the list of found items
			if is_allowed:
				item = DomainsItem()
				item['url_from'] = response.url
				item['url_to'] = link.url
				parsed_uri = urlparse(link.url)
				item['domain'] = '{uri.netloc}'.format(uri=parsed_uri)
				items.append(item)
				# sub_items = scrapy.Request(link.url, callback=self.parse)
				# items.append(sub_items)
        # Return all the found items
		return items        