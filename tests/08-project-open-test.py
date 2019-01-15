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

# reset edits of previous tests
cursor.execute('UPDATE projects SET name="admin-project", description="owned by admin", status='+str(COMPLETED)+' WHERE id=1')
cursor.execute('UPDATE projects SET name="user2-project", description="owned by user2", status='+str(COMPLETED)+' WHERE id=2')
cursor.execute('UPDATE projects SET name="common-project", description="created by user2", status='+str(COMPLETED)+' WHERE id=3')
db.commit();

# if this test fails, you may have forgotten to execute 03-project-cancel-test
cursor.execute('SELECT project_id, user_id, permissions FROM projects_users')
expect(cursor.fetchall() == [
    (1, 1, 1),
    (2, 2, 1),
    (3, 2, 1),
    (3, 1, 2)
])

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/project/9999/open',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fproject%2F9999%2Fopen%3Fid%3D9999')

admin_session,token = getSession('admin','admin','project')
user_session,token = getSession('user2','test-passwd','project')

# no project given, redirect should occur
r = admin_session.get('http://localhost/project/open',allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

# should display error caused by previous call
r = admin_session.get('http://localhost/project/',allow_redirects=False)
expectError(r,'Keine Projekt-ID angegeben!')


# access to non-existing project, redirect should occur
r = admin_session.get('http://localhost/project/9999/open',allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

# should display error caused by previous call
r = admin_session.get('http://localhost/project/',allow_redirects=False)
expectError(r,'Sie sind nicht an diesem Projekt beteiligt!')

# admin should be able to open his project
r = admin_session.get('http://localhost/project/1/open',allow_redirects=False)
expectRedirect(r,'http://localhost/project/1/view')

cursor.execute('SELECT * FROM projects WHERE id = 1')
expect(cursor.fetchone() == (1, None, 'admin-project', 'owned by admin', OPEN))

# user should not be able to open admin's project
r = user_session.get('http://localhost/project/1/open',allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

# should display error caused by previous call
r = user_session.get('http://localhost/project/',allow_redirects=False)
expectError(r,'Sie sind nicht an diesem Projekt beteiligt!')

# user2 should be able to open his project
r = user_session.get('http://localhost/project/2/open',allow_redirects=False)
expectRedirect(r,'http://localhost/project/2/view')

cursor.execute('SELECT * FROM projects WHERE id = 2')
expect(cursor.fetchone() == (2, None, 'user2-project', 'owned by user2', OPEN))
print ('done')