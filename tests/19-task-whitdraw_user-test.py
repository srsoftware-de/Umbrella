#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys, datetime
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
PENDING=40
CANCELED=100

db = sqlite3.connect('../db/tasks.db')
cursor = db.cursor()

# reset edits of previous tests
def resetDb():
    global cursor

    cursor.execute('DELETE FROM tasks')
    cursor.execute('DELETE FROM task_dependencies')
    cursor.execute('DELETE FROM tasks_users')
    db.commit();
    
    cursor.execute('INSERT INTO tasks (id, project_id, parent_task_id, name, description, status, est_time, start_date, due_date) VALUES (1, 3, NULL, "task one", "task without name", 10, 2.5, "2019-01-14", "2019-02-01"), (2, 3, 1, "subtask one", "first subtask", 10, 2.5, "2019-01-14", "2019-02-01"), (3, 2, NULL, "project-2-task", "belongs to project 2", 10, 3.5, "2019-01-27", "2019-02-05")')
    cursor.execute('INSERT INTO tasks_users (task_id, user_id, permissions) VALUES (1,1,1), (2,1,1), (2,2,4), (3,2,1)')
    db.commit();

resetDb()

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/task/1/withdraw_user',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2F1%2Fwithdraw_user%3Fid%3D1')

# login
admin_session,token = getSession('admin','admin','task')
user_session,token = getSession('user2','test-passwd','task');

# project id: none | empty | non-existing | unaccessible | valid
# user id: none | empty | non-existing | valid

# project_id: absent
r = admin_session.get('http://localhost/task/withdraw_user',allow_redirects=False)
expect(r.status_code == 500)

# prject_id empty
r = admin_session.post('http://localhost/task/withdraw_user',allow_redirects=False,data={'project_id':'','user_id':USER2})
expect(r.status_code == 500)

# project_id: non-existing
r = admin_session.post('http://localhost/task/withdraw_user',allow_redirects=False,data={'project_id':9999,'user_id':USER2})
expect(r.status_code == 500)

# project_id: unaccessible
r = admin_session.post('http://localhost/task/withdraw_user',allow_redirects=False,data={'project_id':USER2_PROJECT,'user_id':USER2})
expect(r.status_code == 500)

# project_id: valid
# user_id missing
r = admin_session.post('http://localhost/task/withdraw_user',allow_redirects=False,data={'project_id':COMMON_PROJECT})
expect(r.status_code == 500)

# project_id: valid
# user_id empty
r = admin_session.post('http://localhost/task/withdraw_user',allow_redirects=False,data={'project_id':COMMON_PROJECT,'user_id':''})
expect(r.status_code == 500)

# project_id: valid
# user_id non-existing
r = admin_session.post('http://localhost/task/withdraw_user',allow_redirects=False,data={'project_id':COMMON_PROJECT,'user_id':9999})
expect(r.status_code == 200)
expect(r.text == '')

# task-user-assignment should not be touched
rows = cursor.execute('SELECT * FROM tasks_users')
expect((1,1,1) in rows)
expect((2,1,1) in rows)
expect((2,2,4) in rows)
expect((3,2,1) in rows)

# project_id: valid
# user_id valid
r = admin_session.post('http://localhost/task/withdraw_user',allow_redirects=False,data={'project_id':COMMON_PROJECT,'user_id':USER2})
expect(r.status_code == 200)
expect(r.text == '')

# task-user-assignment should not be touched
rows = cursor.execute('SELECT * FROM tasks_users')
expect((1,1,1) in rows)
expect((2,1,1) in rows)
expect((3,2,1) in rows)

print 'done.'