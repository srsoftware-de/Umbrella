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

time = datetime.datetime.now() + datetime.timedelta(days=1)
tomorrow = time.strftime("%Y-%m-%d %H:%M")
time = datetime.datetime.now() + datetime.timedelta(days=2)
dayAfterTomorrow = time.strftime("%Y-%m-%d %H:%M")


# reset edits of previous tests
def resetDb():
    global cursor,dayAfterTomorrow,tomorrow
    
    cursor.execute('DELETE FROM tasks')
    cursor.execute('DELETE FROM task_dependencies')
    cursor.execute('DELETE FROM tasks_users')
    db.commit();
    
    cursor.execute('INSERT INTO tasks (id, project_id, parent_task_id, name, description, status, est_time, start_date, due_date) VALUES (1, 3, NULL, "task one", "task without name", 10, 2.5, "2019-01-14", "2019-02-01"), (2, 3, 1, "subtask one", "first subtask", 10, 2.5, "'+tomorrow+'", "'+dayAfterTomorrow+'")')
    cursor.execute('INSERT INTO tasks_users (task_id, user_id, permissions) VALUES (1,1,1), (2,1,1), (2,2,4), (3,1,1)')
    db.commit();
    
resetDb()

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/task/1/wait',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2F1%2Fwait%3Fid%3D1')

# login
admin_session,token = getSession('admin','admin','task')
user_session,token = getSession('user2','test-passwd','task');

# task id: none | non-existing | unaccessible | valid

# task_id: absent
r = admin_session.get('http://localhost/task/wait',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = admin_session.get('http://localhost/task/')
expectError(r,'Keine Aufgaben-ID angegeben!')

# task_id: non-existig
r = admin_session.get('http://localhost/task/9999/wait',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = admin_session.get('http://localhost/task/')
expectError(r,'Sie sind nicht berechtigt, auf diese Aufgabe zuzugreifen')

# task_id: non-accessible
r = user_session.get('http://localhost/task/1/wait',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = user_session.get('http://localhost/task/')
expectError(r,'Sie sind nicht berechtigt, auf diese Aufgabe zuzugreifen')

# valid:
# task with parent should redirect to parent
r = user_session.get('http://localhost/task/2/wait',allow_redirects=False)
expectRedirect(r,'http://localhost/task/1/view');

rows = cursor.execute('SELECT id,status FROM tasks').fetchall()
expect((1,OPEN) in rows)
expect((2,PENDING) in rows)
    
# task without redirect should be redirected to itself
r = admin_session.get('http://localhost/task/1/wait',allow_redirects=False)
expect(r,'<legend>Probleme</legend>')
expect(r,'<li>Das Start-Datum (2019-01-14) dieser Aufgabe ist schon vergangen.</li>')
expect(r,'<li>Um diesen Task in den "abwarten"-Status zu versetzen, muss das <b>Start-Datum entfernt werden</b>.</li>')
expect(r,'<a class="button" href="?confirm=yes">Bestätigen</a>')
expect(r,'<a class="button" href="http://localhost/task/1/view">Abbrechen</a>')

print 'done.'