<?php

function get_files_by_rev($rev_id, $limit = null)
{

    $orm = ORM::forTable('changed_path')
        ->whereEqual('rev_id', $rev_id)
        ;
    if ($limit !== null) {
        $orm->limit(5);
    }
    return $orm->findMany();
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

function get_rev($url, $rev)
{
    return ORM::forTable('repo')
        ->join('rev', array('rev.repo_id', '=', 'repo.id'))
        ->whereEqual('repo.repo', $url)
        ->whereEqual('rev.rev', $rev)
        ->selectExpr('rev.*')
        ->findOne();
}

function init_svn_log_db($root_url)
{
    $repo = get_repo($root_url);

    $maxRevInDb = get_max_rev($repo->id);
    $latestRev = get_latest_rev($root_url);
    if ($latestRev > $maxRevInDb) { // update new
        $log = get_log($root_url, null, 100);
    } else { // 补全之前的
        $log = get_log($root_url, array(0, get_min_rev($repo->id)));
    }
    save_log_to_db($log, $repo);
}

function save_log_to_db($log, $repo)
{
    $doc = new DOMDocument();
    $doc->loadXML($log);
    $entrylist = $doc->getElementsByTagName('logentry');
    foreach ($entrylist as $key => $value) {
        $revision = $value->getAttribute('revision');

        if (ORM::forTable('rev')->whereEqual('rev', $revision)->findOne()) {
            continue;
        }

        echo "save $revision\n";
        ORM::get_db()->beginTransaction();

        $rev = ORM::forTable('rev')->create();
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
        foreach ($files as $k => $v) {
            $f = ORM::forTable('changed_path')->create();
            $f->rev_id = $rev->id;
            $f->file_path = $v->nodeValue;
            $f->action = $v->getAttribute('action');
            $f->prop_mods = $v->getAttribute('prop-mods');
            $f->text_mods = $v->getAttribute('text-mods');
            $f->kind = $v->getAttribute('kind');
            $f->save();
            echo "save file $f->file_path rev_id $rev->id\n";
        }
        ORM::get_db()->commit();
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

function search_db($keyword, $root_url)
{
    $revOrm = ORM::forTable('rev');
    $revOrm
        ->join('repo', array('rev.repo_id', '=', 'repo.id'))
        ->left_outer_join('changed_path', array('f.rev_id', '=', 'rev.id'), 'f')
        ->select('rev.*')
        ->groupBy('rev.rev')
        ->orderByDesc('rev.rev')
        ->whereEqual('repo.repo', $root_url)
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

function get_diff_from_db($root_url, $file_path, $revision)
{
    $entry = ORM::forTable('diff')
        ->join('changed_path', array('f.id', '=', 'diff.file_id'), 'f')
        ->join('rev', array('rev.id', '=', 'f.rev_id'))
        ->join('repo', array('repo.id', '=', 'rev.repo_id'))
        ->whereEqual('repo.repo', $root_url)
        ->whereEqual('rev.rev', $revision)
        ->whereEqual('f.file_path', $file_path)
        ->findOne();
    return $entry;
}

function get_diff($url, $file_path, $revision)
{
    if ($entry = get_diff_from_db($url, $file_path, $revision)) {
        return $entry;
    }

    $command = "svn diff --internal-diff -c {$revision} {$url}{$file_path}";
    echo "$command\n";
    $output = shell_exec($command);

    $file = get_changed_file($url, $revision, $file_path);
    $file_id = $file->id;
    $entry = ORM::forTable('diff')->create();
    $entry->file_id = $file_id;
    $entry->diff = $output;
    $entry->save();
    return $entry;
}

function get_blame_from_db($root_url, $file_path, $revision)
{
    $entry = ORM::forTable('blame')
        ->join('changed_path', array('f.id', '=', 'blame.file_id'), 'f')
        ->join('rev', array('rev.id', '=', 'f.rev_id'))
        ->join('repo', array('repo.id', '=', 'rev.repo_id'))
        ->whereEqual('repo.repo', $root_url)
        ->whereEqual('rev.rev', $revision)
        ->whereEqual('f.file_path', $file_path)
        ->findOne();
    return $entry;
}

function get_blame($url, $file_path, $revision)
{
    if ($entry = get_blame_from_db($url, $file_path, $revision)) {
        return $entry;
    }

    $command = "svn blame -r {$revision} {$url}{$file_path}";
    echo "$command\n";
    $output = shell_exec($command);

    $file = get_changed_file($url, $revision, $file_path);
    $file_id = $file->id;
    $entry = ORM::forTable('blame')->create();
    $entry->file_id = $file_id;
    $entry->blame = $output;
    $entry->save();
    return $entry;
}

function get_changed_file($url, $revision, $file_path)
{
    return ORM::forTable('changed_path')
        ->table_alias('f')
        ->join('rev', array('rev.id', '=', 'f.rev_id'))
        ->join('repo', array('rev.repo_id', '=', 'repo.id'))
        ->whereEqual('repo.repo', $url)
        ->whereEqual('rev.rev', $revision)
        ->whereEqual('f.file_path', $file_path)
        ->selectExpr('f.*')
        ->findOne();
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

function highlight_keyword($log, $keywords = null)
{
    if ($keywords) {
        if (is_string($keywords)) {
            $keywords = array($keywords);
        }
        foreach ($keywords as $keyword) {
            $log = str_replace($keyword, "<keyword>$keyword</keyword>", $log);
        }
    }
    return $log;
}

function render($tpl, $vars = array(), $layout = null)
{
    extract($vars);
    if ($layout) {
        include $layout;
    } else {
        include $tpl;
    }
}
