#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
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

# get all projects admin has access to
r = admin_session.get('http://localhost/project/json',allow_redirects=False)
expectJson(r,'{"1":{"id":"1","company_id":null,"name":"admin-project","description":"owned by admin","status":"'+str(OPEN)+'"},"3":{"id":"3","company_id":null,"name":"common-project","description":"created by user2","status":"'+str(OPEN)+'"}}')

# get all projects user2 has access to
r = user_session.get('http://localhost/project/json',allow_redirects=False)
expectJson(r,'{"2": {"status": "10", "description": "owned by user2", "id": "2", "name": "user2-project", "company_id": null},"3":{"id":"3","company_id":null,"name":"common-project","description":"created by user2","status":"'+str(OPEN)+'"}}')

# get project admin's project
r = admin_session.get('http://localhost/project/json?ids=1',allow_redirects=False)
expectJson(r,'{"id":"1","company_id":null,"name":"admin-project","description":"owned by admin","status":"10"}')

# get user2's project
r = user_session.get('http://localhost/project/json?ids=2',allow_redirects=False)
expectJson(r,'{"id":"2","company_id":null,"name":"user2-project","description":"owned by user2","status":"10"}')

# admin has no access to project of user2, should recieve null
r = admin_session.get('http://localhost/project/json?ids=2',allow_redirects=False)
expectJson(r,'null')

# if this fails, try to run user/tests/04-user-edit-test before!
r = admin_session.get('http://localhost/project/json?users=true',allow_redirects=False)
expectJson(r,'{"1":{"id":"1","company_id":null,"name":"admin-project","description":"owned by admin","status":"10","users":{"1":{"permission":"1","data":{"message_delivery": "DELIVER INSTANTLY","login":"admin","email":"user1@example.com","theme":null,"id":1}}}},"3":{"id":"3","company_id":null,"name":"common-project","description":"created by user2","status":"10","users":{"1":{"permission":"2","data":{"message_delivery": "DELIVER INSTANTLY","login":"admin","email":"user1@example.com","theme":null,"id":1}},"2":{"permission":"1","data":{"message_delivery": "DELIVER INSTANTLY","login":"user2","email":"user2@example.com","theme":null,"id":2}}}}}')

#TODO: add tests for company_ids

print ('done')