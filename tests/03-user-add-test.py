#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *





# check redirect to login for users that are not logged in
r = requests.get('http://localhost/user/add',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http://localhost/user/add')

admin_session = requests.session();
# login
r = admin_session.post('http://localhost/user/login', data={'email':'admin', 'pass': 'admin'},allow_redirects=False)

# get token
r = admin_session.post('http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fuser%2Fadd',allow_redirects=False)
expect('location' in r.headers)
redirect = r.headers.get('location');

expect('http://localhost/user/add?token=' in redirect)
param = params(redirect)
token=param['token'][0]

# create new session to test token function
admin_session = requests.session()

# redirect should contain a token in the GET parameters, thus the page should redirect to the same url without token parameter
r = admin_session.get(redirect,allow_redirects=False)
expectRedirect(r,'http://localhost/user/add');

r = admin_session.get('http://localhost/user/add',allow_redirects=False)
expect('<form method="POST">' in r.text)
expect('<input type="text" name="login" />' in r.text)
expect('<input type="password" name="pass" />' in r.text)
expect('<button type="submit">Nutzer hinzufügen</button>' in r.text)

expect('Weitere Tests hinzufügen'=='erledigt')


print ('done')