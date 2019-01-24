#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *





# check redirect to login for users that are not logged in
r = requests.get('http://localhost/user/',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fuser%2F')

admin_session,token = getSession('admin','admin','user')

r = admin_session.get('http://localhost/user/',allow_redirects=False)
assert('<td>admin</td>' in r.text)
assert('<td>user1@example.com</td>' in r.text)
assert('<td>user2</td>' in r.text)
assert('<td>user2@example.com</td>' in r.text)
assert('<td>user3</td>' in r.text)
assert('<td>4</td>' not in r.text)

print 'done'