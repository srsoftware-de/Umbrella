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

# reset edits of previous tests
cursor.execute('DELETE FROM tasks')
cursor.execute('DELETE FROM task_dependencies')
cursor.execute('DELETE FROM tasks_users')
db.commit();

cursor.execute('INSERT INTO tasks (id, project_id, parent_task_id, name, description, status, est_time, start_date, due_date) VALUES (1, 3, NULL, "task one", "task without name", 10, 2.5, "2019-01-14", "2019-02-01"), (2, 3, 1, "subtask one", "first subtask", 10, 2.5, "2019-01-14", "2019-02-01")')
cursor.execute('INSERT INTO tasks_users (task_id, user_id, permissions) VALUES (1,1,1), (2,1,1), (2,2,4)')
db.commit();

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/task/1/drop_user',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2F1%2Fdrop_user%3Fid%3D1')

# login
admin_session,token = getSession('admin','admin','task')
user_session,token = getSession('user2','test-passwd','task');

# task id: none | non-existing | unaccessible | valid

# task_id: absent
r = admin_session.get('http://localhost/task/drop_user',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = admin_session.get('http://localhost/task/')
expectError(r,'Keine Aufgaben-ID angegeben!')

# task_id: non-existig
r = admin_session.get('http://localhost/task/9999/drop_user',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = admin_session.get('http://localhost/task/')
expectError(r,'Sie sind nicht berechtigt, auf diese Aufgabe zuzugreifen')

# task_id: non-accessible
r = user_session.get('http://localhost/task/1/drop_user',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = user_session.get('http://localhost/task/')
expectError(r,'Sie sind nicht berechtigt, auf diese Aufgabe zuzugreifen')

# valid task id, but no write access
r = user_session.get('http://localhost/task/2/drop_user',allow_redirects=False)
expectRedirect(r,'http://localhost/task/2/view');

r = user_session.get('http://localhost/task/2/view')
expectError(r,'Sie haben keine Berechtigung die Aufgabe zu ändern!')

cursor.execute('UPDATE tasks_users SET permissions ='+str(WRITE)+' WHERE task_id=2 AND user_id='+str(USER2));
db.commit();

# valid task id. user_id: none
r = user_session.get('http://localhost/task/2/drop_user',allow_redirects=False)
expectRedirect(r,'http://localhost/task/2/view')

r = user_session.get('http://localhost/task/2/view')
expectError(r,'Keine Benutzer-ID angegeben!')

# user_id: invalid: shoud not affect anything
r = user_session.get('http://localhost/task/2/drop_user?uid=test',allow_redirects=False)
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks_users')
expect((1,ADMIN,OWNER) in rows)
expect((2,ADMIN,OWNER) in rows)
expect((2,USER2,WRITE) in rows)

# user_id: valid: should be removed
r = user_session.get('http://localhost/task/2/drop_user?uid=1',allow_redirects=False)
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks_users')
expect((1,ADMIN,OWNER) in rows)
expect((2,USER2,WRITE) in rows)
expect((2,ADMIN,OWNER) not in rows)

print 'done.'