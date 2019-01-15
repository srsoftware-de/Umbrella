#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *

OPEN = 10
COMPLETED = 60
CANCELED = 100

db = sqlite3.connect('../db/projects.db')
cursor = db.cursor()

# if this test fails, you may have forgotten to execute 04-project-complete-test
cursor.execute('SELECT * FROM projects')
expect(cursor.fetchall() == [
    (1, None, 'admin-project', 'owned by admin', COMPLETED),
    (2, None, 'user2-project', 'owned by user2', COMPLETED),
    (3, None, 'common-project', 'created by user2', OPEN)
])

# check redirect to login for users that are not logged in
r = requests.get("http://localhost/project/edit",allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fproject%2Fedit')


# login
admin_session,token = getSession('admin','admin','project')

# without a project id, a redirect should occur
r = admin_session.get('http://localhost/project/edit',allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

# error for previous problem should be displayed
r = admin_session.get('http://localhost/project/',allow_redirects=False)
expectError(r,'Keine Projekt-ID angegeben!')

# non-existing project id, redirect should occur
r = admin_session.get('http://localhost/project/9999/edit',allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

# error for previous problem should be displayed
r = admin_session.get('http://localhost/project/',allow_redirects=False)
expectError(r,'Sie sind nicht an diesem Projekt beteiligt!')

# check edit form
r = admin_session.get('http://localhost/project/1/edit',allow_redirects=False)
expect('<body class="project 1 edit">' in r.text)
expect('<form method="POST">' in r.text)
expect('<input type="text" name="name" value="admin-project"/>' in r.text)
expect('<textarea name="description">owned by admin</textarea>' in r.text)
expect('<input type="text" name="tags" value="prj1 project" />' in r.text)

# no update, if no name passed. form should be displayed
r = admin_session.post('http://localhost/project/1/edit',allow_redirects=False,data={'description':'new-description'})
expect('<body class="project 1 edit">' in r.text)
expect('<form method="POST">' in r.text)
expect('<input type="text" name="name" value="admin-project"/>' in r.text)
expect('<textarea name="description">owned by admin</textarea>' in r.text)
expect('<input type="text" name="tags" value="prj1 project" />' in r.text)

# check nothing has been altered
cursor.execute('SELECT * FROM projects WHERE id = 1')
expect(cursor.fetchone() == (1, None, 'admin-project', 'owned by admin', COMPLETED))

# no update, if no name passed. form should be displayed
r = admin_session.post('http://localhost/project/1/edit',allow_redirects=False,data={'description':'new-description','name':'project of admin'})
expectRedirect(r,'http://localhost/project/1/view')

# check project has been altered
cursor.execute('SELECT * FROM projects WHERE id = 1')
expect(cursor.fetchone() == (1, None, 'project of admin', 'new-description', COMPLETED))

# login
user_session,token = getSession('user2','test-passwd','project')

# non-existing project id, redirect should occur
r = user_session.get('http://localhost/project/1/edit',allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

# error for previous problem should be displayed
r = user_session.get('http://localhost/project/',allow_redirects=False)
expectError(r,'Sie sind nicht an diesem Projekt beteiligt!')

# no update, if no name passed. form should be displayed
r = user_session.post('http://localhost/project/2/edit',allow_redirects=False,data={'description':'nope'})
expect('<body class="project 2 edit">' in r.text)
expect('<form method="POST">' in r.text)
expect('<input type="text" name="name" value="user2-project"/>' in r.text)
expect('<textarea name="description">owned by user2</textarea>' in r.text)
expect('<input type="text" name="tags" value="prj2 project" />' in r.text)

# check nothing has been altered
cursor.execute('SELECT * FROM projects WHERE id = 2')
expect(cursor.fetchone() == (2, None, 'user2-project', 'owned by user2', COMPLETED))

# no update, if no name passed. form should be displayed
r = user_session.post('http://localhost/project/2/edit',allow_redirects=False,data={'description':'hello world!','name':'project of user2'})
expectRedirect(r,'http://localhost/project/2/view')

# check project has been altered
cursor.execute('SELECT * FROM projects WHERE id = 2')
expect(cursor.fetchone() == (2, None, 'project of user2', 'hello world!', COMPLETED))

print ('done')