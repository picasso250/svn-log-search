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
QUEUE=diff,blame,log php worker.php
```

todo
-----

每人每月的提交数量（更改的文件数量）（增删的行数）

6 more files ...

copyfrom-path="/Branches/yun_v2/Dao/Fangyun/CommissionCustomerGroup.php"
   copyfrom-rev="6015"

x files merge.

filter by month

diff queue quick

change repo
