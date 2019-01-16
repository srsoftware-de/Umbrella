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
admin_session,token = getSession('admin','admin','task')
r=admin_session.get('http://localhost/task')

# project/db should be created upon first call
expect(os.path.isdir("../db"))

db = sqlite3.connect('../db/tasks.db')
cursor = db.cursor()

# check all required tables exist
tables = cursor.execute("SELECT name FROM sqlite_master WHERE type='table';").fetchall()
expect(('tasks',) in tables)
expect(('tasks_users',) in tables)
expect(('task_dependencies',) in tables)

print('done')