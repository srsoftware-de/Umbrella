#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *
import shutil

shutil.rmtree('../db', ignore_errors=True)

# project/db should not exists prior to first call to user module
expect(not os.path.isdir('../db'))

# login with access to project module should create database
admin_session,token = getSession('admin','admin','project')
admin_session.get('http://localhost/project')

# project/db should be created upon first call
expect(os.path.isdir("../db"))

db = sqlite3.connect('../db/projects.db')
cursor = db.cursor()

# check all required tables exist
tables = cursor.execute("SELECT name FROM sqlite_master WHERE type='table';").fetchall()
expect(('projects',) in tables)
expect(('projects_users',) in tables)

print('done')