<?php

if ($argc != 2) {
    echo "Usage: svn-log-search keyword\n";
    exit();
}

$keyword = $argv[1];
search($keyword);

function update_cache($root_url)
{
    echo "update cache, please wait ...\n";
    $log = shell_exec('svn log -v '.$root_url);
    save_log($log, $root_url);
}

function search($keyword)
{
    $root_url = get_svn_root_url();
    if ($root_url === null) {
        echo getcwd()." 不是 svn 工作副本";
        exit();
    }
    $log = read_log($root_url);
    $logs = preg_split('/^-{20,}$/', $log);
    unset($logs[count($logs)-1]);
    $regex = '/'.str_replace('\\', "\\\\", $keyword).'/';
    foreach ($logs as $log) {
        if (preg_match($regex, $log)) {
            echo "$log\n";
        }
    }
}

function read_log($root_url)
{
    $fpath = __DIR__.'log/'.md5($root_url).'.log';
    if (!file_exists($fpath)) {
        update_cache($root_url);
    }
    return file_get_contents($fpath);
}

function save_log($log, $root_url)
{
    $dir = __DIR__.'/log';
    if (!file_exists($dir)) {
        mkdir($dir);
    }
    $fpath = $dir.'/'.md5($root_url).'.log';
    file_put_contents($log, $root_url);
}

function get_svn_root_url()
{
    $info = shell_exec('svn info');
    if (count(explode(PHP_EOL, $info)) < 3) {
        // svn: “.”不是工作副本
        return null;
    }
    preg_match('/URL: .+\s*\.+: (.+)\s/', $info, $matches);
    return $matches[1];
}
