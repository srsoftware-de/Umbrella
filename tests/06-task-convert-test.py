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


db = sqlite3.connect('../db/tasks.db')
cursor = db.cursor()

# reset edits of previous tests
cursor.execute('DELETE FROM tasks WHERE id>1')
cursor.execute('DELETE FROM task_dependencies WHERE task_id>1')
cursor.execute('DELETE FROM tasks_users WHERE task_id>1')
cursor.execute('DELETE FROM tasks_users WHERE user_id>1')
cursor.execute('UPDATE tasks SET status='+str(OPEN))
db.commit();

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/task/1/convert',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2F1%2Fconvert%3Fid%3D1')

# login
admin_session,token = getSession('admin','admin','task')
user_session,token = getSession('user2','test-passwd','task');

# insert tasks for tests
r = admin_session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'subtask one','description':'first subtask','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})

    
# task id: none | non-existing | unaccessible | valid

# task_id: absent
r = admin_session.get('http://localhost/task/convert',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = admin_session.get('http://localhost/task/')
expectError(r,'Keine Aufgaben-ID angegeben!')

# task_id: non-existig
r = admin_session.get('http://localhost/task/9999/convert',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = admin_session.get('http://localhost/task/')
expectError(r,'Sie sind nicht berechtigt, auf diese Aufgabe zuzugreifen')

# task_id: non-accessible
r = user_session.get('http://localhost/task/1/convert',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = user_session.get('http://localhost/task/')
expectError(r,'Sie sind nicht berechtigt, auf diese Aufgabe zuzugreifen')

# valid:
# task without redirect should be redirected to itself
r = admin_session.get('http://localhost/task/1/convert',allow_redirects=False)
expectRedirect(r,'http://localhost/project/4/view');

r = admin_session.get(r.headers['location'])
expectInfo(r,'Task wurde gelöscht')
expect(r,'<title>Umbrella: Projekt task one</title>')
expect(r,'<h1>task one</h1>')
expect(r,'<p>task without name</p>')
expect(r,'<td>2.5 Stunden</td>')
expect(r,'<a href="http://localhost/task/2/view">subtask one</a>')
expect(r,'admin (Eigentümer)')

print 'done.'