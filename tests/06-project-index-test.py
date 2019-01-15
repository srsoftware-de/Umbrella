#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *

OPEN = 10

db = sqlite3.connect('../db/projects.db')
cursor = db.cursor()

# reset edits of previous tests
cursor.execute('UPDATE projects SET name="admin-project", description="owned by admin", status='+str(OPEN)+' WHERE id=1')
cursor.execute('UPDATE projects SET name="user2-project", description="owned by user2", status='+str(OPEN)+' WHERE id=2')
cursor.execute('UPDATE projects SET name="common-project", description="created by user2", status='+str(OPEN)+' WHERE id=3')
db.commit();

# check redirect to index
r = requests.get("http://localhost/project",allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/project/',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fproject%2F')

admin_session,token = getSession('admin','admin','project')
user_session,token = getSession('user2','test-passwd','project')

r = admin_session.get('http://localhost/project/',allow_redirects=False)
expect('<body class="project">' in r.text)
expect('<table class="project-index">' in r.text)
expect('<td><a href="1/view">admin-project</a></td>' in r.text)
expect('<td>offen</td>' in r.text)

r = user_session.get('http://localhost/project/',allow_redirects=False)
expect('<body class="project">' in r.text)
expect('<table class="project-index">' in r.text)
expect('<td><a href="2/view">user2-project</a></td>' in r.text)
expect('<td><a href="3/view">common-project</a></td>' in r.text)
expect('<td>offen</td>' in r.text)
expect('admin-project' not in r.text)

print ('done')