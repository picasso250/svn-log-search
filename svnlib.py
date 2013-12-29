import subprocess
import contextlib
import urllib
import os
import sqlite3

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
    return subprocess.check_output(["svn", "log" , "--xml", "-vl", "1", root_url])

def get_db_file_name():
    return 'svnlog.db'

def get_repo_id(conn, repo):
    c = conn.cursor()
    print 'search repo', repo
    t = (repo,)
    c.execute('SELECT id FROM repo WHERE url=?', t)
    repo_id = c.fetchone()
    if repo_id is None:
        print 'add', repo
        c.execute('INSERT INTO repo (url) VALUES (?)', t)
        conn.commit()
        return c.lastrowid
    else:
        return repo_id[0]

def get_db_conn():
    db_file = get_db_file_name()
    if not os.path.isfile(db_file):
        creat_tables(db_file)
    return sqlite3.connect(db_file)

def init_log_db(root_url):
    log_xml = get_log_xml(root_url)
    print log_xml

    conn = get_db_conn()

    repo_id = get_repo_id(conn, root_url)

    c = conn.cursor()

    from xml.dom import minidom
    xmldoc = minidom.parseString(log_xml)
    entrylist = xmldoc.getElementsByTagName('logentry') 
    for entry in entrylist:
        revision = entry.attributes['revision'].value
        print revision
        author = entry.getElementsByTagName('author')[0].firstChild.nodeValue
        print author
        date = entry.getElementsByTagName('date')[0].firstChild.nodeValue
        print date
        msg = entry.getElementsByTagName('msg')[0].firstChild.nodeValue

        params = (repo_id, revision, author, date, msg)
        c.execute('INSERT INTO rev (repo_id, rev, author, commit_date, msg) VALUES (?,?,?,?,?)', params)

        paths = entry.getElementsByTagName('path')
        for path in paths:
            action =path.attributes['action'].value
            prop = path.attributes['prop-mods'].value
            text = path.attributes['text-mods'].value
            kind = path.attributes['kind'].value
            filepath = path.firstChild.nodeValue
            print action
            print prop
            print text
            print kind
            print filepath
            params = (revision, action, prop, text, kind, filepath)
            c.execute('INSERT INTO changed_path (rev, text_mods, kind, action, prop_mods, file_path) VALUES (?,?,?,?,?,?)', params)
    print len(entrylist)

    conn.commit()

    # We can also close the connection if we are done with it.
    # Just be sure any changes have been committed or they will be lost.
    conn.close()

def search_from_db(root_url, keywords):
    conn = get_db_conn()
    c = conn.cursor()
    repo_id = get_repo_id(conn, root_url)
    print repo_id

    def dict_factory(cursor, row):
        d = {}
        for idx, col in enumerate(cursor.description):
            d[col[0]] = row[idx]
        return d

    conn.row_factory = dict_factory
    c = conn.cursor()
    params = (repo_id, '%'+keywords+'%')
    c.execute('SELECT * FROM rev WHERE repo_id=? AND msg like ?', params)
    print c.fetchall()
    return c.fetchall()

def get_log_from_db(root_url):
    conn = get_db_conn()

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
                 (id INTEGER PRIMARY KEY AUTOINCREMENT, url text)''')
    # Create rev table
    c.execute('''CREATE TABLE rev
                 (repo_id INTEGER, rev INTEGER, author text, commit_date text, line_num text, msg text)''')
    # Create changed_path table
    c.execute('''CREATE TABLE changed_path
                 (rev INTEGER, text_mods INTEGER, kind text, action text, prop_mods INTEGER, file_path text)''')

    # Save (commit) the changes
    conn.commit()

    # We can also close the connection if we are done with it.
    # Just be sure any changes have been committed or they will be lost.
    conn.close()
