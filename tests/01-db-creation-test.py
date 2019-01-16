#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *
import shutil

shutil.rmtree('../db', ignore_errors=True)

# user/db should not exists prior to first call to user module
expect(not os.path.isdir('../db'))

# request login page
r = requests.post("http://localhost/user", data={'number': 12524, 'type': 'issue', 'action': 'show'})

# user/db should be created upon first call
expect(os.path.isdir("../db"))

db = sqlite3.connect('../db/users.db')
cursor = db.cursor()

# check all required tables exist
tables = cursor.execute("SELECT name FROM sqlite_master WHERE type='table';").fetchall()
expect(('users',) in tables)
expect(('tokens',) in tables)
expect(('token_uses',) in tables)
expect(('login_services',) in tables)
expect(('service_ids_users',) in tables)

print('done')