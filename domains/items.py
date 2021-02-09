# -*- coding: utf-8 -*-

# Define here the models for your scraped items
#
# See documentation in:
# https://docs.scrapy.org/en/latest/topics/items.html

import scrapy


class DomainsItem(scrapy.Item):
    # define the fields for your item here like:
    # name = scrapy.Field()
    
    # The source URL
    url_from = scrapy.Field()
    # The destination URL
    url_to = scrapy.Field()
    # Extracted Domain
    host_url = scrapy.Field()
    domain = scrapy.Field()
    sub_domain = scrapy.Field()
    tld = scrapy.Field()
