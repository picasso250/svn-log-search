import subprocess
import contextlib
import urllib
import os

def get_log_path(root_url):
    d = os.path.dirname(os.path.realpath(__file__)) + '/log'
    if not os.path.isdir(d):
        os.mkdir(d);
    return d + '/' + urllib.quote(root_url, '')

def get_log(root_url):
    log_path = get_log_path(root_url)
    with contextlib.closing(open(log_path, 'w')) as log_file:
        subprocess.call(["svn","log" , "-v", root_url], stdout = log_file)

root_url = 'svn://svn.fangdd.net/fdd-web'
get_log(root_url)

