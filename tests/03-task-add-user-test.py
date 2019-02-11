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
admin_session,token = getSession('admin','admin','task')
user_session,token = getSession('user2','test-passwd','user');

# no task id: should produce redirect, then error
r = admin_session.get('http://localhost/task/add_user',allow_redirects=False)
expectRedirect(r,'http://localhost/task/')

r = admin_session.get('http://localhost/task/',allow_redirects=False)
expectError(r,'Keine Aufgaben-ID zum Hinzufügen von Nutzern angegeben!')

# non-existing task id, shoud produce redirect, then error
r = admin_session.get('http://localhost/task/9999/add_user',allow_redirects=False)
expectRedirect(r,'http://localhost/task/')

r = admin_session.get('http://localhost/task/',allow_redirects=False)
expectError(r,'Sie sind nicht berechtigt, auf diese Aufgabe zuzugreifen!')

# test form
r = admin_session.get('http://localhost/task/1/add_user',allow_redirects=False)
expect(r,'Benutzer zu Aufgabe "<a href="view">task one</a>" hinzufügen')
expect(r,'<td>user2</td>')
expect(r,'<input type="radio" name="users[2]" title="lesen + schreiben" value="2" />')
expect(r,'<input type="radio" name="users[2]" title="nur lesen"    value="4" />')
expect(r,'<input type="radio" name="users[2]" title="kein Zugriff"    value="0" checked="checked"/>')
expect(r,'<input type="checkbox" name="notify" checked="checked"> Benutzer benachrichtigen')


print 'done '+CYEL+'(Tests missing: no form value combinations tested)'+CEND+'.'