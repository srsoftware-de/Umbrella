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
expect('<input type="text" autofocus="autofocus" name="username" />' in r.text)
expect('<input type="password" name="pass" />' in r.text)
expect('admin/admin' in r.text)

r = requests.post("http://localhost/user/login", data={'username': 'admin'})
expect('Kein Passwort angegeben!' in r.text)

r = requests.post("http://localhost/user/login", data={'pass': 'admin'})
expect('Kein Benutzername angegeben' in r.text)

r = requests.post("http://localhost/user/login", data={'username':'wrong', 'pass': 'admin'})
expect('angegebene Nutzer/Passwort-Kombination ist nicht gültig' in r.text)

r = requests.post("http://localhost/user/login", data={'username':'admin', 'pass': 'wrong'})
expect('angegebene Nutzer/Passwort-Kombination ist nicht gültig' in r.text)

# should redirect to user/index
r = requests.post("http://localhost/user/login", data={'username':'admin', 'pass': 'admin'})
expect('Liste der Benutzer' in r.text)
expect('<td>1</td>' in r.text)
expect('admin' in r.text)
expect('/user/index' in r.url)

# if external site requests token: don't hand over token
admin_session,token = getSession('admin','admin','user')
r = admin_session.get("http://localhost/user/login?returnTo=http://example.com", allow_redirects=False)
expectRedirect(r,'http://example.com')

# if associated page requests token: hand over token
r = admin_session.get("http://localhost/user/login?returnTo=http://localhost/project/index", allow_redirects=False)
expectRedirect(r,'http://localhost/project/index?token='+token)

print('done')