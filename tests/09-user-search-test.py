#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys, time, json
sys.path.append("/var/www/tests")
from test_routines import *





# check redirect to login for users that are not logged in
r = requests.get('http://localhost/user/search',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http://localhost/user/search')

admin_session = requests.session();
# login
r = admin_session.post('http://localhost/user/login', data={'email':'admin', 'pass': 'admin'},allow_redirects=False)

# get token
r = admin_session.post('http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fuser%2Fsearch',allow_redirects=False)
expect('location' in r.headers)
redirect = r.headers.get('location');

expect('http://localhost/user/search?token=' in redirect)
param = params(redirect)
token=param['token'][0]

# create new session to test token function
admin_session = requests.session()

# redirect should contain a token in the GET parameters, thus the page should redirect to the same url without token parameter
r = admin_session.get(redirect,allow_redirects=False)
expectRedirect(r,'http://localhost/user/search');

# search without key should be aborted, followed by redirect
r = admin_session.get('http://localhost/user/search',allow_redirects=False)
expectRedirect(r,'index');

# after redirect, an error message should appear
r = admin_session.get('http://localhost/user/index',allow_redirects=False)
expectError(r,'Sie müssen angeben, wonach gesucht werden soll!')

r = admin_session.get('http://localhost/user/search?key=e',allow_redirects=False)
assert('<h2>Ihre Suche lieferte die folgenden Ergebnisse:</h2>' in r.text)

# TODO: update search after adding enitites
print 'done'
