#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
from requests.api import request
sys.path.append("/var/www/tests")
from test_routines import *





# check redirect to login for users that are not logged in
r = requests.get('http://localhost/user/edit',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fuser%2Fedit')

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
expect('<input type="password" name="new_pass_repeat" autocomplete="new-password" />' in r.text)

# user should get an error when he tries to alter the password without filling the password repetition field
r = user_session.post('http://localhost/user/2/edit',allow_redirects=False,data={'login':'user-2','email':'user-2@example.com','new_pass':'gargatol'})
expect('<input type="text" name="login" value="user2" />' in r.text)
expect('<input type="text" name="email" value="" />' in r.text)
expect('<input type="password" name="new_pass" autocomplete="new-password" />' in r.text)
expect('<input type="password" name="new_pass_repeat" autocomplete="new-password" />' in r.text)
expectError(r,'Eingegebene Passworte stimmen nicht überein!')

# user2 should be able to edit own settings
r = user_session.post('http://localhost/user/2/edit',allow_redirects=False,data={'login':'user-2','email':'user-2@example.com','new_pass':'gargatol','new_pass_repeat':'gargatol'})
expect('<input type="text" name="login" value="user-2" />' in r.text)
expect('<input type="text" name="email" value="user-2@example.com" /' in r.text)
expectInfo(r,'Daten wurden aktualisiert.')

# admin should be able to edit own properties, check form
r = admin_session.get('http://localhost/user/1/edit',allow_redirects=False)
expect('<input type="text" name="login" value="admin" />' in r.text)
expect('<input type="text" name="email" value="" />' in r.text)
expect('<input type="password" name="new_pass" autocomplete="new-password" />' in r.text)
expect('<input type="password" name="new_pass_repeat" autocomplete="new-password" />' in r.text)

# admin should be able to edit foreign properties, check form
r = admin_session.get('http://localhost/user/2/edit',allow_redirects=False)
expect('<input type="text" name="login" value="user-2" />' in r.text)
expect('<input type="text" name="email" value="user-2@example.com" />' in r.text)
expect('<input type="password" name="new_pass" autocomplete="new-password" />' in r.text)
expect('<input type="password" name="new_pass_repeat" autocomplete="new-password" />' in r.text)

# admin should be able to edit foreign properties, but should get error without password repetition
r = admin_session.post('http://localhost/user/2/edit',allow_redirects=False,data={'login':'user-two','email':'user2@example.com','new_pass':'frittenbude'})
expect('<input type="text" name="login" value="user-2" />' in r.text)
expect('<input type="text" name="email" value="user-2@example.com" />' in r.text)
expect('<input type="password" name="new_pass" autocomplete="new-password" />' in r.text)
expect('<input type="password" name="new_pass_repeat" autocomplete="new-password" />' in r.text)
expectError(r,'Eingegebene Passworte stimmen nicht überein!')

# admin should be able to edit foreign properties, test this (alter username from user2 to user-two, add email, alter password
r = admin_session.post('http://localhost/user/2/edit',allow_redirects=False,data={'login':'user-two','email':'user2@example.com','new_pass':'frittenbude','new_pass_repeat':'frittenbude'})
expect('<input type="text" name="login" value="user-two" />' in r.text)
expect('<input type="text" name="email" value="user2@example.com" /' in r.text)
expectInfo(r,'Daten wurden aktualisiert.')

# admin should be able to edit properties, test this
r = admin_session.post('http://localhost/user/1/edit',allow_redirects=False,data={'login':'user-two','email':'user1@example.com'})
expectError(r,'Es existiert bereits ein Nutzer mit diesem Login!')

# admin account should be changed
r = admin_session.post('http://localhost/user/1/edit',allow_redirects=False,data={'login':'admin','email':'user1@example.com'})
expect('<input type="text" name="email" value="user1@example.com" />' in r.text)
expectInfo(r,'Daten wurden aktualisiert.')

# change back to former credentials for further tests
r = admin_session.post('http://localhost/user/2/edit',allow_redirects=False,data={'login':'user2','new_pass':'test-passwd','new_pass_repeat':'test-passwd'})
print ('done')