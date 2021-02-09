# -*- coding: utf-8 -*-

# Define your item pipelines here
#
# Don't forget to add your pipeline to the ITEM_PIPELINES setting
# See: https://docs.scrapy.org/en/latest/topics/item-pipeline.html


class DomainsPipeline(object):
    def process_item(self, item, spider):
    	query="""INSERT INTO domains (url_from,url_to,host_url,domain,sub_domain,tld) VALUES (%s, %s, %s, %s, %s, %s)"""
        params=(item['url_from'], item['url_to'], item['host_url'], item['domain'], item['sub_domain'], item['tld'])
        spider.cursor.insert(query,params)
        return item
