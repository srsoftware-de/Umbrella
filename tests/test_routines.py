#!/usr/bin/python
import os
import requests
import sqlite3
import urlparse

# next three lines allow unicode handling
import sys
reload(sys)
sys.setdefaultencoding('utf8')

def expect(condition):
    assert condition
    sys.stdout.write('.')
    sys.stdout.flush()
    
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
        
def expectError(response,message):
    expect('<div class="errors">' in response.text)
    expect(message in response.text)
    
def expectInfo(response,message):
    expect('<div class="infos">' in response.text)
    expect(message in response.text)


def params(url):
    return urlparse.parse_qs(urlparse.urlparse(url).query)