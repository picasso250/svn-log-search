<?php

function update_cache($root_url)
{
    echo "update cache, please wait ...\n";
    $log = shell_exec('svn log -v '.$root_url);
    save_log($log, $root_url);
}

function update_cache_async($root_url)
{
    echo "update cache, please wait ...\n";
    shell_exec('svn log -v '.$root_url.' > '.get_log_file_name($root_url).' &');
}

function search($keyword)
{
    $root_url = get_svn_root_url();
    if ($root_url === null) {
        echo getcwd()." 不是 svn 工作副本";
        exit();
    }
    $log = read_log($root_url);
    $logs = explode('------------------------------------------------------------------------', $log);
    unset($logs[0]);
    unset($logs[count($logs)-1]);

    if (empty($keyword)) {
        return $logs;
    }

    $ret = array();
    foreach ($logs as $log) {
        if (stripos($log, $keyword)) {
            $ret[] = $log;
        }
    }
    return $ret;
}

function read_log($root_url)
{
    $fpath = get_log_file_name($root_url);
    if (!file_exists($fpath)) {
        update_cache($root_url);
    }
    return file_get_contents($fpath);
}

function save_log($log, $root_url)
{
    $fpath = get_log_file_name($root_url);
    $rs = file_put_contents($fpath, $log);
}

function get_log_file_name($root_url)
{
    $dir = __DIR__.'/log';
    if (!file_exists($dir)) {
        mkdir($dir);
    }
    $fpath = $dir.'/'.md5($root_url).'.log';
    return $fpath;
}

function get_svn_root_url()
{
    $info = shell_exec('svn info');
    if (count(explode(PHP_EOL, $info)) < 3) {
        // svn: “.”不是工作副本
        return null;
    }
    preg_match('/版本库根: (.+)/', $info, $matches);
    return $matches[1];
}
