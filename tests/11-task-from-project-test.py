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
r = requests.get('http://localhost/task/from_project',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2Ffrom_project')

# login
admin_session,token = getSession('admin','admin','task')
user_session,token = getSession('user2','test-passwd','task');

# project id: none | non-existing | unaccessible | valid

# project_id: absent
r = admin_session.get('http://localhost/task/from_project',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = admin_session.get('http://localhost/task/')
expectError(r,'Keine Projekt-ID angegeben!')

# project_id: non-existig
r = admin_session.get('http://localhost/task/from_project/9999',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = admin_session.get('http://localhost/task/')
expectError(r,'Sie sind nicht berechtigt, auf dieses Projekt zuzugreifen!')

# project_id: non-accessible
r = user_session.get('http://localhost/task/from_project/'+str(ADMIN_PROJECT),allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = user_session.get('http://localhost/task/')
expectError(r,'Sie sind nicht berechtigt, auf dieses Projekt zuzugreifen!')

# project_id valid, target_project none: show form
r = admin_session.get('http://localhost/task/from_project/'+str(ADMIN_PROJECT),allow_redirects=False)
expect(r,'<form method="POST">')
expect(r,'<legend>Projekt "admin-project" in Aufgabe umwandeln</legend>')
expect(r,'<select name="target_project">')
expect(r,'<option value="">== Ziel-Projekt ==</option>')
expect(r,'<option value="'+str(COMMON_PROJECT)+'">common-project</option>')

# target_project: none | empty | non-existing | non-accessible | self | valid

# target_project empty: show form
r = admin_session.post('http://localhost/task/from_project/'+str(ADMIN_PROJECT),allow_redirects=False,data={'target_project':''})
expect(r,'<form method="POST">')

# target_project non-existing: error
r = admin_session.post('http://localhost/task/from_project/'+str(ADMIN_PROJECT),allow_redirects=False,data={'target_project':9999})
expectRedirect(r,'http://localhost/project/'+str(ADMIN_PROJECT)+'/view')

r = admin_session.get('http://localhost/project/'+str(ADMIN_PROJECT)+'/view')
expectError(r,'Sie sind nicht berechtigt, auf dieses Projekt zuzugreifen!')

# target_project non-accessible: error
r = user_session.post('http://localhost/task/from_project/'+str(USER2_PROJECT),allow_redirects=False,data={'target_project':ADMIN_PROJECT})
expectRedirect(r,'http://localhost/project/'+str(USER2_PROJECT)+'/view')

r = user_session.get('http://localhost/project/'+str(USER2_PROJECT)+'/view')
expectError(r,'Sie sind nicht berechtigt, auf dieses Projekt zuzugreifen!')

# target_project = source project: error
r = admin_session.post('http://localhost/task/from_project/'+str(ADMIN_PROJECT),allow_redirects=False,data={'target_project':ADMIN_PROJECT})
expectRedirect(r,'http://localhost/project/'+str(ADMIN_PROJECT)+'/view')

r = admin_session.get('http://localhost/project/'+str(ADMIN_PROJECT)+'/view')
expectError(r,'Sie können das Projekt nicht in sich selbst einfügen!')

# target_project valid: forward to created task
r = admin_session.post('http://localhost/task/from_project/'+str(ADMIN_PROJECT),allow_redirects=False,data={'target_project':COMMON_PROJECT})
expectRedirect(r,'http://localhost/task/3/view')

r = admin_session.get('http://localhost/task/3/view')
expect(r,'<title>admin-project - Umbrella</title>')
expect(r,'<th>Aufgabe</th>')
expect(r,'<h1>admin-project</h1>')
expect(r,'<a href="http://localhost/project/3/view">common-project</a>')
expect(r,'<td class="description"><p>owned by admin</p></td>')

print 'done.'