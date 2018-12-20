#!/usr/bin/python
# -*- coding: utf-8 -*-
import sys
sys.path.append("/var/www/tests")
from test_routines import *

# user/db should not exists prior to first call to user module
expect(not os.path.isdir("../db"))

# request login page
r = requests.post("http://localhost/user", data={'number': 12524, 'type': 'issue', 'action': 'show'})

# user/db should be created upon first call
expect(os.path.isdir("../db"))
print('done')