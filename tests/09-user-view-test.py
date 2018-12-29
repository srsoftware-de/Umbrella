#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *





# check redirect to login for users that are not logged in
r = requests.get('http://localhost/user/view',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http://localhost/user/view')

admin_session,token = getSession('admin','admin','user')

# view without user should display error
r = admin_session.get('http://localhost/user/view',allow_redirects=False)
expectError(r,'Keine User-ID für Ansicht angegeben!')

# with a non-existing user id, the view should display an error
r = admin_session.get('http://localhost/user/9999/view',allow_redirects=False)
expectError(r,'Diesen Nutzer gibt es nicht')

# view should display data of admin user
r = admin_session.get('http://localhost/user/1/view',allow_redirects=False)
expect('<th>Benutzername</th><td>admin</td>' in r.text)
expect('<th>Password (hashed)</th><td>d033e22ae348aeb5660fc2140aec35850c4da997</td>' in r.text)

# display data of user2. requires execution of user-add-test before
r = admin_session.get('http://localhost/user/2/view',allow_redirects=False)
expect('<th>Benutzername</th><td>user2</td>' in r.text)
expect('<th>Password (hashed)</th><td>52313e3ecdfe725b74657040bbcb1ab325d4fc55</td>' in r.text)

# login as user2
user_session,token = getSession('user2','test-passwd','user')

# display data of user2. requires execution of user-add-test before
r = user_session.get('http://localhost/user/2/view',allow_redirects=False)
expect('<th>Benutzername</th><td>user2</td>' in r.text)
expect('<th>Password (hashed)</th><td>52313e3ecdfe725b74657040bbcb1ab325d4fc55</td>' in r.text)


# user2 should not be able to see data of user 1, should be redirected
r = user_session.get('http://localhost/user/1/view',allow_redirects=False)
expectRedirect(r,'../index');

r = user_session.get('http://localhost/user/1/view')
expectError(r,'Im Moment kann nur der Administrator andere Benutzer einsehen!')

print ('done')