#!/usr/bin/python

import Tkinter # sudo apt-get install python-tk
import svnlib

root = Tkinter.Tk()

def layout_root(root):
    keyword_entry = Tkinter.Entry(root)
    keyword_entry.pack(side = Tkinter.TOP)

    text = Tkinter.Text(root)
    text.pack(side = Tkinter.BOTTOM)

    return [keyword_entry, text]

[keyword_entry, text] = layout_root(root)

root_url = 'svn://svn.fangdd.net/fdd-web'
logs = svnlib.search(root_url, 'wangxiaochi')
i = 0
for log in logs:
    text.insert(Tkinter.END, log+'\n\n')
print len(logs)

text.tag_add("here", "1.0", "1.2")
text.tag_add("start", "1.8", "1.13")
text.tag_config("here", background="yellow", foreground="blue")
text.tag_config("start", background="black", foreground="green")

root.mainloop()

