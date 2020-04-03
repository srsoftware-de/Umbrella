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
def resetDb():
    global cursor
    cursor.execute('DELETE FROM tasks')
    cursor.execute('DELETE FROM task_dependencies')
    cursor.execute('DELETE FROM tasks_users')
    db.commit();
    
    cursor.execute('INSERT INTO tasks (id, project_id, parent_task_id, name, description, status, est_time, start_date, due_date) VALUES (1, 3, NULL, "task one", "task without name", 10, 2.5, "2019-01-14", "2019-02-01"), (2, 3, 1, "subtask one", "first subtask", 10, 2.5, "2019-01-14", "2019-02-01"), (3, 2, NULL, "project-2-task", "belongs to project 2", 10, 2.5, "2019-01-14", "2019-02-01")')
    cursor.execute('INSERT INTO tasks_users (task_id, user_id, permissions) VALUES (1,1,1), (2,1,1), (2,2,4), (3,2,1)')
    db.commit();
    
resetDb()

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/task/1/edit',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Ftask%2F1%2Fedit%3Fid%3D1')

# login
admin_session,token = getSession('admin','admin','task')
user_session,token = getSession('user2','test-passwd','task');

r = admin_session.get('http://localhost/project/3/open') # may have been closed before

# task id: none | non-existing | unaccessible | valid

# task_id: absent
r = admin_session.get('http://localhost/task/edit',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = admin_session.get('http://localhost/task/')
expectError(r,'Keine Aufgaben-ID angegeben!')

# task_id: non-existig
r = admin_session.get('http://localhost/task/9999/edit',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = admin_session.get('http://localhost/task/')
expectError(r,'Sie sind nicht berechtigt, auf diese Aufgabe zuzugreifen')

# task_id: non-accessible
r = user_session.get('http://localhost/task/1/edit',allow_redirects=False)
expectRedirect(r,'http://localhost/task/');

r = user_session.get('http://localhost/task/')
expectError(r,'Sie sind nicht berechtigt, auf diese Aufgabe zuzugreifen')

# valid task id, but no write access
r = user_session.get('http://localhost/task/2/edit',allow_redirects=False)
expectRedirect(r,'http://localhost/task/2/view');

r = user_session.get('http://localhost/task/2/view')
expectError(r,'Sie haben keine Berechtigung die Aufgabe zu ändern!')

# check form
r = admin_session.get('http://localhost/task/2/edit',allow_redirects=False)
expect(r,'"subtask one" bearbeiten')
expect(r,'<select name="project_id">') # to be tested
expect(r,'<option value="1" >admin-project</option>')
expect(r,'<option value="3" selected="selected">common-project</option>')
expect(r,'<input type="text" name="name" value="subtask one" autofocus="autofocus"/>') # to be tested
expect(r,'<select name="parent_task_id">') # to be tested
expect(r,'<option value="1" selected="selected">task one</option>')
expect(r,'<textarea name="description">first subtask</textarea>') # to be tested
expect(r,'<input type="number" name="est_time" value="2.5" />') # to be tested
expect(r,'<input type="text" name="tags" value="ene mene muh" />') # to be tested
expect(r,'<input name="start_date" type="date" value="2019-01-14" />') # to be tested
expect(r,'<input name="due_date" type="date" value="2019-02-01" />') # to be tested
expect(r,'<select name="start_extension">') # to be tested
expect(r,'<select name="due_extension">') # to be tested
expect(r,'<input type="checkbox" name="required_tasks[1]" />') # to be tested
expect(r,'<input type="checkbox" name="show_closed" />') # to be tested
expect(r,'<input type="checkbox" name="no_index" />') # to be tested
expect(r,'<input type="checkbox" name="silent" />') # to be tested

# project_id:       missing | empty | invalid | non-existing | non-accessible | valid
# name:             missing | empty |                                           valid
# parent_task_id:   missing | empty | invalid | non-existing | non-accessible | valid
# description:      missing | empty |                                           valid
# est_time:         missing | empty | invalid |                                 valid
# tags:             missing | empty |                                           valid
# start_date:       missing | empty | invalid |                                 valid
# due_date:         missing | empty | invalid | before-start |                  valid
# start_extendsion: missing | empty | invalid | 1wk | 1mth | 3mth | 6mth | 1yr
# due_extendsion:   missing | empty | invalid | 1wk | 1mth | 3mth | 6mth | 1yr
# required_tasks:   missing | empty | invalid |                                 valid
# show closed:      missing | empty | invalid | off | on
# no_index:         missing | empty | invalid | off | on
# silent:           missing | empty | invalid | off | on

# project_id missing: should not alter project id
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')
rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 1) in rows)

