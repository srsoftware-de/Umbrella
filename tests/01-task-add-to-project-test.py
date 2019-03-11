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

new_id = 1;

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/task/add_to_project',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2Fadd_to_project')

# login
admin_session,token = getSession('admin','admin','task')
user_session,token = getSession('user2','test-passwd','task');

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
expect(r,'<input type="radio" name="users[1]" title="beauftragt" value="3" checked="checked" /></td>')
expect(r,'<input type="radio" name="users[1]" title="lesen + schreiben" value="2" /></td>')
expect(r,'<input type="radio" name="users[1]" title="nur lesen" value="4" />')
expect(r,'<input type="radio" name="users[1]" title="kein Zugriff" value="0" />')
expect(r,'<input type="radio" name="users[2]" title="beauftragt" value="3" /></td>')
expect(r,'<input type="radio" name="users[2]" title="lesen + schreiben" value="2" />')
expect(r,'<input type="radio" name="users[2]" title="nur lesen" value="4" />')
expect(r,'<input type="radio" name="users[2]" title="kein Zugriff" value="0" checked="checked" />')
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
expect((new_id, COMMON_PROJECT, None, 'task one', 'task without name', OPEN, 2.5, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users').fetchall()
expect((new_id,ADMIN,OWNER) in rows)
expect((new_id,USER2,READ) in rows)

# end of name cheks

#description: absent | empty | valid

# description absent: shoud produce redirect to task
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task two','est_time':3.5,'users[1]':READ,'users[2]':WRITE,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

# check task has been created in database
rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, COMMON_PROJECT, None, 'task two', None, OPEN, 3.5, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = '+str(new_id)).fetchall()
expect((new_id,ADMIN,OWNER) in rows)
expect((new_id,USER2,WRITE) in rows)

# description empty: shoud produce redirect to task
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task three','description':'','est_time':4.5,'users[1]':WRITE,'users[2]':OWNER,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

# check task has been created in database
rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, COMMON_PROJECT, None, 'task three', '', OPEN, 4.5, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = '+str(new_id)).fetchall()
expect((new_id,ADMIN,OWNER) in rows)
expect((new_id,USER2,OWNER) in rows)

# end of description checks

#est_time: absent | empty | invalid | number

# est_time empty
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task four','description':'fourth task','users[1]':OWNER,'users[2]':WRITE,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, COMMON_PROJECT, None, 'task four', 'fourth task', OPEN, None, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = '+str(new_id)).fetchall()
expect((new_id,ADMIN,OWNER) in rows)
expect((new_id,USER2,WRITE) in rows)

# est_time invalid
r = admin_session.post('http://localhost/task/add_to_project/1',allow_redirects=False,data={'name':'task five','description':'fifth task','est_time':'donald trump','users[1]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectError(r,'"donald trump" ist keine gültige Dauer!')
expect(r,'<input type="number" name="est_time" value="donald trump" />')

# est time is integer
r = admin_session.post('http://localhost/task/add_to_project/1',allow_redirects=False,data={'name':'task five','description':'fifth task','est_time':1,'users[1]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, ADMIN_PROJECT, None, 'task five', 'fifth task', OPEN, 1, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = '+str(new_id)).fetchall()
expect((new_id,ADMIN,OWNER) in rows)

# est time is float
r = admin_session.post('http://localhost/task/add_to_project/1',allow_redirects=False,data={'name':'task six','description':'sixth task','est_time':1.7,'users[1]':WRITE,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, ADMIN_PROJECT, None, 'task six', 'sixth task', OPEN, 1.7, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = '+str(new_id)).fetchall()
expect((new_id,ADMIN,OWNER) in rows)

# end of est_time checks

#users: absent | empty | text | non-existing | not-in-project | valid

# users absent
r = admin_session.post('http://localhost/task/add_to_project/1',allow_redirects=False,data={'name':'task seven','description':'seventh task','est_time':1.7,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectError(r,'Es muss mindestens ein Benutzer ausgewählt werden!')

# users empty
r = admin_session.post('http://localhost/task/add_to_project/1',allow_redirects=False,data={'name':'task seven','description':'seventh task','est_time':1.7,'users':'','notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectError(r,'Es muss mindestens ein Benutzer ausgewählt werden!')

# users is string
r = admin_session.post('http://localhost/task/add_to_project/1',allow_redirects=False,data={'name':'task seven','description':'seventh task','est_time':1.7,'users':'nope','notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectError(r,'Es muss mindestens ein Benutzer ausgewählt werden!')

# users is array of strings
r = admin_session.post('http://localhost/task/add_to_project/1',allow_redirects=False,data={'name':'task seven','description':'seventh task','est_time':1.7,'users[1]':'nope','notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, ADMIN_PROJECT, None, 'task seven', 'seventh task', OPEN, 1.7, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = '+str(new_id)).fetchall()
expect((new_id,ADMIN,OWNER) in rows)


# users contains non-existing user
r = admin_session.post('http://localhost/task/add_to_project/1',allow_redirects=False,data={'name':'task seven','description':'seventh task','est_time':1.7,'users[9999]':OWNER,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectError(r,'Es muss mindestens ein Benutzer ausgewählt werden!')

# users is not in project
r = admin_session.post('http://localhost/task/add_to_project/1',allow_redirects=False,data={'name':'task seven','description':'seventh task','est_time':1.7,'users[2]':OWNER,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectError(r,'Es muss mindestens ein Benutzer ausgewählt werden!')


#notify: absent | empty | off | on

# notify absent
r = user_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task eight','description':'eigth task','est_time':8,'users[1]':READ,'users[2]':READ,'tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = '+str(new_id)).fetchall()
expect((new_id, COMMON_PROJECT, None, 'task eight', 'eigth task', OPEN, 8, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = '+str(new_id)).fetchall()
expect((new_id,ADMIN,READ) in rows)
expect((new_id,USER2,OWNER) in rows)

r = user_session.get('http://localhost/task/8/view')
expect('info' not in r.text)


# notify empty
r = user_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task nine','description':'ninth task','est_time':9,'users[1]':READ,'users[2]':READ,'notify':'','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = '+str(new_id)).fetchall()
expect((new_id, COMMON_PROJECT, None, 'task nine', 'ninth task', OPEN, 9, '2019-01-14', '2019-02-01', None, None,) in rows)

r = user_session.get('http://localhost/task/9/view')
expect('info' not in r.text)


# notify off
r = user_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task ten','description':'10th task','est_time':1.0,'users[1]':READ,'users[2]':READ,'notify':'off','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = '+str(new_id)).fetchall()
expect((new_id, COMMON_PROJECT, None, 'task ten', '10th task', OPEN, 1, '2019-01-14', '2019-02-01', None, None,) in rows)

r = user_session.get('http://localhost/task/10/view')
expect('info' not in r.text)

# notify on
r = user_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task eleven','description':'11th task','est_time':1.1,'users[1]':READ,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = '+str(new_id)).fetchall()
expect((new_id, COMMON_PROJECT, None, 'task eleven', '11th task', OPEN, 1.1, '2019-01-14', '2019-02-01', None, None,) in rows)

r = user_session.get('http://localhost/task/10/view')
expectInfo(r,'Nutzer wurde per Mail benachrichtigt.')

# end of notify tests

# tags: absent | empty | single | multiple

# tags absent
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task twelve','description':'12th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

r = admin_session.get('http://localhost/task/12/view')
expect('Tags' not in r.text)

# tags empty
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task thirteen','description':'13th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

r = admin_session.get('http://localhost/task/13/view')
expect('Tags' not in r.text)

# tags: single word
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task thirteen','description':'14th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'hello','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

r = admin_session.get('http://localhost/task/14/view')
expect(r,'<a class="button" href="http://localhost/bookmark/hello/view">hello</a>')

# tags: multiple words
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task 15','description':'15th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'hello world','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

r = admin_session.get('http://localhost/task/15/view')
expect(r,'<a class="button" href="http://localhost/bookmark/hello/view">hello</a>')
expect(r,'<a class="button" href="http://localhost/bookmark/world/view">world</a>')

# end of tag tests

# start_date: absent | empty | invalid | valid

# start_date absent
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task 16','description':'16th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'hello world','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = '+str(new_id)).fetchall()
expect((new_id, COMMON_PROJECT, None, 'task 16', '16th task', OPEN, 2.5, None, '2019-02-01', None, None,) in rows)

# start_date empty
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task 17','description':'17th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'hello world','start_date':'','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = '+str(new_id)).fetchall()
expect((new_id, COMMON_PROJECT, None, 'task 17', '17th task', OPEN, 2.5, None, '2019-02-01', None, None,) in rows)

# start_date invalid
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task 18','description':'18th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'hello world','start_date':'Mops','due_date':'2019-02-01'})
expectError(r,'Startdatum (Mops) ist kein gültiges Datum!')

# end of start_date tests

# due_date: absent | empty | invalid | valid | before-start

# due_date absent
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task 18','description':'18th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'hello world','start_date':'2019-01-14'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = '+str(new_id)).fetchall()
expect((new_id, COMMON_PROJECT, None, 'task 18', '18th task', OPEN, 2.5, '2019-01-14', None, None, None,) in rows)

# due_date empty
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task 19','description':'19th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'hello world','start_date':'2019-01-14','due_date':''})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = '+str(new_id)).fetchall()
expect((new_id, COMMON_PROJECT, None, 'task 19', '19th task', OPEN, 2.5, '2019-01-14', None, None, None,) in rows)

# due_date invalid
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task 20','description':'20th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'hello world','start_date':'2019-01-14','due_date':'Mops'})
expectError(r,'Fälligkeits-Datum (Mops) ist kein gültiges Datum!')

# due date before start
r = admin_session.post('http://localhost/task/add_to_project/3',allow_redirects=False,data={'name':'task 20','description':'20th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'hello world','start_date':'2019-01-14','due_date':'2018-12-31'})
new_id += 1
expectRedirect(r,'http://localhost/task/'+str(new_id)+'/view')

r = admin_session.get('http://localhost/task/20/view')
expectInfo(r,'Start-Datum wurde dem Fälligkeitsdatum angepasst!')

rows = cursor.execute('SELECT * FROM tasks WHERE id = '+str(new_id)).fetchall()
expect((new_id, COMMON_PROJECT, None, 'task 20', '20th task', OPEN, 2.5, '2018-12-31', '2018-12-31', None, None,) in rows)

print 'done.'