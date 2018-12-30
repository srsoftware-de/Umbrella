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

# add user without project id should fail, user should be redirected to index
r = admin_session.get('http://localhost/project/add_user',allow_redirects=False)
expectRedirect(r,'http://localhost/project/')

# add user without project id should fail, user should shown a message
r = admin_session.get('http://localhost/project/add_user')
expectError(r,'Keine Projekt-ID angegeben!')

# user should not be able to add user to non-existing project
r = admin_session.get('http://localhost/project/9999/add_user',allow_redirects=False)
expectRedirect(r,'http://localhost/project/9999/view')
r = admin_session.get('http://localhost/project/9999/add_user')
expectError(r,'Sie sind nicht berechtigt, die Nutzerliste dieses Projekts zu ändern')
expectError(r,'Sie sind nicht an diesem Projekt beteiligt!')

# user 1 should not be able to add users to project of user 2
r = admin_session.get('http://localhost/project/2/add_user',allow_redirects=False)
expectRedirect(r,'http://localhost/project/2/view')
r = admin_session.get('http://localhost/project/2/view')
expectError(r,'Sie sind nicht berechtigt, die Nutzerliste dieses Projekts zu ändern')
expectError(r,'Sie sind nicht an diesem Projekt beteiligt!')

# user should be able to add users to own project, check form
r = admin_session.get('http://localhost/project/1/add_user',allow_redirects=False)
expect('<body class="project 1 add_user">' in r.text)
expect('<form method="POST">' in r.text)
expect('Benutzer zu admin-project hinzufügen' in r.text)
expect('<select name="new_user_id">' in r.text)
expect('<option value="" selected="true">== Nutzer auswählen ==</option>' in r.text)
expect('<option value="1">admin</option>' not in r.text)
expect('<option value="2">user2</option>' in r.text)
expect('<input type="checkbox" name="notify" value="on" checked="true" />' in r.text)

# user 2 should not be able to add users to project of user 1
r = user_session.get('http://localhost/project/1/add_user',allow_redirects=False)
expectRedirect(r,'http://localhost/project/1/view')
r = user_session.get('http://localhost/project/1/view')
expectError(r,'Sie sind nicht berechtigt, die Nutzerliste dieses Projekts zu ändern')

# user should be able to add users to own project, check form
r = user_session.get('http://localhost/project/3/add_user',allow_redirects=False)
expect('<body class="project 3 add_user">' in r.text)
expect('<form method="POST">' in r.text)
expect('Benutzer zu common-project hinzufügen' in r.text)
expect('<select name="new_user_id">' in r.text)
expect('<option value="" selected="true">== Nutzer auswählen ==</option>' in r.text)
expect('<option value="1">admin</option>' in r.text)
expect('<option value="2">user2</option>' not in r.text)
expect('<input type="checkbox" name="notify" value="on" checked="true" />' in r.text)

# add user 1 to project 3
r = user_session.post('http://localhost/project/3/add_user',allow_redirects=False,data={'new_user_id':1})
expectRedirect(r,'view')
r = user_session.get('http://localhost/project/3/view',allow_redirects=False)
expect('user2 (Eigentümer)' in r.text)
expect('admin (Mitglied)' in r.text)

print ('done')