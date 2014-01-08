svn
===============

first, install php dependencies.

```
composer install
```

than install redis, if you have not yet.

```
apt-get install redis-server
```

you have to start worker first.

```bash
QUEUE=diff,log php worker.php
```

每人每月的提交数量（更改的文件数量）（增删的行数）


