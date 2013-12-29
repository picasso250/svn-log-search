import subprocess
import contextlib
import urllib
import os
import svnlib

root_url = 'svn://svn.fangdd.net/fdd-web'
logs = svnlib.search(root_url, 'wangxiaochi')
for log in logs:
    print log
print len(logs)
