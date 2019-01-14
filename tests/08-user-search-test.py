#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys, time, json
sys.path.append("/var/www/tests")
from test_routines import *





# check redirect to login for users that are not logged in
r = requests.get('http://localhost/user/search',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fuser%2Fsearch')

admin_session,token = getSession('admin','admin','user')

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
