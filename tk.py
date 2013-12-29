#!/usr/bin/python

import Tkinter
root = Tkinter.Tk()

# names

keyword_entry = None

def layout_top(root):
    keyword_entry = Tkinter.Entry(root)
    keyword_entry.pack(side = Tkinter.TOP)

    text = Tkinter.Text(root)
    text.insert(Tkinter.INSERT, "Hello.....")
    text.insert(Tkinter.END, "Bye Bye.....")
    text.pack(side = Tkinter.BOTTOM)

    text.tag_add("here", "1.0", "1.2")
    text.tag_add("start", "1.8", "1.13")
    text.tag_config("here", background="yellow", foreground="blue")
    text.tag_config("start", background="black", foreground="green")


layout_top(root)

root.mainloop()

