#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys,time
sys.path.append("/var/www/tests")
from test_routines import *

OPEN = 10

db = sqlite3.connect('../db/projects.db')
cursor = db.cursor()

# reset edits of previous tests
cursor.execute('UPDATE projects SET name="admin-project", description="owned by admin", status='+str(OPEN)+' WHERE id=1')
cursor.execute('UPDATE projects SET name="user2-project", description="owned by user2", status='+str(OPEN)+' WHERE id=2')
cursor.execute('UPDATE projects SET name="common-project", description="created by user2", status='+str(OPEN)+' WHERE id=3')
db.commit();

# check redirect to login for users that are not logged in
r = requests.get('http://localhost/project/json',allow_redirects=False)
expectRedirect(r,'http://localhost/user/login?returnTo=http%3A%2F%2Flocalhost%2Fproject%2Fjson')

admin_session,token = getSession('admin','admin','project')
user_session,token = getSession('user2','test-passwd','project')

# get all projects (admin has access to all)
r = admin_session.get('http://localhost/project/json',allow_redirects=False)
expectJson(r,'{"1": {"status": "'+str(OPEN)+'", "description": "owned by admin", "id": "1", "name": "admin-project", "company_id": null}, "3": {"status": "'+str(OPEN)+'", "description": "created by user2", "id": "3", "name": "common-project", "company_id": null}}')

# get all projects user2 has access to
r = user_session.get('http://localhost/project/json',allow_redirects=False)
expectJson(r,'{"2": {"status": "'+str(OPEN)+'", "description": "owned by user2", "id": "2", "name": "user2-project", "company_id": null},"3":{"id":"3","company_id":null,"name":"common-project","description":"created by user2","status":"'+str(OPEN)+'"}}')

# get project admin's project: forbidden for user 1
r = user_session.get('http://localhost/project/json?ids=1',allow_redirects=False)
expectJson(r,'null')

r = user_session.get('http://localhost/project/1/json',allow_redirects=False)
expectJson(r,'null')

# get project admin's project
r = admin_session.get('http://localhost/project/json?ids=1',allow_redirects=False)
expectJson(r,'{"id":"1","company_id":null,"name":"admin-project","description":"owned by admin","status":"'+str(OPEN)+'"}')

# get user2's project
r = user_session.get('http://localhost/project/json?ids=2',allow_redirects=False)
expectJson(r,'{"id":"2","company_id":null,"name":"user2-project","description":"owned by user2","status":"'+str(OPEN)+'"}')

# admin has no access to project of user2, should recieve null
r = admin_session.get('http://localhost/project/json?ids=2',allow_redirects=False)
expectJson(r,'null')

# if this fails, try to run user/tests/04-user-edit-test before!
r = admin_session.get('http://localhost/project/json?users=true',allow_redirects=False)
expectJson(r,'{"1": {"status": "'+str(OPEN)+'", "users": {"1": {"data": {"id": 1, "login": "admin", "email": "user1@example.com", "settings": null}, "permission": "1"}}, "company_id": null, "name": "admin-project", "id": "1", "description": "owned by admin"}, "3": {"status": "'+str(OPEN)+'", "users": {"1": {"data": {"id": 1, "login": "admin", "email": "user1@example.com", "settings": null}, "permission": "2"}, "2": {"data": {"id": 2, "login": "user2", "email": "user2@example.com", "settings": null}, "permission": "1"}}, "company_id": null, "name": "common-project", "id": "3", "description": "created by user2"}}')
#TODO: add tests for company_ids

print ('done')