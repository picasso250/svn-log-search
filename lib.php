<?php

function update_cache($root_url)
{
    $log = shell_exec('svn log -v '.$root_url);
    save_log($log, $root_url);
}

function init_svn_log_db($root_url)
{
    $command = 'svn log --xml -v '.$root_url;
    $command = 'svn log --xml -vl 1 '.$root_url;
    $log = shell_exec($command);
    echo "$log\n";
    $doc = new DOMDocument();
    $doc->loadXML($log);
    $entrylist = $doc->getElementsByTagName('logentry');
    foreach ($entrylist as $key => $value) {
        $revision = $value->getAttribute('revision');

        // todo we need to diff and ...
        $revOrm->whereEqual('rev', $revision)->deleteMany();
        $fileOrm->whereEqual('rev', $revision)->deleteMany();
        
        $rev = $revOrm->create();
        $rev->rev = $revision;
        $rev->author = $value->getElementsByTagName('author')->item(0)->nodeValue;
        $rev->date = $value->getElementsByTagName('date')->item(0)->nodeValue;
        $rev->msg = $value->getElementsByTagName('msg')->item(0)->nodeValue;
        $rev->files = $value->getElementsByTagName('path');
        $rev->save();
        foreach ($files as $key => $value) {
            $f = $fileOrm->create();
            $f->rev = $rev->rev;
            $f->file = $value->nodeValue;
            $f->action = $value->getAttribute('action');
            $f->prop_mods = $value->getAttribute('prop-mods');
            $f->text_mods = $value->getAttribute('text-mods');
            $f->kind = $value->getAttribute('kind');
            $f->save();
        }
    }
}

function update_cache_async($root_url)
{
    shell_exec('svn log -v '.$root_url.' > '.get_log_file_name($root_url).' &');
}

function search($keyword, $root_url)
{
    $log = read_log($root_url);
    $logs = explode('------------------------------------------------------------------------', $log);
    unset($logs[0]);
    unset($logs[count($logs)-1]);

    if (empty($keyword)) {
        return $logs;
    }

    $ret = array();
    foreach ($logs as $log) {
        if (is_string($keyword) && trim($keyword) && stripos($log, trim($keyword)) !== false) {
            $ret[] = $log;
        }
        if (is_array($keyword) && match_array($log, $keyword)) {
            $ret[] = $log;
        }
    }
    return $ret;
}

// match all
function match_array($str, $arr)
{
    $arr = array_filter($arr, 'trim');
    foreach ($arr as $kw) {
        if (stripos($str, $kw) === false) {
            return false;
        }
    }
    return true;
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

function syntax($log, $keywords = null)
{
    $log = trim($log);
    $log = preg_replace('/^(r\d+)([\s|]+)(\w+)([\s|]+)(.+?)( \| )(.+)/u', '<rev>$1</rev>$2<name>$3</name>$4<date>$5</date>$6<linenum>$7</linenum>', $log);
    $log = preg_replace('%^\s*A\s+[/\w-.]+%m', '<file><add>$0</add></file>', $log);
    $log = preg_replace('%^\s*M\s+[/\w-.]+%m', '<file><modify>$0</modify></file>', $log);
    $log = preg_replace('%^\s*D\s+[/\w-.]+%m', '<file><del>$0</del></file>', $log);
    $log = preg_replace('%^\s*[A-Z]\s+[/\w-.]+%m', '<file>$0</file>', $log);
    $log = preg_replace('%^改变的路径.+%m', '<span>$0</span>', $log);
    $log = str_replace(' ', '&nbsp;', $log);
    $log = str_replace(PHP_EOL, "<br>\n", $log);
    if ($keywords) {
        if (is_string($keywords)) {
            $keywords = array($keywords);
        }
        foreach ($keywords as $keyword) {
            $log = str_replace($keyword, "<keyword>$keyword</keyword>", $log);
        }
    }
    return "<p class=\"svn-log-entry\">$log</p>";
}
