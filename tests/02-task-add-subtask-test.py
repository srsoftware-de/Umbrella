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
db.commit();

new_id = 2;

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/task/add_subtask',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2Fadd_subtask')

# login
session,token = getSession('admin','admin','task')

# no parent id: should produce redirect, then error
r = session.get('http://localhost/task/add_subtask',allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

r = session.get('http://localhost/project/',allow_redirects=False)
expectError(r,'Keine Id der übergeorndeten Aufgabe angegeben!')

# non-existing parent, shoud produce redirect, then error
r = session.get('http://localhost/task/9999/add_subtask',allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

r = session.get('http://localhost/project/',allow_redirects=False)
expectError(r,'Sie sind nicht berechtigt, auf diese Aufgabe zuzugreifen!')

# test form
r = session.get('http://localhost/task/1/add_subtask',allow_redirects=False)
expect(r,'Unteraufgabe zu "<a href="http://localhost/task/1/view">task one</a>" hinzufügen')
expect(r,'<a href="http://localhost/project/3/view">common-project</a>')
expect(r,'<input type="text" name="name" value="" autofocus="true"/>')
expect(r,'<textarea name="description"></textarea>')
expect(r,'<input type="number" name="est_time" value="" />')
expect(r,'<input type="radio" name="users[1]" title="lesen + schreiben" value="2" checked="checked"/>')
expect(r,'<input type="radio" name="users[1]" title="nur lesen"    value="4" />')
expect(r,'<input type="radio" name="users[1]" title="kein Zugriff"    value="0" />')
expect(r,'<input type="radio" name="users[2]" title="lesen + schreiben" value="2" />')
expect(r,'<input type="radio" name="users[2]" title="nur lesen"    value="4" />')
expect(r,'<input type="radio" name="users[2]" title="kein Zugriff"    value="0" checked="checked"/>')
expect(r,'<input type="checkbox" name="notify" checked="true" />')
expect(r,'<input name="start_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="'+time.strftime("%Y-%m-%d")+'" />')
expect(r,'<input name="due_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="2019-02-01" />')

# fields: name, description, est_time, users, notify, tags, start_date, due_date

#name: abesent | empty | valid
#description: absent | empty | valid
#est_time: absent | empty | text | number
#users: absent | empty | text | number
#notify: absent | empty | on
#start_date: absent | empty | invalid | valid
#due_date: absent | empty | invalid | valid

# name absent: shoud re-produce the form, with fields pre-filled
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'description':'task without name','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expect(r,'Unteraufgabe zu "<a href="http://localhost/task/1/view">task one</a>" hinzufügen')
expect(r,'<a href="http://localhost/project/3/view">common-project</a>')
expect(r,'<input type="text" name="name" value="" autofocus="true"/>')
expect(r,'<textarea name="description">task without name</textarea>')
expect(r,'<input type="number" name="est_time" value="2.5" />')
expect(r,'<input type="checkbox" name="notify" checked="true" />')
expect(r,'<input name="tags" type="text" value="ene mene muh" />')
expect(r,'<input name="start_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="2019-01-14" />')
expect(r,'<input name="due_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="2019-02-01" />')

# name empty: shoud re-produce the form, with fields pre-filled
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'','description':'task without name','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expect(r,'Unteraufgabe zu "<a href="http://localhost/task/1/view">task one</a>" hinzufügen')
expect(r,'<a href="http://localhost/project/3/view">common-project</a>')
expect(r,'<input type="text" name="name" value="" autofocus="true"/>')
expect(r,'<textarea name="description">task without name</textarea>')
expect(r,'<input type="number" name="est_time" value="2.5" />')
expect(r,'<input type="checkbox" name="notify" checked="true" />')
expect(r,'<input name="tags" type="text" value="ene mene muh" />')
expect(r,'<input name="start_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="2019-01-14" />')
expect(r,'<input name="due_date" type="date" pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}" value="2019-02-01" />')

# name valid: shoud produce redirect to task
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'subtask one','description':'first subtask','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectRedirect(r,'http://localhost/task/1/view')

# check task has been created in database
rows = cursor.execute('SELECT * FROM tasks ORDER BY id DESC LIMIT 1').fetchall()
expect((new_id, COMMON_PROJECT, 1, 'subtask one', 'first subtask', OPEN, 2.5, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users').fetchall()
expect((new_id,ADMIN,OWNER) in rows)
expect((new_id,USER2,READ) in rows)


# end of name cheks

#description: absent | empty | valid

# description absent: shoud produce redirect to task
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'task three','est_time':3.5,'users[1]':READ,'users[2]':WRITE,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id+=1
expectRedirect(r,'http://localhost/task/1/view')

# check task has been created in database
rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, COMMON_PROJECT, 1, 'task three', None, OPEN, 3.5, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = '+str(new_id)).fetchall()
expect((new_id,ADMIN,OWNER) in rows)
expect((new_id,USER2,WRITE) in rows)

# description empty: shoud produce redirect to task
r = session.post('http://localhost/task/2/add_subtask',allow_redirects=False,data={'name':'task four','description':'','est_time':4.5,'users[1]':WRITE,'users[2]':OWNER,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id+=1
expectRedirect(r,'http://localhost/task/2/view')

# check task has been created in database
rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, COMMON_PROJECT, 2, 'task four', '', OPEN, 4.5, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = '+str(new_id)).fetchall()
expect((new_id,ADMIN,OWNER) in rows)
expect((new_id,USER2,OWNER) in rows)

# end of description checks

#est_time: absent | empty | invalid | number

# est_time empty
r = session.post('http://localhost/task/2/add_subtask',allow_redirects=False,data={'name':'task four','description':'fourth task','users[1]':OWNER,'users[2]':WRITE,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()

expect((new_id, COMMON_PROJECT, 2, 'task four', 'fourth task', OPEN, None, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = '+str(new_id)).fetchall()
expect((new_id,ADMIN,OWNER) in rows)
expect((new_id,USER2,WRITE) in rows)

# est_time invalid
r = session.post('http://localhost/task/2/add_subtask',allow_redirects=False,data={'name':'task five','description':'fifth task','est_time':'donald trump','users[1]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectError(r,'"donald trump" ist keine gültige Dauer!')
expect(r,'<input type="number" name="est_time" value="donald trump" />')

# est time is integer
r = session.post('http://localhost/task/2/add_subtask',allow_redirects=False,data={'name':'task five','description':'fifth task','est_time':1,'users[1]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, COMMON_PROJECT, 2, 'task five', 'fifth task', OPEN, 1, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = '+str(new_id)).fetchall()
expect((new_id,ADMIN,OWNER) in rows)

# est time is float
r = session.post('http://localhost/task/2/add_subtask',allow_redirects=False,data={'name':'task six','description':'sixth task','est_time':1.7,'users[1]':WRITE,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, COMMON_PROJECT, 2, 'task six', 'sixth task', OPEN, 1.7, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = '+str(new_id)).fetchall()
expect((new_id,ADMIN,OWNER) in rows)

# end of est_time checks

#users: absent | empty | text | non-existing | not-in-project | valid

# users absent
r = session.post('http://localhost/task/2/add_subtask',allow_redirects=False,data={'name':'task seven','description':'seventh task','est_time':1.7,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectError(r,'Es muss mindestens ein Benutzer ausgewählt werden!')

# users empty
r = session.post('http://localhost/task/2/add_subtask',allow_redirects=False,data={'name':'task seven','description':'seventh task','est_time':1.7,'users':'','notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectError(r,'Es muss mindestens ein Benutzer ausgewählt werden!')

# users is string
r = session.post('http://localhost/task/2/add_subtask',allow_redirects=False,data={'name':'task seven','description':'seventh task','est_time':1.7,'users':'nope','notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectError(r,'Es muss mindestens ein Benutzer ausgewählt werden!')

# users is array of strings
r = session.post('http://localhost/task/2/add_subtask',allow_redirects=False,data={'name':'task seven','description':'seventh task','est_time':1.7,'users[1]':'nope','notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, COMMON_PROJECT, 2, 'task seven', 'seventh task', OPEN, 1.7, '2019-01-14', '2019-02-01', None, None,) in rows)

rows = cursor.execute('SELECT * FROM tasks_users WHERE task_id = '+str(new_id)).fetchall()
expect((new_id,ADMIN,OWNER) in rows)

# users contains non-existing user
r = session.post('http://localhost/task/2/add_subtask',allow_redirects=False,data={'name':'task seven','description':'seventh task','est_time':1.7,'users[9999]':OWNER,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectError(r,'Es muss mindestens ein Benutzer ausgewählt werden!')

# user is not in project
r = session.post('http://localhost/task/2/add_subtask',allow_redirects=False,data={'name':'task seven','description':'seventh task','est_time':1.7,'users[3]':OWNER,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
expectError(r,'Es muss mindestens ein Benutzer ausgewählt werden!')


#notify: absent | empty | off | on

#notify: absent
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'task seven','description':'seventh task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/1/view')

r = session.post('http://localhost/task/1/view')
expectNot(r,'Nutzer wurde per Mail benachrichtigt.')

#notify: empty
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'task eigth','description':'eigth task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/1/view')

r = session.post('http://localhost/task/1/view')
expectNot(r,'Nutzer wurde per Mail benachrichtigt.')

#notify: off
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'task nine','description':'ninth task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'off','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/1/view')

r = session.post('http://localhost/task/1/view')
expectNot(r,'Nutzer wurde per Mail benachrichtigt.')

#notify: on
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'task ten','description':'tenth task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/1/view')

r = session.post('http://localhost/task/1/view')
expect(r,'Nutzer wurde per Mail benachrichtigt.')

#start_date: absent | empty | invalid | valid
# start_date absent
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'task eleven','description':'eleventh task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/1/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, COMMON_PROJECT, 1, 'task eleven', 'eleventh task', 10, 2.5, None, '2019-02-01', None, None) in rows)

# start_date empty
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'task twelfe','description':'twelfth task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'','due_date':'2019-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/1/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, COMMON_PROJECT, 1, 'task twelfe', 'twelfth task', 10, 2.5, None, '2019-02-01', None, None) in rows)

# start_date invalid
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'task thirteen','description':'13th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'grunzwanzling','due_date':'2019-02-01'})
expect(r,'Startdatum (grunzwanzling) ist kein gültiges Datum!')

#due_date: absent | empty | invalid | valid
#due_date: abent
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'task thirteen','description':'13th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14'})
new_id += 1
expectRedirect(r,'http://localhost/task/1/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, COMMON_PROJECT, 1, 'task thirteen', '13th task', 10, 2.5, '2019-01-14', None, None, None) in rows)

#due_date: empty
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'task fourteen','description':'14th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':''})
new_id += 1
expectRedirect(r,'http://localhost/task/1/view')

rows = cursor.execute('SELECT * FROM tasks').fetchall()
expect((new_id, COMMON_PROJECT, 1, 'task fourteen', '14th task', 10, 2.5, '2019-01-14', None, None, None) in rows)

#due_date: invalid
r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'task fourteen','description':'14th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'vorgonische Dichtung'})
expect(r,'Fälligkeits-Datum (vorgonische Dichtung) ist kein gültiges Datum!')

r = session.post('http://localhost/task/1/add_subtask',allow_redirects=False,data={'name':'task fifteen','description':'15th task','est_time':2.5,'users[1]':OWNER,'users[2]':READ,'notify':'on','tags':'ene mene muh','start_date':'2019-01-14','due_date':'2018-02-01'})
new_id += 1
expectRedirect(r,'http://localhost/task/1/view')

r = session.get('http://localhost/task/1/view')
expect(r,'Start-Datum wurde dem Fälligkeitsdatum angepasst!')

print 'done.'