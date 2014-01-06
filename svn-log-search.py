#!/usr/bin/python

import Tkinter # sudo apt-get install python-tk
import svnlib

url = svnlib.get_svn_url()
print 'update cache', url, '...'
svnlib.init_log_db(url)
print 'ok.'

root = Tkinter.Tk()

def layout_root(root):
    keyword = Tkinter.StringVar()
    keyword_entry = Tkinter.Entry(root, textvariable=keyword)
    keyword_entry.pack(side = Tkinter.TOP)

    text = Tkinter.Text(root)
    text.pack(side = Tkinter.BOTTOM)

    return [keyword, keyword_entry, text]

[keyword, keyword_entry, text] = layout_root(root)

def enter_key(event):
    logs = svnlib.search_from_db(url, keyword.get())
    set_logs(logs)

def set_logs(logs):
    text.delete("1.0", "end-1c")
    if logs is None:
        print 0
        return
    i = 0
    for log in logs:
        line = ' | '.join(['r'+str(log['rev']), log['author'], log['commit_date']])
        files = [' '+f['action']+' '+f['file_path'] for f in log['paths']]
        lines = '\n'.join([line, '\n'+'\n'.join(files), '\n\t'+log['msg']])

        text.insert(Tkinter.END, lines+'\n\n')
    print len(logs)

keyword_entry.bind("<Return>", enter_key)

logs = svnlib.search_from_db(url, '')
set_logs(logs)

# text.tag_add("here", "1.0", "1.2")
# text.tag_add("start", "1.8", "1.13")
# text.tag_config("here", background="yellow", foreground="blue")
# text.tag_config("start", background="black", foreground="green")

root.mainloop()

