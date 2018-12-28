#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *

# test login page
r = requests.post("http://localhost/user")
expect(r.status_code == 200)
expect('<title>Umbrella login</title>' in r.text)
expect('<form method="POST">' in r.text)
expect('<input type="text" autofocus="autofocus" name="email" />' in r.text)
expect('<input type="password" name="pass" />' in r.text)
expect('admin/admin' in r.text)

r = requests.post("http://localhost/user/login", data={'email': 'admin'})
expect('Kein Passwort angegeben!' in r.text)

r = requests.post("http://localhost/user/login", data={'pass': 'admin'})
expect('Keine Email angegeben' in r.text)

#r = requests.post("http://localhost/user/login", data={'email':'wrong', 'pass': 'admin'})
#expect('angegebene Nutzer/Passwort-Kombination ist nicht gültig' in r.text)

#r = requests.post("http://localhost/user/login", data={'email':'admin', 'pass': 'wrong'})
#expect('angegebene Nutzer/Passwort-Kombination ist nicht gültig' in r.text)

r = requests.post("http://localhost/user/login", data={'email':'admin', 'pass': 'admin'})
expect('Liste der Benutzer' in r.text)
expect('<td>1</td>' in r.text)
expect('admin' in r.text)
expect('/user/index' in r.url)

print('done')