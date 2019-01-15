#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *

import urlparse

# check redirect to login for users that are not logged in
r = requests.get("http://localhost/project/view",allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fproject%2Fview')


# login
admin_session,token = getSession('admin','admin','project')

# without a project id, an redirect should occur
r = admin_session.get('http://localhost/project/view',allow_redirects=False)
expectRedirect(r,'http://localhost/project/');

# should display the error belonging to the previous request
r = admin_session.get('http://localhost/project/',allow_redirects=False)
expectError(r,'Keine Projekt-ID angegeben!')

# non-existing project id, redirect
r = admin_session.get('http://localhost/project/9999/view',allow_redirects=False)
expectRedirect(r,'http://localhost/project/');

# should display the error belonging to the previous request
r = admin_session.get('http://localhost/project/',allow_redirects=False)
expectError(r,'Sie sind nicht an diesem Projekt beteiligt!')

r = admin_session.get('http://localhost/project/1/view',allow_redirects=False)
expect('<h1>admin-project</h1>' in r.text)
expect('<p>owned by admin</p>' in r.text)
expect('admin (Eigentümer)' in r.text)



# TODO: sicherstellen, dass der Nutzer auch auf existierende Projekte keinen Zugriff hat, wenn er nicht mitglied ist

print ('done')