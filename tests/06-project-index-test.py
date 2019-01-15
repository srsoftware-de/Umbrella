#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *

# check redirect to index
r = requests.get("http://localhost/project",allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/project/',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fproject%2F')

admin_session,token = getSession('admin','admin','project')
user_session,token = getSession('user2','test-passwd','project')

r = admin_session.get('http://localhost/project/',allow_redirects=False)
print r.text
expect('<body class="project">' in r.text)
expect('<table class="project-index">' in r.text)
expect('<td><a href="1/view">project of admin</a></td>' in r.text)
expect('<td>offen</td>' in r.text)

r = user_session.get('http://localhost/project/',allow_redirects=False)
print r.text
expect('<body class="project">' in r.text)
expect('<table class="project-index">' in r.text)
expect('<td><a href="2/view">project of user2</a></td>' in r.text)
expect('<td><a href="3/view">common-project</a></td>' in r.text)
expect('<td>offen</td>' in r.text)
expect('admin-project' not in r.text)

print ('done')