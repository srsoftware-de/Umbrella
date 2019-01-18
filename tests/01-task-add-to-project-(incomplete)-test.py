#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys, time
sys.path.append("/var/www/tests")
from test_routines import *

# Projects
ADMIN_PROJECT=1
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

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/task/add_to_project',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2Fadd_to_project')

# login
admin_session,token = getSession('admin','admin','task')

# no project id: should produce redirect, then error
r = admin_session.get('http://localhost/task/add_to_project',allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

r = admin_session.get('http://localhost/project/',allow_redirects=False)
expectError(r,'Keine Projekt-ID angegeben!')

# non-existing project, shoud produce redirect, then error
r = admin_session.get('http://localhost/task/add_to_project/9999',allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

r = admin_session.get('http://localhost/project/',allow_redirects=False)
expectError(r,'Sie sind nicht berechtigt, auf dieses Projekt zuzugreifen!')

# project of other user, shoud produce redirect, then error
r = admin_session.get('http://localhost/task/add_to_project/2',allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

r = admin_session.get('http://localhost/project/',allow_redirects=False)
expectError(r,'Sie sind nicht berechtigt, auf dieses Projekt zuzugreifen!')

# common project. check form
r = admin_session.get('http://localhost/task/add_to_project/3',allow_redirects=False)
expect(r,'Neue Aufgabe anlegen')
expect(r,'<a href="http://localhost/project/3/view">common-project</a>')
expect(r,'<input type="text" name="name" value="" autofocus="true"/>')
expect(r,'<textarea name="description"></textarea>')
expect(r,'<input type="number" name="est_time" value="" />')
expect(r,'<input type="radio" name="users[1]" title="lesen + schreiben" value="2" checked="checked"/>')
expect(r,'<input type="radio" name="users[1]" title="nur lesen" value="4" />')
expect(r,'<input type="radio" name="users[1]" title="kein Zugriff" value="0" />')
expect(r,'<input type="radio" name="users[2]" title="lesen + schreiben" value="2" />')
expect(r,'<input type="radio" name="users[2]" title="nur lesen" value="4" />')
expect(r,'<input type="radio" name="users[2]" title="kein Zugriff" value="0" checked="checked"/>')
expect(r,'<input type="checkbox" name="notify" checked="true" />')
expect(r,'<input name="tags" type="text" value="" />')
expect(r,'<input name="start_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="'+time.strftime("%Y-%m-%d")+'" />')
expect(r,'<input name="due_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="" />')

# name absent: shoud re-produce the form, with fields pre-filled
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'description':'task without name','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expect(r,'Neue Aufgabe anlegen')
expect(r,'<a href="http://localhost/project/3/view">common-project</a>')
expect(r,'<input type="text" name="name" value="" autofocus="true"/>')
expect(r,'<textarea name="description">task without name</textarea>')
expect(r,'<input type="number" name="est_time" value="2.5" />')
expect(r,'<input type="checkbox" name="notify" checked="true" />')
expect(r,'<input name="tags" type="text" value="ene mene muh" />')
expect(r,'<input name="start_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="2019-01-14" />')
expect(r,'<input name="due_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="2019-02-01" />')

# name empty: shoud re-produce the form, with fields pre-filled
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'','description':'task without name','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expect(r,'Neue Aufgabe anlegen')
expect(r,'<a href="http://localhost/project/3/view">common-project</a>')
expect(r,'<input type="text" name="name" value="" autofocus="true"/>')
expect(r,'<textarea name="description">task without name</textarea>')
expect(r,'<input type="number" name="est_time" value="2.5" />')
expect(r,'<input type="checkbox" name="notify" checked="true" />')
expect(r,'<input name="tags" type="text" value="ene mene muh" />')
expect(r,'<input name="start_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="2019-01-14" />')
expect(r,'<input name="due_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="2019-02-01" />')

# name valid: shoud produce redirect to task
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task one','description':'task without name','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectRedirect(r,'http://localhost/task/1/view')

# check task has been created in database
rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((1, COMMON_PROJECT, None, 'task one', 'task without name', OPEN, 2.5, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users').fetchall()
expect((1,ADMIN,OWNER) in rows)
expect((1,USER2,READ) in rows)

# end of name cheks

#description: absent | empty | valid

# description absent: shoud produce redirect to task
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task two','est_time':3.5,'users[1]':READ,'users[2]':WRITE,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectRedirect(r,'http://localhost/task/2/view')

# check task has been created in database
rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((2, COMMON_PROJECT, None, 'task two', None, OPEN, 3.5, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = 2').fetchall()
expect((2,ADMIN,OWNER) in rows)
expect((2,USER2,WRITE) in rows)

# description empty: shoud produce redirect to task
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task three','description':'','est_time':4.5,'users[1]':WRITE,'users[2]':OWNER,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectRedirect(r,'http://localhost/task/3/view')

# check task has been created in database
rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((3, COMMON_PROJECT, None, 'task three', '', OPEN, 4.5, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = 3').fetchall()
expect((3,ADMIN,OWNER) in rows)
expect((3,USER2,OWNER) in rows)

# end of description checks

#est_time: absent | empty | invalid | number

# est_time empty
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task four','description':'fourth task','users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectRedirect(r,'http://localhost/task/4/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((4, COMMON_PROJECT, None, 'task four', 'fourth task', OPEN, None, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = 4').fetchall()
expect((4,ADMIN,OWNER) in rows)
expect((4,USER2,READ) in rows)

# est_time invalid
r = admin_session.post('http://localhost/task/add_to_project/1',allow_redirects=False,data={'name':'task five','description':'fifth task','est_time':'donald trump','users[1]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectError(r,'"donald trump" is not a valid duration!')
expect(r,'<input type="number" name="est_time" value="donald trump" />')

# est time is integer
r = admin_session.post('http://localhost/task/add_to_project/1',allow_redirects=False,data={'name':'task five','description':'fifth task','est_time':1,'users[1]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectRedirect(r,'http://localhost/task/5/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((5, ADMIN_PROJECT, None, 'task five', 'fifth task', OPEN, 1, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = 5').fetchall()
expect((5,ADMIN,OWNER) in rows)

# est time is float
r = admin_session.post('http://localhost/task/add_to_project/1',allow_redirects=False,data={'name':'task six','description':'sixth task','est_time':1.7,'users[1]':WRITE,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectRedirect(r,'http://localhost/task/6/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((6, ADMIN_PROJECT, None, 'task six', 'sixth task', OPEN, 1.7, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = 6').fetchall()
expect((6,ADMIN,OWNER) in rows)

# end of est_time checks

#users: absent | empty | text | number


# fields: name, description, est_time, users, notify, tags, start_date, due_date




#notify: absent | empty | on
#start_date: absent | empty | invalid | valid
#due_date: absent | empty | invalid | valid

print 'done.'