# project_id empty: should not alter project id
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':'','name':'first subtask','parent_task_id':1,'description':'this is the first subtask.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')
rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is the first subtask.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 1) in rows)

# project id invalid
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':'buggy','name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectError(r,'Aufgabe muss an existierendes Projekt gebunden sein!')

# project id non-existing
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':9999,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectError(r,'Aufgabe muss an existierendes Projekt gebunden sein!')

# project id non-accessible
if time.time() > 1585956026: # ignore this test for now. in the future, this should be re-implemented
    r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':2,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
    expectError(r,'Aufgabe muss an existierendes Projekt gebunden sein!')

# project id valid: should be updated
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':1,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 1, None, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 1) in rows)

# name missing: should return form
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expect(r,'<legend>"first subtask" bearbeiten</legend>')

# name empty: should return form
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expect(r,'<legend>"first subtask" bearbeiten</legend>')

# parent_task_id missing: update field
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'primary subtask','description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, None, 'primary subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 1) in rows)
resetDb()

# parent task_id empty: update field
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'primary subtask','parent_task_id':'','description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, None, 'primary subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 1) in rows)

resetDb()

# parent task_id invalid: show error
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':'buggy','description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectError(r,'Übergeordnete Aufgabe muss zum gleichen Projekt gehören wie die bearbeitete Aufgabe!')

# parent task_id non-existing: show error
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':9999,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectError(r,'Übergeordnete Aufgabe muss zum gleichen Projekt gehören wie die bearbeitete Aufgabe!')

# parent task_id non-accessible: show error
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':3,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectError(r,'Übergeordnete Aufgabe muss zum gleichen Projekt gehören wie die bearbeitete Aufgabe!')

# description missing: do not alter
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'primary subtask','parent_task_id':1,'est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'primary subtask', 'first subtask', 10, 2.5, '2019-02-01', '2019-03-01', 1, 1) in rows)

# description empty
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', '', 10, 2.5, '2019-02-01', '2019-03-01', 1, 1) in rows)

#est_time missing: do not alter
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 1) in rows)

#est_time empty: should drop est_time
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':'','tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, None, '2019-02-01', '2019-03-01', 1, 1) in rows)

# est_time invlaid: should produce error
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':'buggy','tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectError(r,'"buggy" ist keine gültige Dauer!')

# tags missing: should not alter tags
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

r = admin_session.get('http://localhost/task/2/view')
expect(r,'href="http://localhost/bookmark/bist/view">bist</a>')
expect(r,'href="http://localhost/bookmark/du/view">du</a>')
expect(r,'href="http://localhost/bookmark/ene/view">ene</a>')
expect(r,'href="http://localhost/bookmark/mene/view">mene</a>')
expect(r,'href="http://localhost/bookmark/muh/view">muh</a>')
expect(r,'href="http://localhost/bookmark/raus/view">raus</a>')
expect(r,'href="http://localhost/bookmark/und/view">und</a>')

# tags empty: won't dop tags
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

r = admin_session.get('http://localhost/task/2/view')
expect(r,'href="http://localhost/bookmark/bist/view">bist</a>')
expect(r,'href="http://localhost/bookmark/du/view">du</a>')
expect(r,'href="http://localhost/bookmark/ene/view">ene</a>')
expect(r,'href="http://localhost/bookmark/mene/view">mene</a>')
expect(r,'href="http://localhost/bookmark/muh/view">muh</a>')
expect(r,'href="http://localhost/bookmark/raus/view">raus</a>')
expect(r,'href="http://localhost/bookmark/und/view">und</a>')

# tags set: add new tags
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'austin.powers','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

r = admin_session.get('http://localhost/task/2/view')
expect(r,'href="http://localhost/bookmark/austin.powers/view">austin.powers</a>')
expect(r,'href="http://localhost/bookmark/bist/view">bist</a>')
expect(r,'href="http://localhost/bookmark/du/view">du</a>')
expect(r,'href="http://localhost/bookmark/ene/view">ene</a>')
expect(r,'href="http://localhost/bookmark/mene/view">mene</a>')
expect(r,'href="http://localhost/bookmark/muh/view">muh</a>')
expect(r,'href="http://localhost/bookmark/raus/view">raus</a>')
expect(r,'href="http://localhost/bookmark/und/view">und</a>')

