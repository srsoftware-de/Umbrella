#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
from requests.api import request
sys.path.append("/var/www/tests")
from test_routines import *





# check redirect to login for users that are not logged in
r = requests.get('http://localhost/user/edit',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http://localhost/user/edit')

# login as admin
admin_session,token = getSession('admin','admin','user');
user_session,token = getSession('user2','test-passwd','user');

# edit reuires a user id
r = admin_session.get('http://localhost/user/edit',allow_redirects=False)
expectError(r,'Keine User-ID an user/edit übergeben!')

# test: user2 should not be able to edit other urser, should be redirected to index
r = user_session.get('http://localhost/user/1/edit',allow_redirects=False)
expectRedirect(r,'../index')

# test: user2 should not be able to edit other urser, should see warning after redirect
r = user_session.get('http://localhost/user/1/edit')
expectError(r,'Im Moment kann nur der Administrator andere Benutzer ändern!');

# user2 should be able to edit own properties, check form
r = user_session.get('http://localhost/user/2/edit',allow_redirects=False)
expect('<input type="text" name="login" value="user2" />' in r.text)
expect('<input type="text" name="email" value="" />' in r.text)
expect('<input type="password" name="new_pass" autocomplete="new-password" />' in r.text)

# admin should be able to edit own properties, check form
r = admin_session.get('http://localhost/user/1/edit',allow_redirects=False)
expect('<input type="text" name="login" value="admin" />' in r.text)
expect('<input type="text" name="email" value="" />' in r.text)
expect('<input type="password" name="new_pass" autocomplete="new-password" />' in r.text)

# admin should be able to edit own properties, check form
r = admin_session.get('http://localhost/user/2/edit',allow_redirects=False)
expect('<input type="text" name="login" value="user2" />' in r.text)
expect('<input type="text" name="email" value="" />' in r.text)
expect('<input type="password" name="new_pass" autocomplete="new-password" />' in r.text)

# admin should be able to edit properties, test this
r = admin_session.post('http://localhost/user/2/edit',allow_redirects=False,data={'login':'user-two','email':'user2@example.com','new_pass':'frittenbude'})
expect('<input type="text" name="login" value="user-two" />' in r.text)
expect('<input type="text" name="email" value="user2@example.com" /' in r.text)

# admin should be able to edit properties, test this
r = admin_session.post('http://localhost/user/1/edit',allow_redirects=False,data={'login':'user-two','email':'user1@example.com'})
expectError(r,'Es existiert bereits ein Nutzer mit diesem Login!')

# change back to former credentials for further tests
r = admin_session.post('http://localhost/user/2/edit',allow_redirects=False,data={'login':'user2','new_pass':'test-passwd'})

print ('done')