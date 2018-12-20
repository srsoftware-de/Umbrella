#!/usr/bin/python
from test_routines import *

# user/db shoudl not exists prior to first call to user module
expect(not os.path.isdir("../user/db"))

# test login page
r = requests.post("http://localhost", data={'number': 12524, 'type': 'issue', 'action': 'show'})
expect(r.status_code == 200)
expect('<a class="button" href="project">Login</a>' in r.text)

# user/db should still not exist
expect(not os.path.isdir("../user/db"))

print('done')