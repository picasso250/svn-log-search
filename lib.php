<?php

function get_files_by_rev($rev_id)
{
    return ORM::forTable('changed_path')
        ->whereEqual('rev_id', $rev_id)
        ->findMany();
}

function update_cache($root_url)
{
    $log = shell_exec('svn log -v '.$root_url);
    save_log($log, $root_url);
}

function update_svn_log_db($root_url)
{
    return init_svn_log_db($root_url);
}

function get_repo($url)
{
    $repoOrm = ORM::forTable('repo');
    $repo = $repoOrm->whereEqual('repo', $url)->findOne();
    if (empty($repo)) {
        $repo = $repoOrm->create();
        $repo->repo = $url;
        $repo->save();
    }
    return $repo;
}

function init_svn_log_db($root_url)
{
    $repo = get_repo($root_url);

    $maxRevInDb = get_max_rev($repo->id);
    $latestRev = get_latest_rev($root_url);
    if ($latestRev > $maxRevInDb) { // update new
        $log = get_log($root_url, null, 100);
    } else { // 补全之前的
        $log = get_log($root_url);
    }
    save_log_to_db($log, $repo);
}

function save_log_to_db($log, $repo)
{
    $revOrm = ORM::forTable('rev');
    $fileOrm = ORM::forTable('changed_path');

    $doc = new DOMDocument();
    $doc->loadXML($log);
    $entrylist = $doc->getElementsByTagName('logentry');
    foreach ($entrylist as $key => $value) {
        $revision = $value->getAttribute('revision');

        if (ORM::forTable('rev')->whereEqual('rev', $revision)->findOne()) {
            echo "skip $revision\n";
            continue;
        }

        // ORM::forTable('rev')->whereEqual('rev', $revision)->deleteMany();
        // ORM::forTable('changed_path')->whereEqual('rev', $revision)->deleteMany();

        echo "save $revision\n";

        $rev = $revOrm->create();
        $rev->rev = $revision;
        $rev->repo_id = $repo->id;
        $author = $value->getElementsByTagName('author')->item(0);
        if (empty($author)) {
            echo "author is empty\n";
            $rev->author = '';
        } else {
            $rev->author = $author->nodeValue;
        }
        $rev->commit_date = $value->getElementsByTagName('date')->item(0)->nodeValue;
        $rev->msg = $value->getElementsByTagName('msg')->item(0)->nodeValue;
        $rev->save();
        $files = $value->getElementsByTagName('path');
        foreach ($files as $key => $value) {
            $f = $fileOrm->create();
            $f->rev_id = $rev->id;
            $f->file_path = $value->nodeValue;
            $f->action = $value->getAttribute('action');
            $f->prop_mods = $value->getAttribute('prop-mods');
            $f->text_mods = $value->getAttribute('text-mods');
            $f->kind = $value->getAttribute('kind');
            $f->save();
        }
    }
}

function get_log($repo_url, $rev = null, $limit = null)
{
    $command = 'svn log --xml -v ';
    if ($limit !== null && is_int($limit)) {
        $command .= "-l $limit ";
    }
    if (is_string($rev)) {
        $command .= '-r $rev ';
    } elseif (is_array($rev)) {
        $command .= "-r $rev[0]:$rev[1] ";
    }
    $command .= $repo_url;
    echo "$command\n";
    $log = shell_exec($command);
    return $log;
}

function get_latest_rev($repo_url)
{
    $command = 'svn log --xml -r HEAD '.$repo_url;
    echo "$command\n";
    $log = shell_exec($command);
    $doc = new DOMDocument();
    $doc->loadXML($log);
    $entrylist = $doc->getElementsByTagName('logentry');
    $value = $entrylist->item(0);
    $revision = $value->getAttribute('revision');
    return $revision;
}

function get_max_rev($repo_id) {
    return ORM::forTable('rev')
        ->selectExpr('MAX(rev) as mr')
        ->whereEqual('repo_id', $repo_id)
        ->findOne()->mr ?: 0;
}

function get_min_rev($repo_id) {
    return ORM::forTable('rev')
        ->selectExpr('MIN(rev) as mr')
        ->whereEqual('repo_id', $repo_id)
        ->findOne()->mr ?: 0;
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

function search_db($keyword, $root_url)
{
    $revOrm = ORM::forTable('rev');
    $revOrm
        ->join('repo', array('rev.repo_id', '=', 'repo.id'))
        ->join('changed_path', array('f.rev_id', '=', 'rev.id'), 'f')
        ->select('rev.*')
        ->groupBy('rev.rev')
        ->orderByDesc('rev.rev')
        ->limit(500);

    if (is_string($keyword)) {
        $keyword = trim($keyword);
        if (empty($keyword)) {
            return $revOrm->findMany();
        }
        $keywords = implode(' ', $keyword);
    } else {
        $keywords = $keyword;
    }
    $keywords = array_map('trim', array_filter($keywords, 'trim'));
    if (empty($keywords)) {
        return $revOrm->findMany();
    }
    $whereExpr = array();
    $values = array();
    foreach ($keywords as $kw) {
        $whereExpr[] = '(rev.rev=? OR rev.author LIKE ? OR rev.commit_date LIKE ? OR rev.msg LIKE ? OR f.file_path LIKE ?)';
        $values[] = $kw;
        $values[] = "%$kw%";
        $values[] = "%$kw%";
        $values[] = "%$kw%";
        $values[] = "%$kw%";
    }
    $revOrm->whereRaw(implode(' AND ', $whereExpr), $values);
    return $revOrm->findMany();
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

function get_diff($root_url, $file_path, $revision)
{
    $diffOrm = ORM::forTable('diff');
    $entry = $diffOrm
        ->join('repo', array('repo.id', '=', 'diff.repo_id'))
        ->whereEqual('repo.repo', $root_url)
        ->whereEqual('diff.rev', $revision)
        ->whereEqual('diff.file', $file_path)
        ->findOne();
    if (empty($entry)) {
        // repo get
        // svn diff get
        $entry = $diffOrm->create();
        $entry->save();
    }
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
