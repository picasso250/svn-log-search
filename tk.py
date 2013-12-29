#!/usr/bin/python

import Tkinter # sudo apt-get install python-tk
import svnlib

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
    root_url = 'svn://svn.fangdd.net/fdd-web'
    print 'search', keyword.get()
    logs = svnlib.search_from_db(root_url, keyword.get())
    i = 0
    for log in logs:
        print log
        line = ' | '.join(['r'+str(log['rev']), log['author'], log['commit_date']])
        lines = '\n'.join([line, log['msg']])
        text.insert(Tkinter.END, lines+'\n\n')
    print len(logs)

keyword_entry.bind("<Return>", enter_key)


text.tag_add("here", "1.0", "1.2")
text.tag_add("start", "1.8", "1.13")
text.tag_config("here", background="yellow", foreground="blue")
text.tag_config("start", background="black", foreground="green")

root.mainloop()

