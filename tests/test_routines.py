#!/usr/bin/python
import os
import requests
import sqlite3
import urlparse
import json

# next three lines allow unicode handling
import sys
reload(sys)
sys.setdefaultencoding('utf8')

def expect(condition):
    assert condition
    sys.stdout.write('.')
    sys.stdout.flush()
    
def expectError(response,message):
    expect('<div class="errors">' in response.text)
    expect(message in response.text)

def expectInfo(response,message):
    expect('<div class="infos">' in response.text)
    expect(message in response.text)

def expectRedirect(response,url):
    if ('location' in response.headers.keys()):
        sys.stdout.write('.')
    else:
        print('No location header set, but '+url+' expected')
        exit(-1)
    if response.headers.get('location') == url:
        sys.stdout.write('.')
        sys.stdout.flush()
    else:
        print('Expected redirect to '+url+', but found '+response.headers.get('location'))
        exit(-1)


def expectJson(response,json_string):
    j1 = json.loads(json_string)
    j2 = json.loads(response.text)
    if j1 == j2:
        sys.stdout.write('.')
    else:
        print ''
        print 'expected json: '+json.dumps(j1)
        print '     got json: '+json.dumps(j2)
        exit(-1)

def params(url):
    return urlparse.parse_qs(urlparse.urlparse(url).query)

def getSession(login, password, module):
    session = requests.session();
    r = session.post('http://localhost/user/login', data={'username':login, 'pass': password},allow_redirects=False)

    # get token
    r = session.get('http://localhost/user/login?returnTo=http://localhost/'+module+'/',allow_redirects=False)
    expect('location' in r.headers)
    redirect = r.headers.get('location');

    expect('http://localhost/'+module+'/?token=' in redirect)
    prefix,token=redirect.split('=')

    # create new session to test token function
    session = requests.session()

    # redirect should contain a token in the GET parameters, thus the page should redirect to the same url without token parameter
    r = session.get(redirect,allow_redirects=False)
    expectRedirect(r,'http://localhost/'+module+'/');
    
    return session,token