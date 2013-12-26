<?php

if ($argc != 2) {
    echo "Usage: svn-log-search keyword\n";
    exit();
}

require 'lib.php';

$keyword = $argv[1];
$root_url = get_svn_root_url();
if ($root_url === null) {
    echo getcwd()." 不是 svn 工作副本";
    exit();
}
$logs = search($keyword, $root_url);

foreach ($logs as $log) {
    echo "$log\n";
}
