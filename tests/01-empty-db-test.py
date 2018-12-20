#!/usr/bin/python
import requests
import os

# next three lines allow unicode handling
import sys
reload(sys)
sys.setdefaultencoding('utf8')

# user/db shoudl not exists prior to first call to user module
assert not os.path.isdir("../user/db")

# test login page
r = requests.post("http://localhost", data={'number': 12524, 'type': 'issue', 'action': 'show'})
assert r.status_code == 200
assert '<a class="button" href="project">Login</a>' in r.text

# user/db should still not exist
assert not os.path.isdir("../user/db")