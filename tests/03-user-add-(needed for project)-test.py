#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *





# check redirect to login for users that are not logged in
r = requests.get('http://localhost/user/add',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fuser%2Fadd')

admin_session,token = getSession('admin','admin','user')

# check add form after login
r = admin_session.get('http://localhost/user/add',allow_redirects=False)
expect('<form method="POST">' in r.text)
expect('<input type="text" name="login" />' in r.text)
expect('<input type="password" name="pass" />' in r.text)
expect('<button type="submit">Nutzer hinzufügen</button>' in r.text)

# adding a new uer without setting a password should not work
r = admin_session.post('http://localhost/user/add',data={'login':'new_user'},allow_redirects=False)
expectError(r,'Kein Passwort angegeben!')

# adding a new user without a login name should not work
r = admin_session.post('http://localhost/user/add',data={'pass':'insecure_password'},allow_redirects=False)
expectError(r,'Keine Email angegeben')

# adding a user whose login name matches an existing user should not work
r = admin_session.post('http://localhost/user/add',data={'login':'admin','pass':'new-pass'},allow_redirects=False)
expectError(r,'Es existiert bereits ein Nutzer mit diesem Login!')

# this should actually add a new user
r = admin_session.post('http://localhost/user/add',data={'login':'user2','pass':'test-passwd'},allow_redirects=False)
expectRedirect(r,'index')

# the new user should appear afterwards
r = admin_session.get('http://localhost/user/',allow_redirects=False)
expectInfo(r,'Nutzer user2 wurde hinzugefügt')
expect('<td>admin</td>' in r.text)
expect('<td>user2</td>' in r.text)

print ('done')