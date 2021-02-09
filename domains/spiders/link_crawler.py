import re
from urllib.parse import urlparse

import scrapy
from scrapy import signals


class LinkCheckerSpider(scrapy.Spider):
    name = 'link_crawler'
    # Set the HTTP error codes that should be handled
    handle_httpstatus_list = [404]
    valid_url = []
    invalid_url = []
    # Set the maximum depth
    maxdepth = 6;
    domain = ''

    def __init__(self, url='https://oxosolutions.com', *args, **kwargs):
        super(LinkCheckerSpider, self).__init__(*args, **kwargs)
        self.start_urls = [url]

    @classmethod
    def from_crawler(cls, crawler, *args, **kwargs):
        spider = super(LinkCheckerSpider, cls).from_crawler(crawler, *args, **kwargs)
        # Register the spider_closed handler on spider_closed signal
        crawler.signals.connect(spider.spider_closed, signals.spider_closed)
        return spider

    def spider_closed(self):
        """ Handler for spider_closed signal."""
        print('----------')
        print('There are', len(self.valid_url), 'working links and',
              len(self.valid_url), 'broken links.', sep=' ')
        if len(self.valid_url) > 0:
            print('Broken links are:')
            for valid in self.valid_url:
                print(valid)
        print('----------')

    def parse(self, response):
        """ Main method that parse downloaded pages. """
        # Set defaults for the first page that won't have any meta information
        from_url = ''
        from_text = ''
        depth = 0;
        # Extract the meta information from the response, if any
        if 'from' in response.meta: from_url = response.meta['from']
        if 'text' in response.meta: from_text = response.meta['text']
        if 'depth' in response.meta: depth = response.meta['depth']

        # If first response, update domain (to manage redirect cases)
        if len(self.domain) == 0:
            parsed_uri = urlparse(response.url)
            self.domain = parsed_uri.netloc

        # 404 error, populate the broken links array
        if response.status == 404:
            self.invalid_url.append({'url': response.url,
                                     'from': from_url,
                                     'text': from_text})
        else:
            # Populate the working links array
            self.valid_url.append({'url': response.url,
                                   'from': from_url,
                                   'text': from_text})
            # Extract domain of current page
            parsed_uri = urlparse(response.url)
            # Parse new links only:
            #   - if current page is not an extra domain
            #   - and depth is below maximum depth
            if parsed_uri.netloc == self.domain and depth < self.maxdepth:
                # Get all the <a> tags
                a_selectors = response.xpath("//a")
                # Loop on each tag
                for selector in a_selectors:
                    # Extract the link text
                    text = selector.xpath('text()').extract_first()
                    # Extract the link href
                    link = selector.xpath('@href').extract_first()
                    # Create a new Request object
                    request = response.follow(link, callback=self.parse)
                    request.meta['from'] = response.url;
                    request.meta['text'] = text
                    # Return it thanks to a generator
                    yield request