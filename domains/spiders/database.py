# class DatabaseConnect(scrapy.Spider):
#     name = "databaseconnect"

#     host = 'localhost'
#     user = 'root'
#     password = 'darlic'
#     db = 'dev_temp'

#     def __init__(self):
#         self.connection = MySQLdb.connect(self.host, self.user, self.password, self.db,use_unicode=True, charset="utf8")
#         self.cursor = self.connection.cursor()

#     def insert(self, query,params):
#         try:
#             self.cursor.execute(query,params)
#             self.connection.commit()
#         except Exception as ex:
#             self.connection.rollback()


#     def __del__(self):
#         self.connection.close()


import MySQLdb


class Database:

    host = 'localhost'
    user = 'root'
    password = 'darlic'
    db = 'dev_temp'

    def __init__(self):
        self.connection = MySQLdb.connect(self.host, self.user, self.password, self.db,use_unicode=True, charset="utf8")
        self.cursor = self.connection.cursor()

    def insert(self, query,params):
        try:
            self.cursor.execute(query,params)
            self.connection.commit()
        except Exception as ex:
            self.connection.rollback()


    def __del__(self):
        self.connection.close()


 

