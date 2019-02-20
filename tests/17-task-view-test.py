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
def resetDb():
    global cursor
    cursor.execute('DELETE FROM tasks')
    cursor.execute('DELETE FROM task_dependencies')
    cursor.execute('DELETE FROM tasks_users')
    db.commit();
    
    cursor.execute('INSERT INTO tasks (id, project_id, parent_task_id, name, description, status, est_time, start_date, due_date) VALUES (1, 3, NULL, "task one", "task without name", 10, 2.5, "2019-01-14", "2019-02-01"), (2, 3, 1, "subtask one", "first subtask", 10, 2.5, "2019-01-14", "2019-02-01"), (3, 1, NULL, "project-1-task", "belongs to project 1", 10, 3.5, "2019-01-27", "2019-02-05")')
    cursor.execute('INSERT INTO tasks_users (task_id, user_id, permissions) VALUES (1,1,1), (2,1,1), (2,2,4), (3,1,1)')
    db.commit();
    
resetDb()

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/task/1/view',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2F1%2Fview%3Fid%3D1')

# login
admin_session,token = getSession('admin','admin','task')
user_session,token = getSession('user2','test-passwd','task');

# insert tasks for tests
r = admin_session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'subtask one','description':'first subtask','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})

    
# task id: none | non-existing | unaccessible | valid

# task_id: absent
r = admin_session.get('http://localhost/task/view',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = admin_session.get('http://localhost/task/')
expectError(r,'Keine Aufgaben-ID angegeben!')

# task_id: non-existig
r = admin_session.get('http://localhost/task/9999/view',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = admin_session.get('http://localhost/task/')
expectError(r,'Sie sind nicht berechtigt, auf diese Aufgabe zuzugreifen')

# task_id: non-accessible
r = user_session.get('http://localhost/task/1/view',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = user_session.get('http://localhost/task/')
expectError(r,'Sie sind nicht berechtigt, auf diese Aufgabe zuzugreifen')

# valid:
r = user_session.get('http://localhost/task/2/view',allow_redirects=False)
expect(r,'<body class="task 2 view">')
expect(r,'<h1>subtask one</h1>')
expect(r,'<a href="http://localhost/project/3/view">common-project</a>')
expect(r,'<td class="description"><p>first subtask</p></td>')
expect(r,'2.5 Stunden')
expect(r,'2019-01-14')
expect(r,'2019-02-01')
expect(r,'<a class="button" href="http://localhost/bookmark/ene/view">ene</a')
expect(r,'<a class="button" href="http://localhost/bookmark/mene/view">mene</a>')
expect(r,'<a class="button" href="http://localhost/bookmark/muh/view">muh</a>')

r = admin_session.get('http://localhost/task/3/view',allow_redirects=False)
expect(r,'<body class="task 3 view">')
expect(r,'<h1>project-1-task</h1>')
expect(r,'<a href="http://localhost/project/1/view">admin-project</a>')
expect(r,'<td class="description"><p>belongs to project 1</p></td>')
expect(r,'3.5 Stunden')
expect(r,'2019-01-27')
expect(r,'2019-02-05')

print 'done.'