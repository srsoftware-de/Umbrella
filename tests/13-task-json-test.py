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
r = requests.get('http://localhost/task/1/json',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2F1%2Fjson%3Fid%3D1')

# login
admin_session,token = getSession('admin','admin','task')
user_session,token = getSession('user2','test-passwd','task');

r = admin_session.get('http://localhost/project/3/open') # may have been closed before

# task id: none | non-existing | unaccessible | valid

# task_id: absent
r = admin_session.get('http://localhost/task/json',allow_redirects=False)
expect(r.text=='{"1":{"id":"1","project_id":"3","parent_task_id":null,"name":"task one","description":"task without name","status":"10","est_time":"2.5","start_date":"2019-01-14","due_date":"2019-02-01","show_closed":"0","no_index":"0"},"2":{"id":"2","project_id":"3","parent_task_id":"1","name":"subtask one","description":"first subtask","status":"10","est_time":"2.5","start_date":"2019-01-14","due_date":"2019-02-01","show_closed":"0","no_index":"0"},"3":{"id":"3","project_id":"2","parent_task_id":null,"name":"project-2-task","description":"belongs to project 2","status":"10","est_time":"2.5","start_date":"2019-01-14","due_date":"2019-02-01","show_closed":"0","no_index":"0"}}')

# task_id: non-existig
r = admin_session.get('http://localhost/task/9999/json',allow_redirects=False)
expect(r.text == 'null')

# task_id: non-accessible
r = user_session.get('http://localhost/task/1/json',allow_redirects=False)
expect(r.text == 'null')

# task_id: valid
r = admin_session.get('http://localhost/task/1/json',allow_redirects=False)
expect(r.text == '{"id":"1","project_id":"3","parent_task_id":null,"name":"task one","description":"task without name","status":"10","est_time":"2.5","start_date":"2019-01-14","due_date":"2019-02-01","show_closed":"0","no_index":"0"}')

r = admin_session.get('http://localhost/task/json?ids=1',allow_redirects=False)
expect(r.text == '{"id":"1","project_id":"3","parent_task_id":null,"name":"task one","description":"task without name","status":"10","est_time":"2.5","start_date":"2019-01-14","due_date":"2019-02-01","show_closed":"0","no_index":"0"}')

r = admin_session.post('http://localhost/task/json',allow_redirects=False,data={'ids[0]':1,'ids[1]':3})
expect(r.text=='{"1":{"id":"1","project_id":"3","parent_task_id":null,"name":"task one","description":"task without name","status":"10","est_time":"2.5","start_date":"2019-01-14","due_date":"2019-02-01","show_closed":"0","no_index":"0"},"3":{"id":"3","project_id":"2","parent_task_id":null,"name":"project-2-task","description":"belongs to project 2","status":"10","est_time":"2.5","start_date":"2019-01-14","due_date":"2019-02-01","show_closed":"0","no_index":"0"}}')

print 'done.'