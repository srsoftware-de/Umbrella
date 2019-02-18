#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys, time
sys.path.append("/var/www/tests")
from test_routines import *

# Projects
ADMIN_PROJECT=1
USER2_PROJECT=2
COMMON_PROJECT=3

# Users
ADMIN=1
USER2=2

# Permissions
OWNER=1
WRITE=2
READ=4

# states:
OPEN=10
COMPLETE=60

db = sqlite3.connect('../db/tasks.db')
cursor = db.cursor()

# reset edits of previous tests
cursor.execute('DELETE FROM tasks')
cursor.execute('DELETE FROM task_dependencies')
cursor.execute('DELETE FROM tasks_users')
db.commit();

cursor.execute('INSERT INTO tasks (id, project_id, parent_task_id, name, description, status, est_time, start_date, due_date, no_index) VALUES (1, '+str(COMMON_PROJECT)+', NULL, "task one", "task without name", '+str(OPEN)+', 2.5, "2019-01-14", "2019-02-01", 0), (2, '+str(COMMON_PROJECT)+', 1, "subtask one", "first subtask", '+str(OPEN)+', 2.5, "2019-01-14", "2019-02-01", 0), (3, '+str(ADMIN_PROJECT)+', NULL, "task two", "task hidden from index", '+str(OPEN)+', 2.5, "2019-01-14", "2019-02-01", 1), (4, '+str(ADMIN_PROJECT)+', NULL, "task four", "completed task", '+str(COMPLETE)+', 2.5, "2019-01-14", "2019-02-01", 0)')
cursor.execute('INSERT INTO tasks_users (task_id, user_id, permissions) VALUES (1,1,4), (2,1,2), (2,2,1), (3,1,1), (4,1,1)')
db.commit();

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/task/',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2F')

# login
admin_session,token = getSession('admin','admin','task')

r = admin_session.get('http://localhost/task',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = admin_session.get('http://localhost/task/')
expect(r,'<table class="tasks list">')
# show sub-task
expect(r,'<tr class="project3">')
expect(r,'<a href="../project/3/view">common-project</a>')
expect(r,'<td class="open"><a href="2/view">subtask one</a></td>')
expect(r,'<a href="../task/1/view">task one</a>')
expect(r,'<td>2019-01-14</td>')
expect(r,'<td>2019-02-01</td>')
# do not show parent task
expectNot(r,'<td class="open"><a href="1/view">task one</a></td>')
# do not show hidden task
expectNot(r,'<td class="open"><a href="3/view">task two</a></td>')
# do not show completed task
expectNot(r,'<a href="4/view">task four</a></td>')

print 'done.'