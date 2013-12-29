import subprocess
import contextlib
import urllib
import os

def get_log_path(root_url):
    d = os.path.dirname(os.path.realpath(__file__)) + '/log'
    if not os.path.isdir(d):
        os.mkdir(d)
    return d + '/' + urllib.quote(root_url, '')

def get_log(root_url):
    log_path = get_log_path(root_url)
    if not os.path.isfile(log_path):
        with contextlib.closing(open(log_path, 'w')) as log_file:
            subprocess.call(["svn","log" , "-v", root_url], stdout = log_file)
    with contextlib.closing(open(log_path, 'r')) as log_file:
        return log_file.read()

# match all key words
def match_array(s, keywords):
    keywords = [kw.strip() for kw in keywords.split(' ') if len(kw.strip()) > 0]
    for kw in keywords:
        if s.find(kw) == -1:
            return False
    return True

def search(root_url, keyword):
    logs = get_log(root_url)
    sep = '------------------------------------------------------------------------'
    logs = [log.strip() for log in logs.split(sep) if len(log.strip()) > 0]

    keyword = keyword.strip()
    if (len(keyword) == 0):
        return logs
    return [log for log in logs if match_array(log, keyword) == True ]
