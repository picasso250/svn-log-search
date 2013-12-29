#!/usr/bin/python

import svnlib

root_url = svnlib.get_svn_root_url()
print 'update cache', root_url, '...'
svnlib.init_log_db(root_url)
print 'ok.'