# start_date missing: drop
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, None, '2019-03-01', 1, 1) in rows)

# start date empty: drop
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, None, '2019-03-01', 1, 1) in rows)

# start_date invalid: show error
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'7896','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'buggy','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectError(r,'Startdatum (buggy +1 month) ist kein gültiges Datum!')

# due_date: missing: drop
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', None, 1, 1) in rows)

# due_date empty: drop
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', None, 1, 1) in rows)

# due_date invalid: show error
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'buggy','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectError(r,'Fälligkeits-Datum (buggy +1 month) ist kein gültiges Datum!')

# due_date before start: update dates
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2018-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2018-03-01', '2018-03-01', 1, 1) in rows)

r = admin_session.get('http://localhost/task/2/view')
expectInfo(r,'Start-Datum wurde dem Fälligkeitsdatum angepasst!')

# start_extension missing
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})# start_extension empty
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-01-01', '2019-03-01', 1, 1) in rows)

# start extension invalid
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'buggy','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectError(r,'Startdatum (2019-01-01 buggy) ist kein gültiges Datum!')

# start extension 1 week
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 week','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-01-08', '2019-03-01', 1, 1) in rows)

# start extension 1 month
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2018-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2018-02-01', '2019-03-01', 1, 1) in rows)

# start extension 3 months
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2018-01-01','due_date':'2019-02-01','start_extension':'+3 months','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2018-04-01', '2019-03-01', 1, 1) in rows)

# start extension 6 months
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2018-01-01','due_date':'2019-02-01','start_extension':'+6 months','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2018-07-01', '2019-03-01', 1, 1) in rows)

# start extension 1 year
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2018-01-01','due_date':'2019-02-01','start_extension':'+1 year','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-01-01', '2019-03-01', 1, 1) in rows)

# due extension missing
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})# due extension empty
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-02-01', 1, 1) in rows)

# due extension invalid
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'buggy','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectError(r,'Fälligkeits-Datum (2019-02-01 buggy) ist kein gültiges Datum!')

# due extension 1 week
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 week','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-02-08', 1, 1) in rows)

# due extension 1 month
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 1) in rows)

# due extension 3 months
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+3 months','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-05-01', 1, 1) in rows)

# due extension 6 months
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+6 months','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-08-01', 1, 1) in rows)

# due extension 1 year
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 year','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2020-02-01', 1, 1) in rows)

# required_tasks missing: drop dependencies
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')
rows = cursor.execute('SELECT count(*) FROM task_dependencies WHERE task_id = 2')
expect((0,) in rows)

# required_tasks: valid
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')
rows = cursor.execute('SELECT * FROM task_dependencies WHERE task_id = 2')
expect((2,1) in rows)

# required_tasks: empty
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'7890','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[]':'','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')
rows = cursor.execute('SELECT count(*) FROM task_dependencies WHERE task_id = 2')
expect((0,) in rows)

# required_tasks: invalid
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks':'error','show_closed':'on','no_index':'on','silent':'on'})
expectError(r,'required_tasks sollte eine Liste sein, gefunden wurde aber error!')

# show_closed missing
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 0, 1) in rows)

# show_closed empty
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 0, 1) in rows)

# show_closed invalid
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'buggy','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 0, 1) in rows)

# show_closed off
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'of','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 0, 1) in rows)

# show_closed on
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 1) in rows)

# no_index missing
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 0) in rows)

# no_index empty
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 0) in rows)

# no_index invalid
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'buggy','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 0) in rows)

# no_index off
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'off','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 0) in rows)

# no_index on
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 1) in rows)

#valid request with all fields set:
r = admin_session.post('http://localhost/task/2/edit',allow_redirects=False,data={'project_id':3,'name':'first subtask','parent_task_id':1,'description':'this is subtask number one.','est_time':2.5,'tags':'ene mene muh und raus bist du','start_date':'2019-01-01','due_date':'2019-02-01','start_extension':'+1 month','due_extension':'+1 month','required_tasks[1]':'on','show_closed':'on','no_index':'on','silent':'on'})
expectRedirect(r,'http://localhost/task/2/view')

rows = cursor.execute('SELECT * FROM tasks WHERE id = 2')
expect((2, 3, 1, 'first subtask', 'this is subtask number one.', 10, 2.5, '2019-02-01', '2019-03-01', 1, 1) in rows)

rows = cursor.execute('SELECT * FROM task_dependencies WHERE task_id = 2')
expect((2,1) in rows)
    
print 'done.'