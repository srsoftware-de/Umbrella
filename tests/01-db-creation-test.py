#!/usr/bin/python
import requests
import os

# next three lines allow unicode handling
import sys
reload(sys)
sys.setdefaultencoding('utf8')

# user/db should not exists prior to first call to user module
assert not os.path.isdir("../db")

# request login page
r = requests.post("http://localhost/user", data={'number': 12524, 'type': 'issue', 'action': 'show'})

# user/db should be created upon first call
assert os.path.isdir("../db")