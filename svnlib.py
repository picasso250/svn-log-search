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

def get_log_xml(root_url):
    return subprocess.check_output(["svn", "log" , "--xml", "-v", root_url])

def init_log_db(root_url):
    xml = get_log_xml(root_url)
    print xml

def get_log_from_db(root_url):
    db_file = 'svnlog.db'
    if not os.path.isfile(db_file):
        creat_tables(db_file)
    conn = sqlite3.connect(db_file)

    c = conn.cursor()

    t = (root_url,)
    c.execute('SELECT * FROM repo WHERE url=?', t)

    conn.close()

    return c.fetchall()

def creat_tables(db_file):
    conn = sqlite3.connect(db_file)
    c = conn.cursor()

    # Create repo table
    c.execute('''CREATE TABLE repo
                 (id int, url text)''')
    # Create rev table
    c.execute('''CREATE TABLE rev
                 (id int, repo_id int, rev int, author text, commit_date text, line_num text, msg text)''')
    # Create changed_path table
    c.execute('''CREATE TABLE changed_path
                 (id int, rev_id int, text_mods int, kind text, action text, prop_mods int, file_path text)''')

    # Save (commit) the changes
    conn.commit()

    # We can also close the connection if we are done with it.
    # Just be sure any changes have been committed or they will be lost.
    conn.close()
