#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *

import urlparse

# check redirect to login for users that are not logged in
r = requests.get("http://localhost/project/add",allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fproject%2Fadd')


# login
session = requests.session();
r = session.post('http://localhost/user/login', data={'email':'admin', 'pass': 'admin'},allow_redirects=False)

# get token
r = session.post('http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fproject%2Fadd',allow_redirects=False)
expect('location' in r.headers)
redirect = r.headers.get('location');

expect('http://localhost/project/add?token=' in redirect)
param = urlparse.parse_qs(urlparse.urlparse(redirect).query)
token=param['token'][0]

# create new session to test token function
session = requests.session()

# redirect should contain a token in the GET parameters, thus the page should redirect to the same url without token parameter
r = session.get(redirect,allow_redirects=False)
expectRedirect(r,'http://localhost/project/add');

# check the form
r = session.get('http://localhost/project/add')
expect('<form method="POST">' in r.text)
expect('<input type="text" name="name" />' in r.text)
expect('<textarea name="description"></textarea>' in r.text)
expect('<input name="tags" type="text" value="" />' in r.text)
expect('<button type="submit">' in r.text)

# form should not create new project as long as name is missing, but it should keep entered data
r = session.post('http://localhost/project/add',data={'description':'this is the description','tags':'tag1 tag2'})
expect('<form method="POST">' in r.text)
expect('<input type="text" name="name" />' in r.text)
expect('<textarea name="description">this is the description</textarea>' in r.text)
expect('<input name="tags" type="text" value="tag1 tag2" />' in r.text)
expect('<button type="submit">' in r.text)

db = sqlite3.connect('../db/projects.db')
cursor = db.cursor()

# check no projects exists
cursor.execute('SELECT * FROM projects')
rows = cursor.fetchall()
expect(not rows)

# project should have been created, user should be redirected to index
r = session.post('http://localhost/project/add',data={'name':'testproject','description':'this is the description','tags':'tag1 tag2'},allow_redirects=False)
expectRedirect(r,'http://localhost/project/1/view')
cursor.execute('SELECT * FROM projects')
expect(cursor.fetchall() == [(1, None, 'testproject', 'this is the description', 10)])

# project should appear on index page
r = session.get('http://localhost/project/',allow_redirects=False)
expect('<body class="project">' in r.text)
expect('<table class="project-index">' in r.text)
expect('<td><a href="1/view">testproject</a></td>'in r.text)
expect('<td>offen</td>' in r.text)
expect('admin<br/>' in r.text)

print ('done')