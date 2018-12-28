#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *





# check redirect to login for users that are not logged in
r = requests.get('http://localhost/user/view',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http://localhost/user/view')

admin_session = requests.session();
# login
r = admin_session.post('http://localhost/user/login', data={'email':'admin', 'pass': 'admin'},allow_redirects=False)

# get token
r = admin_session.post('http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fuser%2Fview',allow_redirects=False)
expect('location' in r.headers)
redirect = r.headers.get('location');

expect('http://localhost/user/view?token=' in redirect)
param = params(redirect)
token=param['token'][0]

# create new session to test token function
admin_session = requests.session()

# redirect should contain a token in the GET parameters, thus the page should redirect to the same url without token parameter
r = admin_session.get(redirect,allow_redirects=False)
expectRedirect(r,'http://localhost/user/view');

# without a user id, the view shoud display an error
r = admin_session.get('http://localhost/user/view',allow_redirects=False)
expectError(r,'Keine User-ID für Ansicht angegeben!')

# with a non-existing user id, the view should display an error
r = admin_session.get('http://localhost/user/9999/view',allow_redirects=False)
expectError(r,'Diesen Nutzer gibt es nicht')

# view should display data of admin user
r = admin_session.get('http://localhost/user/1/view',allow_redirects=False)
expect('<td>admin</td>' in r.text)
expect('<td>d033e22ae348aeb5660fc2140aec35850c4da997</td>' in r.text)

# display data of user2. requires execution of user-add-test before
r = admin_session.get('http://localhost/user/2/view',allow_redirects=False)
expect('<td>user2</td>' in r.text)
expect('<td>52313e3ecdfe725b74657040bbcb1ab325d4fc55</td>' in r.text)

# login as user2
user_session = requests.session();
r = user_session.post('http://localhost/user/login', data={'email':'user2', 'pass': 'test-passwd'},allow_redirects=False)

# get token
r = user_session.get('http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fuser%2Fedit',allow_redirects=False)
expect('location' in r.headers)
redirect = r.headers.get('location');

expect('http://localhost/user/edit?token=' in redirect)

# redirect should contain a token in the GET parameters, thus the page should redirect to the same url without token parameter
r = user_session.get(redirect,allow_redirects=False)
expectRedirect(r,'http://localhost/user/edit');

# user2 should not be able to see data of user 1, should be redirected
r = user_session.get('http://localhost/user/1/view',allow_redirects=False)
expectRedirect(r,'../index');

r = user_session.get('http://localhost/user/1/view')
expectError(r,'Im Moment kann nur der Administrator andere Benutzer einsehen!')

print ('done')