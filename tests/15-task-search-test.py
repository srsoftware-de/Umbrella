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
CANCELED=100

db = sqlite3.connect('../db/tasks.db')
cursor = db.cursor()

# reset jsons of previous tests
def resetDb():
    global cursor
    cursor.execute('DELETE FROM tasks')
    cursor.execute('DELETE FROM task_dependencies')
    cursor.execute('DELETE FROM tasks_users')
    db.commit();
    
    cursor.execute('INSERT INTO tasks (id, project_id, parent_task_id, name, description, status, est_time, start_date, due_date) VALUES (1, 3, NULL, "task one", "task without name", 10, 2.5, "2019-01-14", "2019-02-01"), (2, 3, 1, "subtask one", "first subtask", 10, 2.5, "2019-01-14", "2019-02-01"), (3, 2, NULL, "project-2-task", "belongs to project 2", 10, 2.5, "2019-01-14", "2019-02-01")')
    cursor.execute('INSERT INTO tasks_users (task_id, user_id, permissions) VALUES (1,1,1), (2,1,1), (2,2,4), (3,1,1)')
    db.commit();
    
resetDb()

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/task/search',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2Fsearch')

# login
admin_session,token = getSession('admin','admin','task')
user_session,token = getSession('user2','test-passwd','task');

r = admin_session.get('http://localhost/project/3/open') # may have been closed before

# key: none | empty | valid

# key absent
r = admin_session.get('http://localhost/task/search',allow_redirects=False)
expect(r.text == '')

# key empty
r = admin_session.get('http://localhost/task/search?key=',allow_redirects=False)
expect(r.text == '')

# key valid
r = admin_session.get('http://localhost/task/search?key=task',allow_redirects=False)
expect(r,'<a href="http://localhost/task/1/view"><span class="hit">task</span> one</a>')
expect(r,'<a href="http://localhost/task/2/view">sub<span class="hit">task</span> one</a>')
expect(r,'<a href="http://localhost/task/3/view">project-2-<span class="hit">task</span></a>')

# key valid
r = admin_session.get('http://localhost/task/search?key=without',allow_redirects=False)
expect(r,'<a href="http://localhost/task/1/view">task one</a>')
expectNot(r,'subtask one')
expectNot(r,'project-2-task')

print 'done.'

