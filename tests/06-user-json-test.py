#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys, time, json
sys.path.append("/var/www/tests")
from test_routines import *





# check redirect to login for users that are not logged in
r = requests.get('http://localhost/user/json',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http://localhost/user/json')

admin_session = requests.session();
# login
r = admin_session.post('http://localhost/user/login', data={'email':'admin', 'pass': 'admin'},allow_redirects=False)

# get token
r = admin_session.post('http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fuser%2Fjson',allow_redirects=False)
expect('location' in r.headers)
redirect = r.headers.get('location');

expect('http://localhost/user/json?token=' in redirect)
param = params(redirect)
token=param['token'][0]

# create new session to test token function
admin_session = requests.session()

# redirect should contain a token in the GET parameters, thus the page should redirect to the same url without token parameter
r = admin_session.get(redirect,allow_redirects=False)
expectRedirect(r,'http://localhost/user/json');

r = admin_session.get('http://localhost/user/json',allow_redirects=False)
assert(r.status_code == 400)
assert('Sie müssen eine Nutzer-ID angeben!' in r.text)

r = admin_session.get('http://localhost/user/1/json',allow_redirects=False)
expect(r.text=='{"id":"1","login":"admin","email":"user2@example.com"}')

r = admin_session.get('http://localhost/user/2/json',allow_redirects=False)
expect(r.text=='{"id":"2","login":"user2","email":"user2@example.com"}')

r = admin_session.get('http://localhost/user/json?ids=1',allow_redirects=False)
expect(r.text=='{"id":"1","login":"admin","email":"user2@example.com"}')

r = admin_session.get('http://localhost/user/json?ids=2',allow_redirects=False)
expect(r.text=='{"id":"2","login":"user2","email":"user2@example.com"}')

r = admin_session.post('http://localhost/user/json',allow_redirects=False,data={'ids[0]':2,'ids[1]':1})
expect(r.text=='{"1":{"id":"1","login":"admin","email":"user2@example.com"},"2":{"id":"2","login":"user2","email":"user2@example.com"}}')

r = admin_session.get('http://localhost/user/3/json',allow_redirects=False)
assert(r.status_code == 400)
assert('Diesen Nutzer gibt es nicht' in r.text)

print 'done'
