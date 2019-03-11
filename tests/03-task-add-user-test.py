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
db.commit();

new_id = 2;

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/task/add_user',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2Fadd_user')

# login
session,token = getSession('admin','admin','task')

# no task id: should produce redirect, then error
r = session.get('http://localhost/task/add_user',allow_redirects=False)
expectRedirect(r,'http://localhost/task/')

r = session.get('http://localhost/task/',allow_redirects=False)
expectError(r,'Keine Aufgaben-ID angegeben!')

# non-existing task id, shoud produce redirect, then error
r = session.get('http://localhost/task/9999/add_user',allow_redirects=False)
expectRedirect(r,'http://localhost/task/')

r = session.get('http://localhost/task/',allow_redirects=False)
expectError(r,'Sie sind nicht berechtigt, auf diese Aufgabe zuzugreifen!')

# test form
r = session.get('http://localhost/task/1/add_user',allow_redirects=False)
expect(r,'Benutzer zu Aufgabe "<a href="view">task one</a>" hinzufügen')
expect(r,'<td>user2</td>')
expect(r,'<input type="radio" name="users[2]" title="lesen + schreiben" value="2" />')
expect(r,'<input type="radio" name="users[2]" title="nur lesen" value="4" />')
expect(r,'<input type="radio" name="users[2]" title="kein Zugriff" value="0" checked="checked" />')
expect(r,'<input type="checkbox" name="notify" checked="checked"> Benutzer benachrichtigen')

# users: missing | empty | non-existing | not-in-project | already-assinged | valid
# users/value: missing | undefined | owner | read | write
# notify: missing | empty | off | on

# users: missing, should re-produce form
r = session.post('http://localhost/task/1/add_user',allow_redirects=False,data={'notify':'on'})
expect(r,'Benutzer zu Aufgabe "<a href="view">task one</a>" hinzufügen')
expect(r,'<td>user2</td>')

# users: empty, should re-produce the form
r = session.post('http://localhost/task/1/add_user',allow_redirects=False,data={'users':'','notify':'on'})
expect(r,'Benutzer zu Aufgabe "<a href="view">task one</a>" hinzufügen')
expect(r,'<td>user2</td>')

# users: non-existing
r = session.post('http://localhost/task/1/add_user',allow_redirects=False,data={'users[9999]':WRITE})
expectError(r,'Nutzer mit ID 9999 ist nicht am Projekt beteiligt!');

# users: not-in-project
r = session.post('http://localhost/task/1/add_user',allow_redirects=False,data={'users[3]':WRITE})
expectError(r,'Nutzer mit ID 3 ist nicht am Projekt beteiligt!');

# users: already-assigned
r = session.post('http://localhost/task/1/add_user',allow_redirects=False,data={'users[1]':WRITE})
expectWarning(r,'admin ist dieser Aufgabe bereits zugewiesen')

# users/value: missing, removes user from task
r = session.post('http://localhost/task/1/add_user',allow_redirects=False,data={'users[2]':''})
expectRedirect(r,'http://localhost/task/1/view');



# users/value: undefined
r = session.post('http://localhost/task/1/add_user',allow_redirects=False,data={'users[2]':9999})
expectError(r,'Ungültige Berechtigung für user2 angefordert');

# users/value: owner
r = session.post('http://localhost/task/1/add_user',allow_redirects=False,data={'users[2]':OWNER})
expectRedirect(r,'http://localhost/task/1/view');

rows = cursor.execute('SELECT * FROM tasks_users').fetchall()
expect((1,USER2,WRITE) in rows)
expect((1,USER2,OWNER) not in rows)
cursor.execute('DELETE FROM tasks_users WHERE user_id>1')
db.commit();

# users/value: read
r = session.post('http://localhost/task/1/add_user',allow_redirects=False,data={'users[2]':READ})
expectRedirect(r,'http://localhost/task/1/view');

rows = cursor.execute('SELECT * FROM tasks_users').fetchall()
expect((1,USER2,READ) in rows)
cursor.execute('DELETE FROM tasks_users WHERE user_id>1')
db.commit();

# users/value: read
r = session.post('http://localhost/task/1/add_user',allow_redirects=False,data={'users[2]':WRITE})
expectRedirect(r,'http://localhost/task/1/view');

rows = cursor.execute('SELECT * FROM tasks_users').fetchall()
expect((1,USER2,WRITE) in rows)
cursor.execute('DELETE FROM tasks_users WHERE user_id>1')
db.commit();

# notify: absent
r = session.post('http://localhost/task/1/add_user',data={'users[2]':WRITE})
expectNot(r,'benachrichtigt')

cursor.execute('DELETE FROM tasks_users WHERE user_id>1')
db.commit();

# notify: empty
r = session.post('http://localhost/task/1/add_user',data={'users[2]':WRITE,'notify':''})
expectNot(r,'benachrichtigt')

cursor.execute('DELETE FROM tasks_users WHERE user_id>1')
db.commit();

# notify: off
r = session.post('http://localhost/task/1/add_user',data={'users[2]':WRITE,'notify':'off'})
expectNot(r,'benachrichtigt')

cursor.execute('DELETE FROM tasks_users WHERE user_id>1')
db.commit();

# notify: on
r = session.post('http://localhost/task/1/add_user',allow_redirects=False,data={'users[2]':WRITE,'notify':'on'})
expectRedirect(r,'http://localhost/task/1/view')

r = session.get('http://localhost/task/1/view')
expectInfo(r,'Nutzer wurde per Mail benachrichtigt.')

rows = cursor.execute('SELECT * FROM tasks_users').fetchall()
expect((1,ADMIN,OWNER) in rows)
expect((1,USER2,WRITE) in rows) # user should be added

print 'done'