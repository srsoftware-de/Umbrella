#!/usr/bin/python
import sqlite3

OPEN = 10

print 'adding entries to projects.db'
db = sqlite3.connect('../../project/db/projects.db')
cursor = db.cursor()

# reset edits of previous tests
cursor.execute('INSERT OR IGNORE INTO projects (id, name, description, status) VALUES (1, "admin-project", "owned by admin", '+str(OPEN)+')')
cursor.execute('INSERT OR IGNORE INTO projects (id, name, description, status) VALUES (2, "user2-project", "owned by user2", '+str(OPEN)+')')
cursor.execute('INSERT OR IGNORE INTO projects (id, name, description, status) VALUES (3, "common-project", "created by user2", '+str(OPEN)+')')
db.commit();