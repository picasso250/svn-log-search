#!/usr/bin/python

import svnlib

root_url = svnlib.get_svn_root_url()
svnlib.init_log_db(root_url)
