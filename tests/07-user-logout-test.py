#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys, time, json
sys.path.append("/var/www/tests")
from test_routines import *





# check redirect to login for users that are not logged in
r = requests.get('http://localhost/user/',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fuser%2F')

admin_session,token = getSession('admin','admin','user')

r = admin_session.get('http://localhost/user/',allow_redirects=False)
expect('<legend>Liste der Login-Services</legend>' in r.text)

r = admin_session.get('http://localhost/user/logout',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login')

r = admin_session.get('http://localhost/user/',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fuser%2F')

# redirect contains token. token should be included in session variable and redirect to same page without token should follow
r = admin_session.get('http://localhost/user/?token='+token,allow_redirects=False)
expectRedirect(r,'http://localhost/user/')

# user should be redirected to login page
r = admin_session.get('http://localhost/user/',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fuser%2F')

# user should see login page
r = admin_session.get('http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fuser%2F',allow_redirects=False)
expect('<title>Umbrella login</title>' in r.text)
expect('<form method="POST">' in r.text)

print 'done'
