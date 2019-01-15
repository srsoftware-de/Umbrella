#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *

import urlparse

OPEN = 10

# check redirect to login for users that are not logged in
r = requests.get("http://localhost/project/add",allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fproject%2Fadd')


# login
admin_session,token = getSession('admin','admin','project')
user_session,token = getSession('user2','test-passwd','project')

# check the form
r = admin_session.get('http://localhost/project/add')
expect('<form method="POST">' in r.text)
expect('<input type="text" name="name" />' in r.text)
expect('<textarea name="description"></textarea>' in r.text)
expect('<input name="tags" type="text" value="" />' in r.text)
expect('<button type="submit">' in r.text)

# form should not create new project as long as name is missing, but it should keep entered data
r = admin_session.post('http://localhost/project/add',data={'description':'this is the description','tags':'tag1 tag2'})
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
r = admin_session.post('http://localhost/project/add',data={'name':'admin-project','description':'owned by admin','tags':'project prj1'},allow_redirects=False)
expectRedirect(r,'http://localhost/project/1/view')

# project should have been created, user should be redirected to index
r = user_session.post('http://localhost/project/add',data={'name':'user2-project','description':'owned by user2','tags':'project prj2'},allow_redirects=False)
expectRedirect(r,'http://localhost/project/2/view')

# project should have been created, user should be redirected to index
r = user_session.post('http://localhost/project/add',data={'name':'common-project','description':'created by user2','tags':'project prj3'},allow_redirects=False)
expectRedirect(r,'http://localhost/project/3/view')


cursor.execute('SELECT * FROM projects')
expect(cursor.fetchall() == [
    (1, None, 'admin-project', 'owned by admin', OPEN),
    (2, None, 'user2-project', 'owned by user2', OPEN),
    (3, None, 'common-project', 'created by user2', OPEN)
])

print ('done')