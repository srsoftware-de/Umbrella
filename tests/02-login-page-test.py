#!/usr/bin/python
# -*- coding: utf-8 -*-
import requests
import os

# next three lines allow unicode handling
import sys
reload(sys)
sys.setdefaultencoding('utf8')

def dot(condition):
    assert condition
    sys.stdout.write('.')
    sys.stdout.flush()

# test login page
r = requests.post("http://localhost/user")
dot(r.status_code == 200)
dot('<title>Umbrella login</title>' in r.text)
dot('<form method="POST">' in r.text)
dot('<input type="text" autofocus="autofocus" name="email" />' in r.text)
dot('<input type="password" name="pass" />' in r.text)
dot('admin/admin' in r.text)

r = requests.post("http://localhost/user/login", data={'email': 'admin'})
dot('No password given!' in r.text)

r = requests.post("http://localhost/user/login", data={'pass': 'admin'})
dot('No email given' in r.text)

r = requests.post("http://localhost/user/login", data={'email':'wrong', 'pass': 'admin'})
dot('angegebene Nutzer/Passwort-Kombination ist nicht gültig' in r.text)

r = requests.post("http://localhost/user/login", data={'email':'admin', 'pass': 'wrong'})
dot('angegebene Nutzer/Passwort-Kombination ist nicht gültig' in r.text)

r = requests.post("http://localhost/user/login", data={'email':'admin', 'pass': 'admin'})
dot('Liste der Benutzer' in r.text)
dot('<td>1</td>' in r.text)
dot('admin' in r.text)
dot('/user/index' in r.url)

print r.status_code,r.reason
print r.text
print r.headers
print r.cookies