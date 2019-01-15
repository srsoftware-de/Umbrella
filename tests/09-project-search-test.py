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
cursor.execute('UPDATE projects SET name="admin-project", description="owned by admin", status='+str(OPEN)+' WHERE id=1')
cursor.execute('UPDATE projects SET name="user2-project", description="owned by user2", status='+str(COMPLETED)+' WHERE id=2')
cursor.execute('UPDATE projects SET name="common-project", description="created by user2", status='+str(CANCELED)+' WHERE id=3')
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
r = requests.get('http://localhost/project/9999/search',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fproject%2F9999%2Fsearch%3Fid%3D9999')

admin_session,token = getSession('admin','admin','project')
user_session,token = getSession('user2','test-passwd','project')

# no search key. should return empty page
r = admin_session.get('http://localhost/project/search',allow_redirects=False)
expect(r.text == '')

# should find no results => empty page
r = admin_session.get('http://localhost/project/search?key=echnaton',allow_redirects=False)
expect(r.text == '')

# should find project 3
r = admin_session.get('http://localhost/project/search?key=common',allow_redirects=False)
expect('<td><a href="http://localhost/project/3/view">common-project</a></td>' in r.text)

# should find project 3
r = user_session.get('http://localhost/project/search?key=common',allow_redirects=False)
expect('<td><a href="http://localhost/project/3/view">common-project</a></td>' in r.text)

# should find project 1
r = admin_session.get('http://localhost/project/search?key=owned',allow_redirects=False)
expect('<td><a href="http://localhost/project/1/view">admin-project</a></td>' in r.text)

# should find project 2
r = user_session.get('http://localhost/project/search?key=owned',allow_redirects=False)
expect('<td><a href="http://localhost/project/2/view">user2-project</a></td>' in r.text)

# should find project 2+3
r = user_session.get('http://localhost/project/search?key=ed',allow_redirects=False)
expect('<td><a href="http://localhost/project/2/view">user2-project</a></td>' in r.text)
expect('<td><a href="http://localhost/project/3/view">common-project</a></td>' in r.text)

print ('done')