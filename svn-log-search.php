<?php

if ($argc != 2) {
    echo "Usage: svn-log-search keyword\n";
    exit();
}

require 'lib.php';

$keyword = $argv[1];
$logs = search($keyword);

foreach ($logs as $log) {
    echo "$log\n";
}
